<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statement lines are unique per client *and bank account*.
 *
 * The unique index was created without bank_account_id while the uploader
 * de-duplicates per (client, bank account, fingerprint, occurrence). A client
 * with two bank accounts could therefore have the same statement line imported
 * under the second account: the application saw no duplicate and the database
 * rejected the insert, failing the whole upload.
 *
 * The index is widened to match the upload semantics — "import rows into the
 * correct client/business/bank account and prevent duplicates" (verification
 * guide, acceptance tests). Widening a unique key can only relax it, so no
 * existing row can violate the new index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropUnique('bank_unique_statement_line');
        });

        Schema::table('bank_entries', function (Blueprint $t) {
            $t->unique(
                ['client_id', 'bank_account_id', 'line_fingerprint', 'line_occurrence'],
                'bank_unique_statement_line'
            );
        });
    }

    /**
     * Narrowing the key again can fail when the same statement line legitimately
     * exists under two bank accounts of one client; merge those rows first.
     */
    public function down(): void
    {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropUnique('bank_unique_statement_line');
        });

        Schema::table('bank_entries', function (Blueprint $t) {
            $t->unique(['client_id', 'line_fingerprint', 'line_occurrence'], 'bank_unique_statement_line');
        });
    }
};
