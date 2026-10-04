<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Accounting-identity tests for the Excel-aligned reports.
// Every fixture below is double-entry balanced (each debit has an equal
// credit), so a correct report MUST satisfy:
//   Trial Balance: total debits == total credits
//   P&L:           net profit == income - expenses
//   Balance Sheet: total assets == total liabilities + total equity
// The Excel-replica figures intentionally do NOT balance (they preserve the
// source workbook's formula quirks); that mismatch is asserted as documented.
class AccountingBalanceTest extends TestCase
{
    protected AccountingController $c;
    protected int $userId;
    protected int $clientId;
    protected array $headIds; // code => id

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->c = new AccountingController();
        $this->userId = DB::table('users')->first()->id;
        $this->clientId = DB::table('clients')->first()->id;
        $this->headIds = DB::table('account_heads')
            ->whereNull('client_credential_id')
            ->pluck('id', 'code')
            ->all();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // Post one balanced double-entry event: debit head (payable) + credit head (receivable).
    // Dated June 2020 (verified empty of live data) to isolate fixtures.
    protected function postBalanced(string $debitCode, string $creditCode, float $amount, string $date = '2020-06-15'): void
    {
        static $n = 0;
        $n++;
        $receiptId = DB::table('receipts')->insertGetId([
            'client_id' => $this->clientId,
            'receipt_number' => 'TEST-BAL-' . $n . '-' . time(),
            'receipt_date' => $date,
            'status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('receipt_details')->insert([
            'receipt_id' => $receiptId,
            'account_head_id' => $this->headIds[$debitCode],
            'invoice_date' => $date,
            'total_amount' => $amount,
            'paid' => false,
            'payment_method' => 'bank',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([[$debitCode, 'payable'], [$creditCode, 'receivable']] as [$code, $type]) {
            DB::table('transactions')->insert([
                'transaction_uid' => 'TESTBAL' . $n . $code . time() . rand(1000, 9999),
                'receipt_id' => $receiptId,
                'account_head_id' => $this->headIds[$code],
                'type' => $type,
                'amount' => $amount,
                'total_amount' => $amount,
                'parent_id' => null,
                'created_by' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // Pure balance-sheet fixture (no revenue/expense heads).
    protected function seedPureBalanceSheet(): void
    {
        $this->postBalanced('452', '603', 5000); // cash introduced as owners fund
        $this->postBalanced('402', '451', 2000); // equipment bought via bank
        $this->postBalanced('455', '501', 800);  // inventory bought on credit
        $this->postBalanced('451', '551', 3000); // bank loan received
        $this->postBalanced('604', '452', 200);  // owner drawing paid from cash
        $this->postBalanced('551', '451', 500);  // loan repaid
    }

    // Trading fixture (revenue + expenses mixed in, still balanced).
    protected function seedTrading(): void
    {
        $this->postBalanced('451', '101', 1000); // sale received to bank
        $this->postBalanced('201', '451', 300);  // goods purchased
        $this->postBalanced('321', '452', 100);  // rent paid from cash
    }

    public function test_trial_balance_debits_equal_credits(): void
    {
        $this->seedPureBalanceSheet();
        $this->seedTrading();

        $tb = $this->c->trialBalanceData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);

        // 11500 (pure BS pairs) + 1400 (trading pairs) each side
        $this->assertEqualsWithDelta(12900, $tb['total_debit'], 0.01);
        $this->assertEqualsWithDelta(12900, $tb['total_credit'], 0.01);
        $this->assertTrue($tb['balanced'], 'Trial balance must balance: debits == credits');
    }

    public function test_profit_and_loss_nets_correctly(): void
    {
        $this->seedTrading();

        $pl = $this->c->profitLossData(new Request(['from' => '2020-06-01', 'to' => '2020-06-30']))->getData(true);

        // Legacy accounting-correct section: income 1000 - expenses 400 = 600
        $this->assertEqualsWithDelta(1000, $pl['total_income']['total'], 0.01);
        $this->assertEqualsWithDelta(400, $pl['total_expense']['total'], 0.01);
        $this->assertEqualsWithDelta(
            $pl['total_income']['total'] - $pl['total_expense']['total'],
            $pl['net_profit']['total'],
            0.01,
            'P&L net profit must equal income minus expenses'
        );
        // Excel replica formula chain: E = C - D
        $e = $pl['excel'];
        $this->assertEqualsWithDelta(1000, $e['total_turnover_A']['total'], 0.01);
        $this->assertEqualsWithDelta(700, $e['gross_profit_C']['total'], 0.01);
        $this->assertEqualsWithDelta(
            $e['gross_profit_C']['total'] - $e['total_admin_D']['total'],
            $e['operating_profit_E']['total'],
            0.01,
            'Excel replica must hold E = C - D'
        );
    }

    public function test_balance_sheet_left_equals_right(): void
    {
        $this->seedPureBalanceSheet();
        $this->seedTrading();

        $bs = $this->c->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true);

        // Pure-BS net assets 4800 + trading profit 600 retained = 8700 each side
        $this->assertEqualsWithDelta(8700, $bs['total_assets'], 0.01);
        $this->assertEqualsWithDelta(
            $bs['total_assets'],
            $bs['total_liabilities'] + $bs['total_equity'],
            0.01,
            'Balance sheet must balance: assets == liabilities + equity'
        );
    }

    public function test_balance_sheet_correct_reference_values_balance(): void
    {
        $this->seedPureBalanceSheet();

        $e = $this->c->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true)['excel'];

        // Count every current-asset head exactly once (undoes the B25/B26 double-count)
        $currentTrue = array_sum(array_column($e['current_heads'], 'balance'))
            + $e['inventory_head_455']['balance'];
        $this->assertEqualsWithDelta(6100, $currentTrue, 0.01);

        // Correct accounting identity: A + B_true - C - D == full capital (601-604)
        $netAssetsCorrect = $e['total_fixed_A'] + $currentTrue - $e['total_current_C'] - $e['total_noncurrent_D'];
        $this->assertEqualsWithDelta(4800, $netAssetsCorrect, 0.01);
        $this->assertEqualsWithDelta(
            $netAssetsCorrect,
            $e['total_capital_all'],
            0.01,
            'Correct reference values must balance: A + B - C - D == capital'
        );
    }

    public function test_excel_replica_mismatch_is_documented_not_hidden(): void
    {
        $this->seedPureBalanceSheet();

        $e = $this->c->balanceSheetData(new Request(['as_of' => '2020-06-30']))->getData(true)['excel'];

        // The replica preserves the workbook's quirks, so it must NOT balance here:
        // net assets 7300 (doubled B, dropped A) vs capital 0 (603/604 excluded).
        $this->assertEqualsWithDelta(7300, $e['net_assets_excel'], 0.01);
        $this->assertEqualsWithDelta(0, $e['total_capital_excel'], 0.01);
        $this->assertNotEqualsWithDelta(
            $e['net_assets_excel'],
            $e['total_capital_excel'],
            0.01,
            'Excel replica mismatch must stay visible (source-formula quirks), with correct values alongside'
        );
        $this->assertNotEmpty($e['notes'], 'Replica quirks must be footnoted on the report');
    }
}
