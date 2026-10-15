<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadMessage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MetaWhatsAppService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);
    }

    public function configured(): bool
    {
        return filled(config('meta_whatsapp.access_token')) && filled(config('meta_whatsapp.phone_number_id'));
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('meta_whatsapp.app_secret');
        if ($secret === '') {
            return true;
        }
        if (!$signature || !str_starts_with($signature, 'sha256=')) {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public function sendText(Lead $lead, string $text, string $senderType = 'bot', ?int $senderUserId = null): LeadMessage
    {
        return $this->send($lead, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $lead->whatsapp_id,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $text],
        ], $text, 'text', $senderType, $senderUserId);
    }

    public function sendTemplate(Lead $lead, string $templateName, string $language = 'en_GB', string $senderType = 'staff', ?int $senderUserId = null): LeadMessage
    {
        return $this->send($lead, [
            'messaging_product' => 'whatsapp',
            'to' => $lead->whatsapp_id,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ], '[Template: ' . $templateName . ']', 'template', $senderType, $senderUserId);
    }

    public function sendButtons(Lead $lead, string $body, array $buttons, string $senderType = 'bot'): LeadMessage
    {
        $actions = [];
        foreach (array_slice($buttons, 0, 3) as $id => $title) {
            $actions[] = [
                'type' => 'reply',
                'reply' => ['id' => (string) $id, 'title' => mb_substr((string) $title, 0, 20)],
            ];
        }

        return $this->send($lead, [
            'messaging_product' => 'whatsapp',
            'to' => $lead->whatsapp_id,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $actions],
            ],
        ], $body, 'interactive_button', $senderType);
    }

    public function sendList(Lead $lead, string $body, string $buttonText, array $rows, string $senderType = 'bot'): LeadMessage
    {
        $listRows = [];
        foreach (array_slice($rows, 0, 10) as $id => $title) {
            $listRows[] = ['id' => (string) $id, 'title' => mb_substr((string) $title, 0, 24)];
        }

        return $this->send($lead, [
            'messaging_product' => 'whatsapp',
            'to' => $lead->whatsapp_id,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $body],
                'action' => [
                    'button' => mb_substr($buttonText, 0, 20),
                    'sections' => [[
                        'title' => 'Choose one',
                        'rows' => $listRows,
                    ]],
                ],
            ],
        ], $body, 'interactive_list', $senderType);
    }

    private function send(Lead $lead, array $payload, string $displayBody, string $messageType, string $senderType, ?int $senderUserId = null): LeadMessage
    {
        if (!$this->configured()) {
            throw new RuntimeException('Meta WhatsApp is not configured.');
        }

        $url = rtrim(config('meta_whatsapp.base_url'), '/') . '/' . config('meta_whatsapp.graph_version') . '/' . config('meta_whatsapp.phone_number_id') . '/messages';

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('meta_whatsapp.access_token'),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            $data = json_decode((string) $response->getBody(), true) ?: [];
            $providerId = data_get($data, 'messages.0.id');

            $message = LeadMessage::create([
                'lead_id' => $lead->id,
                'provider_message_id' => $providerId,
                'direction' => 'outbound',
                'sender_type' => $senderType,
                'sender_user_id' => $senderUserId,
                'message_type' => $messageType,
                'body' => $displayBody,
                'interactive_payload' => $payload['interactive'] ?? null,
                'delivery_status' => 'sent',
            ]);

            $lead->update(['last_outbound_at' => now()]);
            return $message;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            LeadMessage::create([
                'lead_id' => $lead->id,
                'direction' => 'outbound',
                'sender_type' => $senderType,
                'sender_user_id' => $senderUserId,
                'message_type' => $messageType,
                'body' => $displayBody,
                'interactive_payload' => $payload['interactive'] ?? null,
                'delivery_status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
};
