<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campaign intent is controlled by an explicit mapping, not by guessing
     * from ad marketing copy. An ad/campaign with no row here is treated as a
     * general advert: intent stays empty and the bot asks the first question.
     */
    public function up(): void
    {
        Schema::create('meta_ad_intents', function (Blueprint $table) {
            $table->id();
            $table->string('ad_id')->unique();
            $table->string('campaign_id')->nullable()->index();
            $table->string('intent');
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ad_intents');
    }
};
