<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Excel chart (codes 101-604) needs:
 *  - duplicate head names across types/scopes (Director Loan Account 509 + 554)
 *  - the same head name as the legacy chart during the transition
 *    (Sales Revenue 4001 vs 101)
 * Uniqueness is enforced per scope in the AccountHeadController, so the
 * DB-level single-column uniques are relaxed to plain indexes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropUnique('account_heads_code_unique');
            $table->dropUnique('account_heads_name_unique');
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->index('code');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['name']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->unique('code');
            $table->unique('name');
        });
    }
};
