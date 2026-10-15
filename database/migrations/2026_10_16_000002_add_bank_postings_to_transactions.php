<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Bank-only ledger rows carry no receipt, so the foreign key must allow
        // NULL. Done with raw SQL (drop/re-add the FK around it) because
        // doctrine/dbal is not installed and Laravel 10 needs it for ->change().
        Schema::table('transactions', fn(Blueprint $t) => $t->dropForeign(['receipt_id']));
        DB::statement('ALTER TABLE `transactions` MODIFY `receipt_id` BIGINT UNSIGNED NULL');
        Schema::table('transactions', function (Blueprint $t) {
            $t->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade');
        });

        Schema::table('transactions', function (Blueprint $t) {
            // Link back to the bank entry that produced this ledger row, so a
            // reversal can remove exactly its own postings and nothing else.
            $t->foreignId('bank_entry_id')->nullable()->after('receipt_id')
                ->constrained('bank_entries')->cascadeOnDelete();
            // Denormalised scope/date so reports can include bank rows without
            // joining through receipts (which they do not have).
            $t->foreignId('client_id')->nullable()->after('bank_entry_id')
                ->constrained('clients')->nullOnDelete();
            $t->date('entry_date')->nullable()->after('client_id');
            $t->index(['client_id', 'entry_date'], 'transactions_client_entry_date_index');
        });
    }

    public function down(): void {
        // Never leave orphaned bank postings behind when reverting.
        DB::table('transactions')->whereNotNull('bank_entry_id')->delete();

        Schema::table('transactions', function (Blueprint $t) {
            $t->dropIndex('transactions_client_entry_date_index');
            $t->dropConstrainedForeignId('bank_entry_id');
            $t->dropConstrainedForeignId('client_id');
            $t->dropColumn('entry_date');
        });

        Schema::table('transactions', fn(Blueprint $t) => $t->dropForeign(['receipt_id']));
        DB::statement('ALTER TABLE `transactions` MODIFY `receipt_id` BIGINT UNSIGNED NOT NULL');
        Schema::table('transactions', function (Blueprint $t) {
            $t->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade');
        });
    }
};
