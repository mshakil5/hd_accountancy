<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('clients');
            $t->foreignId('bank_entry_id')->constrained('bank_entries');
            $t->foreignId('receipt_id')->nullable()->constrained('receipts');
            $t->decimal('bank_amount', 15, 2);
            $t->decimal('receipt_amount', 15, 2)->nullable();
            $t->decimal('settled_amount', 15, 2);
            $t->decimal('remaining_amount', 15, 2)->default(0);
            $t->string('settlement_type', 24); // 'exact', 'partial', 'over_payment', 'under_payment'
            $t->string('status', 24)->default('open'); // 'open', 'cleared', 'written_off'
            $t->timestamps();
            $t->index(['client_id', 'bank_entry_id']);
            $t->index(['client_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('bank_settlements');
    }
};
