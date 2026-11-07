<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->boolean('is_manually_added')->default(false)->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('bank_entries', function (Blueprint $t) {
            $t->dropColumn('is_manually_added');
        });
    }
};
