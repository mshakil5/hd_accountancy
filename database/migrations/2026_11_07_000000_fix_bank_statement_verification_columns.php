<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add missing columns to bank_entries table
        if (Schema::hasTable('bank_entries')) {
            if (!Schema::hasColumn('bank_entries', 'is_manually_added')) {
                Schema::table('bank_entries', function ($table) {
                    $table->boolean('is_manually_added')->default(false)->after('verified_at');
                });
            }
            if (!Schema::hasColumn('bank_entries', 'is_manually_corrected')) {
                Schema::table('bank_entries', function ($table) {
                    $table->boolean('is_manually_corrected')->default(false)->after('is_manually_added');
                });
            }
        }

        // 2. Ensure bank_entry_revisions has author_id column (not actor_id)
        if (Schema::hasTable('bank_entry_revisions')) {
            // If actor_id exists, rename it to author_id
            if (Schema::hasColumn('bank_entry_revisions', 'actor_id') && !Schema::hasColumn('bank_entry_revisions', 'author_id')) {
                Schema::table('bank_entry_revisions', function ($table) {
                    $table->renameColumn('actor_id', 'author_id');
                });
            }
            // If neither exists, add author_id
            if (!Schema::hasColumn('bank_entry_revisions', 'author_id')) {
                Schema::table('bank_entry_revisions', function ($table) {
                    $table->unsignedBigInteger('author_id')->nullable()->after('bank_entry_id');
                });
            }
        }
    }

    public function down(): void
    {
        // No rollback needed - this is a one-way fix migration
    }
};