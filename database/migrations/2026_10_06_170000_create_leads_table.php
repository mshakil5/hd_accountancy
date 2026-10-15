<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('whatsapp_id', 50)->index();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('source')->default('whatsapp');
            $table->string('click_id')->nullable()->index();
            $table->string('campaign_id')->nullable()->index();
            $table->string('campaign_name')->nullable();
            $table->string('adset_id')->nullable()->index();
            $table->string('adset_name')->nullable();
            $table->string('ad_id')->nullable()->index();
            $table->string('ad_name')->nullable();
            $table->string('ad_headline')->nullable();
            $table->text('ad_body')->nullable();
            $table->text('source_url')->nullable();
            $table->string('intent')->nullable()->index();
            $table->string('person_type')->nullable();
            $table->string('current_accountant')->nullable();
            $table->string('contact_timeframe')->nullable();
            $table->json('qualification_data')->nullable();
            $table->string('qualification_status')->default('NEW')->index();
            $table->string('sales_status')->default('NEW')->index();
            $table->string('conversation_step')->default('intent');
            $table->text('ai_summary')->nullable();
            $table->boolean('human_takeover')->default(false)->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->index(['whatsapp_id', 'qualification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
