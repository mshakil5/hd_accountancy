<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->boolean('from_reconciliation')->default(false)->after('status');
            $table->index('from_reconciliation');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex(['from_reconciliation']);
            $table->dropColumn('from_reconciliation');
        });
    }
};
