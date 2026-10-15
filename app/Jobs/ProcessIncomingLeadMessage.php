<?php

namespace App\Jobs;

use App\Models\LeadMessage;
use App\Services\LeadConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingLeadMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 60, 180];
    public int $timeout = 60;

    public function __construct(public int $leadMessageId) {}

    public function handle(LeadConversationService $conversation): void
    {
        $message = LeadMessage::with('lead')->find($this->leadMessageId);
        if (!$message || $message->direction !== 'inbound') return;
        $conversation->process($message);
    }
};
