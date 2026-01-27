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
        Schema::table('director_infos', function (Blueprint $table) {
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('post_code')->nullable();
            $table->boolean('photo_id_saved')->nullable();
            $table->string('dir_verification_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('director_infos', function (Blueprint $table) {
            $table->dropColumn('address_line_2');
            $table->dropColumn('city');
            $table->dropColumn('country');
            $table->dropColumn('post_code');
            $table->dropColumn('photo_id_saved');
            $table->dropColumn('dir_verification_code');
        });
    }
};
