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
        Schema::create('staff_holiday_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('holiday_type_id')->nullable();
            $table->foreign('holiday_type_id')->references('id')->on('holiday_types')->onDelete('cascade');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->foreign('staff_id')->references('id')->on('users')->whereIn('type', ['2','3'])->onDelete('cascade');
            $table->string('day')->nullable();
            $table->boolean('status')->default(1);
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
        Schema::dropIfExists('staff_holiday_types');
    }
};
