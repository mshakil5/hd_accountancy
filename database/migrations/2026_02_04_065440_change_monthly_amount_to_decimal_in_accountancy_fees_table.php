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
        Schema::table('accountancy_fees', function (Blueprint $table) {
            $table->decimal('monthly_amount', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accountancy_fees', function (Blueprint $table) {
            $table->integer('monthly_amount')->nullable()->change();
        });
    }
};
