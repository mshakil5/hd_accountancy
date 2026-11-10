<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaAdIntent;
use App\Services\MetaAdIntentResolver;
use Illuminate\Http\Request;

class MetaAdIntentController extends Controller
{
    public function __construct(private MetaAdIntentResolver $resolver) {}

    public function index()
    {
        $mappings = MetaAdIntent::orderByDesc('is_active')->orderBy('ad_id')->get();
        $intents = config('lead_qualification.intent_options');

        return view('admin.leads.ad_intents', compact('mappings', 'intents'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $existing = MetaAdIntent::where('ad_id', $data['ad_id'])->first();
        $mapping = MetaAdIntent::updateOrCreate(['ad_id' => $data['ad_id']], $data);

        $this->resolver->forget($mapping->ad_id, $mapping->campaign_id);
        if ($existing) $this->resolver->forget($existing->ad_id, $existing->campaign_id);

        return back()->with('success', "Ad {$mapping->ad_id} now routes to {$mapping->intent}.");
    }

    public function update(Request $request, MetaAdIntent $mapping)
    {
        $data = $this->validated($request, $mapping->id);

        $previousAdId = $mapping->ad_id;
        $previousCampaign = $mapping->campaign_id;
        $mapping->update($data);

        $this->resolver->forget($mapping->ad_id, $mapping->campaign_id);
        $this->resolver->forget($previousAdId, $previousCampaign);

        return back()->with('success', "Mapping for ad {$mapping->ad_id} updated.");
    }

    public function destroy(MetaAdIntent $mapping)
    {
        $adId = $mapping->ad_id;
        $campaignId = $mapping->campaign_id;
        $mapping->delete();
        $this->resolver->forget($adId, $campaignId);

        return back()->with('success', "Mapping for ad {$adId} removed. That advert will now ask the first question.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $intents = array_keys(config('lead_qualification.intent_options'));

        $data = $request->validate([
            'ad_id' => 'required|string|max:255|unique:meta_ad_intents,ad_id' . ($ignoreId ? ',' . $ignoreId : ''),
            'campaign_id' => 'nullable|string|max:255',
            'intent' => 'required|in:' . implode(',', $intents),
            'note' => 'nullable|string|max:255',
        ], [
            'ad_id.unique' => 'This Ad ID already has a mapping. Edit the existing row instead.',
            'intent.in' => 'Choose one of the qualification intents.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
