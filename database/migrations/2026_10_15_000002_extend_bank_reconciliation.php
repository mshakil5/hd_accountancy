<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bank_imports', function(Blueprint $t) {
            $t->string('storage_path')->nullable();
            $t->string('file_type',8)->default('csv');
            $t->string('processing_status',48)->default('parsed');
        });
        Schema::table('bank_entries', function(Blueprint $t) {
            $t->string('line_fingerprint',64)->nullable();
            $t->unsignedInteger('line_occurrence')->default(1);
            $t->text('review_note')->nullable();
            $t->unique(['client_id','line_fingerprint','line_occurrence'],'bank_unique_statement_line');
        });
        Schema::create('bank_receipt_requests', function(Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('clients');
            $t->foreignId('bank_entry_id')->constrained('bank_entries');
            $t->unsignedBigInteger('requested_by');
            $t->text('message')->nullable();
            $t->string('status',24)->default('open');
            $t->timestamps();
            $t->index(['client_id','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('bank_receipt_requests');
        Schema::table('bank_entries', function(Blueprint $t) {
            $t->dropUnique('bank_unique_statement_line');
            $t->dropColumn(['line_fingerprint','line_occurrence','review_note']);
        });
        Schema::table('bank_imports', fn(Blueprint $t) => $t->dropColumn(['storage_path','file_type','processing_status']));
    }
};
