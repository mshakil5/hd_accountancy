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
        Schema::create('service_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('client_sub_service_id')->nullable();
            $table->longText('message')->nullable();
            $table->boolean('status')->default(1);
            $table->json('viewed_by')->nullable();
            $table->foreign('manager_id')->references('id')->on('users')->where('type', 2)->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->where('type', 3)->onDelete('cascade');
            $table->foreign('client_sub_service_id')->references('id')->on('client_sub_services')->onDelete('cascade');
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
        Schema::dropIfExists('service_messages');
    }
};
