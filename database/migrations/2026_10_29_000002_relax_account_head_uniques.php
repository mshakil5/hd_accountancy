<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Excel chart intentionally repeats names (Director Loan Account 509/554)
 * and the app enforces uniqueness per client scope + sub type in the
 * controller, so the DB-wide uniques must go.
 */
return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM account_heads'))->pluck('Key_name')->unique();

        if ($indexes->contains('account_heads_code_unique')) {
            Schema::table('account_heads', fn(Blueprint $table) => $table->dropUnique('account_heads_code_unique'));
        }
        if ($indexes->contains('account_heads_name_unique')) {
            Schema::table('account_heads', fn(Blueprint $table) => $table->dropUnique('account_heads_name_unique'));
        }

        Schema::table('account_heads', function (Blueprint $table) use ($indexes) {
            if (!$indexes->contains('account_heads_code_index')) $table->index('code');
            if (!$indexes->contains('account_heads_name_index')) $table->index('name');
        });
    }

    public function down(): void
    {
        // Irreversible without recreating unique violations.
    }
};
