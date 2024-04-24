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
        Schema::create('sub_services', function (Blueprint $table) {
             $table->id();
             $table->unsignedBigInteger('service_id');
             $table->string('name')->nullable();
             $table->string('deadline')->nullable();
             $table->text('note')->nullable();
             $table->boolean('status')->default(1);
             $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
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
        Schema::dropIfExists('sub_services');
    }
};
