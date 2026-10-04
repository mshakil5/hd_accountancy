<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountHeadController;
use App\Http\Controllers\Admin\AccountingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Very deep testing: randomized fuzz rounds over P&L/BS/TB invariants,
// drill-vs-report agreement on EVERY row, exports mirroring JSON,
// date boundaries, method fallback, scope spanning, validation edges,
// repeated-query stability, and a 500-transaction volume run.
// Fixed RNG seed => reproducible. All fixtures roll back.
class AccountingDeepTest extends TestCase
{
    protected AccountingController $ac;
    protected AccountHeadController $hc;
    protected int $userId;
    protected int $clientId;
    protected array $headIds;
    protected static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->ac = new AccountingController();
        $this->hc = new AccountHeadController();
        $this->userId = DB::table('users')->first()->id;
        $this->clientId = DB::table('clients')->first()->id;
        $this->headIds = DB::table('account_heads')
            ->whereNull('client_credential_id')
            ->pluck('id', 'code')->all();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    protected function receipt(string $method, string $date): int
    {
        $id = DB::table('receipts')->insertGetId([
            'client_id' => $this->clientId, 'receipt_number' => 'TEST-DEEP-' . (++self::$seq) . time() . rand(10, 99),
            'receipt_date' => $date, 'status' => 'ready',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('receipt_details')->insert([
            'receipt_id' => $id, 'account_head_id' => $this->headIds['101'],
            'invoice_date' => $date, 'total_amount' => 1, 'paid' => false,
            'payment_method' => $method, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    protected function leg(int $r, string $code, string $type, float $amt, ?int $headId = null): void
    {
        DB::table('transactions')->insert([
            'transaction_uid' => 'TESTDEEP' . (++self::$seq) . time() . rand(100, 999),
            'receipt_id' => $r, 'account_head_id' => $headId ?? $this->headIds[$code],
            'type' => $type, 'amount' => $amt, 'total_amount' => $amt,
            'parent_id' => null, 'created_by' => $this->userId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function W(): array
    {
        return ['from' => '2020-06-01', 'to' => '2020-06-30'];
    }

    // ---- 1. Fuzz: 25 randomized rounds, invariants must hold every round ----
    public function test_invariants_hold_on_random_fixtures(): void
    {
        mt_srand(20261004);
        $codes = array_keys($this->headIds);
        $methods = ['cash', 'bank', 'card'];
        for ($round = 1; $round <= 25; $round++) {
            // 3-7 balanced pairs + 0-2 unbalanced singles, random dates/methods
            $n = mt_rand(3, 7);
            for ($i = 0; $i < $n; $i++) {
                $d = $codes[mt_rand(0, count($codes) - 1)];
                $cc = $codes[mt_rand(0, count($codes) - 1)];
                $amt = mt_rand(1, 5000) / 100;
                $day = str_pad((string) mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
                $r = $this->receipt($methods[mt_rand(0, 2)], "2020-06-$day");
                $this->leg($r, $d, 'payable', $amt);
                $this->leg($r, $cc, 'receivable', $amt);
            }
            for ($s = 0, $sn = mt_rand(0, 2); $s < $sn; $s++) {
                $c = $codes[mt_rand(0, count($codes) - 1)];
                $r = $this->receipt('bank', '2020-06-15');
                $this->leg($r, $c, mt_rand(0, 1) ? 'payable' : 'receivable', mt_rand(1, 999) / 100);
            }

            $pl = $this->ac->profitLossData(new Request($this->W()))->getData(true);
            $e = $pl['excel'];
            $sumRows = fn($rows) => array_sum(array_column($rows, 'total'));
            $this->assertEqualsWithDelta($sumRows(array_merge($e['turnover_heads'], $e['other_income_heads'])), $e['total_turnover_A']['total'], 0.01, "round $round: A");
            $b201 = array_values(array_filter($e['direct_heads'], fn($h) => $h['code'] === '201'))[0]['total'];
            $this->assertEqualsWithDelta($b201, $e['total_cost_B_excel']['total'], 0.01, "round $round: B=201");
            $this->assertEqualsWithDelta($e['total_turnover_A']['total'] - $e['total_cost_B_excel']['total'], $e['gross_profit_C']['total'], 0.01, "round $round: C");
            $this->assertEqualsWithDelta($sumRows($e['admin_heads']), $e['total_admin_D']['total'], 0.01, "round $round: D");
            $this->assertEqualsWithDelta($e['gross_profit_C']['total'] - $e['total_admin_D']['total'], $e['operating_profit_E']['total'], 0.01, "round $round: E");
            foreach (array_merge($e['turnover_heads'], $e['other_income_heads'], $e['direct_heads'], $e['admin_heads']) as $h) {
                $this->assertEqualsWithDelta($h['cash'] + $h['bank'] + $h['card'], $h['total'], 0.01, "round $round: methods sum row {$h['code']}");
            }
            $this->assertEqualsWithDelta($pl['total_income']['total'] - $pl['total_expense']['total'], $pl['net_profit']['total'], 0.01, "round $round: legacy net");

            $x = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true)['excel'];
            $inv = array_sum(array_column($x['current_heads'], 'balance'));
            $this->assertEqualsWithDelta($inv, $x['inventory_row_display'], 0.01, "round $round: B25");
            $this->assertEqualsWithDelta($inv + $inv, $x['total_current_B_excel'], 0.01, "round $round: doubled B");
            $this->assertEqualsWithDelta($x['total_current_B_excel'] - $x['total_current_C'], $x['net_current_BC'], 0.01, "round $round: B-C");
            $this->assertEqualsWithDelta(0 + $x['net_current_BC'], $x['total_assets_less_current_excel'], 0.01, "round $round: B17=0");
            $this->assertEqualsWithDelta($x['total_assets_less_current_excel'] - $x['total_noncurrent_D'], $x['net_assets_excel'], 0.01, "round $round: net assets");
            $caps = [];
            foreach ($x['capital_heads'] as $h) $caps[$h['code']] = $h['balance'];
            $this->assertEqualsWithDelta(($caps['601'] ?? 0) + ($caps['602'] ?? 0), $x['total_capital_excel'], 0.01, "round $round: cap 61+62");

            $tb = $this->ac->trialBalanceData(new Request($this->W()))->getData(true);
            $this->assertEqualsWithDelta(array_sum(array_column($tb['rows'], 'debit')), $tb['total_debit'], 0.01, "round $round: TB debits");
            $this->assertEqualsWithDelta(array_sum(array_column($tb['rows'], 'credit')), $tb['total_credit'], 0.01, "round $round: TB credits");
            $this->assertSame(abs($tb['total_debit'] - $tb['total_credit']) < 0.01, $tb['balanced'], "round $round: TB flag");
        }
        $this->assertTrue(true); // 25 rounds x ~20 assertions survived
    }

    // ---- 2. Drill-down agrees with EVERY P&L row ----
    public function test_drill_matches_every_pl_row(): void
    {
        $put = function ($code, $side, $amt) {
            $r = $this->receipt('bank', '2020-06-15');
            $this->leg($r, $code, $side, $amt);
            $r2 = $this->receipt('bank', '2020-06-15');
            $this->leg($r2, '451', $side === 'payable' ? 'receivable' : 'payable', $amt);
        };
        $put('101', 'receivable', 100);
        $put('107', 'payable', 20);
        $put('109', 'receivable', 7);
        $put('201', 'payable', 30);
        $put('204', 'payable', 8);
        $put('301', 'payable', 11);
        $put('329', 'payable', 3);

        $e = $this->ac->profitLossData(new Request($this->W()))->getData(true)['excel'];
        $checked = 0;
        foreach (array_merge($e['turnover_heads'], $e['other_income_heads'], $e['direct_heads'], $e['admin_heads']) as $h) {
            $d = $this->ac->headTransactions(new Request(['code' => $h['code']] + $this->W()))->getData(true);
            $this->assertEqualsWithDelta($h['total'], $d['total'], 0.01, "drill(code {$h['code']}) == row");
            $checked++;
        }
        $this->assertSame(42, $checked, 'all 7+2+4+29 rows checked');
    }

    // ---- 3. Exports mirror the JSON report ----
    public function test_exports_mirror_report_json(): void
    {
        $r = $this->receipt('bank', '2020-06-15');
        $this->leg($r, '451', 'payable', 500);
        $r2 = $this->receipt('bank', '2020-06-15');
        $this->leg($r2, '101', 'receivable', 500);
        $r3 = $this->receipt('cash', '2020-06-15');
        $this->leg($r3, '301', 'payable', 80);

        $cap = function ($resp) {
            ob_start();
            $resp->getCallback()();
            return ob_get_clean();
        };
        $pl = $this->ac->profitLossData(new Request($this->W()))->getData(true)['excel'];
        $csv = $cap($this->ac->profitLossExport(new Request(['format' => 'csv'] + $this->W())));
        $this->assertStringContainsString('"Total Turnover (A)",0,500,0,500', $csv);
        $this->assertStringContainsString('"Total Administrative Costs (D)",80,0,0,80', $csv);
        $this->assertStringContainsString('"OPERATING PROFIT (E = C-D)",-80,500,0,420', $csv);
        $this->assertEqualsWithDelta(420, $pl['operating_profit_E']['total'], 0.01);

        $bs = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true)['excel'];
        $bcsv = $cap($this->ac->balanceSheetExport(new Request(['format' => 'csv', 'as_of' => '2020-06-30'])));
        $this->assertStringContainsString('"Total Fixed Assets (A)",0', $bcsv);
        $this->assertStringContainsString('"Total Current Asset (B, double-counts per Excel)",' . (int) $bs['total_current_B_excel'], $bcsv);

        // XLSX reload: values survive the round trip
        $bin = $cap($this->ac->profitLossExport(new Request(['format' => 'xlsx'] + $this->W())));
        $tmp = tempnam(sys_get_temp_dir(), 'dpx') . '.xlsx';
        file_put_contents($tmp, $bin);
        $vals = [];
        foreach (\PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet()->toArray() as $row) {
            if (isset($row[0]) && str_contains((string) $row[0], 'OPERATING PROFIT')) $vals = $row;
        }
        unlink($tmp);
        $this->assertSame(['OPERATING PROFIT (E = C-D)', -80, 500, 0, 420], [$vals[0], (int) $vals[1], (int) $vals[2], (int) $vals[3], (int) $vals[4]]);
    }

    // ---- 4. Date boundaries inclusive ----
    public function test_date_boundaries_inclusive(): void
    {
        $r = $this->receipt('bank', '2020-06-01');
        $this->leg($r, '101', 'receivable', 11);
        $r2 = $this->receipt('bank', '2020-06-30');
        $this->leg($r2, '101', 'receivable', 22);
        $e = $this->ac->profitLossData(new Request($this->W()))->getData(true)['excel'];
        $this->assertEqualsWithDelta(33, $e['total_turnover_A']['total'], 0.01, 'BETWEEN is inclusive both ends');
        $tb = $this->ac->trialBalanceData(new Request($this->W()))->getData(true);
        $this->assertEqualsWithDelta(33, $tb['total_credit'], 0.01);
        $onDay = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true);
        $dayBefore = $this->ac->balanceSheetData(new Request(['as_of' => '2020-06-29']))->getData(true);
        $this->assertGreaterThan($dayBefore['total_liabilities'], $onDay['total_liabilities'] + 21);
    }

    // ---- 5. NULL payment method falls back to cash bucket (ENUM blocks anything else) ----
    public function test_unknown_payment_method_falls_back_to_cash(): void
    {
        $r = $this->receipt('cash', '2020-06-15');
        DB::table('receipt_details')->where('receipt_id', $r)->update(['payment_method' => null]);
        $this->leg($r, '101', 'receivable', 90);
        $e = $this->ac->profitLossData(new Request($this->W()))->getData(true)['excel'];
        $this->assertEqualsWithDelta(90, $e['total_turnover_A']['cash'], 0.01);
        $this->assertEqualsWithDelta(90, $e['total_turnover_A']['total'], 0.01);
    }

    // ---- 6. Drill by code spans global + per-client same-code heads ----
    public function test_drill_by_code_spans_scopes(): void
    {
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '301', 'name' => 'Client 301 Copy', 'client_credential_id' => 1]));
        $copyId = DB::table('account_heads')->where('code', '301')->where('client_credential_id', 1)->value('id');
        $r = $this->receipt('bank', '2020-06-15');
        $this->leg($r, '301', 'payable', 50);
        $r2 = $this->receipt('bank', '2020-06-15');
        $this->leg($r2, '301', 'payable', 70, $copyId);
        $d = $this->ac->headTransactions(new Request(['code' => '301'] + $this->W()))->getData(true);
        $this->assertEquals(2, $d['count']);
        $this->assertEqualsWithDelta(120, $d['total'], 0.01);
        $this->assertCount(2, $d['heads']);
        $g = $this->ac->headTransactions(new Request(['head_id' => $this->headIds['301']] + $this->W()))->getData(true);
        $this->assertEquals(1, $g['count']);
    }

    // ---- 7. Validation edges ----
    public function test_validation_edges(): void
    {
        // global update to out-of-range code blocked
        $id = DB::table('account_heads')->where('code', '301')->whereNull('client_credential_id')->value('id');
        $r = $this->hc->update(new Request(['codeid' => $id, 'account_type_id' => 9, 'code' => '999', 'name' => 'Rent']))->getData(true);
        $this->assertEquals(303, $r['status']);
        // delete then re-create same code works
        $this->hc->store(new Request(['account_type_id' => 9, 'code' => '996', 'name' => 'Temp Head', 'client_credential_id' => 2]));
        $tid = DB::table('account_heads')->where('code', '996')->value('id');
        $this->hc->delete($tid);
        $r = $this->hc->store(new Request(['account_type_id' => 9, 'code' => '996', 'name' => 'Temp Head', 'client_credential_id' => 2]))->getData(true);
        $this->assertEquals(300, $r['status']);
        // unknown lookups return empty, not errors
        $this->assertSame([], $this->hc->byType(99999)->getData(true));
        $this->assertSame([], $this->ac->getBusinesses(new Request(['credential_id' => 999999]))->getData(true));
    }

    // ---- 8. Repeated queries are stable ----
    public function test_repeated_queries_stable(): void
    {
        $r = $this->receipt('bank', '2020-06-15');
        $this->leg($r, '101', 'receivable', 123);
        $a = json_encode($this->ac->profitLossData(new Request($this->W()))->getData(true));
        $b = json_encode($this->ac->profitLossData(new Request($this->W()))->getData(true));
        $this->assertSame($a, $b);
        $c = json_encode($this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true));
        $d = json_encode($this->ac->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true));
        $this->assertSame($c, $d);
    }

    // ---- 9. Volume: 500 legs, still correct and fast ----
    public function test_volume_500_legs(): void
    {
        $t0 = microtime(true);
        $r = $this->receipt('bank', '2020-06-15');
        for ($i = 0; $i < 250; $i++) {
            $this->leg($r, '101', 'receivable', 10);
            $this->leg($r, '301', 'payable', 4);
        }
        $e = $this->ac->profitLossData(new Request($this->W()))->getData(true)['excel'];
        $this->assertEqualsWithDelta(2500, $e['total_turnover_A']['total'], 0.01);
        $this->assertEqualsWithDelta(1000, $e['total_admin_D']['total'], 0.01);
        $this->assertEqualsWithDelta(1500, $e['operating_profit_E']['total'], 0.01);
        $d = $this->ac->headTransactions(new Request(['code' => '101'] + $this->W()))->getData(true);
        $this->assertEquals(200, $d['count'], 'drill cap holds at volume');
        $dt = microtime(true) - $t0;
        $this->assertLessThan(30, $dt, "500 legs incl. inserts+reports took {$dt}s");
        echo "\n    [volume] 500 legs in " . round($dt, 2) . "s";
    }
}
