<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->string('transfer_type', 24)->nullable()->after('reconciliation_status'); // 'internal_transfer'
            $t->unsignedBigInteger('linked_bank_entry_id')->nullable()->after('transfer_type');
            $t->foreign('linked_bank_entry_id')->references('id')->on('bank_entries')->nullOnDelete();
            $t->index(['client_id', 'transfer_type'], 'bank_entries_transfer_index');
        });
    }

    public function down(): void {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropIndex('bank_entries_transfer_index');
            $t->dropConstrainedForeignId('linked_bank_entry_id');
            $t->dropColumn(['transfer_type', 'linked_bank_entry_id']);
        });
    }
};
