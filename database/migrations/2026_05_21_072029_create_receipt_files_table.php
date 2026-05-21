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
        Schema::create('receipt_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("receipt_id");
            $table->string("file_path");
            $table->string("file_name");
            $table->string("file_type");
            $table->string("mime_type")->nullable();
            $table->unsignedBigInteger("file_size")->nullable();
            $table->timestamps();
            $table->foreign("receipt_id")->references("id")->on("receipts")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_files');
    }
};
