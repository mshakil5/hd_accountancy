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
        Schema::create('client_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_service_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('frequency')->nullable();
            $table->string('deadline')->nullable();
            $table->longText('note')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('manager_notification')->default(1);
            $table->boolean('staff_notification')->default(1); // default 1 == No notification , 0 == New notification
            $table->foreign('client_service_id')->references('id')->on('client_service')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->where('type', 2)->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->where('type', 3)->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_sub_services');
    }
};
