<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The system is deterministic (buttons/lists + regex rules). No external AI
     * service is used, so the field is named for what it actually is: a
     * generated qualification summary.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->renameColumn('ai_summary', 'qualification_summary');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->renameColumn('qualification_summary', 'ai_summary');
        });
    }
};
