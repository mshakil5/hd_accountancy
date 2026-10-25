<?php

namespace App\Jobs;

use App\Models\LeadMessage;
use App\Services\LeadAlertService;
use App\Services\LeadConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingLeadMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 60, 180];
    public int $timeout = 60;

    public function __construct(public int $leadMessageId, public int $leadId = 0) {}

    /**
     * Serialise all turns for one lead. Without this, two quick messages can be
     * processed by different workers at the same time and send duplicate
     * questions or overwrite each other's qualification state.
     *
     * Jobs queued before `leadId` was added fall back to the message id so they
     * still run safely.
     */
    public function middleware(): array
    {
        $key = $this->leadId > 0 ? 'lead-' . $this->leadId : 'lead-message-' . $this->leadMessageId;

        return [
            (new WithoutOverlapping($key))
                ->releaseAfter(10)
                ->expireAfter(120),
        ];
    }

    public function handle(LeadConversationService $conversation): void
    {
        $message = LeadMessage::with('lead')->find($this->leadMessageId);
        if (!$message || $message->direction !== 'inbound') return;
        if ($this->leadId > 0 && (int) $message->lead_id !== $this->leadId) return;
        if ($message->delivery_status === 'processed') return;

        $conversation->process($message);

        // Mark only after the whole turn (including outbound sends) succeeded,
        // so a failed attempt can still be retried by the queue.
        $message->forceFill(['delivery_status' => 'processed'])->save();
    }

    public function failed(\Throwable $exception): void
    {
        $message = LeadMessage::with('lead')->find($this->leadMessageId);
        if (!$message || $message->delivery_status === 'processed') return;

        $message->forceFill([
            'delivery_status' => 'failed',
            'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
        ])->save();

        if ($message->lead) {
            app(LeadAlertService::class)->notifyProcessingFailure($message->lead, $exception->getMessage());
        }
    }
}
