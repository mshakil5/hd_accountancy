<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use App\Models\AccountSubType;
use Illuminate\Database\Seeder;

/**
 * Seeds the Excel "Chart of account, P&L, BS.xlsx" chart as GLOBAL
 * (client_credential_id = NULL) so every client sees it, while per-client
 * custom heads (client_credential_id = X) keep working as overrides.
 *
 * Flow: Account Type -> Account Sub Type -> Account Head.
 * Types and sub types are seeded by AccountTypeSeeder / AccountSubTypeSeeder.
 *
 * Documented quirks kept from the Excel file:
 *  - "Director Loan Account" appears twice (509 current + 554 non-current)
 *  - 107 Sales Refund is debit-natured inside revenue
 *  - 604 Owners Drawing is debit-natured inside credit equity
 */
class ChartOfAccountsExcelSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AccountTypeSeeder::class);
        $this->call(AccountSubTypeSeeder::class);

        $subTypes = AccountSubType::with('accountType')->get()->keyBy('name');

        // 1) Retire the legacy global chart (1001-6010) so dropdowns/reports
        // default to the Excel chart. History is preserved.
        AccountHead::whereNull('client_credential_id')
            ->whereIn('code', ['1001','1002','1003','1101','1102','2001','2002','2101','3001','3002','4001','4002','4101','5001','5002','6001','6002','6003','6004','6005','6006','6007','6008','6009','6010'])
            ->update(['is_active' => 0]);

        // 2) Seed Excel heads as global. [code, sub type, name]
        $heads = [
            ['101', 'Turnover', 'Sales revenue'],
            ['102', 'Turnover', 'Service Revenue'],
            ['103', 'Turnover', 'Commissions received'],
            ['104', 'Turnover', 'Grants and subsidies'],
            ['105', 'Turnover', 'Rental income'],
            ['106', 'Turnover', 'Other trading Income'],
            ['107', 'Turnover', 'Sales Refund'],
            ['108', 'Turnover', 'Investment income'],
            ['109', 'Turnover', 'Other Non-Trading Income'],
            ['201', 'Direct Expenses', 'Cost of Good Sold/Purchase'],
            ['202', 'Direct Expenses', 'Direct Wages'],
            ['203', 'Direct Expenses', 'Subcontracts'],
            ['204', 'Direct Expenses', 'Other Direct Expenses'],
            ['301', 'Expenses', 'Accountancy & Audit Fees'],
            ['302', 'Expenses', 'Advertising & Marketing'],
            ['303', 'Expenses', 'Bank Charges'],
            ['304', 'Expenses', 'Cleaning'],
            ['305', 'Expenses', 'Corporation Tax'],
            ['306', 'Expenses', 'Depreciation'],
            ['307', 'Expenses', 'Director Salary/Remuneration'],
            ['308', 'Expenses', 'Donation'],
            ['309', 'Expenses', 'Employer NIC'],
            ['310', 'Expenses', 'Entertainment'],
            ['311', 'Expenses', 'Insurance'],
            ['312', 'Expenses', 'Interest Paid'],
            ['313', 'Expenses', 'IT, Software & Consumable'],
            ['314', 'Expenses', 'Legal E& Professional'],
            ['315', 'Expenses', 'Light, Heat & Power'],
            ['316', 'Expenses', 'Motor Expenses'],
            ['317', 'Expenses', 'Pension Cost'],
            ['318', 'Expenses', 'Postage & Courier'],
            ['319', 'Expenses', 'Printing & Stationery'],
            ['320', 'Expenses', 'Rate'],
            ['321', 'Expenses', 'Rent'],
            ['322', 'Expenses', 'Repair & Maintenance'],
            ['323', 'Expenses', 'Salaries'],
            ['324', 'Expenses', 'Subscription'],
            ['325', 'Expenses', 'Telephone & Internet'],
            ['326', 'Expenses', 'Training'],
            ['327', 'Expenses', 'Travel & Subsistence'],
            ['328', 'Expenses', 'Water'],
            ['329', 'Expenses', 'Website'],
            ['401', 'Fixed Assets', 'Furniture & Fixture'],
            ['402', 'Fixed Assets', 'Office Equipment'],
            ['403', 'Fixed Assets', 'IT & Computer Equipment'],
            ['404', 'Fixed Assets', 'Building'],
            ['405', 'Fixed Assets', 'Motor Vehicles'],
            ['406', 'Fixed Assets', 'Plant & Machinery'],
            ['407', 'Fixed Assets', 'Intangible'],
            ['451', 'Current Assets', 'Bank'],
            ['452', 'Current Assets', 'Cash In Hand'],
            ['453', 'Current Assets', 'Accounts Receivable'],
            ['454', 'Current Assets', 'Prepayment'],
            ['455', 'Current Assets', 'Inventory'],
            ['501', 'Current Liability', 'Accounts Payable'],
            ['502', 'Current Liability', 'Unpaid Expenses'],
            ['503', 'Current Liability', 'Accruals'],
            ['504', 'Current Liability', 'Advance Income'],
            ['505', 'Current Liability', 'Credit Card Control Account'],
            ['506', 'Current Liability', 'NIC Payable'],
            ['507', 'Current Liability', 'PAYE Payable'],
            ['508', 'Current Liability', 'Provision for Corporation tax'],
            ['509', 'Current Liability', 'Director Loan Account'],
            ['510', 'Current Liability', 'Suspense'],
            ['511', 'Current Liability', 'Pension Payable'],
            ['512', 'Current Liability', 'Loan'],
            ['513', 'Current Liability', 'VAT'],
            ['551', 'Non-current Liability', 'Bank Loan'],
            ['552', 'Non-current Liability', 'Other Long Term Loan'],
            ['553', 'Non-current Liability', 'Hire Purchase Loan'],
            ['554', 'Non-current Liability', 'Director Loan Account'],
            ['601', 'Capital and Reserves', 'Share Capital'],
            ['602', 'Capital and Reserves', 'Retained Earning'],
            ['603', 'Capital and Reserves', 'Owners Fund Introduced'],
            ['604', 'Capital and Reserves', 'Owners Drawing'],
        ];

        foreach ($heads as [$code, $subTypeName, $name]) {
            $subType = $subTypes->get($subTypeName);
            if (!$subType) {
                continue;
            }

            $normal = $subType->accountType?->normal_balance ?? 'debit';
            if (in_array($code, ['107', '604'], true)) {
                $normal = 'debit';
            }

            $attrs = [
                'account_type_id' => $subType->account_type_id,
                'account_sub_type_id' => $subType->id,
                'name' => $name,
                'normal_balance' => $normal,
                'is_active' => 1,
            ];

            $existing = AccountHead::where('code', $code)
                ->whereNull('client_credential_id')
                ->first();

            if ($existing) {
                $existing->update($attrs);
            } else {
                AccountHead::create($attrs + [
                    'client_credential_id' => null,
                    'code' => $code,
                ]);
            }
        }
    }
}
