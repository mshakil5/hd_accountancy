<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BankReconciliationPageController;
use App\Models\User;
use App\Services\BankLedgerPostingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bank reconciliation flow: ledger posting (idempotent), internal transfers,
 * partial settlements and fuzzy receipt scoring.
 *
 * Follows the suite convention of running inside a wrapping transaction that
 * is rolled back, so no fixture residue is left on the database.
 */
class BankReconciliationTest extends TestCase
{
    protected int $userId;
    protected object $client;
    protected int $credId;
    protected int $headId;
    protected int $bankA;
    protected int $bankB;
    protected int $importId;
    protected static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // The reconciliation schema must exist before these tests are meaningful.
        if (!Schema::hasTable('bank_settlements')
            || !Schema::hasColumn('bank_entries', 'transfer_type')
            || !Schema::hasColumn('bank_allocation_lines', 'tax_percent')) {
            $this->markTestSkipped('Run `php artisan migrate` to enable bank reconciliation tests.');
        }

        // Resolve fixtures and skip *before* opening the transaction, so a skip
        // never leaves a dangling transaction behind.
        $this->client = DB::table('clients')->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->first();
        if (!$this->client) {
            $this->markTestSkipped('No client fixture available.');
        }

        DB::beginTransaction();

        $this->userId = (int) (DB::table('users')->value('id') ?? 0);
        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) $this->actingAs($user);
        }

        $this->credId = (int) $this->client->client_credential_id;
        $this->headId = $this->ensureHead($this->credId);
        $this->bankA = $this->makeBankAccount('HSBC');
        $this->bankB = $this->makeBankAccount('Barclays');
        $this->importId = $this->makeImport($this->bankA);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    protected function uid(string $p): string
    {
        return $p . (++self::$seq) . time() . rand(1000, 9999);
    }

    /** An account head owned by this client's credential (reused if present). */
    protected function ensureHead(int $credId): int
    {
        $existing = DB::table('account_heads')->where('client_credential_id', $credId)->value('id');
        if ($existing) return (int) $existing;

        $typeId = DB::table('account_types')->value('id');
        if (!$typeId) {
            DB::table('account_types')->insert([
                'name' => 'Test Asset ' . $this->uid('AT'), 'category' => 'asset',
                'normal_balance' => 'debit', 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $typeId = DB::table('account_types')->value('id');
        }
        return (int) DB::table('account_heads')->insertGetId([
            'account_type_id' => $typeId, 'code' => 'TEST-' . $this->uid('H'),
            'name' => 'Test Head ' . $this->uid('N'), 'is_active' => 1,
            'client_credential_id' => $credId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function makeBankAccount(string $bank): int
    {
        return (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->client->id, 'bank_name' => $bank, 'account_label' => 'Current',
            'account_last_four' => (string) rand(1000, 9999), 'currency' => 'GBP', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function makeImport(int $bankAccountId): int
    {
        return (int) DB::table('bank_imports')->insertGetId([
            'client_id' => $this->client->id, 'bank_account_id' => $bankAccountId, 'filename' => 'test.csv',
            'sha256' => hash('sha256', $this->uid('sha')), 'created_by' => $this->userId,
            'transaction_count' => 1, 'file_type' => 'csv', 'processing_status' => 'parsed',
            // Reconciliation only opens for statements an accountant has verified.
            'verification_status' => 'verified', 'verified_by' => $this->userId, 'verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function makeEntry(float $amount, string $status, ?int $bankAccountId = null): int
    {
        return (int) DB::table('bank_entries')->insertGetId([
            'client_id' => $this->client->id, 'bank_import_id' => $this->importId,
            'bank_account_id' => $bankAccountId ?? $this->bankA,
            'transaction_date' => '2020-06-15', 'description' => 'Test bank line ' . $this->uid('E'),
            'reference' => 'REF' . $this->uid('R'), 'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'GBP', 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function makeReceipt(float $amount): int
    {
        $rid = (int) DB::table('receipts')->insertGetId([
            'client_id' => $this->client->id, 'receipt_number' => 'TEST-' . $this->uid('R'),
            'receipt_date' => '2020-06-15', 'status' => 'ready', 'supplier' => 'Acme Ltd',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('receipt_details')->insert([
            'receipt_id' => $rid, 'account_head_id' => $this->headId, 'invoice_date' => '2020-06-15',
            'total_amount' => $amount, 'paid' => false, 'payment_method' => 'bank',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $rid;
    }

    protected function controller(): BankReconciliationPageController
    {
        return new BankReconciliationPageController(app(BankLedgerPostingService::class));
    }

    /** A categorised entry posts exactly one ledger row, and never twice. */
    public function test_categorized_entry_posts_to_ledger_once(): void
    {
        $entryId = $this->makeEntry(-100.00, 'categorized');
        DB::table('bank_entries')->where('id', $entryId)->update(['account_head_id' => $this->headId]);

        $service = app(BankLedgerPostingService::class);
        $first = $service->postEntry((object) DB::table('bank_entries')->find($entryId), $this->userId);
        $this->assertSame(1, $first);
        $this->assertSame(1, DB::table('transactions')->where('bank_entry_id', $entryId)->count());

        // The journal_posted_at guard makes a re-post a no-op.
        $second = $service->postEntry((object) DB::table('bank_entries')->find($entryId), $this->userId);
        $this->assertSame(0, $second);
        $this->assertSame(1, DB::table('transactions')->where('bank_entry_id', $entryId)->count());
    }

    /** A transfer links both sides and posts one net-zero leg to each bank head. */
    public function test_internal_transfer_links_and_posts_both_sides(): void
    {
        $out = $this->makeEntry(-250.00, 'unmatched', $this->bankA);
        $in  = $this->makeEntry(250.00, 'unmatched', $this->bankB);

        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'transfer',
            'linked_entry_id' => $in, 'account_head_id' => $this->headId,
        ]), $out);

        $this->assertSame('internal_transfer', DB::table('bank_entries')->where('id', $out)->value('transfer_type'));
        $this->assertSame($in, (int) DB::table('bank_entries')->where('id', $out)->value('linked_bank_entry_id'));
        $this->assertSame($out, (int) DB::table('bank_entries')->where('id', $in)->value('linked_bank_entry_id'));

        // Each side needs its own bank head before approval.
        DB::table('bank_entries')->where('id', $in)->update(['account_head_id' => $this->headId]);

        $service = app(BankLedgerPostingService::class);
        foreach ([$out, $in] as $id) {
            DB::table('bank_entries')->where('id', $id)->update([
                'reconciliation_status' => 'approved', 'approved_at' => now(),
            ]);
            $service->postEntry((object) DB::table('bank_entries')->find($id), $this->userId);
        }
        $this->assertSame(1, DB::table('transactions')->where('bank_entry_id', $out)->count());
        $this->assertSame(1, DB::table('transactions')->where('bank_entry_id', $in)->count());
    }

    /** A partial settlement records the settled amount and the remainder. */
    public function test_partial_settlement_records_remainder(): void
    {
        $entryId = $this->makeEntry(-60.00, 'unmatched');
        $receiptId = $this->makeReceipt(100.00);

        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'settle',
            'receipt_id' => $receiptId, 'settlement_type' => 'partial',
        ]), $entryId);

        $row = DB::table('bank_settlements')->where('bank_entry_id', $entryId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(60.00, (float) $row->settled_amount, 0.001);
        $this->assertEqualsWithDelta(40.00, (float) $row->remaining_amount, 0.001);
        $this->assertSame('open', $row->status);
    }

    /** Fuzzy scoring ranks an exact amount/date/supplier hit highest. */
    public function test_fuzzy_scoring_ranks_exact_match_highest(): void
    {
        $entry = (object) ['id' => 0, 'amount' => -100.00, 'transaction_date' => '2020-06-15',
            'description' => 'Acme Ltd', 'reference' => 'INV100'];
        $receipts = collect([
            (object) ['id' => 1, 'receipt_number' => 'INV100', 'supplier' => 'Acme Ltd',
                'detail' => (object) ['total_amount' => 100.00, 'invoice_date' => '2020-06-15']],
            (object) ['id' => 2, 'receipt_number' => 'X9', 'supplier' => 'Other Co',
                'detail' => (object) ['total_amount' => 99.50, 'invoice_date' => '2020-06-15']],
        ]);

        $controller = $this->controller();
        $method = new \ReflectionMethod($controller, 'scoreReceipts');
        $method->setAccessible(true);
        $ranked = $method->invoke($controller, $entry, $receipts);

        $this->assertNotEmpty($ranked);
        $this->assertSame(1, $ranked[0]['id']);
        $this->assertGreaterThanOrEqual($ranked[count($ranked) - 1]['score'], $ranked[0]['score']);
    }

    /** Bank statements are PDF-only: a PDF upload is accepted and stored as PDF. */
    public function test_bank_statement_upload_accepts_pdf(): void
    {
        $client = DB::table('clients')->where('status', true)->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->whereKey($this->client->id)->first();
        if (!$client) {
            $this->markTestSkipped('No client fixture available.');
        }

        $bankId = (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $client->id, 'bank_name' => 'HSBC', 'account_label' => 'Current',
            'account_last_four' => (string) rand(1000, 9999), 'currency' => 'GBP', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $pdf = "%PDF-1.4\n"
            . "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            . "4 0 obj<</Length 74>>stream\nBT /F1 10 Tf 50 800 Td (15/06/2024 ACME LTD -100.00) Tj ET\nendstream endobj\n"
            . "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            . "trailer<</Root 1 0 R/Size 6>>\n%%EOF\n";

        $request = Request::create('/api/businesses/' . $client->id . '/bank-imports', 'POST', [
            'bank_account_id' => $bankId,
        ], [], ['file' => UploadedFile::fake()->createWithContent('statement.pdf', $pdf)]);
        $request->setUserResolver(fn () => User::find($this->userId));

        $response = app(\App\Http\Controllers\Api\BankReconciliationController::class)
            ->upload($request, (int) $client->id);

        $this->assertSame(201, $response->getStatusCode());
        $import = DB::table('bank_imports')->where('client_id', $client->id)
            ->where('filename', 'statement.pdf')->first();
        $this->assertNotNull($import, 'PDF statement was not imported.');
        $this->assertSame('pdf', $import->file_type);
        $this->assertStringEndsWith('.pdf', $import->storage_path);

        // Upload now runs the verification engine and reports its verdict.
        $payload = $response->getData(true);
        $this->assertContains($payload['verification_status'], ['ready_for_reconciliation', 'needs_accountant_review', 'extraction_failed']);
        $this->assertArrayHasKey('blocking_exceptions', $payload);
        $this->assertSame($import->verification_status, DB::table('bank_imports')->where('id', $import->id)->value('verification_status'));

        Storage::disk('local')->delete($import->storage_path);
    }

    /** Bank statements are PDF-only: a CSV upload must be rejected before import. */
    public function test_bank_statement_upload_rejects_csv(): void
    {
        $client = DB::table('clients')->where('status', true)->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->whereKey($this->client->id)->first();
        if (!$client) {
            $this->markTestSkipped('No client fixture available.');
        }

        $request = Request::create('/api/businesses/' . $client->id . '/bank-imports', 'POST', [], [], [
            'file' => UploadedFile::fake()->create('statement.csv', 12, 'text/csv'),
        ]);
        $request->setUserResolver(fn () => User::find($this->userId));

        $this->expectException(ValidationException::class);
        app(\App\Http\Controllers\Api\BankReconciliationController::class)
            ->upload($request, (int) $client->id);
    }
}
