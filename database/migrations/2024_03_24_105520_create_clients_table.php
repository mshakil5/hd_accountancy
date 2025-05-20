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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('refid')->nullable();
            $table->unsignedBigInteger('client_type_id')->nullable();
            $table->foreign('client_type_id')->references('id')->on('client_types');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->foreign('manager_id')->references('id')->on('users')->where('type', 2)->onDelete('cascade');
            $table->string('photo')->nullable();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('business_name')->nullable();
            $table->string('utr_number')->nullable();
            $table->string('hmrc_authorization')->nullable();
            $table->string('ni_number')->nullable();
            $table->string('dob')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('address_line3')->nullable();
            $table->string('agreement_date')->nullable();
            $table->string('cessation_date')->nullable();
            $table->string('trading_address')->nullable();
            $table->longText('about_business')->nullable();
            $table->string('city')->nullable();
            $table->string('town')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->string('photo_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
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
        Schema::dropIfExists('clients');
    }
};
