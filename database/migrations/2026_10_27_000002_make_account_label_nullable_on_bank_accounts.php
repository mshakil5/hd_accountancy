<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The app no longer collects an account label when a user creates a bank
 * account for statement uploads. Make the legacy column nullable so the
 * schema matches the API and no empty placeholder is required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliation_bank_accounts', function (Blueprint $table) {
            $table->string('account_label', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_bank_accounts', function (Blueprint $table) {
            $table->string('account_label', 100)->nullable(false)->change();
        });
    }
};
