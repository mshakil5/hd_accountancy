<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Not every bank_entries row comes from an uploaded statement: an internal transfer
 * creates a counter entry in the destination bank account, which belongs to no
 * bank_import. With bank_import_id NOT NULL those inserts fail with
 * SQLSTATE 1364 "Field 'bank_import_id' doesn't have a default value", so the column
 * has to be nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_entries', function (Blueprint $table) {
            $table->foreignId('bank_import_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows without a statement cannot satisfy the NOT NULL constraint again.
        DB::table('bank_entries')->whereNull('bank_import_id')->delete();

        Schema::table('bank_entries', function (Blueprint $table) {
            $table->foreignId('bank_import_id')->nullable(false)->change();
        });
    }
};
