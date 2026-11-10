<?php

namespace App\Services;

use App\Models\Getway;
use App\Models\Lead;
use App\Models\LeadMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

/**
 * Team alerts.
 *
 * The operating model relies on the HD team being told about a lead, so an
 * alert failure must never look like a success. Every attempt is recorded on
 * the lead (alert_status / alert_last_error / alert_last_attempt_at) and every
 * method returns the resulting status so callers and admins can react.
 */
class LeadAlertService
{
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NOT_CONFIGURED = 'not_configured';

    public function notify(Lead $lead): ?string
    {
        return $this->deliver(
            $lead,
            'HD Accountancy: ' . $lead->qualification_status . ' WhatsApp lead',
            $this->summary($lead)
        );
    }

    /**
     * A completed lead has replied again. The bot must not restart, but the
     * team still needs to know about the new message.
     */
    public function notifyFollowUp(Lead $lead, LeadMessage $message): ?string
    {
        $preview = trim((string) $message->body) ?: '[' . $message->message_type . ' message]';

        return $this->deliver(
            $lead,
            'HD Accountancy: new WhatsApp reply',
            "New reply from a completed WhatsApp lead\n"
            . "Name: " . ($lead->name ?: 'WhatsApp prospect') . "\n"
            . "WhatsApp: {$lead->whatsapp_id}\n"
            . "Campaign: " . ($lead->campaign_name ?: $lead->ad_name ?: $lead->ad_headline ?: 'Direct/unknown') . "\n"
            . "Message: " . mb_substr($preview, 0, 900) . "\n"
            . "Admin: " . url('/admin/leads/' . $lead->id)
        );
    }

    public function notifyProcessingFailure(Lead $lead, string $reason): ?string
    {
        return $this->deliver(
            $lead,
            'HD Accountancy: WhatsApp lead processing failed',
            "A WhatsApp lead could not be processed after all queue retries.\n"
            . "Name: " . ($lead->name ?: 'WhatsApp prospect') . "\n"
            . "WhatsApp: {$lead->whatsapp_id}\n"
            . "Reason: " . mb_substr($reason, 0, 900) . "\n"
            . "Admin: " . url('/admin/leads/' . $lead->id)
        );
    }

    /**
     * Health-page test send. Returns ['ok' => bool, 'message' => string].
     */
    public function sendTestAlert(string $channel): array
    {
        if ($channel === 'email') {
            $to = config('lead_qualification.alert_email');
            if (!$to) {
                return ['ok' => false, 'message' => 'No LEAD_ALERT_EMAIL is configured.'];
            }
            try {
                Mail::raw(
                    "This is a test alert from the HD Accountancy lead system.\n\nSent " . now()->toDateTimeString(),
                    fn($message) => $message->to($to)->subject('HD Accountancy: lead alert test')
                );
                return ['ok' => true, 'message' => "Test email accepted for delivery to {$to}."];
            } catch (\Throwable $e) {
                Log::warning('Lead alert test email failed', ['error' => $e->getMessage()]);
                return ['ok' => false, 'message' => 'Test email failed: ' . mb_substr($e->getMessage(), 0, 300)];
            }
        }

        if ($channel === 'sms') {
            if (!config('lead_qualification.alert_sms_enabled')) {
                return ['ok' => false, 'message' => 'SMS alerts are disabled (LEAD_ALERT_SMS_ENABLED).'];
            }
            $to = config('lead_qualification.alert_phone');
            if (!$to) {
                return ['ok' => false, 'message' => 'No LEAD_ALERT_PHONE is configured.'];
            }
            $error = $this->sendSms($to, 'HD Accountancy lead alert test — ' . now()->format('d M H:i'));
            return $error
                ? ['ok' => false, 'message' => 'Test SMS failed: ' . mb_substr($error, 0, 300)]
                : ['ok' => true, 'message' => "Test SMS sent to {$to}."];
        }

        return ['ok' => false, 'message' => 'Unknown alert channel.'];
    }

    private function deliver(Lead $lead, string $subject, string $body): ?string
    {
        $attempted = 0;
        $failed = 0;
        $errors = [];

        $email = config('lead_qualification.alert_email');
        if ($email) {
            $attempted++;
            try {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = 'Email: ' . $e->getMessage();
                Log::warning('Lead alert email failed', ['subject' => $subject, 'error' => $e->getMessage()]);
            }
        }

        if (config('lead_qualification.alert_sms_enabled') && config('lead_qualification.alert_phone')) {
            $attempted++;
            $error = $this->sendSms(config('lead_qualification.alert_phone'), mb_substr($body, 0, 1450));
            if ($error) {
                $failed++;
                $errors[] = 'SMS: ' . $error;
            }
        }

        $status = match (true) {
            $attempted === 0 => self::STATUS_NOT_CONFIGURED,
            $failed === 0 => self::STATUS_SENT,
            $failed < $attempted => self::STATUS_PARTIAL,
            default => self::STATUS_FAILED,
        };

        // Record on the lead so the admin dashboard shows a real delivery state
        // instead of assuming the promised alert arrived.
        $lead->forceFill([
            'alert_status' => $status,
            'alert_last_error' => $errors ? mb_substr(implode("\n", $errors), 0, 2000) : null,
            'alert_last_attempt_at' => now(),
        ])->save();

        return $status;
    }

    /**
     * @return string|null Error message, or null when Twilio accepted the SMS.
     */
    private function sendSms(string $to, string $body): ?string
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
                return 'Twilio SID/token/from is incomplete.';
            }
            (new TwilioClient($sid, $token))->messages->create($to, ['from' => $from, 'body' => $body]);
            return null;
        } catch (\Throwable $e) {
            Log::warning('Lead alert SMS failed', ['error' => $e->getMessage()]);
            return $e->getMessage();
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
            . "Summary: " . ($lead->qualification_summary ?: 'Qualification completed.') . "\n"
            . "Admin: " . url('/admin/leads/' . $lead->id);
    }
}
