<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Kept separate from `human_takeover`: the bot may have finished its
            // qualification flow without a member of staff taking the lead over.
            $table->timestamp('automation_completed_at')->nullable()->after('qualified_at');
            $table->index('last_inbound_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['last_inbound_at']);
            $table->dropColumn('automation_completed_at');
        });
    }
};
