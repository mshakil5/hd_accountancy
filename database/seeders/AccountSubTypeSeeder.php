<?php

namespace Database\Seeders;

use App\Models\AccountSubType;
use App\Models\AccountType;
use Illuminate\Database\Seeder;

/**
 * The fixed global sub types. Seeded only — there is no module for them.
 */
class AccountSubTypeSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'Revenue' => ['Turnover'],
            'Expense' => ['Direct Expenses', 'Expenses'],
            'Assets' => ['Fixed Assets', 'Current Assets'],
            'Liabilities' => ['Current Liability', 'Non-current Liability'],
            'Capital and Reserves' => ['Capital and Reserves'],
        ];

        foreach ($map as $typeName => $subTypes) {
            $type = AccountType::where('name', $typeName)->first();
            if (!$type) {
                continue;
            }
            foreach ($subTypes as $name) {
                AccountSubType::updateOrCreate(
                    ['account_type_id' => $type->id, 'name' => $name],
                    ['is_active' => 1]
                );
            }
        }
    }
}
