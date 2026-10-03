<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use App\Models\AccountType;
use Illuminate\Database\Seeder;

/**
 * Seeds the Excel "Chart of account, P&L, BS.xlsx" chart as GLOBAL
 * (client_credential_id = NULL) so every client sees it, while per-client
 * custom heads (client_credential_id = X) keep working as overrides
 * via AccountHeadController@byType (global + credential-specific).
 *
 * Excel mapping (names trimmed, Dabit/Credit -> type normal_balance):
 *  Turnover (revenue/credit): 101-107
 *  Other Income (revenue/credit): 108-109
 *  Direct Expenses (expense/debit): 201-204
 *  Expenses (expense/debit): 301-329
 *  Fixed Assets (asset/debit): 401-407
 *  Current Assets (asset/debit): 451-455
 *  Current Liability (liability/credit): 501-513
 *  Non-current Liability (liability/credit): 551-554
 *  Capital and reserves (equity/credit): 601-604 (604 Owners Drawing is contra-equity, debit-natured)
 *
 * Replicates Excel exactly, including known quirks (documented, not fixed per request):
 *  - "Director Loan Account" appears twice (509 current + 554 non-current)
 *  - 107 Sales Refund is debit-natured inside revenue section, included in turnover SUM
 *  - 604 Owners Drawing is debit-natured inside credit equity section
 */
class ChartOfAccountsExcelSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Align the 9 existing types to Excel sub-category names (keep ids/categories).
        $types = [
            6 => ['name' => 'Turnover', 'category' => 'revenue', 'normal_balance' => 'credit'],
            7 => ['name' => 'Other Income', 'category' => 'revenue', 'normal_balance' => 'credit'],
            8 => ['name' => 'Direct Expenses', 'category' => 'expense', 'normal_balance' => 'debit'],
            9 => ['name' => 'Expenses', 'category' => 'expense', 'normal_balance' => 'debit'],
            2 => ['name' => 'Fixed Assets', 'category' => 'asset', 'normal_balance' => 'debit'],
            1 => ['name' => 'Current Assets', 'category' => 'asset', 'normal_balance' => 'debit'],
            3 => ['name' => 'Current Liability', 'category' => 'liability', 'normal_balance' => 'credit'],
            4 => ['name' => 'Non-current Liability', 'category' => 'liability', 'normal_balance' => 'credit'],
            5 => ['name' => 'Capital and reserves', 'category' => 'equity', 'normal_balance' => 'credit'],
        ];
        foreach ($types as $id => $attrs) {
            AccountType::updateOrCreate(['id' => $id], $attrs + ['is_active' => 1]);
        }

        // 2) Retire legacy global chart (1001-6010) so dropdowns/reports default to Excel chart.
        // History is preserved: transactions still join to these heads; byType() only returns is_active=1.
        AccountHead::whereNull('client_credential_id')
            ->whereIn('code', ['1001','1002','1003','1101','1102','2001','2002','2101','3001','3002','4001','4002','4101','5001','5002','6001','6002','6003','6004','6005','6006','6007','6008','6009','6010'])
            ->update(['is_active' => 0]);

        // 3) Seed Excel heads as global. Lookup by code+global scope (codes are unique per scope in app logic).
        $heads = [
            // [code, type_id, name, excel note]
            ['101', 6, 'Sales revenue', 'Revenue/Turnover, Credit'],
            ['102', 6, 'Service Revenue', 'Revenue/Turnover, Credit'],
            ['103', 6, 'Commissions received', 'Revenue/Turnover, Credit'],
            ['104', 6, 'Grants and subsidies', 'Revenue/Turnover, Credit'],
            ['105', 6, 'Rental income', 'Revenue/Turnover, Credit'],
            ['106', 6, 'Other trading Income', 'Revenue/Turnover, Credit'],
            ['107', 6, 'Sales Refund', 'Revenue/Turnover, Debit (contra-revenue, included in turnover SUM per Excel)'],
            ['108', 7, 'Investment income', 'Revenue, Credit (no sub-category in Excel)'],
            ['109', 7, 'Other Non-Trading Income', 'Revenue, Credit (no sub-category in Excel)'],
            ['201', 8, 'Cost of Good Sold/Purchase', 'Expense/Direct Expenses, Debit'],
            ['202', 8, 'Direct Wages', 'Expense/Direct Expenses, Debit'],
            ['203', 8, 'Subcontracts', 'Expense/Direct Expenses, Debit'],
            ['204', 8, 'Other Direct Expenses', 'Expense/Direct Expenses, Debit'],
            ['301', 9, 'Accountancy & Audit Fees', 'Expense/Expenses, Debit'],
            ['302', 9, 'Advertising & Marketing', 'Expense/Expenses, Debit'],
            ['303', 9, 'Bank Charges', 'Expense/Expenses, Debit'],
            ['304', 9, 'Cleaning', 'Expense/Expenses, Debit'],
            ['305', 9, 'Corporation Tax', 'Expense/Expenses, Debit'],
            ['306', 9, 'Depreciation', 'Expense/Expenses, Debit'],
            ['307', 9, 'Director Salary/Remuneration', 'Expense/Expenses, Debit'],
            ['308', 9, 'Donation', 'Expense/Expenses, Debit'],
            ['309', 9, 'Employer NIC', 'Expense/Expenses, Debit'],
            ['310', 9, 'Entertainment', 'Expense/Expenses, Debit'],
            ['311', 9, 'Insurance', 'Expense/Expenses, Debit'],
            ['312', 9, 'Interest Paid', 'Expense/Expenses, Debit'],
            ['313', 9, 'IT, Software & Consumable', 'Expense/Expenses, Debit'],
            ['314', 9, 'Legal E& Professional', 'Expense/Expenses, Debit'],
            ['315', 9, 'Light, Heat & Power', 'Expense/Expenses, Debit'],
            ['316', 9, 'Motor Expenses', 'Expense/Expenses, Debit'],
            ['317', 9, 'Pension Cost', 'Expense/Expenses, Debit'],
            ['318', 9, 'Postage & Courier', 'Expense/Expenses, Debit'],
            ['319', 9, 'Printing & Stationery', 'Expense/Expenses, Debit'],
            ['320', 9, 'Rate', 'Expense/Expenses, Debit'],
            ['321', 9, 'Rent', 'Expense/Expenses, Debit'],
            ['322', 9, 'Repair & Maintenance', 'Expense/Expenses, Debit'],
            ['323', 9, 'Salaries', 'Expense/Expenses, Debit'],
            ['324', 9, 'Subscription', 'Expense/Expenses, Debit'],
            ['325', 9, 'Telephone & Internet', 'Expense/Expenses, Debit'],
            ['326', 9, 'Training', 'Expense/Expenses, Debit'],
            ['327', 9, 'Travel & Subsistence', 'Expense/Expenses, Debit'],
            ['328', 9, 'Water', 'Expense/Expenses, Debit'],
            ['329', 9, 'Website', 'Expense/Expenses, Debit'],
            ['401', 2, 'Furniture & Fixture', 'Assets/Fixed assets, Debit'],
            ['402', 2, 'Office Equipment', 'Assets/Fixed assets, Debit'],
            ['403', 2, 'IT & Computer Equipment', 'Assets/Fixed assets, Debit'],
            ['404', 2, 'Building', 'Assets/Fixed assets, Debit'],
            ['405', 2, 'Motor Vehicles', 'Assets/Fixed assets, Debit'],
            ['406', 2, 'Plant & Machinery', 'Assets/Fixed assets, Debit'],
            ['407', 2, 'Intangible', 'Assets/Fixed assets, Debit'],
            ['451', 1, 'Bank', 'Assets/Current assets, Debit'],
            ['452', 1, 'Cash In Hand', 'Assets/Current assets, Debit'],
            ['453', 1, 'Accounts Receivable', 'Assets/Current assets, Debit'],
            ['454', 1, 'Prepayment', 'Assets/Current assets, Debit'],
            ['455', 1, 'Inventory', 'Assets/Current assets, Debit'],
            ['501', 3, 'Accounts Payable', 'Liabilities/Current Liability, Credit'],
            ['502', 3, 'Unpaid Expenses', 'Liabilities/Current Liability, Credit'],
            ['503', 3, 'Accruals', 'Liabilities/Current Liability, Credit'],
            ['504', 3, 'Advance Income', 'Liabilities/Current Liability, Credit'],
            ['505', 3, 'Credit Card Control Account', 'Liabilities/Current Liability, Credit'],
            ['506', 3, 'NIC Payable', 'Liabilities/Current Liability, Credit'],
            ['507', 3, 'PAYE Payable', 'Liabilities/Current Liability, Credit'],
            ['508', 3, 'Provision for Corporation tax', 'Liabilities/Current Liability, Credit'],
            ['509', 3, 'Director Loan Account', 'Liabilities/Current Liability, Credit (also 554 non-current)'],
            ['510', 3, 'Suspense', 'Liabilities/Current Liability, Credit'],
            ['511', 3, 'Pension Payable', 'Liabilities/Current Liability, Credit'],
            ['512', 3, 'Loan', 'Liabilities/Current Liability, Credit'],
            ['513', 3, 'VAT', 'Liabilities/Current Liability, Credit'],
            ['551', 4, 'Bank Loan', 'Liabilities/Non-current Liability, Credit'],
            ['552', 4, 'Other Long Term Loan', 'Liabilities/Non-current Liability, Credit'],
            ['553', 4, 'Hire Purchase Loan', 'Liabilities/Non-current Liability, Credit'],
            ['554', 4, 'Director Loan Account', 'Liabilities/Non-current Liability, Credit (also 509 current)'],
            ['601', 5, 'Share Capital', 'Capital and reserves, Credit'],
            ['602', 5, 'Retained Earning', 'Capital and reserves, Credit'],
            ['603', 5, 'Owners Fund Introduced', 'Capital and reserves, Credit'],
            ['604', 5, 'Owners Drawing', 'Capital and reserves, Debit (contra-equity)'],
        ];

        foreach ($heads as [$code, $typeId, $name, $note]) {
            $existing = AccountHead::where('code', $code)
                ->whereNull('client_credential_id')
                ->first();
            if ($existing) {
                $existing->update([
                    'account_type_id' => $typeId,
                    'name' => $name,
                    'description' => $note,
                    'is_active' => 1,
                ]);
            } else {
                AccountHead::create([
                    'account_type_id' => $typeId,
                    'tax_rate_id' => null,
                    'client_credential_id' => null,
                    'code' => $code,
                    'name' => $name,
                    'description' => $note,
                    'is_active' => 1,
                ]);
            }
        }
    }
}
