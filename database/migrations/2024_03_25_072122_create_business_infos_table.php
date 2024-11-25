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
        Schema::create('business_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->string('nature_of_business')->nullable();
            $table->string('company_number')->nullable();
            $table->date('year_end_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('confirmation_due_date')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('company_utr')->nullable();
            $table->integer('vat_number',30)->nullable();
            $table->integer('hmrc_authorisation')->default(1);
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
        Schema::dropIfExists('business_infos');
    }
};
