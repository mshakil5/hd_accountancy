<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_imports', function (Blueprint $t) {
            $t->id(); $t->foreignId('client_id')->constrained('clients');
            $t->string('filename'); $t->string('sha256',64); $t->unsignedBigInteger('created_by');
            $t->unsignedInteger('transaction_count')->default(0); $t->timestamps();
            $t->unique(['client_id','sha256']);
        });
        Schema::create('bank_entries', function (Blueprint $t) {
            $t->id(); $t->foreignId('client_id')->constrained('clients');
            $t->foreignId('bank_import_id')->constrained('bank_imports');
            $t->date('transaction_date'); $t->string('description',500); $t->string('reference')->nullable();
            $t->decimal('amount',15,2); $t->string('currency',3)->default('GBP');
            $t->string('status',24)->default('unmatched'); $t->foreignId('receipt_id')->nullable()->constrained('receipts');
            $t->foreignId('account_head_id')->nullable()->constrained('account_heads');
            $t->unsignedBigInteger('matched_by')->nullable(); $t->timestamp('matched_at')->nullable(); $t->timestamps();
            $t->index(['client_id','status','transaction_date']);
        });
        Schema::create('bank_match_audits', function (Blueprint $t) {
            $t->id(); $t->foreignId('bank_entry_id')->constrained('bank_entries');
            $t->unsignedBigInteger('actor_id'); $t->string('action',32);
            $t->json('old_values')->nullable(); $t->json('new_values')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('bank_match_audits'); Schema::dropIfExists('bank_entries'); Schema::dropIfExists('bank_imports');
    }
};
