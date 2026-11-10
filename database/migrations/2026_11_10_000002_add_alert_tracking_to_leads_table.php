<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alert delivery must be observable: a swallowed email/SMS failure must not
     * look like a successful handover to the HD team.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('alert_status', 20)->nullable()->after('automation_completed_at');
            $table->text('alert_last_error')->nullable()->after('alert_status');
            $table->timestamp('alert_last_attempt_at')->nullable()->after('alert_last_error');
        });

        // Unrecognised free text must never silently produce a confident lead.
        // One clarifying question is asked; a second unclear reply goes to REVIEW.
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedTinyInteger('unclear_replies')->default(0)->after('conversation_step');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['alert_status', 'alert_last_error', 'alert_last_attempt_at', 'unclear_replies']);
        });
    }
};
