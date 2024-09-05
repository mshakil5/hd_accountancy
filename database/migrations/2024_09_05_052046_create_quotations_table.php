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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('business_type')->nullable();
            $table->decimal('turnover', 15, 2)->nullable();
            $table->string('vat_returns')->nullable();
            $table->string('payroll')->nullable(); 
            $table->string('bookkeeping')->nullable();
            $table->string('bookkeeping_software')->nullable();
            $table->string('management_account')->nullable();
            $table->integer('bank_accounts')->nullable();
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
        Schema::dropIfExists('quotations');
    }
};
