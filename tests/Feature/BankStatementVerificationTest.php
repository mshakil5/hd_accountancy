<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BankReconciliationPageController;
use App\Models\BankImport;
use App\Models\User;
use App\Services\BankLedgerPostingService;
use App\Services\BankStatementVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Statement verification (HD_Bank_Statement_OCR_Verification_Guide): page
 * coverage, balance maths, unparsed rows, the accountant correction audit and
 * the hard gate that keeps unverified statements out of reconciliation.
 *
 * Follows the suite convention: fixtures live inside a transaction that is
 * rolled back, so no residue is left on the database.
 */
class BankStatementVerificationTest extends TestCase
{
    protected int $userId;
    protected object $client;
    protected int $bankId;
    protected static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('bank_statement_exceptions')
            || !Schema::hasColumn('bank_imports', 'verification_status')
            || !Schema::hasColumn('bank_entries', 'source_page')
            || !Schema::hasTable('bank_entry_revisions')) {
            $this->markTestSkipped('Run `php artisan migrate` to enable statement verification tests.');
        }

        $this->client = DB::table('clients')->where('status', true)->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->first();
        if (!$this->client) {
            $this->markTestSkipped('No client fixture available.');
        }

        DB::beginTransaction();

        $this->userId = (int) (DB::table('users')->value('id') ?? 0);
        if ($this->userId && ($user = User::find($this->userId))) {
            $this->actingAs($user);
        }

        $this->bankId = (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->client->id, 'bank_name' => 'HSBC', 'account_label' => 'Current',
            'account_last_four' => (string) rand(1000, 9999), 'currency' => 'GBP', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function makeImport(string $status = 'pending'): BankImport
    {
        $id = (int) DB::table('bank_imports')->insertGetId([
            'client_id' => $this->client->id, 'bank_account_id' => $this->bankId,
            'filename' => 'statement.pdf',
            'sha256' => hash('sha256', 'stmt' . (++self::$seq) . microtime(true)),
            'storage_path' => null, 'file_type' => 'pdf',
            'created_by' => $this->userId ?: 1, 'transaction_count' => 0,
            'processing_status' => 'awaiting_verification', 'verification_status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return BankImport::findOrFail($id);
    }

    private function makeEntry(BankImport $import, string $date, string $description, float $amount,
                               ?float $balance = null, int $page = 1, int $position = 1): int
    {
        return (int) DB::table('bank_entries')->insertGetId([
            'client_id' => $import->client_id, 'bank_import_id' => $import->id,
            'bank_account_id' => $import->bank_account_id,
            'transaction_date' => $date, 'description' => $description, 'reference' => null,
            'amount' => number_format($amount, 2, '.', ''), 'currency' => 'GBP', 'status' => 'needs_review',
            'source_page' => $page, 'source_position' => $position,
            'balance_after' => $balance === null ? null : number_format($balance, 2, '.', ''),
            'reconciliation_status' => 'draft',
            'line_fingerprint' => hash('sha256', $description . (++self::$seq) . microtime(true)),
            'line_occurrence' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function analysis(array $overrides = []): array
    {
        return array_merge([
            'pages' => [1 => ['text_length' => 400, 'transactions_detected' => true, 'rows' => 1]],
            'rows' => [], 'unparsed' => 0, 'unparsed_samples' => [],
            'opening_balance' => 100.00, 'closing_balance' => 90.00,
            'ocr' => false, 'pages_total' => 1, 'text' => '',
        ], $overrides);
    }

    private function verifier(): BankStatementVerificationService
    {
        return app(BankStatementVerificationService::class);
    }

    private function adminController(): BankReconciliationPageController
    {
        return new BankReconciliationPageController(app(BankLedgerPostingService::class));
    }

    /** @return array{rows: array<int,array<string,mixed>>, unparsed:int, samples:array<int,string>} */
    private function parsePage(string $text): array
    {
        $method = new \ReflectionMethod($this->verifier(), 'parsePage');
        $method->setAccessible(true);
        return $method->invoke($this->verifier(), $text, 1, false);
    }

    /** Monzo glues the date to the description and prints amount + running balance. */
    public function test_monzo_single_line_rows_are_parsed(): void
    {
        $parsed = $this->parsePage("Date Description (GBP) Amount(GBP) Balance\n30/04/2025Joseph Buckle (Faster Payments) -50.00 26.07");

        $this->assertCount(1, $parsed['rows']);
        $this->assertSame('2025-04-30', $parsed['rows'][0]['transaction_date']);
        $this->assertSame(-50.00, (float) $parsed['rows'][0]['amount']);
        $this->assertSame(26.07, (float) $parsed['rows'][0]['balance_after']);
        $this->assertStringContainsString('Joseph Buckle', $parsed['rows'][0]['description']);
    }

    /** Monzo wraps an entry across several lines: date, description, then "amount balance". */
    public function test_monzo_wrapped_rows_are_parsed(): void
    {
        $parsed = $this->parsePage("30/04/2025\nM Marsden (Faster Payments) Reference:\nMEGAN MARSDEN\n57.00 176.07");

        $this->assertCount(1, $parsed['rows']);
        $this->assertSame(57.00, (float) $parsed['rows'][0]['amount']);
        $this->assertSame(176.07, (float) $parsed['rows'][0]['balance_after']);
        $this->assertStringContainsString('MEGAN MARSDEN', $parsed['rows'][0]['description']);
    }

    /** Statement periods and summary blocks must never become transactions. */
    public function test_statement_header_is_not_a_transaction(): void
    {
        $parsed = $this->parsePage("Business Account statement\n01/04/2025 - 30/04/2025\n£26.07 Business Account balance\n-£3,258.77 Total outgoings");

        $this->assertCount(0, $parsed['rows']);
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** Without a sign or a balance column the direction is unknown, so the row is flagged. */
    public function test_unsigned_amount_without_balance_is_unparsed(): void
    {
        $parsed = $this->parsePage('15/06/2024 ACME LTD 100.00');

        $this->assertCount(0, $parsed['rows']);
        $this->assertSame(1, $parsed['unparsed']);
    }

    /** A statement whose rows reconcile to the printed closing balance is ready. */
    public function test_clean_statement_is_ready_for_reconciliation(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);

        $summary = $this->verifier()->refresh($import, $this->analysis());

        $this->assertSame(BankStatementVerificationService::READY, $summary['status']);
        $this->assertSame(0, $summary['blocking']);
        $this->assertSame('0.00', number_format((float) $summary['balance_difference'], 2, '.', ''));
    }

    /** A missing row leaves money unaccounted for, so the statement must be reviewed. */
    public function test_balance_mismatch_blocks_verification(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);

        $summary = $this->verifier()->refresh($import, $this->analysis(['closing_balance' => 80.00]));

        $this->assertSame(BankStatementVerificationService::REVIEW, $summary['status']);
        $this->assertGreaterThan(0, $summary['blocking']);
        $this->assertSame(10.00, round((float) $summary['balance_difference'], 2));
        $this->assertSame(1, DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->where('code', 'balance_mismatch')->whereNull('resolved_at')->count());

        $this->expectException(ValidationException::class);
        $this->verifier()->verify($import, $this->userId);
    }

    /** Lines that look like transactions but were not parsed must be flagged. */
    public function test_unreadable_rows_are_flagged_as_blocking(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);

        $summary = $this->verifier()->refresh($import, $this->analysis(['unparsed' => 2, 'unparsed_samples' => ['15/06/2020 ACME 100.00']]));

        $this->assertSame(BankStatementVerificationService::REVIEW, $summary['status']);
        $this->assertSame(1, DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->where('code', 'unparsed_transaction_rows')->count());
    }

    /** A page with transactions but no extracted rows is a coverage failure. */
    public function test_page_without_rows_is_flagged(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);

        $analysis = $this->analysis([
            'pages' => [
                1 => ['text_length' => 400, 'transactions_detected' => true, 'rows' => 1],
                2 => ['text_length' => 400, 'transactions_detected' => true, 'rows' => 0],
            ],
            'pages_total' => 2,
        ]);
        $summary = $this->verifier()->refresh($import, $analysis);

        $this->assertSame(BankStatementVerificationService::REVIEW, $summary['status']);
        $this->assertSame(1, DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->where('code', 'page_without_rows')->where('page_no', 2)->count());
        $this->assertSame(2, DB::table('bank_statement_pages')->where('bank_import_id', $import->id)->count());
    }

    /** A broken running balance means a row is missing or mis-signed. */
    public function test_running_balance_break_is_blocking(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00, 1, 1);
        $this->makeEntry($import, '2020-06-16', 'Beta Ltd', -5.00, 80.00, 1, 2);

        $summary = $this->verifier()->refresh($import, $this->analysis(['closing_balance' => 80.00]));

        $this->assertSame(BankStatementVerificationService::REVIEW, $summary['status']);
        $this->assertSame(1, DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->where('code', 'running_balance_mismatch')->count());
    }

    /** Identical rows are suspicious but can be genuine, so they warn rather than block. */
    public function test_possible_duplicates_are_a_warning(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00, 1, 1);
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 80.00, 1, 2);

        $summary = $this->verifier()->refresh($import, $this->analysis(['closing_balance' => 80.00]));

        $this->assertSame(BankStatementVerificationService::READY, $summary['status']);
        $this->assertSame(2, DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->where('code', 'possible_duplicate')->where('severity', 'warning')->count());
    }

    /** Scanned PDFs (OCR) cannot be page-mapped, so the balance check warns. */
    public function test_missing_balances_produce_a_warning_not_a_block(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00);

        $summary = $this->verifier()->refresh($import, $this->analysis([
            'opening_balance' => null, 'closing_balance' => null, 'ocr' => true,
        ]));

        $this->assertSame(BankStatementVerificationService::READY, $summary['status']);
        $this->assertSame(1, DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->where('code', 'balance_not_detected')->where('severity', 'warning')->count());
    }

    /** Verification records who signed it off and unlocks reconciliation. */
    public function test_verification_records_the_approver(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);
        $this->verifier()->refresh($import, $this->analysis());

        $this->verifier()->verify($import, $this->userId, 'Compared all pages with the PDF.');

        $row = DB::table('bank_imports')->find($import->id);
        $this->assertSame(BankStatementVerificationService::VERIFIED, $row->verification_status);
        $this->assertSame($this->userId, (int) $row->verified_by);
        $this->assertNotNull($row->verified_at);
        $this->assertSame('verified', $row->processing_status);
        // Verification releases rows to reconciliation; it never posts to the ledger.
        $this->assertSame(0, DB::table('transactions')->where('bank_entry_id', DB::table('bank_entries')
            ->where('bank_import_id', $import->id)->value('id'))->count());
    }

    /** A blank information page is expected, not an exception. */
    public function test_blank_page_does_not_block_verification(): void
    {
        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);

        $analysis = $this->analysis([
            'pages' => [
                1 => ['text_length' => 400, 'transactions_detected' => true, 'rows' => 1],
                2 => ['text_length' => 0, 'transactions_detected' => false, 'rows' => 0],
            ],
            'pages_total' => 2,
        ]);
        $summary = $this->verifier()->refresh($import, $analysis);

        $this->assertSame(BankStatementVerificationService::READY, $summary['status']);
        $this->assertSame('empty', DB::table('bank_statement_pages')
            ->where('bank_import_id', $import->id)->where('page_no', 2)->value('status'));
        $this->assertSame(0, DB::table('bank_statement_exceptions')
            ->where('bank_import_id', $import->id)->whereNull('resolved_at')->count());
    }

    /** Reconciliation actions refuse to run while the statement is unverified. */
    public function test_reconciliation_action_is_blocked_until_verified(): void
    {
        $import = $this->makeImport(BankStatementVerificationService::REVIEW);
        $entryId = $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);

        $this->expectException(HttpException::class);
        $this->adminController()->action(new Request([
            'client_id' => $this->client->id,
            'action' => 'categorize',
        ]), $entryId);
    }

    /** Bulk approval of a statement is refused until the statement is verified. */
    public function test_approve_import_is_refused_until_verified(): void
    {
        $import = $this->makeImport(BankStatementVerificationService::READY);

        $response = $this->adminController()->approveImport(new Request([
            'client_id' => $this->client->id,
        ]), $import->id);

        $this->assertSame('error', $response->getSession()->get('error') ? 'error' : null);
        $this->assertSame(BankStatementVerificationService::READY,
            DB::table('bank_imports')->where('id', $import->id)->value('verification_status'));
    }

    /** The accountant workspace renders the PDF, rows, pages and exceptions. */
    public function test_verification_workspace_renders(): void
    {
        $admin = DB::table('users')->where('type', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user fixture available.');
        }
        $this->actingAs(User::find($admin->id));

        $import = $this->makeImport();
        $this->makeEntry($import, '2020-06-15', 'Acme Ltd', -10.00, 90.00);
        $this->verifier()->refresh($import, $this->analysis(['closing_balance' => 80.00]));

        $response = $this->get(route('admin.bank-reconciliation.verification', $import->id));

        $response->assertOk();
        $response->assertSee('Statement verification');
        $response->assertSee('Page coverage');
        $response->assertSee('balance mismatch');
        $response->assertSee('Acme Ltd');
    }

    /** Correcting a row keeps the original value, the correction and the reason. */
    public function test_row_correction_keeps_original_value_and_reason(): void
    {
        $import = $this->makeImport(BankStatementVerificationService::VERIFIED);
        $entryId = $this->makeEntry($import, '2020-06-15', 'ACME LTO', -10.00, 90.00);
        DB::table('bank_imports')->where('id', $import->id)->update(['verified_at' => now()]);

        $this->adminController()->updateEntry(new Request([
            'transaction_date' => '2020-06-15',
            'description' => 'ACME LTD',
            'amount' => -10.00,
            'reason' => 'OCR misread the supplier name',
        ]), $entryId);

        $revision = DB::table('bank_entry_revisions')->where('bank_entry_id', $entryId)->where('action', 'edit')->first();
        $this->assertNotNull($revision);
        $this->assertStringContainsString('ACME LTO', (string) $revision->original_values);
        $this->assertStringContainsString('ACME LTD', (string) $revision->corrected_values);
        $this->assertSame('OCR misread the supplier name', $revision->reason);
        $this->assertSame($this->userId, (int) $revision->actor_id);

        // The edit reopened verification: a corrected statement must be signed off again.
        $this->assertNull(DB::table('bank_imports')->where('id', $import->id)->value('verified_at'));
        $this->assertSame('ACME LTD', DB::table('bank_entries')->where('id', $entryId)->value('description'));
    }
}
