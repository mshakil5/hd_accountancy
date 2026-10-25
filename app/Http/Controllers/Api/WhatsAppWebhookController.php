<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingLeadMessage;
use App\Models\Lead;
use App\Models\LeadMessage;
use App\Services\MetaWhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $configured = (string) config('meta_whatsapp.verify_token');
        if ($configured !== '' && $token !== null && $token !== ''
            && $mode === 'subscribe' && hash_equals($configured, (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }
        return response('Forbidden', 403);
    }

    public function receive(Request $request, MetaWhatsAppService $meta)
    {
        $raw = $request->getContent();
        if (!$meta->verifySignature($raw, $request->header('X-Hub-Signature-256'))) {
            Log::warning('Invalid Meta webhook signature', ['ip' => $request->ip()]);
            return response()->json(['ok' => false], 403);
        }

        $payload = $request->json()->all();
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $this->handleStatuses($value['statuses'] ?? []);
                foreach ($value['messages'] ?? [] as $message) {
                    $this->handleMessage($value, $message);
                }
            }
        }
        return response()->json(['ok' => true]);
    }

    private function handleMessage(array $value, array $message): void
    {
        $providerId = $message['id'] ?? null;
        if ($providerId && LeadMessage::where('provider_message_id', $providerId)->exists()) return;

        $from = (string) ($message['from'] ?? '');
        if ($from === '') return;

        $contact = collect($value['contacts'] ?? [])->firstWhere('wa_id', $from) ?: [];
        $name = data_get($contact, 'profile.name');

        // Meta sends ad referrals with source_type=ad. Posts and other
        // referral types must not be treated as ad attribution.
        $referral = $message['referral'] ?? [];
        $isAdReferral = !empty($referral) && ($referral['source_type'] ?? 'ad') === 'ad';

        $lead = Lead::where('whatsapp_id', $from)->open()->latest('id')->first();

        // A new ad click after the bot finished is a fresh enquiry. Never
        // overwrite the completed lead's campaign attribution with it. While a
        // human has the old conversation open, keep the reply there instead of
        // starting a second qualification thread.
        if ($lead && $lead->automation_completed_at && !$lead->human_takeover && $isAdReferral) {
            $lead = null;
        }

        if (!$lead) {
            $inferredIntent = $isAdReferral ? $this->inferIntentFromReferral($referral) : null;
            $lead = Lead::create([
                'name' => $name,
                'whatsapp_id' => $from,
                'phone' => $from,
                'source' => $isAdReferral ? 'meta_ad_whatsapp' : 'whatsapp',
                'click_id' => $isAdReferral ? ($referral['ctwa_clid'] ?? null) : null,
                'ad_id' => $isAdReferral ? ($referral['source_id'] ?? null) : null,
                'ad_headline' => $isAdReferral ? ($referral['headline'] ?? null) : null,
                'ad_body' => $isAdReferral ? ($referral['body'] ?? null) : null,
                'source_url' => $isAdReferral ? ($referral['source_url'] ?? null) : null,
                'qualification_status' => 'NEW',
                'sales_status' => 'NEW',
                'conversation_step' => $inferredIntent ? 'person_type' : 'intent',
                'intent' => $inferredIntent,
            ]);
        } elseif (!$lead->name && $name) {
            $lead->name = $name;
            $lead->save();
        }

        if ($isAdReferral) {
            if (!$lead->intent) {
                $lead->intent = $this->inferIntentFromReferral($referral);
                if ($lead->intent && $lead->conversation_step === 'intent') {
                    $lead->conversation_step = 'person_type';
                }
            }

            $lead->fill(array_filter([
                'source' => 'meta_ad_whatsapp',
                'click_id' => $referral['ctwa_clid'] ?? $lead->click_id,
                'ad_id' => $referral['source_id'] ?? $lead->ad_id,
                'ad_headline' => $referral['headline'] ?? $lead->ad_headline,
                'ad_body' => $referral['body'] ?? $lead->ad_body,
                'source_url' => $referral['source_url'] ?? $lead->source_url,
            ], fn($v) => $v !== null && $v !== ''));
        }
        $lead->last_inbound_at = now();
        $lead->save();

        [$body, $type, $interactive] = $this->normaliseMessage($message);
        try {
            $leadMessage = LeadMessage::create([
                'lead_id' => $lead->id,
                'provider_message_id' => $providerId,
                'direction' => 'inbound',
                'sender_type' => 'prospect',
                'message_type' => $type,
                'body' => $body,
                'interactive_payload' => $interactive,
                'raw_payload' => $message,
                'delivery_status' => 'received',
                'provider_timestamp' => isset($message['timestamp']) ? Carbon::createFromTimestamp((int) $message['timestamp']) : now(),
            ]);
        } catch (QueryException $e) {
            // Meta can deliver the same webhook twice at the same time; the
            // unique provider_message_id index is the final guard. A duplicate
            // is a success, not a 500 that makes Meta retry again.
            if ((string) $e->getCode() === '23000') return;
            throw $e;
        }

        // Manual takeover suppresses automation entirely. A completed lead is
        // still queued so the team gets a follow-up alert about the reply.
        if (!$lead->human_takeover) {
            ProcessIncomingLeadMessage::dispatch($leadMessage->id, $lead->id)->onQueue('leads');
        }
    }

    private function normaliseMessage(array $message): array
    {
        $type = $message['type'] ?? 'unknown';
        if ($type === 'text') return [(string) data_get($message, 'text.body', ''), 'text', null];
        if ($type === 'button') return [(string) data_get($message, 'button.text', ''), 'button', ['id' => data_get($message, 'button.payload')]];
        if ($type === 'interactive') {
            $it = data_get($message, 'interactive.type');
            $reply = $it === 'button_reply' ? data_get($message, 'interactive.button_reply', []) : data_get($message, 'interactive.list_reply', []);
            return [(string) ($reply['title'] ?? ''), 'interactive', ['id' => $reply['id'] ?? null, 'title' => $reply['title'] ?? null]];
        }
        return ['[' . $type . ' message]', $type, null];
    }

    private function inferIntentFromReferral(array $referral): ?string
    {
        $text = mb_strtolower(trim(($referral['headline'] ?? '') . ' ' . ($referral['body'] ?? '')));
        if ($text === '') return null;
        if (preg_match('/change|switch|new accountant|current accountant/', $text)) return 'change_accountant';
        if (preg_match('/landlord|property|rental/', $text)) return 'landlord_property';
        if (preg_match('/hmrc|investigation|penalty|tax problem/', $text)) return 'hmrc_issue';
        if (preg_match('/vat|payroll|bookkeep|tax return|accounts/', $text)) return 'tax_vat_payroll';
        if (preg_match('/accountant/', $text)) return 'need_accountant';
        return null;
    }

    private function handleStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $id = $status['id'] ?? null;
            if (!$id) continue;
            $message = LeadMessage::where('provider_message_id', $id)->first();
            if (!$message) continue;
            $message->delivery_status = $status['status'] ?? $message->delivery_status;
            if (!empty($status['errors'])) $message->failure_reason = json_encode($status['errors']);
            $message->save();
        }
    }
};
