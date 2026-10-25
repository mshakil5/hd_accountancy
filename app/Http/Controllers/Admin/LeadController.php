<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadMessage;
use App\Services\LeadAlertService;
use App\Services\MetaWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Admin-initiated clean-up. This is the manual alternative to a scheduled
     * retention prune: the admin can see every lead and deliberately empty the
     * list. It permanently removes all leads and their conversations.
     */
    public function clearAll(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:DELETE',
        ], [
            'confirmation.in' => 'Type DELETE in capitals to confirm.',
            'confirmation.required' => 'Type DELETE in capitals to confirm.',
        ]);

        $deleted = DB::transaction(function () {
            $count = Lead::count();
            LeadMessage::query()->delete();
            Lead::query()->delete();

            return $count;
        });

        Log::warning('All WhatsApp leads were deleted by an admin', [
            'admin_id' => auth()->id(),
            'leads_deleted' => $deleted,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.leads.index')
            ->with('success', $deleted === 1
                ? 'The lead and its WhatsApp messages were deleted.'
                : "All {$deleted} leads and their WhatsApp messages were deleted.");
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

        return back()->with('success', $lead->automation_completed_at
            ? 'Manual takeover released. Automation had already completed for this lead; new replies will alert the team.'
            : 'Automation re-enabled for future inbound messages.');
    }

    public function send(Request $request, Lead $lead, MetaWhatsAppService $whatsApp)
    {
        $request->validate(['message' => 'required|string|max:4096']);
        if (!$lead->isWithinWhatsAppWindow()) {
            return back()->withErrors(['message' => 'The 24-hour WhatsApp customer-service window has expired. Send an approved WhatsApp template from Meta before sending free-form messages.']);
        }
        $lead->update(['human_takeover' => true, 'assigned_to' => auth()->id()]);
        try {
            $whatsApp->sendText($lead, $request->message, 'staff', auth()->id());
        } catch (\Throwable $e) {
            Log::warning('Admin WhatsApp send failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['message' => 'WhatsApp could not send this message. Check the integration health page and try again.']);
        }
        return back()->with('success', 'WhatsApp message sent.');
    }

    public function sendReengagementTemplate(Lead $lead, MetaWhatsAppService $whatsApp)
    {
        $template = config('meta_whatsapp.reengagement_template');
        if (!$template) {
            return back()->withErrors(['message' => 'No approved re-engagement template is configured.']);
        }
        $lead->update(['human_takeover' => true, 'assigned_to' => auth()->id()]);
        try {
            $whatsApp->sendTemplate($lead, $template, config('meta_whatsapp.reengagement_template_language'), 'staff', auth()->id());
        } catch (\Throwable $e) {
            Log::warning('Admin WhatsApp template send failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['message' => 'The WhatsApp template could not be sent. Check the integration health page and try again.']);
        }
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
        $notesProvided = $request->exists('admin_notes');
        $notes = $request->input('admin_notes');
        unset($data['admin_notes']);

        // "nullable" lets an empty string through (and the web middleware turns
        // it into null); never write either into the status columns.
        foreach (['qualification_status', 'sales_status'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === '' || $data[$field] === null)) {
                unset($data[$field]);
            }
        }
        if (($data['sales_status'] ?? null) === 'CONTACTED' && !$lead->contacted_at) $data['contacted_at'] = now();

        // A lead moved back out of a closed sales status must not keep a stale
        // closed timestamp.
        if (array_key_exists('sales_status', $data)) {
            $data['closed_at'] = in_array($data['sales_status'], ['WON','LOST','CLOSED'], true) ? now() : null;
        }

        // Reopening qualification (anything other than a terminal status)
        // restarts automation on the next inbound message. Clear the previous
        // answers as well, otherwise the next message is classified from the
        // old data and the closing text is sent again without any questions.
        if (array_key_exists('qualification_status', $data)
            && !in_array($data['qualification_status'], ['GENUINE','REVIEW','REJECTED','EXISTING_CLIENT','NON_SALES'], true)) {
            $data['automation_completed_at'] = null;
            $data['qualified_at'] = null;
            $data['intent'] = null;
            $data['person_type'] = null;
            $data['current_accountant'] = null;
            $data['contact_timeframe'] = null;
            $data['qualification_data'] = null;
            $data['conversation_step'] = 'intent';
        }

        $attributes = array_filter($data, fn($v) => $v !== null);
        foreach (['closed_at', 'automation_completed_at', 'qualified_at', 'intent', 'person_type', 'current_accountant', 'contact_timeframe', 'qualification_data'] as $nullableField) {
            if (array_key_exists($nullableField, $data) && $data[$nullableField] === null) {
                $attributes[$nullableField] = null;
            }
        }
        if ($notesProvided) $attributes['admin_notes'] = $notes;
        $lead->update($attributes);
        return back()->with('success', 'Lead updated.');
    }

    public function notifyAgain(Lead $lead, LeadAlertService $alerts)
    {
        $alerts->notify($lead);
        return back()->with('success', 'Lead notification sent.');
    }

    public function health()
    {
        $checks = [
            'Meta access token' => filled(config('meta_whatsapp.access_token')),
            'Meta phone number ID' => filled(config('meta_whatsapp.phone_number_id')),
            'Meta verify token' => filled(config('meta_whatsapp.verify_token')),
            'Meta app secret' => filled(config('meta_whatsapp.app_secret')),
            'Re-engagement template (optional)' => filled(config('meta_whatsapp.reengagement_template')),
            'Lead alert email or phone' => filled(config('lead_qualification.alert_email')) || filled(config('lead_qualification.alert_phone')),
            'Queue is asynchronous' => config('queue.default') !== 'sync',
            'Database queue table exists' => config('queue.default') !== 'database' || Schema::hasTable('jobs'),
            'Lead tables migrated' => Schema::hasTable('leads') && Schema::hasColumn('leads', 'automation_completed_at'),
            'Alert SMS sender (if enabled)' => !config('lead_qualification.alert_sms_enabled') || filled(config('lead_qualification.twilio_from')),
        ];
        return view('admin.leads.health', compact('checks'));
    }
};
