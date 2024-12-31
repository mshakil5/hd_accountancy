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
        Schema::table('service_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('client_service_id')->nullable();
            $table->foreign('client_service_id')->references('id')->on('client_service')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_messages', function (Blueprint $table) {
            //
        });
    }
};
