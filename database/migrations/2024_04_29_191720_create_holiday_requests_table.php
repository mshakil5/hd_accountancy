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
        Schema::create('holiday_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->foreign('staff_id')->references('id')->on('users')->whereIn('type', ['2','3'])->onDelete('cascade');
            $table->string('holiday_type')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->longText('comment')->nullable();
            $table->longText('admin_note')->nullable();
            $table->integer('total_day')->nullable();
            $table->boolean('status')->default(0); // 0 = Processing, 1 = Approved, 2 = Decline
            $table->boolean('staff_notification')->default(0); // 0 = note get, 1 = get
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
        Schema::dropIfExists('holiday_requests');
    }
};
