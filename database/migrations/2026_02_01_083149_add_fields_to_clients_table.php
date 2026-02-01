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
        Schema::table('clients', function (Blueprint $table) {
             $table->string('trading_city')->nullable()->after('trading_address_line2');
             $table->string('trading_country')->nullable()->after('trading_city');
             $table->string('trading_postcode')->nullable()->after('trading_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('trading_city');
            $table->dropColumn('trading_country');
            $table->dropColumn('trading_postcode');
        });
    }
};
