<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marker table that records that this migration created the shared `jobs`
     * queue table. It lets `down()` remove the table only when this migration
     * owns it, and never when the table already existed.
     */
    private const OWNER_TABLE = 'lead_queue_jobs_owner';

    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });

            Schema::create(self::OWNER_TABLE, function (Blueprint $table) {
                $table->id();
                $table->string('migration');
            });
        }
    }

    public function down(): void
    {
        // The `jobs` table is shared with the rest of the application (Laravel's
        // database queue) and may have existed before this migration ran. It can
        // also hold queued lead messages, so a rollback must never drop a table
        // this migration did not create.
        if (!Schema::hasTable(self::OWNER_TABLE)) {
            return;
        }

        Schema::dropIfExists('jobs');
        Schema::drop(self::OWNER_TABLE);
    }
};
