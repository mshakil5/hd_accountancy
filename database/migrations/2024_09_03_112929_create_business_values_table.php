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
        Schema::create('business_values', function (Blueprint $table) {
            $table->id();
            $table->string('short_title')->nullable();
            $table->longText('long_title')->nullable();
            $table->string('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('accounting_solution')->default(0);
            $table->boolean('tax_solution')->default(0);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_values');
    }
};
