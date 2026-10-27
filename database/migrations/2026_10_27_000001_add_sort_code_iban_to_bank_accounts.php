<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add sort_code and iban to reconciliation_bank_accounts.
 *
 * The recognition service already detects these from statement headers and
 * uses them to score candidate accounts. They were never stored on the
 * account record itself, so the scores could never be persisted and the
 * same account could be re-created on every upload.
 *
 * Now the user can register an account once (bank name, sort code, IBAN,
 * last four account digits) and later uploads are matched against it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliation_bank_accounts', function (Blueprint $t) {
            $t->string('sort_code', 32)->nullable()->after('account_last_four');
            $t->string('iban', 64)->nullable()->after('sort_code');
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_bank_accounts', function (Blueprint $t) {
            $t->dropColumn(['sort_code', 'iban']);
        });
    }
};