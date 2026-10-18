<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Head-first chart of accounts flip.
 *
 * The link between head and type becomes two-way and optional:
 *   - account_heads.account_type_id becomes nullable (SET NULL on delete),
 *     so retiring a type no longer wipes real heads/transactions.
 *   - account_types gains account_head_id (CASCADE on head delete) so a type
 *     can be created under its head, and the name unique is relaxed to a
 *     plain index (same name under different heads is legal).
 *
 * Reports keep resolving head -> type -> category/normal_balance.
 * Note: the account_heads uniques are relaxed by the earlier
 * 2026_10_04_000001_relax_account_heads_uniques_for_excel_chart migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. The head -> type link becomes optional and survives type deletion.
        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropForeign(['account_type_id']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->unsignedBigInteger('account_type_id')->nullable()->change();
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->foreign('account_type_id')
                ->references('id')->on('account_types')
                ->onDelete('set null');
        });

        // 2. Relax the name unique on account_types — the same name under
        //    different heads is legal now — but keep a plain index for lookups.
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropUnique('account_types_name_unique');
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->index('name');
        });

        // 3. The two-way link: a type is created under its head and is pinned
        //    to the same client scope as that head.
        Schema::table('account_types', function (Blueprint $table) {
            $table->foreignId('account_head_id')->nullable()->after('id')
                ->constrained('account_heads')->cascadeOnDelete();
            $table->foreignId('client_credential_id')->nullable()->after('account_head_id')
                ->constrained('client_credentials')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_head_id');
            $table->dropConstrainedForeignId('client_credential_id');
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->unique('name');
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropForeign(['account_type_id']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->unsignedBigInteger('account_type_id')->nullable(false)->change();
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->foreign('account_type_id')
                ->references('id')->on('account_types')
                ->cascadeOnDelete();
        });
    }
};
