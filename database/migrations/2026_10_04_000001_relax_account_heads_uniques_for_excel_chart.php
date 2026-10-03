<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Excel chart (101-604) needs:
     *  - duplicate head name across types/scopes (Director Loan Account 509 + 554)
     *  - same head name as legacy chart (Sales Revenue 4001 vs 101) during transition
     * Controller already scopes uniqueness by client_credential_id in app code,
     * so DB-level single-column uniques are relaxed to plain indexes.
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

    public function down(): void
    {
        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['name']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            // Note: will fail if duplicates exist; that is intentional.
            $table->unique('code', 'account_heads_code_unique');
            $table->unique('name', 'account_heads_name_unique');
        });
    }
};
