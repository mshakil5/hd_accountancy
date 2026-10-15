<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('bank_allocation_lines',function(Blueprint $t){
            $t->id();$t->foreignId('bank_entry_id')->constrained('bank_entries')->cascadeOnDelete();
            $t->foreignId('account_head_id')->nullable()->constrained('account_heads');
            $t->foreignId('receipt_id')->nullable()->constrained('receipts');
            $t->decimal('amount',15,2);$t->string('memo',500)->nullable();
            $t->unsignedBigInteger('created_by');$t->timestamps();
            $t->index(['bank_entry_id','receipt_id']);
        });
        Schema::create('bank_review_notes',function(Blueprint $t){
            $t->id();$t->foreignId('bank_entry_id')->constrained('bank_entries')->cascadeOnDelete();
            $t->unsignedBigInteger('author_id');$t->string('type',32)->default('internal');
            $t->text('body');$t->timestamps();
        });
    }
    public function down():void {
        Schema::dropIfExists('bank_review_notes');
        Schema::dropIfExists('bank_allocation_lines');
    }
};
