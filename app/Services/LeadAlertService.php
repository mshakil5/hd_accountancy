<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Getway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

class LeadAlertService
{
    public function notify(Lead $lead): void
    {
        $summary = $this->summary($lead);
        $email = config('lead_qualification.alert_email');
        if ($email) {
            try {
                Mail::raw($summary, function ($message) use ($email, $lead) {
                    $message->to($email)->subject('HD Accountancy: ' . $lead->qualification_status . ' WhatsApp lead');
                });
            } catch (\Throwable $e) {
                Log::warning('Lead alert email failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        if (config('lead_qualification.alert_sms_enabled') && config('lead_qualification.alert_phone')) {
            $this->sendSms(config('lead_qualification.alert_phone'), mb_substr($summary, 0, 1450));
        }
    }

    private function sendSms(string $to, string $body): void
    {
        try {
            $sid = env('TWILIO_ACCOUNT_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $from = env('TWILIO_SMS_FROM');
            if (!$sid || !$token || !$from) {
                $gateway = Getway::where('name', 'Twilio')->first();
                $sid = $sid ?: data_get($gateway, 'clientid');
                $token = $token ?: data_get($gateway, 'secretid');
            }
            if (!$sid || !$token || !$from) return;
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
};
