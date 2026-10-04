<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountHeadController;
use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\ReceiptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Big-phase sweep: every accounting module, looking for errors.
// Modules: chart seed, account types CRUD, account heads CRUD + guards,
// P&L, balance sheet, trial balance, drill-down, CSV/XLSX exports,
// receipt-head integration. All fixtures post in June 2020 (verified empty
// of live data) and roll back, leaving zero residue.
class AccountingModulesTest extends TestCase
{
    protected AccountingController $ac;
    protected AccountHeadController $hc;
    protected AccountTypeController $tc;
    protected ReceiptController $rc;
    protected int $userId;
    protected int $clientA = 1; // credential 1
    protected int $clientB = 2; // credential 2
    protected int $credA = 1;
    protected int $credB = 2;
    protected array $headIds;
    protected static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->ac = new AccountingController();
        $this->hc = new AccountHeadController();
        $this->tc = new AccountTypeController();
        $this->rc = new ReceiptController();
        $this->userId = DB::table('users')->first()->id;
        $this->headIds = DB::table('account_heads')
            ->whereNull('client_credential_id')
            ->pluck('id', 'code')->all();
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

    protected function receipt(int $clientId, string $date, string $method = 'bank', string $status = 'ready'): int
    {
        $id = DB::table('receipts')->insertGetId([
            'client_id' => $clientId, 'receipt_number' => 'TEST-MOD-' . $this->uid('R'),
            'receipt_date' => $date, 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('receipt_details')->insert([
            'receipt_id' => $id, 'account_head_id' => $this->headIds['101'],
            'invoice_date' => $date, 'total_amount' => 1,
            'paid' => false, 'payment_method' => $method,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    protected function leg(int $receiptId, string $code, string $type, float $amount, ?int $parentId = null): int
    {
        return DB::table('transactions')->insertGetId([
            'transaction_uid' => $this->uid('T' . $code), 'receipt_id' => $receiptId,
            'account_head_id' => $this->headIds[$code], 'type' => $type,
            'amount' => $amount, 'total_amount' => $amount,
            'parent_id' => $parentId, 'created_by' => $this->userId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // One balanced event: debit leg (payable) + credit leg (receivable).
    protected function pair(string $debit, string $credit, float $amount, int $client = 1, string $date = '2020-06-15', string $method = 'bank', string $status = 'ready'): int
    {
        $r = $this->receipt($client, $date, $method, $status);
        $this->leg($r, $debit, 'payable', $amount);
        $this->leg($r, $credit, 'receivable', $amount);
        return $r;
    }

    protected function capture($response): string
    {
        ob_start();
        $response->getCallback()();
        return ob_get_clean();
    }

    // ---------------- A. Chart seed state ----------------

    public function test_global_chart_has_75_active_heads_with_excel_codes(): void
    {
        $codes = DB::table('account_heads')->whereNull('client_credential_id')
            ->where('is_active', 1)->orderBy('code')->pluck('code')->all();
        $this->assertCount(75, $codes);
        foreach (['101', '107', '108', '109', '201', '204', '301', '329', '401', '407', '451', '455', '501', '513', '551', '554', '601', '604'] as $c) {
            $this->assertContains($c, $codes, "Excel code $c missing from global chart");
        }
        // Seeded duplicate name across two types is intact
        $this->assertEquals(2, DB::table('account_heads')->where('name', 'Director Loan Account')->whereNull('client_credential_id')->count());
    }

    public function test_account_types_match_excel_structure(): void
    {
        $types = DB::table('account_types')->orderBy('id')->pluck('name', 'id')->all();
        $this->assertCount(9, $types);
        $this->assertEquals('Turnover', $types[6]);
        $this->assertEquals('Other Income', $types[7]);
        $this->assertEquals('Direct Expenses', $types[8]);
        $this->assertEquals('Expenses', $types[9]);
        $this->assertEquals('Capital and reserves', $types[5]);
    }

    // ---------------- B. Account types CRUD ----------------

    public function test_type_store_rejects_missing_fields(): void
    {
        $this->assertEquals(303, $this->tc->store(new Request([]))->getData(true)['status']);
        $this->assertEquals(303, $this->tc->store(new Request(['name' => 'X']))->getData(true)['status']);
        $this->assertEquals(303, $this->tc->store(new Request(['name' => 'X', 'category' => 'expense']))->getData(true)['status']);
    }

    public function test_type_store_update_delete_toggle(): void
    {
        $this->assertEquals(303, $this->tc->store(new Request(['name' => 'Turnover', 'category' => 'revenue', 'normal_balance' => 'credit']))->getData(true)['status']);
        $this->assertEquals(300, $this->tc->store(new Request(['name' => 'Mod Test Type', 'category' => 'expense', 'normal_balance' => 'debit']))->getData(true)['status']);
        $id = DB::table('account_types')->where('name', 'Mod Test Type')->value('id');

        $this->assertEquals('Mod Test Type', $this->tc->edit($id)->getData(true)['name']);
        // update to existing name blocked; self-update allowed
        $this->assertEquals(303, $this->tc->update(new Request(['codeid' => $id, 'name' => 'Turnover', 'category' => 'expense', 'normal_balance' => 'debit']))->getData(true)['status']);
        $this->assertEquals(300, $this->tc->update(new Request(['codeid' => $id, 'name' => 'Mod Test Type', 'category' => 'expense', 'normal_balance' => 'debit']))->getData(true)['status']);

        $this->tc->toggleStatus($id);
        $this->assertEquals(0, DB::table('account_types')->where('id', $id)->value('is_active'));
        $this->tc->toggleStatus($id);
        $this->assertEquals(1, DB::table('account_types')->where('id', $id)->value('is_active'));

        $this->assertTrue($this->tc->delete($id)->getData(true)['success']);
        $this->assertNull(DB::table('account_types')->where('id', $id)->first());
    }

    // ---------------- C. Account heads ----------------

    public function test_head_store_rejects_missing_fields(): void
    {
        $this->assertEquals(303, $this->hc->store(new Request([]))->getData(true)['status']);
        $this->assertEquals(303, $this->hc->store(new Request(['account_type_id' => 9]))->getData(true)['status']);
        $this->assertEquals(303, $this->hc->store(new Request(['account_type_id' => 9, 'code' => '330']))->getData(true)['status']);
    }

    public function test_head_code_unique_per_scope(): void
    {
        // duplicate global code blocked
        $r = $this->hc->store(new Request(['account_type_id' => 9, 'code' => '301', 'name' => 'Brand New Name']))->getData(true);
        $this->assertEquals(303, $r['status']);
        // same code under another credential allowed (per-client override)
        $r = $this->hc->store(new Request(['account_type_id' => 9, 'code' => '301', 'name' => 'Client Copy', 'client_credential_id' => $this->credA]))->getData(true);
        $this->assertEquals(300, $r['status']);
    }

    public function test_head_name_unique_per_type_and_scope(): void
    {
        // same name + same type + global blocked
        $r = $this->hc->store(new Request(['account_type_id' => 9, 'code' => '330', 'name' => 'Rent']))->getData(true);
        $this->assertEquals(303, $r['status']);
        // same name under a different type allowed
        $r = $this->hc->store(new Request(['account_type_id' => 8, 'code' => '205', 'name' => 'Rent', 'client_credential_id' => $this->credA]))->getData(true);
        $this->assertEquals(300, $r['status']);
    }

    public function test_head_global_code_range_guard(): void
    {
        // Turnover global must be 101-107
        $r = $this->hc->store(new Request(['account_type_id' => 6, 'code' => '999', 'name' => 'Out Of Range']))->getData(true);
        $this->assertEquals(303, $r['status']);
        // per-client heads are free-form
        $r = $this->hc->store(new Request(['account_type_id' => 6, 'code' => '999', 'name' => 'Custom Head', 'client_credential_id' => $this->credA]))->getData(true);
        $this->assertEquals(300, $r['status']);
        // boundary codes accepted globally
        $r = $this->hc->store(new Request(['account_type_id' => 6, 'code' => '107', 'name' => 'Clash Name']))->getData(true);
        $this->assertEquals(303, $r['status']); // 107 taken -> dup code path
    }

    public function test_head_check_code(): void
    {
        $this->assertFalse($this->hc->checkCode(new Request(['code' => '301']))->getData(true)['available']);
        $this->assertTrue($this->hc->checkCode(new Request(['code' => '301', 'client_credential_id' => $this->credB]))->getData(true)['available']);
        $id = DB::table('account_heads')->where('code', '301')->whereNull('client_credential_id')->value('id');
        $this->assertTrue($this->hc->checkCode(new Request(['code' => '301', 'id' => $id]))->getData(true)['available']);
    }

    public function test_head_by_type_scoping(): void
    {
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '990', 'name' => 'Cred A Only', 'client_credential_id' => $this->credA]));
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '991', 'name' => 'Inactive Head', 'client_credential_id' => $this->credA]));
        DB::table('account_heads')->where('code', '991')->update(['is_active' => 0]);

        $global = collect($this->hc->byType(9)->getData(true));
        $this->assertTrue($global->contains('name', 'Rent'));
        $this->assertFalse($global->contains('name', 'Cred A Only'));
        $this->assertFalse($global->contains('name', 'Inactive Head'));
        $codes = $global->pluck('code')->all();
        $sorted = $codes;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, array_values($codes), 'byType must order by code');

        app()->instance('request', Request::create('/', 'GET', ['client_credential_id' => $this->credA]));
        $withCred = collect($this->hc->byType(9)->getData(true));
        $this->assertTrue($withCred->contains('name', 'Cred A Only'));
        $this->assertFalse($withCred->contains('name', 'Inactive Head'));
    }

    public function test_head_update_delete_toggle_edit(): void
    {
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '992', 'name' => 'Upd Head', 'client_credential_id' => $this->credA]));
        $id = DB::table('account_heads')->where('code', '992')->value('id');
        $this->assertEquals('Upd Head', $this->hc->edit($id)->getData(true)['name']);

        // self-update with same code/name passes
        $r = $this->hc->update(new Request(['codeid' => $id, 'account_type_id' => 9, 'code' => '992', 'name' => 'Upd Head', 'client_credential_id' => $this->credA]))->getData(true);
        $this->assertEquals(300, $r['status']);
        // colliding with another head's code blocked
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '993', 'name' => 'Other Head', 'client_credential_id' => $this->credA]));
        $r = $this->hc->update(new Request(['codeid' => $id, 'account_type_id' => 9, 'code' => '993', 'name' => 'Upd Head', 'client_credential_id' => $this->credA]))->getData(true);
        $this->assertEquals(303, $r['status']);

        $this->hc->toggleStatus($id);
        $this->assertEquals(0, DB::table('account_heads')->where('id', $id)->value('is_active'));
        $this->assertTrue($this->hc->delete($id)->getData(true)['success']);
        $this->assertNull(DB::table('account_heads')->where('id', $id)->first());
    }

    // ---------------- D. P&L ----------------

    public function test_pl_empty_window_returns_zeroed_excel_shape(): void
    {
        $pl = $this->ac->profitLossData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $e = $pl['excel'];
        $this->assertCount(7, $e['turnover_heads']);
        $this->assertCount(2, $e['other_income_heads']);
        $this->assertCount(4, $e['direct_heads']);
        $this->assertCount(29, $e['admin_heads']);
        $this->assertEquals(0, $e['total_turnover_A']['total']);
        $this->assertEquals(0, $e['operating_profit_E']['total']);
        $this->assertEquals('101', $e['turnover_heads'][0]['code']);
        $this->assertNotEmpty($e['refs']['A']);
        $this->assertNotEmpty($e['notes']);
        $this->assertArrayHasKey('net_profit', $pl);
    }

    public function test_pl_values_and_payment_method_filter(): void
    {
        $this->pair('451', '101', 1000);                       // sale, bank
        $this->pair('201', '451', 300);                        // direct cost, bank
        $this->pair('321', '452', 100, $this->clientA, '2020-06-15', 'cash'); // rent, cash
        $base = ['from' => '2020-06-01', 'to' => '2020-06-30'];

        $e = $this->ac->profitLossData(new Request($base))->getData(true)['excel'];
        $this->assertEqualsWithDelta(1000, $e['total_turnover_A']['total'], 0.01);
        $this->assertEqualsWithDelta(1000, $e['total_turnover_A']['bank'], 0.01);
        $this->assertEqualsWithDelta(300, $e['total_cost_B_excel']['total'], 0.01);
        $this->assertEqualsWithDelta(100, $e['total_admin_D']['total'], 0.01);
        $this->assertEqualsWithDelta(100, $e['total_admin_D']['cash'], 0.01);
        $this->assertEqualsWithDelta(600, $e['operating_profit_E']['total'], 0.01);

        $cash = $this->ac->profitLossData(new Request($base + ['payment_method' => 'cash']))->getData(true)['excel'];
        $this->assertEqualsWithDelta(0, $cash['total_turnover_A']['total'], 0.01);
        $this->assertEqualsWithDelta(100, $cash['total_admin_D']['total'], 0.01);
    }

    public function test_pl_excludes_cancelled_receipts(): void
    {
        $r = $this->pair('451', '101', 5000);
        DB::table('receipts')->where('id', $r)->update(['status' => 'cancelled']);
        $e = $this->ac->profitLossData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true)['excel'];
        $this->assertEqualsWithDelta(0, $e['total_turnover_A']['total'], 0.01);
    }

    public function test_only_completed_receipts_feed_reports(): void
    {
        // ready + archived count; pending + to_review + cancelled do not.
        $this->pair('451', '101', 100, $this->clientA, '2020-06-15', 'bank', 'ready');
        $this->pair('451', '101', 200, $this->clientA, '2020-06-15', 'bank', 'archived');
        $this->pair('451', '101', 400, $this->clientA, '2020-06-15', 'bank', 'pending');
        $this->pair('451', '101', 800, $this->clientA, '2020-06-15', 'bank', 'to_review');
        $this->pair('451', '101', 1600, $this->clientA, '2020-06-15', 'bank', 'cancelled');
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30'];

        $e = $this->ac->profitLossData(new Request($w))->getData(true)['excel'];
        $this->assertEqualsWithDelta(300, $e['total_turnover_A']['total'], 0.01);

        $tb = $this->ac->trialBalanceData(new Request($w))->getData(true);
        $this->assertEqualsWithDelta(300, $tb['total_credit'], 0.01);
        $this->assertEqualsWithDelta(300, $tb['total_debit'], 0.01);
        $this->assertTrue($tb['balanced']);

        $bs = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true);
        $this->assertEqualsWithDelta(300, $bs['total_assets'], 0.01);
        $this->assertEqualsWithDelta($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'], 0.01);

        $d = $this->ac->headTransactions(new Request(['code' => '101'] + $w))->getData(true);
        $this->assertEquals(2, $d['count']);
        $this->assertEqualsWithDelta(300, $d['total'], 0.01);
    }

    public function test_client_head_shows_as_zero_row_in_its_report(): void
    {
        // Client-specific head with no postings: shows with its label and zeros
        // in that credential's report (all heads come, 0 if unused).
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '997', 'name' => 'Cred A Marketing', 'client_credential_id' => $this->credA]));
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30', 'client_credential_id' => $this->credA];
        $e = $this->ac->profitLossData(new Request($w))->getData(true)['excel'];
        $found = array_values(array_filter($e['admin_heads'], fn($h) => $h['code'] === '997'));
        $this->assertCount(1, $found);
        $this->assertEquals('Cred A Marketing', $found[0]['head_name']);
        $this->assertEqualsWithDelta(0, $found[0]['total'], 0.01);

        // ...and carries the amount once posted to from that credential's receipt.
        $r = $this->receipt($this->clientA, '2020-06-15');
        $copyId = DB::table('account_heads')->where('code', '997')->value('id');
        DB::table('transactions')->insert([
            'transaction_uid' => $this->uid('T997'), 'receipt_id' => $r,
            'account_head_id' => $copyId, 'type' => 'payable',
            'amount' => 65, 'total_amount' => 65, 'parent_id' => null,
            'created_by' => $this->userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $e2 = $this->ac->profitLossData(new Request($w))->getData(true)['excel'];
        $found2 = array_values(array_filter($e2['admin_heads'], fn($h) => $h['code'] === '997'));
        $this->assertEqualsWithDelta(65, $found2[0]['total'], 0.01);
        $this->assertEqualsWithDelta(65, $e2['total_admin_D']['total'], 0.01);
    }

    public function test_pl_business_and_credential_scoping(): void
    {
        $this->pair('451', '101', 700, $this->clientB);
        $argsA = ['from' => '2020-06-01', 'to' => '2020-06-30', 'client_id' => $this->clientA];
        $argsB = ['from' => '2020-06-01', 'to' => '2020-06-30', 'client_id' => $this->clientB];
        $this->assertEqualsWithDelta(0, $this->ac->profitLossData(new Request($argsA))->getData(true)['excel']['total_turnover_A']['total'], 0.01);
        $this->assertEqualsWithDelta(700, $this->ac->profitLossData(new Request($argsB))->getData(true)['excel']['total_turnover_A']['total'], 0.01);
        $credB = $this->ac->profitLossData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30', 'client_credential_id' => $this->credB]))->getData(true);
        $this->assertEqualsWithDelta(700, $credB['excel']['total_turnover_A']['total'], 0.01);
        $this->assertStringContainsString('All Businesses', $this->ac->profitLossData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true)['business_name']);
    }

    public function test_child_legs_excluded_from_all_reports(): void
    {
        $r = $this->receipt($this->clientA, '2020-06-15');
        $parent = $this->leg($r, '301', 'paid', 250); // parent leg: non-reportable type
        $this->leg($r, '301', 'payable', 250, $parent); // child leg: reportable type but has parent
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30'];
        $this->assertEqualsWithDelta(0, $this->ac->profitLossData(new Request($w))->getData(true)['excel']['total_admin_D']['total'], 0.01);
        $tb = $this->ac->trialBalanceData(new Request($w))->getData(true);
        $this->assertEqualsWithDelta(0, $tb['total_debit'], 0.01);
        $bs = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true);
        $this->assertEqualsWithDelta(0, $bs['total_assets'], 0.01);
        $d = $this->ac->headTransactions(new Request(['code' => '301'] + $w))->getData(true);
        $this->assertEquals(0, $d['count'], 'drill-down must agree with the report row');
    }

    // ---------------- E. Balance sheet ----------------

    public function test_retired_heads_keep_history_visible(): void
    {
        // Post a balanced pair touching the head, then retire it: its balance
        // must still appear (marked inactive) and sections must still tie.
        $this->pair('301', '451', 250);
        $headId = $this->headIds['301'];
        DB::table('account_heads')->where('id', $headId)->update(['is_active' => 0]);
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30'];
        $e = $this->ac->profitLossData(new Request($w))->getData(true)['excel'];
        $found = array_values(array_filter($e['admin_heads'], fn($h) => $h['head_id'] === $headId));
        $this->assertCount(1, $found);
        $this->assertEqualsWithDelta(250, $found[0]['total'], 0.01);
        $this->assertStringContainsString('(inactive)', $found[0]['head_name']);
        $this->assertEqualsWithDelta(250, $e['total_admin_D']['total'], 0.01);

        $bs = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true);
        $this->assertEqualsWithDelta($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'], 0.01);
    }

    public function test_bs_as_of_cutoff(): void
    {
        $this->pair('402', '451', 2000, $this->clientA, '2020-06-15');
        $early = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-01']))->getData(true)['excel'];
        $this->assertEqualsWithDelta(0, $early['total_fixed_A'], 0.01);
        $late = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true)['excel'];
        $this->assertEqualsWithDelta(2000, $late['total_fixed_A'], 0.01);
    }

    public function test_bs_dynamic_sections_and_legacy_sections(): void
    {
        $this->pair('452', '603', 5000);
        $this->pair('402', '451', 2000);
        $this->pair('455', '501', 800);
        $bs = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true);

        // Straight sums over live heads: A=2000, B=-2000+5000+0+0+800=3800,
        // C=800, net=3000, A+net=5000, D=0, net assets=5000, capital=5000.
        $e = $bs['excel'];
        $this->assertEqualsWithDelta(2000, $e['total_fixed_A'], 0.01);
        $this->assertEqualsWithDelta(3800, $e['total_current_B_excel'], 0.01);
        $this->assertEqualsWithDelta(800, $e['total_current_C'], 0.01);
        $this->assertEqualsWithDelta(3000, $e['net_current_BC'], 0.01);
        $this->assertEqualsWithDelta(5000, $e['total_assets_less_current_excel'], 0.01);
        $this->assertEqualsWithDelta(5000, $e['net_assets_excel'], 0.01);
        $this->assertEqualsWithDelta(5000, $e['total_capital_excel'], 0.01);
        $this->assertEqualsWithDelta($e['net_assets_excel'], $e['total_capital_excel'], 0.01);
        $this->assertNotEmpty($e['notes']);
        $this->assertNotEmpty($e['section_titles']['capital']);
        // every row carries a real head label from the heads table
        foreach (array_merge($e['fixed_heads'], $e['current_heads'], $e['capital_heads']) as $row) {
            $this->assertNotEmpty($row['head_id']);
            $this->assertStringContainsString(' - ', $row['name']);
        }

        foreach (['assets', 'liabilities', 'equity', 'net_profit', 'total_assets', 'total_liabilities', 'total_equity', 'total_liab_equity'] as $k) {
            $this->assertArrayHasKey($k, $bs, "BS missing key $k");
        }
        $this->assertEqualsWithDelta($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'], 0.01);
    }

    public function test_bs_business_scoping(): void
    {
        $this->pair('402', '451', 2000, $this->clientB);
        $a = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30', 'client_id' => $this->clientA]))->getData(true)['excel'];
        $b = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30', 'client_id' => $this->clientB]))->getData(true)['excel'];
        $this->assertEqualsWithDelta(0, $a['total_fixed_A'], 0.01);
        $this->assertEqualsWithDelta(2000, $b['total_fixed_A'], 0.01);
    }

    // ---------------- F. Trial balance ----------------

    public function test_tb_sorted_rows_and_side_rules(): void
    {
        $this->pair('451', '101', 1000);
        $this->pair('201', '451', 300);
        $tb = $this->ac->trialBalanceData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $codes = array_column($tb['rows'], 'code');
        $sorted = $codes;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $codes);
        $byCode = [];
        foreach ($tb['rows'] as $row) $byCode[$row['code']] = $row;
        $this->assertEqualsWithDelta(1000, $byCode['101']['credit'], 0.01); // receivable -> credit
        $this->assertEqualsWithDelta(300, $byCode['201']['debit'], 0.01);   // payable -> debit
    }

    public function test_tb_balanced_flag_reflects_reality(): void
    {
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30'];
        $empty = $this->ac->trialBalanceData(new Request($w))->getData(true);
        $this->assertSame([], $empty['rows']);
        $this->assertTrue($empty['balanced']);

        $r = $this->receipt($this->clientA, '2020-06-15');
        $this->leg($r, '101', 'receivable', 500); // single-sided: credit only
        $single = $this->ac->trialBalanceData(new Request($w))->getData(true);
        $this->assertFalse($single['balanced']);
        $this->assertEqualsWithDelta(500, $single['total_credit'], 0.01);
        $this->assertEqualsWithDelta(0, $single['total_debit'], 0.01);
    }

    // ---------------- G. Drill-down ----------------

    public function test_drill_isolates_duplicate_names_by_head_id(): void
    {
        $this->pair('509', '451', 111); // Director Loan Account (current liab)
        $this->pair('554', '451', 222); // Director Loan Account (non-current)
        $h509 = $this->headIds['509'];
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30'];
        $d509 = $this->ac->headTransactions(new Request(['head_id' => $h509] + $w))->getData(true);
        $this->assertEquals(1, $d509['count']);
        $this->assertEqualsWithDelta(111, $d509['total'], 0.01);
        // code lookup spans every head sharing the code (none duplicated by code here)
        $dcode = $this->ac->headTransactions(new Request(['code' => '509'] + $w))->getData(true);
        $this->assertEquals(1, $dcode['count']);
    }

    public function test_drill_bs_mode_uses_as_of(): void
    {
        $this->pair('402', '451', 2000, $this->clientA, '2020-06-15');
        $before = $this->ac->headTransactions(new Request(['mode' => 'bs', 'code' => '402', 'as_of' => '2020-06-01']))->getData(true);
        $after = $this->ac->headTransactions(new Request(['mode' => 'bs', 'code' => '402', 'as_of' => '2020-06-30']))->getData(true);
        $this->assertEquals(0, $before['count']);
        $this->assertEquals(1, $after['count']);
        $pl = $this->ac->headTransactions(new Request(['code' => '402', 'from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $this->assertEquals(1, $pl['count']);
        // BS mode ignores child legs
        $r = $this->receipt($this->clientA, '2020-06-15');
        $parent = $this->leg($r, '402', 'paid', 50);
        $this->leg($r, '402', 'payable', 50, $parent);
        $after2 = $this->ac->headTransactions(new Request(['mode' => 'bs', 'code' => '402', 'as_of' => '2020-06-30']))->getData(true);
        $this->assertEquals(1, $after2['count']);
    }

    public function test_drill_filters_and_errors(): void
    {
        $this->pair('451', '101', 400, $this->clientB, '2020-06-15', 'cash');
        $biz = $this->ac->headTransactions(new Request(['code' => '101', 'client_id' => $this->clientA, 'from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $this->assertEquals(0, $biz['count']);
        $cash = $this->ac->headTransactions(new Request(['code' => '101', 'payment_method' => 'cash', 'from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $this->assertEquals(1, $cash['count']);
        $bank = $this->ac->headTransactions(new Request(['code' => '101', 'payment_method' => 'bank', 'from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $this->assertEquals(0, $bank['count']);
        $this->assertEquals(422, $this->ac->headTransactions(new Request([]))->getStatusCode());
        $this->assertEquals(404, $this->ac->headTransactions(new Request(['head_id' => 999999999]))->getStatusCode());
    }

    public function test_breakdown_matches_row_and_links_bill(): void
    {
        // Mixed sides on one head: BS breakdown total must equal the signed
        // row balance (300), while P&L breakdown stays a plain sum (700).
        $r = $this->receipt($this->clientA, '2020-06-15', 'bank', 'ready');
        $this->leg($r, '451', 'payable', 500);
        $r2 = $this->receipt($this->clientA, '2020-06-16', 'cash', 'ready');
        $this->leg($r2, '451', 'receivable', 200);
        $w = ['from' => '2020-06-01', 'to' => '2020-06-30'];

        $bs = $this->ac->headTransactions(new Request(['mode' => 'bs', 'code' => '451', 'as_of' => '2020-06-30']))->getData(true);
        $this->assertEquals(2, $bs['count']);
        $this->assertEqualsWithDelta(300, $bs['total'], 0.01);
        $report = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true)['excel'];
        // current_heads may hold several rows; find 451 specifically
        $bal451 = null;
        foreach ($report['current_heads'] as $h) {
            if ($h['code'] === '451') $bal451 = $h['balance'];
        }
        $this->assertEqualsWithDelta(300, $bal451, 0.01);
        $this->assertEqualsWithDelta($bal451, $bs['total'], 0.01, 'BS breakdown total must equal the row balance');

        $pl = $this->ac->headTransactions(new Request(['code' => '451'] + $w))->getData(true);
        $this->assertEqualsWithDelta(700, $pl['total'], 0.01);

        // every breakdown row carries display fields incl. the printable bill link
        foreach ($bs['rows'] as $row) {
            $this->assertArrayHasKey('signed_amount', $row);
            $this->assertArrayHasKey('payment_method', $row);
            $this->assertArrayHasKey('type', $row);
            $this->assertStringContainsString('/bill', $row['bill_url']);
            $this->assertStringContainsString((string) $row['receipt_id'], $row['bill_url']);
        }
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.receipt.bill'));
    }

    public function test_drill_caps_at_200_rows(): void
    {
        $r = $this->receipt($this->clientA, '2020-06-15');
        for ($i = 0; $i < 205; $i++) $this->leg($r, '301', 'payable', 10);
        $d = $this->ac->headTransactions(new Request(['code' => '301', 'from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);
        $this->assertEquals(200, $d['count']);
        $this->assertEqualsWithDelta(2000, $d['total'], 0.01);
        $this->assertArrayHasKey('receipt_number', $d['rows'][0]);
    }

    // ---------------- H. Exports ----------------

    public function test_pl_csv_export(): void
    {
        $this->pair('451', '101', 1000);
        $this->pair('107', '451', 50);
        $this->pair('201', '451', 300);
        $csv = $this->capture($this->ac->profitLossExport(new Request(['format' => 'csv', 'from' => '2020-06-01', 'to' => '2020-06-30'])));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'CSV needs BOM for Excel');
        $this->assertMatchesRegularExpression('/"OPERATING PROFIT \(E = C-D\)",0,750,0,750/', $csv);
        $this->assertStringContainsString('"107 - Sales Refund"', $csv);
        $this->assertStringContainsString('"Total Cost of Sales (B)",0,300,0,300', $csv);
    }

    public function test_pl_xlsx_export(): void
    {
        $this->pair('451', '101', 1000);
        $bin = $this->capture($this->ac->profitLossExport(new Request(['format' => 'xlsx', 'from' => '2020-06-01', 'to' => '2020-06-30'])));
        $this->assertSame('PK', substr($bin, 0, 2));
        $this->assertGreaterThan(4000, strlen($bin));
        $tmp = tempnam(sys_get_temp_dir(), 'plx') . '.xlsx';
        file_put_contents($tmp, $bin);
        $wb = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        $this->assertSame('P&L', $wb->getActiveSheet()->getTitle());
        $found = false;
        foreach ($wb->getActiveSheet()->toArray() as $row) {
            if (in_array('OPERATING PROFIT (E = C-D)', $row, true)) { $found = true; break; }
        }
        $this->assertTrue($found, 'XLSX must contain operating profit row');
        unlink($tmp);
    }

    public function test_bs_csv_and_xlsx_exports(): void
    {
        $this->pair('452', '603', 5000);
        $this->pair('402', '451', 2000);
        $csv = $this->capture($this->ac->balanceSheetExport(new Request(['format' => 'csv', 'as_of' => '2020-06-30'])));
        $this->assertStringContainsString('"Total Fixed Assets (A)",2000', $csv);
        $this->assertStringContainsString('"Total Capital and Reserves",5000', $csv);
        $bin = $this->capture($this->ac->balanceSheetExport(new Request(['format' => 'xlsx', 'as_of' => '2020-06-30'])));
        $this->assertSame('PK', substr($bin, 0, 2));
        $this->assertGreaterThan(4000, strlen($bin));
    }

    public function test_unknown_export_format_defaults_to_csv(): void
    {
        $out = $this->capture($this->ac->profitLossExport(new Request(['format' => 'pdf', 'from' => '2020-06-01', 'to' => '2020-06-30'])));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $out);
        $out2 = $this->capture($this->ac->balanceSheetExport(new Request(['format' => 'pdf', 'as_of' => '2020-06-30'])));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $out2);
    }

    public function test_exports_respect_date_filters(): void
    {
        $this->pair('451', '101', 1000, $this->clientA, '2020-06-15');
        $csv = $this->capture($this->ac->profitLossExport(new Request(['format' => 'csv', 'from' => '2021-01-01', 'to' => '2021-12-31'])));
        $this->assertMatchesRegularExpression('/"OPERATING PROFIT \(E = C-D\)",0,0,0,0/', $csv);
        $bs = $this->capture($this->ac->balanceSheetExport(new Request(['format' => 'csv', 'as_of' => '2020-01-01'])));
        $this->assertStringContainsString('"Total Fixed Assets (A)",0', $bs);
    }

    // ---------------- I. Receipt integration ----------------

    public function test_receipt_head_dropdown_scoping(): void
    {
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '994', 'name' => 'Cred A Extra', 'client_credential_id' => $this->credA]));
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '995', 'name' => 'Cred B Extra', 'client_credential_id' => $this->credB]));

        $rows = collect($this->rc->getAccountHeads(new Request(['account_type_id' => 9, 'client_credential_id' => $this->credA]))->getData(true));
        $this->assertTrue($rows->contains('name', 'Rent'));
        $this->assertTrue($rows->contains('name', 'Cred A Extra'));
        $this->assertFalse($rows->contains('name', 'Cred B Extra'));

        $codes = $rows->pluck('code')->all();
        $sorted = $codes;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, array_values($codes));
    }

    public function test_get_businesses_lists_credential_clients(): void
    {
        $list = $this->ac->getBusinesses(new Request(['credential_id' => $this->credA]))->getData(true);
        $this->assertNotEmpty($list);
        $ids = array_column($list, 'id');
        $this->assertContains($this->clientA, $ids);
        $this->assertArrayHasKey('text', $list[0]);
    }
}
