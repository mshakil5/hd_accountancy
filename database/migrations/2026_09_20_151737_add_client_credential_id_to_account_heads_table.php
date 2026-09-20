<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_heads', function (Blueprint $table) {
            $table->unsignedBigInteger('client_credential_id')->nullable()->after('account_type_id');
            $table->foreign('client_credential_id')->references('id')->on('client_credentials')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropForeign(['client_credential_id']);
            $table->dropColumn('client_credential_id');
        });
    }
};
