<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Type -> Sub Type -> Head chart of accounts.
 *
 * account_types becomes the five fixed, global account classes
 * (Revenue, Expense, Assets, Liabilities, Capital and Reserves).
 * account_sub_types becomes the fixed, seeded second level.
 * account_heads keeps only what the Chart of Accounts screen needs:
 * type, sub type, client scope, code, name, normal balance, active flag.
 *
 * The VAT rate master is retired: account_heads.tax_rate_id and the
 * tax_rates table are dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. The fixed second level.
        Schema::create('account_sub_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_type_id')->constrained('account_types')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['account_type_id', 'name']);
        });

        // 2. Heads no longer point at the old type rows, and VAT/description go.
        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropForeign(['account_type_id']);
            $table->dropForeign(['tax_rate_id']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropColumn(['tax_rate_id', 'description']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->foreignId('account_sub_type_id')->nullable()->after('account_type_id')
                ->constrained('account_sub_types')->nullOnDelete();
            $table->enum('normal_balance', ['debit', 'credit'])->default('debit')->after('name');
        });

        // 3. Remember the old shape so heads keep a sensible type/sub type.
        $oldTypes = DB::table('account_types')->get()->keyBy('id');
        $oldHeads = DB::table('account_heads')->get(['id', 'account_type_id']);

        // 4. Rebuild account_types as the five fixed classes.
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropForeign(['account_head_id']);
            $table->dropForeign(['client_credential_id']);
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->dropColumn(['account_head_id', 'client_credential_id']);
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        DB::table('account_heads')->update(['account_type_id' => null]);
        DB::table('account_types')->delete();

        $now = now();
        $types = [
            ['name' => 'Revenue', 'category' => 'revenue', 'normal_balance' => 'credit'],
            ['name' => 'Expense', 'category' => 'expense', 'normal_balance' => 'debit'],
            ['name' => 'Assets', 'category' => 'asset', 'normal_balance' => 'debit'],
            ['name' => 'Liabilities', 'category' => 'liability', 'normal_balance' => 'credit'],
            ['name' => 'Capital and Reserves', 'category' => 'equity', 'normal_balance' => 'credit'],
        ];
        foreach ($types as $type) {
            DB::table('account_types')->insert($type + [
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $typeIds = DB::table('account_types')->pluck('id', 'name');

        $subTypes = [
            'Revenue' => ['Turnover'],
            'Expense' => ['Direct Expenses', 'Expenses'],
            'Assets' => ['Fixed Assets', 'Current Assets'],
            'Liabilities' => ['Current Liability', 'Non-current Liability'],
            'Capital and Reserves' => ['Capital and Reserves'],
        ];
        foreach ($subTypes as $typeName => $names) {
            foreach ($names as $name) {
                DB::table('account_sub_types')->insert([
                    'account_type_id' => $typeIds[$typeName],
                    'name' => $name,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $subTypeRows = DB::table('account_sub_types')->get();
        $subTypeId = [];
        $firstSubForType = [];
        foreach ($subTypeRows as $row) {
            $subTypeId[$row->account_type_id . '|' . $row->name] = $row->id;
            $firstSubForType[$row->account_type_id] ??= $row->id;
        }

        $categoryToType = [
            'revenue' => 'Revenue',
            'expense' => 'Expense',
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Capital and Reserves',
        ];
        // Legacy type names -> canonical sub type names.
        $legacySubType = [
            'Sales' => 'Turnover',
            'Turnover' => 'Turnover',
            'Other Income' => 'Turnover',
            'Overhead' => 'Expenses',
            'Overhead Expense' => 'Expenses',
            'Expenses' => 'Expenses',
            'Purchase' => 'Direct Expenses',
            'Direct Expenses' => 'Direct Expenses',
            'Current Asset' => 'Current Assets',
            'Current Assets' => 'Current Assets',
            'Fixed Assets' => 'Fixed Assets',
            'Loan' => 'Current Liability',
            'Current Liability' => 'Current Liability',
            'Non-current Liability' => 'Non-current Liability',
            'Share Capital' => 'Capital and Reserves',
            'Capital and reserves' => 'Capital and Reserves',
            'Capital and Reserves' => 'Capital and Reserves',
        ];

        foreach ($oldHeads as $head) {
            $old = $oldTypes[$head->account_type_id] ?? null;
            $typeName = $categoryToType[$old->category ?? ''] ?? 'Assets';
            $typeId = $typeIds[$typeName];
            $subName = $legacySubType[$old->name ?? ''] ?? null;
            $subId = ($subName && isset($subTypeId[$typeId . '|' . $subName]))
                ? $subTypeId[$typeId . '|' . $subName]
                : $firstSubForType[$typeId];

            DB::table('account_heads')->where('id', $head->id)->update([
                'account_type_id' => $typeId,
                'account_sub_type_id' => $subId,
                // Keep the old nature; 107 / 604 are the documented corrections.
                'normal_balance' => $old->normal_balance ?? ($typeName === 'Revenue' || $typeName === 'Liabilities' || $typeName === 'Capital and Reserves' ? 'credit' : 'debit'),
            ]);
        }

        // Documented contra heads from the Excel chart.
        DB::table('account_heads')->whereNull('client_credential_id')->whereIn('code', ['107', '604'])
            ->update(['normal_balance' => 'debit']);

        // 5. Constraints + the one-name-per-type rule is gone: types are fixed.
        Schema::table('account_types', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->foreign('account_type_id')
                ->references('id')->on('account_types')
                ->nullOnDelete();
        });

        Schema::dropIfExists('tax_rates');
    }

    public function down(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropForeign(['account_sub_type_id']);
            $table->dropForeign(['account_type_id']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropColumn(['account_sub_type_id', 'normal_balance']);
            $table->string('description')->nullable();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('account_types', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::dropIfExists('account_sub_types');

        Schema::table('account_heads', function (Blueprint $table) {
            $table->foreign('account_type_id')->references('id')->on('account_types')->nullOnDelete();
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->foreignId('account_head_id')->nullable()->after('id')->constrained('account_heads')->cascadeOnDelete();
            $table->foreignId('client_credential_id')->nullable()->after('account_head_id')->constrained('client_credentials')->cascadeOnDelete();
        });
    }
};
