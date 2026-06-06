<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uid')->unique();
            $table->foreignId('receipt_id')->constrained('receipts')->onDelete('cascade');
            $table->foreignId('account_head_id')->constrained('account_heads')->onDelete('cascade');
            $table->enum('type', ['payable', 'receivable', 'paid', 'received']);
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_percent', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank', 'card'])->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
