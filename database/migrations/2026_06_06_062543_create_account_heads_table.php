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
        Schema::create('account_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_type_id')->constrained('account_types')->onDelete('cascade');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onDelete('set null');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_heads');
    }
};
