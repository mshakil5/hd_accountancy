<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->decimal('system_balance', 15, 2)->nullable()->after('balance_after');
        });
    }

    public function down(): void
    {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropColumn('system_balance');
        });
    }
};
