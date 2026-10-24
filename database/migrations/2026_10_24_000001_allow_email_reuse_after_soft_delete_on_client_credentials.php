<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * client_credentials uses soft deletes, but the plain unique index on `email`
 * also covered trashed rows. That meant deleting a credential and re-creating
 * one with the same email failed with a duplicate key error.
 *
 * MySQL has no partial unique indexes, so we keep a generated column that holds
 * the email only while the row is active (deleted_at IS NULL). Multiple NULLs
 * are allowed in a unique index, so trashed rows no longer block re-use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_credentials', function (Blueprint $table) {
            $table->dropUnique('client_credentials_email_unique');
        });

        DB::statement(
            'ALTER TABLE `client_credentials` ADD COLUMN `active_email` VARCHAR(255) '
            . 'GENERATED ALWAYS AS (CASE WHEN `deleted_at` IS NULL THEN `email` ELSE NULL END) STORED AFTER `email`'
        );

        Schema::table('client_credentials', function (Blueprint $table) {
            $table->unique('active_email', 'client_credentials_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('client_credentials', function (Blueprint $table) {
            $table->dropUnique('client_credentials_email_unique');
        });

        DB::statement('ALTER TABLE `client_credentials` DROP COLUMN `active_email`');

        // Note: this fails if an email was re-used after a soft delete, because
        // the same address may then exist on both an active and a trashed row.
        Schema::table('client_credentials', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
