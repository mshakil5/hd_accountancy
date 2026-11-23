<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_entries', function (Blueprint $table) {
            $table->dropUnique('bank_unique_statement_line');
            $table->index(
                ['client_id', 'bank_account_id', 'line_fingerprint', 'line_occurrence'],
                'bank_statement_line_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bank_entries', function (Blueprint $table) {
            $table->dropIndex('bank_statement_line_lookup');
            $table->unique(
                ['client_id', 'bank_account_id', 'line_fingerprint', 'line_occurrence'],
                'bank_unique_statement_line'
            );
        });
    }
};
