<?php

namespace App\Services;

use App\Models\MetaAdIntent;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a WhatsApp referral to a qualification intent using the explicit
 * admin mapping (meta_ad_intents). It never inspects the ad headline/body.
 *
 * Unknown / unmapped ad => null => the first short question is asked.
 */
class MetaAdIntentResolver
{
    private const CACHE_TTL_MINUTES = 5;

    public function forAd(?string $adId): ?string
    {
        $adId = trim((string) $adId);
        if ($adId === '') return null;

        return $this->cached('meta-ad-intent:ad:' . $adId, fn() => $this->lookup(['ad_id' => $adId]));
    }

    /**
     * Campaign mapping is used only when the ad itself is not mapped. The
     * campaign id becomes available after Meta attribution enrichment.
     */
    public function forCampaign(?string $campaignId): ?string
    {
        $campaignId = trim((string) $campaignId);
        if ($campaignId === '') return null;

        return $this->cached('meta-ad-intent:campaign:' . $campaignId, fn() => $this->lookup(['campaign_id' => $campaignId]));
    }

    /**
     * A WhatsApp ad referral. Meta sends the ad id as source_id for
     * source_type=ad. Campaign/ad set ids are not part of the referral payload.
     */
    public function forReferral(array $referral): ?string
    {
        return $this->forAd($referral['source_id'] ?? null);
    }

    public function forget(?string $adId = null, ?string $campaignId = null): void
    {
        if ($adId) Cache::forget('meta-ad-intent:ad:' . trim($adId));
        if ($campaignId) Cache::forget('meta-ad-intent:campaign:' . trim($campaignId));
    }

    /**
     * Negative results are cached too (as false), so unmapped adverts do not
     * hit the database on every message.
     */
    private function cached(string $key, \Closure $resolve): ?string
    {
        $cached = Cache::get($key, '__miss__');
        if ($cached !== '__miss__') {
            return $cached ?: null;
        }

        $intent = $resolve();
        Cache::put($key, $intent ?: false, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $intent;
    }

    private function lookup(array $where): ?string
    {
        $intent = MetaAdIntent::query()
            ->active()
            ->where($where)
            ->orderByDesc('id')
            ->value('intent');

        return $intent ?: null;
    }
}
