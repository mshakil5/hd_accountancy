<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadAlertService;
use App\Services\MetaWhatsAppService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with('assignee')->latest('last_inbound_at')->latest('id');
        if ($request->filled('status')) $query->where('qualification_status', $request->status);
        if ($request->filled('sales')) $query->where('sales_status', $request->sales);
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('whatsapp_id', 'like', "%{$q}%")
                    ->orWhere('ad_headline', 'like', "%{$q}%")
                    ->orWhere('ai_summary', 'like', "%{$q}%");
            });
        }
        $leads = $query->paginate(30)->withQueryString();
        $counts = Lead::selectRaw('qualification_status, count(*) as total')->groupBy('qualification_status')->pluck('total','qualification_status');
        return view('admin.leads.index', compact('leads','counts'));
    }

    public function show(Lead $lead)
    {
        $lead->load(['messages.sender','assignee']);
        return view('admin.leads.show', compact('lead'));
    }

    public function takeOver(Lead $lead)
    {
        $lead->update(['human_takeover' => true, 'assigned_to' => auth()->id()]);
        return back()->with('success', 'Human takeover enabled. Automated replies are now paused.');
    }

    public function release(Lead $lead)
    {
        $lead->update(['human_takeover' => false]);
        return back()->with('success', 'Automation re-enabled for future inbound messages.');
    }

    public function send(Request $request, Lead $lead, MetaWhatsAppService $whatsApp)
    {
        $request->validate(['message' => 'required|string|max:4096']);
        if (!$lead->isWithinWhatsAppWindow()) {
            return back()->withErrors(['message' => 'The 24-hour WhatsApp customer-service window has expired. Send an approved WhatsApp template from Meta before sending free-form messages.']);
        }
        $lead->update(['human_takeover' => true, 'assigned_to' => auth()->id()]);
        $whatsApp->sendText($lead, $request->message, 'staff', auth()->id());
        return back()->with('success', 'WhatsApp message sent.');
    }

    public function sendReengagementTemplate(Lead $lead, MetaWhatsAppService $whatsApp)
    {
        $template = config('meta_whatsapp.reengagement_template');
        if (!$template) {
            return back()->withErrors(['message' => 'No approved re-engagement template is configured.']);
        }
        $lead->update(['human_takeover' => true, 'assigned_to' => auth()->id()]);
        $whatsApp->sendTemplate($lead, $template, config('meta_whatsapp.reengagement_template_language'), 'staff', auth()->id());
        return back()->with('success', 'Approved WhatsApp template sent.');
    }

    public function update(Request $request, Lead $lead)
    {
        $request->validate([
            'qualification_status' => 'nullable|in:NEW,QUALIFYING,GENUINE,REVIEW,REJECTED,EXISTING_CLIENT,NON_SALES',
            'sales_status' => 'nullable|in:NEW,CONTACTED,CONSULTATION_BOOKED,PROPOSAL_SENT,WON,LOST,CLOSED',
            'admin_notes' => 'nullable|string|max:10000',
        ]);
        $data = $request->only(['qualification_status','sales_status','admin_notes']);
        if (($data['sales_status'] ?? null) === 'CONTACTED' && !$lead->contacted_at) $data['contacted_at'] = now();
        if (in_array($data['sales_status'] ?? '', ['WON','LOST','CLOSED'], true)) $data['closed_at'] = now();
        $lead->update(array_filter($data, fn($v) => $v !== null));
        return back()->with('success', 'Lead updated.');
    }

    public function notifyAgain(Lead $lead, LeadAlertService $alerts)
    {
        $alerts->notify($lead);
        return back()->with('success', 'Lead notification sent.');
    }

    public function health(MetaWhatsAppService $whatsApp)
    {
        $checks = [
            'Meta access token' => filled(config('meta_whatsapp.access_token')),
            'Meta phone number ID' => filled(config('meta_whatsapp.phone_number_id')),
            'Meta verify token' => filled(config('meta_whatsapp.verify_token')),
            'Meta app secret' => filled(config('meta_whatsapp.app_secret')),
            'Re-engagement template (optional)' => filled(config('meta_whatsapp.reengagement_template')),
            'OpenAI API key' => filled(config('openai.api_key')),
            'Lead alert email or phone' => filled(config('lead_qualification.alert_email')) || filled(config('lead_qualification.alert_phone')),
            'Queue is asynchronous' => config('queue.default') !== 'sync',
        ];
        return view('admin.leads.health', compact('checks'));
    }
};
