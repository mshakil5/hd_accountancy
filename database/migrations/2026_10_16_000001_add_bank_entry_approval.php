<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bank_entries', function (Blueprint $t) {
            // draft -> approved|rejected. Approval is the accountant sign-off that
            // later drives ledger posting; rejection returns the row for review.
            $t->string('reconciliation_status', 24)->default('draft');
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            // Idempotency guard for the posting engine (phase 2): once set, an
            // entry can never be posted twice and cannot be reopened unposted.
            $t->timestamp('journal_posted_at')->nullable();
            $t->index(['client_id', 'reconciliation_status'], 'bank_entries_approval_index');
        });
    }

    public function down(): void {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropIndex('bank_entries_approval_index');
            $t->dropColumn(['reconciliation_status', 'approved_by', 'approved_at', 'journal_posted_at']);
        });
    }
};
