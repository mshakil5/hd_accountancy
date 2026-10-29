<?php

namespace Database\Seeders;

use App\Models\AccountType;
use Illuminate\Database\Seeder;

/**
 * The five global account types. Fixed: there is no Account Types screen,
 * so this seeder is the single source of truth.
 */
class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Revenue', 'category' => 'revenue', 'normal_balance' => 'credit'],
            ['name' => 'Expense', 'category' => 'expense', 'normal_balance' => 'debit'],
            ['name' => 'Assets', 'category' => 'asset', 'normal_balance' => 'debit'],
            ['name' => 'Liabilities', 'category' => 'liability', 'normal_balance' => 'credit'],
            ['name' => 'Capital and Reserves', 'category' => 'equity', 'normal_balance' => 'credit'],
        ];

        foreach ($types as $type) {
            AccountType::updateOrCreate(['name' => $type['name']], $type + ['is_active' => 1]);
        }
    }
}
