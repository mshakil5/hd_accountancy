<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statement-level verification (see HD_Bank_Statement_OCR_Verification_Guide):
 * extraction alone is not proof of completeness, so each imported PDF carries a
 * verification state, page coverage, balance checks and an exception list that an
 * accountant must clear before any row can be reconciled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_imports', function (Blueprint $t) {
            $t->string('verification_status', 32)->default('pending')->after('processing_status');
            $t->unsignedInteger('pages_total')->default(0);
            $t->unsignedInteger('pages_processed')->default(0);
            $t->decimal('opening_balance', 15, 2)->nullable();
            $t->decimal('closing_balance', 15, 2)->nullable();
            $t->decimal('calculated_closing_balance', 15, 2)->nullable();
            $t->decimal('balance_difference', 15, 2)->nullable();
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->text('verification_note')->nullable();
            $t->index('verification_status', 'bank_imports_verification_index');
        });

        Schema::table('bank_entries', function (Blueprint $t) {
            $t->unsignedInteger('source_page')->nullable();
            $t->unsignedInteger('source_position')->nullable();
            $t->string('extraction_source', 8)->default('text');
            $t->decimal('balance_after', 15, 2)->nullable();
            $t->json('original_values')->nullable();
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
        });

        Schema::create('bank_statement_pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bank_import_id')->constrained('bank_imports')->cascadeOnDelete();
            $t->unsignedInteger('page_no');
            $t->unsignedInteger('extracted_rows')->default(0);
            $t->unsignedInteger('text_length')->default(0);
            $t->string('status', 24)->default('review');
            $t->string('note', 255)->nullable();
            $t->timestamps();
            $t->unique(['bank_import_id', 'page_no'], 'bank_statement_pages_unique');
        });

        Schema::create('bank_statement_exceptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bank_import_id')->constrained('bank_imports')->cascadeOnDelete();
            $t->foreignId('bank_entry_id')->nullable()->constrained('bank_entries')->nullOnDelete();
            $t->unsignedInteger('page_no')->nullable();
            $t->string('code', 48);
            $t->string('severity', 12)->default('blocking');
            $t->text('message');
            $t->text('source_value')->nullable();
            $t->unsignedBigInteger('resolved_by')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->text('resolution')->nullable();
            $t->timestamps();
            $t->index(['bank_import_id', 'resolved_at'], 'bank_statement_exceptions_open_index');
        });

        Schema::create('bank_entry_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bank_entry_id')->nullable()->constrained('bank_entries')->nullOnDelete();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('action', 24);
            $t->json('original_values')->nullable();
            $t->json('corrected_values')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
        });

        // Statements imported before this gate existed were reviewed by hand on the
        // reconciliation page, so they keep working: only new uploads start unverified.
        DB::table('bank_imports')->update([
            'verification_status' => 'verified',
            'verified_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_entry_revisions');
        Schema::dropIfExists('bank_statement_exceptions');
        Schema::dropIfExists('bank_statement_pages');

        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropColumn([
                'source_page', 'source_position', 'extraction_source',
                'balance_after', 'original_values', 'verified_by', 'verified_at',
            ]);
        });

        Schema::table('bank_imports', function (Blueprint $t) {
            $t->dropIndex('bank_imports_verification_index');
            $t->dropColumn([
                'verification_status', 'pages_total', 'pages_processed',
                'opening_balance', 'closing_balance', 'calculated_closing_balance',
                'balance_difference', 'verified_by', 'verified_at', 'verification_note',
            ]);
        });
    }
};
