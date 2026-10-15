<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction', 20); // inbound / outbound
            $table->string('sender_type', 20)->default('prospect'); // prospect / bot / staff / system
            $table->unsignedBigInteger('sender_user_id')->nullable();
            $table->string('message_type', 40)->default('text');
            $table->text('body')->nullable();
            $table->json('interactive_payload')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('delivery_status', 30)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('provider_timestamp')->nullable();
            $table->timestamps();

            $table->foreign('sender_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_messages');
    }
};
