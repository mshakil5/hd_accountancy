<?php

namespace App\Services;

use App\Models\Lead;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MetaAdAttributionService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10, 'connect_timeout' => 5]);
    }

    public function enrich(Lead $lead): void
    {
        if (!$lead->ad_id || $lead->campaign_id) return;
        $token = env('META_ADS_ACCESS_TOKEN') ?: config('meta_whatsapp.access_token');
        if (!$token) return;

        $url = rtrim(config('meta_whatsapp.base_url'), '/') . '/' . config('meta_whatsapp.graph_version') . '/' . $lead->ad_id;
        try {
            $response = $this->client->get($url, [
                'query' => [
                    'fields' => 'id,name,adset{id,name},campaign{id,name}',
                    'access_token' => $token,
                ],
            ]);
            $data = json_decode((string) $response->getBody(), true) ?: [];
            $lead->update([
                'ad_name' => $data['name'] ?? $lead->ad_name,
                'adset_id' => data_get($data, 'adset.id', $lead->adset_id),
                'adset_name' => data_get($data, 'adset.name', $lead->adset_name),
                'campaign_id' => data_get($data, 'campaign.id', $lead->campaign_id),
                'campaign_name' => data_get($data, 'campaign.name', $lead->campaign_name),
            ]);
        } catch (\Throwable $e) {
            Log::info('Meta ad attribution enrichment unavailable', ['lead_id' => $lead->id, 'ad_id' => $lead->ad_id, 'error' => $e->getMessage()]);
        }
    }
}
