<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingLeadMessage;
use App\Models\Lead;
use App\Models\LeadMessage;
use App\Services\MetaAdIntentResolver;
use App\Services\MetaWhatsAppService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private MetaAdIntentResolver $adIntents) {}

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

        $lead = $this->resolveLead($from, $name, $referral, $isAdReferral);

        if ($isAdReferral) {
            $adId = $referral['source_id'] ?? null;

            if (!$lead->intent) {
                $lead->intent = $this->adIntents->forReferral($referral);
                if ($lead->intent) {
                    $lead->intent_source = 'ad_mapping';
                    if ($lead->conversation_step === 'intent') {
                        $lead->conversation_step = 'person_type';
                    }
                }
            }

            if ($adId && $lead->ad_id && $adId !== $lead->ad_id) {
                // A different advert than the one that acquired this contact.
                if (!$lead->original_ad_id) {
                    // The lead predates the original_* columns: the attribution
                    // already on the record IS the first touch, so preserve it
                    // before the later click is recorded.
                    $lead->original_ad_id = $lead->ad_id;
                    $lead->original_click_id = $lead->click_id;
                    $lead->original_campaign_id = $lead->campaign_id;
                    $lead->original_source_url = $lead->source_url;
                }
                // Never rewrite the original acquisition; keep the extra click
                // separately so campaign reporting still credits advert one.
                $lead->latest_touch_ad_id = $adId;
                $lead->latest_touch_click_id = $referral['ctwa_clid'] ?? null;
                $lead->latest_touch_at = now();
            } elseif ($adId && !$lead->original_ad_id) {
                // First touch: this contact had no acquisition source yet, so
                // it is safe to record it now.
                $lead->fill(array_filter([
                    'source' => 'meta_ad_whatsapp',
                    'click_id' => $referral['ctwa_clid'] ?? $lead->click_id,
                    'ad_id' => $adId,
                    'ad_headline' => $referral['headline'] ?? $lead->ad_headline,
                    'ad_body' => $referral['body'] ?? $lead->ad_body,
                    'source_url' => $referral['source_url'] ?? $lead->source_url,
                    'original_ad_id' => $adId,
                    'original_click_id' => $referral['ctwa_clid'] ?? null,
                    'original_source_url' => $referral['source_url'] ?? null,
                ], fn($v) => $v !== null && $v !== ''));
            }
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

    /**
     * Find the open lead for this number, or create it.
     *
     * Meta can deliver several messages from a brand new contact at the same
     * time. Without a per-number lock two webhook requests can both miss the
     * row and create two leads, splitting one conversation down the middle, so
     * the whole find-or-create runs under a short cache lock.
     */
    private function resolveLead(string $from, ?string $name, array $referral, bool $isAdReferral): Lead
    {
        $resolve = function () use ($from, $name, $referral, $isAdReferral): Lead {
            $lead = Lead::where('whatsapp_id', $from)->open()->latest('id')->first();

            // A new ad click after the bot finished is a fresh enquiry. Never
            // overwrite the completed lead's campaign attribution with it. While
            // a human has the old conversation open, keep the reply there
            // instead of starting a second qualification thread.
            if ($lead && $lead->automation_completed_at && !$lead->human_takeover && $isAdReferral) {
                $lead = null;
            }

            if (!$lead) {
                return $this->createLead($from, $name, $referral, $isAdReferral);
            }

            if (!$lead->name && $name) {
                $lead->name = $name;
                $lead->save();
            }

            return $lead;
        };

        try {
            $lock = Cache::lock('whatsapp-lead:' . $from, 10);
        } catch (\Throwable $e) {
            // A cache store without lock support must never block inbound leads.
            Log::warning('Lead creation lock unavailable; continuing without it', ['error' => $e->getMessage()]);
            return $resolve();
        }

        try {
            return $lock->block(3, $resolve);
        } catch (LockTimeoutException $e) {
            // The other request is still creating the lead; a plain retry of the
            // lookup is better than failing the webhook.
            return $resolve();
        }
    }

    private function createLead(string $from, ?string $name, array $referral, bool $isAdReferral): Lead
    {
        // Campaign intent is controlled by the explicit Ad ID mapping, never
        // guessed from the advert's marketing copy. An unmapped advert is a
        // general advert: intent stays empty and the bot asks question 1.
        $mappedIntent = $isAdReferral ? $this->adIntents->forReferral($referral) : null;

        return Lead::create([
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
            'conversation_step' => $mappedIntent ? 'person_type' : 'intent',
            'intent' => $mappedIntent,
            // The explicit Ad ID mapping is authoritative. Recording where the
            // intent came from lets the conversation refuse to overwrite it
            // with the pre-filled advert text.
            'intent_source' => $mappedIntent ? 'ad_mapping' : null,
            'original_ad_id' => $isAdReferral ? ($referral['source_id'] ?? null) : null,
            'original_click_id' => $isAdReferral ? ($referral['ctwa_clid'] ?? null) : null,
            'original_source_url' => $isAdReferral ? ($referral['source_url'] ?? null) : null,
        ]);
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
