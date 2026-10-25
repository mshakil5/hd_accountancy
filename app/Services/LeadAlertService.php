<?php

namespace App\Services;

use App\Models\Getway;
use App\Models\Lead;
use App\Models\LeadMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

class LeadAlertService
{
    public function notify(Lead $lead): void
    {
        $this->deliver(
            'HD Accountancy: ' . $lead->qualification_status . ' WhatsApp lead',
            $this->summary($lead)
        );
    }

    /**
     * A completed lead has replied again. The bot must not restart, but the
     * team still needs to know about the new message.
     */
    public function notifyFollowUp(Lead $lead, LeadMessage $message): void
    {
        $preview = trim((string) $message->body) ?: '[' . $message->message_type . ' message]';

        $this->deliver(
            'HD Accountancy: new WhatsApp reply',
            "New reply from a completed WhatsApp lead\n"
            . "Name: " . ($lead->name ?: 'WhatsApp prospect') . "\n"
            . "WhatsApp: {$lead->whatsapp_id}\n"
            . "Campaign: " . ($lead->campaign_name ?: $lead->ad_name ?: $lead->ad_headline ?: 'Direct/unknown') . "\n"
            . "Message: " . mb_substr($preview, 0, 900) . "\n"
            . "Admin: " . url('/admin/leads/' . $lead->id)
        );
    }

    public function notifyProcessingFailure(Lead $lead, string $reason): void
    {
        $this->deliver(
            'HD Accountancy: WhatsApp lead processing failed',
            "A WhatsApp lead could not be processed after all queue retries.\n"
            . "Name: " . ($lead->name ?: 'WhatsApp prospect') . "\n"
            . "WhatsApp: {$lead->whatsapp_id}\n"
            . "Reason: " . mb_substr($reason, 0, 900) . "\n"
            . "Admin: " . url('/admin/leads/' . $lead->id)
        );
    }

    private function deliver(string $subject, string $body): void
    {
        $email = config('lead_qualification.alert_email');
        if ($email) {
            try {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::warning('Lead alert email failed', ['subject' => $subject, 'error' => $e->getMessage()]);
            }
        }

        if (config('lead_qualification.alert_sms_enabled') && config('lead_qualification.alert_phone')) {
            $this->sendSms(config('lead_qualification.alert_phone'), mb_substr($body, 0, 1450));
        }
    }

    private function sendSms(string $to, string $body): void
    {
        try {
            $sid = config('lead_qualification.twilio_sid');
            $token = config('lead_qualification.twilio_token');
            $from = config('lead_qualification.twilio_from');
            if (!$sid || !$token || !$from) {
                $gateway = Getway::where('name', 'Twilio')->first();
                $sid = $sid ?: data_get($gateway, 'clientid');
                $token = $token ?: data_get($gateway, 'secretid');
            }
            if (!$sid || !$token || !$from) {
                Log::warning('Lead alert SMS skipped: Twilio SID/token/from is incomplete.');
                return;
            }
            (new TwilioClient($sid, $token))->messages->create($to, ['from' => $from, 'body' => $body]);
        } catch (\Throwable $e) {
            Log::warning('Lead alert SMS failed', ['error' => $e->getMessage()]);
        }
    }

    public function summary(Lead $lead): string
    {
        $intent = config('lead_qualification.intent_options.' . $lead->intent, $lead->intent ?: 'Unknown');
        $time = config('lead_qualification.timeframe_options.' . $lead->contact_timeframe, $lead->contact_timeframe ?: 'Unknown');
        return "{$lead->qualification_status} LEAD\n"
            . "Name: " . ($lead->name ?: 'WhatsApp prospect') . "\n"
            . "WhatsApp: {$lead->whatsapp_id}\n"
            . "Requirement: {$intent}\n"
            . "Timeframe: {$time}\n"
            . "Campaign: " . ($lead->campaign_name ?: $lead->ad_name ?: $lead->ad_headline ?: 'Direct/unknown') . "\n"
            . "Summary: " . ($lead->ai_summary ?: 'Qualification completed.') . "\n"
            . "Admin: " . url('/admin/leads/' . $lead->id);
    }
}
