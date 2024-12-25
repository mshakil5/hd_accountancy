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
        Schema::create('client_service', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('service_deadline')->nullable();
            $table->string('due_date')->nullable();
            $table->string('legal_deadline')->nullable();
            $table->string('service_frequency')->nullable();
            $table->string('next_service_deadline')->nullable();
            $table->string('next_due_date')->nullable();
            $table->string('next_legal_deadline')->nullable();
            $table->boolean('manager_notification')->default(1);// default 1 == No notification , 0 == New notification
            $table->boolean('status')->default(1); // defaualt 1 = Work isnt start yet , 2 = Work is completed , 0 = work is processing, 3 = work assigned
            $table->boolean('continue')->default(1); // default 1 = continue , 0 = stop
            $table->integer('type')->default(1); // 1 = Normal job , 2 = One time job
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->where('type', 2)->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_service');
    }
};
