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
        Schema::table('client_service', function (Blueprint $table) {
            $table->unsignedBigInteger('director_info_id')->after('manager_id')->nullable();
            $table->foreign('director_info_id')->references('id')->on('director_infos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_service', function (Blueprint $table) {
            //
        });
    }
};
