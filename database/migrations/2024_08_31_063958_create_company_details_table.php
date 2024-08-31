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
        Schema::create('company_details', function (Blueprint $table) {
            $table->id();
            $table->string('company_name',191)->nullable();
            $table->string('company_logo',191)->nullable();
            $table->string('footer_logo',191)->nullable();
            $table->string('fav_icon',191)->nullable();
            $table->string('address1',191)->nullable();
            $table->string('address2',191)->nullable();
            $table->string('phone1',191)->nullable();
            $table->string('phone2',191)->nullable();
            $table->string('phone3',191)->nullable();
            $table->string('phone4',191)->nullable();
            $table->string('email1',191)->nullable();
            $table->string('email2',191)->nullable();
            $table->string('website',191)->nullable();
            $table->string('footer_content',191)->nullable();
            $table->string('footer_link',191)->nullable();
            $table->string('opening_time')->nullable();
            $table->string('header_content',191)->nullable();
            $table->string('google_play_link',191)->nullable();
            $table->string('google_appstore_link',191)->nullable();
            $table->string('tawkto',191)->nullable();
            $table->string('facebook',191)->nullable();
            $table->string('twitter',191)->nullable();
            $table->string('instagram',191)->nullable();
            $table->string('linkedin',191)->nullable();
            $table->string('youtube',191)->nullable();
            $table->longText('google_map')->nullable();
            $table->longText('about_us')->nullable();
            $table->string('currency',191)->nullable();
            $table->string('created_by',191)->nullable();
            $table->string('updated_by',191)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_details');
    }
};
