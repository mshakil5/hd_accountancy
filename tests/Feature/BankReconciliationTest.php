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

    protected function makeReceipt(float $amount, string $invoiceDate = '2020-06-15', bool $paid = false): int
    {
        $rid = (int) DB::table('receipts')->insertGetId([
            'client_id' => $this->client->id, 'receipt_number' => 'TEST-' . $this->uid('R'),
            'receipt_date' => $invoiceDate, 'status' => 'ready', 'supplier' => 'Acme Ltd',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('receipt_details')->insert([
            'receipt_id' => $rid, 'account_head_id' => $this->headId, 'invoice_date' => $invoiceDate,
            'total_amount' => $amount, 'paid' => $paid, 'payment_method' => 'bank',
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

    /** Same-amount receipts on other dates surface as suggestions, nearest date first. */
    public function test_same_amount_receipts_on_other_dates_are_suggested_first(): void
    {
        DB::table('clients')->where('id', $this->client->id)->update(['status' => 1]);
        $user = DB::table('users')->where('email', '!=', 'jane@gmail.com')->first();
        if ($user) {
            $this->actingAs(User::find($user->id));
        }

        $entryId = $this->makeEntry(-120.00, 'unmatched');
        $same  = $this->makeReceipt(120.00, '2020-06-15', true);
        $near  = $this->makeReceipt(120.00, '2020-06-18', true);
        $far   = $this->makeReceipt(120.00, '2020-05-15', true);
        $other = $this->makeReceipt(50.00, '2020-06-15', true);

        $view = $this->controller()->index(new Request([
            'client_credential_id' => $this->credId,
            'client_id' => $this->client->id,
            'bank_account_id' => $this->bankA,
            'reconcile_status' => 'all',
            'date_from' => '2020-06-15',
            'date_to' => '2020-06-15',
        ]));
        $data = $view->getData();

        // Match tab: exact amount, any date, ordered by date gap.
        $suggestedIds = collect($data['fuzzyMatches'][$entryId] ?? [])->pluck('id')->all();
        $this->assertSame([$same, $near, $far], array_values(array_intersect($suggestedIds, [$same, $near, $far])));
        $this->assertSame(0, $data['suggestionMeta'][$entryId][$same]['days']);
        $this->assertSame(3, $data['suggestionMeta'][$entryId][$near]['days']);
        $this->assertSame(31, $data['suggestionMeta'][$entryId][$far]['days']);

        // Find & Match: the same suggestions lead the list, other receipts follow.
        $findIds = collect($data['findMatches'][$entryId] ?? [])->pluck('id')->all();
        $this->assertSame([$same, $near, $far], array_values(array_intersect($findIds, [$same, $near, $far])));
        $this->assertContains($other, $findIds);
        $this->assertGreaterThan(
            array_search($far, $findIds, true),
            array_search($other, $findIds, true)
        );
    }

    /** The popup endpoint returns the receipt details the accountant needs to judge a match. */
    public function test_receipt_summary_returns_details_for_the_business(): void
    {
        $receiptId = $this->makeReceipt(120.00, '2020-06-18', true);

        $response = $this->controller()->receiptSummary(new Request(['client_id' => $this->client->id]), $receiptId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame($receiptId, $data['id']);
        $this->assertSame('Acme Ltd', $data['supplier']);
        $this->assertEqualsWithDelta(120.00, (float) $data['total_amount'], 0.001);
        $this->assertSame('18/06/2020', $data['invoice_date']);
        $this->assertTrue($data['paid']);
        $this->assertNotNull($data['account_head']);
        $this->assertSame([], $data['files']);
    }

    /** The popup endpoint is scoped: another business cannot open the receipt. */
    public function test_receipt_summary_is_scoped_to_the_selected_business(): void
    {
        $receiptId = $this->makeReceipt(10.00, '2020-06-15', true);
        $otherClientId = (int) DB::table('clients')->where('id', '!=', $this->client->id)->value('id');
        if (!$otherClientId) {
            $this->markTestSkipped('Need a second client fixture.');
        }

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->controller()->receiptSummary(new Request(['client_id' => $otherClientId]), $receiptId);
    }

    /** Every create keeps the gross fixed and derives the net from the VAT %. */
    public function test_create_applies_vat_to_the_fixed_gross(): void
    {
        $entryId = $this->makeEntry(-120.00, 'unmatched');

        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'create',
            'account_head_id' => $this->headId, 'supplier' => 'VAT Supplier',
            'invoice_date' => '2020-06-15', 'vat_percent' => 20,
        ]), $entryId);

        $receiptId = (int) DB::table('bank_entries')->where('id', $entryId)->value('receipt_id');
        $this->assertGreaterThan(0, $receiptId);

        $detail = DB::table('receipt_details')->where('receipt_id', $receiptId)->first();
        // Gross stays at the bank amount; net + VAT come from the percentage.
        $this->assertEqualsWithDelta(120.00, (float) $detail->total_amount, 0.001);
        $this->assertEqualsWithDelta(100.00, (float) $detail->net_amount, 0.001);
        $this->assertEqualsWithDelta(20.00, (float) $detail->vat_amount, 0.001);
    }

    /** Create refuses a gross that is not the bank amount; part payments use Split / Find & Match. */
    public function test_create_rejects_a_gross_that_is_not_the_bank_amount(): void
    {
        $entryId = $this->makeEntry(-120.00, 'unmatched');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'create',
            'account_head_id' => $this->headId, 'supplier' => 'VAT Supplier',
            'invoice_date' => '2020-06-15', 'gross' => 100.00, 'vat_percent' => 20,
        ]), $entryId);
    }

    /** Split lines use the same rule: gross in, VAT % applied, net derived. */
    public function test_split_lines_apply_vat_per_line(): void
    {
        $entryId = $this->makeEntry(-120.00, 'unmatched');

        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'split',
            'splits' => [
                ['amount' => 100.00, 'account_head_id' => $this->headId, 'vat_percent' => 0],
                ['amount' => 20.00, 'account_head_id' => $this->headId, 'vat_percent' => 20],
            ],
        ]), $entryId);

        $lines = DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertEqualsWithDelta(20.00, (float) $lines[1]->amount, 0.001);
        $this->assertEqualsWithDelta(20.00, (float) $lines[1]->tax_percent, 0.001);
        $this->assertEqualsWithDelta(3.33, (float) $lines[1]->vat_amount, 0.001);

        $detail = DB::table('receipt_details')->where('receipt_id', $lines[1]->receipt_id)->first();
        $this->assertEqualsWithDelta(20.00, (float) $detail->total_amount, 0.001);
        $this->assertEqualsWithDelta(16.67, (float) $detail->net_amount, 0.001);
        $this->assertEqualsWithDelta(3.33, (float) $detail->vat_amount, 0.001);
    }

    /** Find & Match: part of an existing receipt plus a new receipt for the rest. */
    public function test_find_match_applies_part_of_a_receipt_and_creates_the_remainder(): void
    {
        $entryId = $this->makeEntry(-34.00, 'unmatched');
        $receiptId = $this->makeReceipt(40.00, '2020-06-15', true);

        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'allocations' => [$receiptId => 20.00],
            'creates' => [[
                'gross' => 14.00, 'vat_percent' => 0, 'account_head_id' => $this->headId,
                'supplier' => 'Remainder Ltd', 'invoice_date' => '2020-06-15',
            ]],
        ]), $entryId);

        $entry = DB::table('bank_entries')->find($entryId);
        $this->assertSame('approved', $entry->reconciliation_status);
        $this->assertSame('split', $entry->status);

        $lines = DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->get();
        $this->assertCount(2, $lines);
        $partial = $lines->firstWhere('receipt_id', $receiptId);
        $this->assertNotNull($partial);
        $this->assertEqualsWithDelta(20.00, (float) $partial->amount, 0.001);

        // Only £20 of the £40 receipt was used, so the other £20 is still usable.
        $second = $this->makeEntry(-20.00, 'unmatched');
        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'allocations' => [$receiptId => 20.00],
        ]), $second);
        $this->assertSame($receiptId, (int) DB::table('bank_entries')->where('id', $second)->value('receipt_id'));
    }

    /** A receipt can never give more than what is left of it. */
    public function test_find_match_rejects_more_than_a_receipt_has_left(): void
    {
        $first = $this->makeEntry(-34.00, 'unmatched');
        $receiptId = $this->makeReceipt(40.00, '2020-06-15', true);
        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'allocations' => [$receiptId => 20.00],
            'creates' => [['gross' => 14.00, 'account_head_id' => $this->headId]],
        ]), $first);

        $second = $this->makeEntry(-34.00, 'unmatched');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'allocations' => [$receiptId => 25.00],
            'creates' => [['gross' => 9.00, 'account_head_id' => $this->headId]],
        ]), $second);
    }

    /** The applied receipts plus new receipts must cover the bank line exactly. */
    public function test_find_match_rejects_an_unbalanced_combination(): void
    {
        $entryId = $this->makeEntry(-34.00, 'unmatched');
        $receiptId = $this->makeReceipt(40.00, '2020-06-15', true);

        $this->expectException(ValidationException::class);
        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'allocations' => [$receiptId => 20.00],
            'creates' => [['gross' => 13.00, 'account_head_id' => $this->headId]],
        ]), $entryId);
    }

    /** Every reconcile tab renders the controls the VAT + Find & Match flows need. */
    public function test_reconcile_tabs_render_the_vat_and_find_match_controls(): void
    {
        DB::table('clients')->where('id', $this->client->id)->update(['status' => 1]);
        $user = DB::table('users')->where('email', '!=', 'jane@gmail.com')->first();
        if ($user) {
            $this->actingAs(User::find($user->id));
        }

        $this->makeEntry(-34.00, 'unmatched');
        $this->makeReceipt(40.00, '2020-06-15', true);

        $html = $this->controller()->index(new Request([
            'client_credential_id' => $this->credId,
            'client_id' => $this->client->id,
            'bank_account_id' => $this->bankA,
            'reconcile_status' => 'all',
            'date_from' => '2020-06-15',
            'date_to' => '2020-06-15',
        ]))->render();

        // All six tabs and their panels are present.
        foreach (['match', 'create', 'split', 'find', 'transfer', 'discuss'] as $tab) {
            $this->assertStringContainsString('data-hd-tab="' . $tab . '"', $html);
            $this->assertStringContainsString('data-hd-panel="' . $tab . '"', $html);
        }

        // Create: the same amount block as Split / Find & Match — net derived, VAT %
        // and gross typed.
        $this->assertStringContainsString('Net Amount', $html);
        $this->assertStringContainsString('VAT Percent', $html);
        $this->assertStringContainsString('Gross Amount', $html);
        $this->assertStringContainsString('name="vat_percent"', $html);
        $this->assertStringContainsString('name="gross"', $html);
        $this->assertStringContainsString('hd-vat-box', $html);
        $this->assertStringContainsString('hd-vat-net', $html);
        $this->assertStringContainsString('hd-vat-amt', $html);
        // Create + 2 split lines + 1 find-&-match create line.
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'class="hd-vat-box"'));

        // Split: each line carries its own gross + VAT %.
        $this->assertStringContainsString('splits[0][vat_percent]', $html);
        $this->assertStringContainsString('splits[1][vat_percent]', $html);
        $this->assertStringContainsString('hd-vat-gross', $html);

        // Find & Match: apply amount per receipt + new receipts for the remainder.
        $this->assertStringContainsString('hd-find-apply', $html);
        $this->assertStringContainsString('name="allocations[', $html);
        $this->assertStringContainsString('data-remaining=', $html);
        $this->assertStringContainsString('creates[0][gross]', $html);
        $this->assertStringContainsString('creates[0][vat_percent]', $html);
        $this->assertStringContainsString('hd-create-meter', $html);
        $this->assertStringContainsString('hd-find-submit', $html);

        // Dates are shown day/month/year throughout the accounting module.
        $this->assertStringContainsString('15/06/2020', $html);
        $this->assertStringNotContainsString('15-06-2020', $html);

        // The wiring that makes those buttons work.
        foreach (['hdVatCalc', 'hdVatUpdate', 'hdFindUpdate', 'hdFindTotals', 'hdAddCreateLine', 'hdRemoveCreateLine', 'hdAddSplitLine', 'hdSplitTotals'] as $fn) {
            $this->assertStringContainsString('function ' . $fn, $html);
        }
    }

    /** A plain multi/single receipt selection (no apply boxes) still matches in full. */
    public function test_find_match_accepts_a_plain_full_receipt_selection(): void
    {
        $entryId = $this->makeEntry(-40.00, 'unmatched');
        $receiptId = $this->makeReceipt(40.00, '2020-06-15', true);

        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'receipt_ids' => [$receiptId],
        ]), $entryId);

        $entry = DB::table('bank_entries')->find($entryId);
        $this->assertSame('matched', $entry->status);
        $this->assertSame($receiptId, (int) $entry->receipt_id);
        $this->assertSame('approved', $entry->reconciliation_status);
    }

    /** A ticked receipt with no apply amount is refused instead of assuming full. */
    public function test_find_match_rejects_a_ticked_receipt_without_an_apply_amount(): void
    {
        $entryId = $this->makeEntry(-34.00, 'unmatched');
        $receiptId = $this->makeReceipt(40.00, '2020-06-15', true);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->controller()->action(new Request([
            'client_id' => $this->client->id, 'action' => 'find_match',
            'receipt_ids' => [$receiptId],
            'allocations' => [$receiptId => ''],
        ]), $entryId);
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
