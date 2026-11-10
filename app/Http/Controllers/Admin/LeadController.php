<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadMessage;
use App\Models\MetaAdIntent;
use App\Services\LeadAlertService;
use App\Services\MetaWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
                    ->orWhere('qualification_summary', 'like', "%{$q}%");
            });
        }
        $leads = $query->paginate(30)->withQueryString();
        $counts = Lead::selectRaw('qualification_status, count(*) as total')->groupBy('qualification_status')->pluck('total','qualification_status');
        return view('admin.leads.index', compact('leads','counts'));
    }

    /**
     * Maintenance purge of the entire lead history.
     *
     * This is NOT a routine admin action: it is gated behind the dedicated
     * `purge-leads` super-admin permission, requires a typed confirmation, and
     * always writes a full JSON archive before anything is removed. Routine
     * retention is handled by the scheduled `leads:prune` command instead.
     */
    public function clearAll(Request $request)
    {
        Gate::authorize('purge-leads');

        $request->validate([
            'confirmation' => 'required|in:DELETE',
        ], [
            'confirmation.in' => 'Type DELETE in capitals to confirm.',
            'confirmation.required' => 'Type DELETE in capitals to confirm.',
        ]);

        $archive = null;
        $deleted = 0;

        DB::transaction(function () use (&$archive, &$deleted) {
            $deleted = Lead::count();
            if ($deleted > 0) {
                // Archive first: a failed write must abort the deletion.
                $archive = $this->archiveAllLeads();
            }
            LeadMessage::query()->delete();
            Lead::query()->delete();
        });

        Log::warning('All WhatsApp leads were deleted by a super-admin', [
            'admin_id' => auth()->id(),
            'leads_deleted' => $deleted,
            'archive' => $archive,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.leads.index')
            ->with('success', $deleted === 0
                ? 'There were no leads to delete.'
                : "All {$deleted} lead(s) and their WhatsApp messages were archived to {$archive} and deleted.");
    }

    private function archiveAllLeads(): string
    {
        $path = 'lead-archives/leads-' . now()->format('Ymd-His') . '.json';
        $leads = Lead::with('messages')->orderBy('id')->get();

        // JSON_INVALID_UTF8_SUBSTITUTE keeps a corrupt provider payload from
        // making the whole archive fail. A false return still aborts the purge.
        $json = json_encode([
            'archived_at' => now()->toIso8601String(),
            'archived_by' => auth()->id(),
            'lead_count' => $leads->count(),
            'leads' => $leads->toArray(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($json === false) {
            throw new \RuntimeException('The lead archive could not be encoded; the purge was aborted.');
        }

        if (Storage::disk('local')->put($path, $json) === false) {
            throw new \RuntimeException('The lead archive could not be written; the purge was aborted.');
        }

        return Storage::disk('local')->path($path);
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
            $data['unclear_replies'] = 0;
        }

        $attributes = array_filter($data, fn($v) => $v !== null);
        foreach (['closed_at', 'automation_completed_at', 'qualified_at', 'intent', 'person_type', 'current_accountant', 'contact_timeframe', 'qualification_data', 'unclear_replies'] as $nullableField) {
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
        $status = $alerts->notify($lead);

        if ($status === LeadAlertService::STATUS_FAILED || $status === LeadAlertService::STATUS_NOT_CONFIGURED) {
            $lead->refresh();
            return back()->withErrors(['alert' => 'The lead alert could not be delivered ('
                . str_replace('_', ' ', (string) $status)
                . '). Check the integration health page.'
                . ($lead->alert_last_error ? ' ' . $lead->alert_last_error : '')]);
        }

        return back()->with('success', 'Lead notification sent and recorded on the lead.');
    }

    /**
     * Health screen: every green row must mean genuinely operational. Optional
     * integrations show "Optional", never a failure.
     */
    public function health()
    {
        $emailConfigured = filled(config('lead_qualification.alert_email'));
        $smsEnabled = (bool) config('lead_qualification.alert_sms_enabled');
        $smsComplete = filled(config('lead_qualification.alert_phone')) && filled(config('lead_qualification.twilio_from'));
        $activeAlertChannel = $emailConfigured || ($smsEnabled && $smsComplete);
        $mappingConfigured = $this->hasAdIntentMappings();

        $checks = [
            $this->check('Meta WhatsApp access token', filled(config('meta_whatsapp.access_token')) ? 'ready' : 'missing', 'Required — send bot and staff replies.'),
            $this->check('Meta phone number ID', filled(config('meta_whatsapp.phone_number_id')) ? 'ready' : 'missing', 'Required — WhatsApp Business Platform sender.'),
            $this->check('Meta verify token', filled(config('meta_whatsapp.verify_token')) ? 'ready' : 'missing', 'Required — webhook GET verification.'),
            $this->check('Meta app secret', filled(config('meta_whatsapp.app_secret')) ? 'ready' : 'missing', 'Required — X-Hub-Signature-256 validation.'),
            $this->check('Re-engagement template', filled(config('meta_whatsapp.reengagement_template')) ? 'ready' : 'optional', 'Optional — only needed for outreach after the 24-hour window.'),
            $this->check('Lead alert channel', $activeAlertChannel ? 'ready' : 'missing', 'At least one active channel: alert email, or SMS with Twilio.'),
            $this->check('Lead alert email', $emailConfigured ? 'ready' : ($activeAlertChannel ? 'optional' : 'missing'), 'LEAD_ALERT_EMAIL.'),
            $this->check('Lead alert SMS (Twilio)', !$smsEnabled ? 'optional' : ($smsComplete ? 'ready' : 'missing'), $smsEnabled ? 'LEAD_ALERT_PHONE and TWILIO_SMS_FROM are required.' : 'SMS alerts are disabled.'),
            $this->check('Mail configuration (SMTP)', $this->mailConfigured() ? 'ready' : 'missing', 'Used for lead alert emails.'),
            $this->check('META_ADS_ACCESS_TOKEN', filled(config('meta_whatsapp.ads_access_token')) ? 'ready' : 'optional', 'Optional — campaign/ad name attribution. Must be a dedicated Marketing API token.'),
            $this->check('Campaign/ad intent mapping', $mappingConfigured ? 'ready' : 'optional', $mappingConfigured ? 'Known adverts route to their intended qualification path.' : 'No adverts mapped: every advert asks the first question.'),
            $this->check('Queue is asynchronous', config('queue.default') !== 'sync' ? 'ready' : 'missing', 'Lead bot processing must not run inside the webhook request.'),
            $this->check('Database queue table exists', config('queue.default') !== 'database' || Schema::hasTable('jobs') ? 'ready' : 'missing'),
            $this->check('Lead tables migrated', Schema::hasTable('leads') && Schema::hasColumn('leads', 'automation_completed_at') ? 'ready' : 'missing'),
            $this->check('Retention prune scheduled', $this->pruneIsScheduled() ? 'ready' : 'missing', 'leads:prune runs daily at 02:30.'),
        ];

        return view('admin.leads.health', compact('checks'));
    }

    public function sendTestAlert(Request $request, LeadAlertService $alerts)
    {
        $request->validate(['channel' => 'required|in:email,sms,whatsapp']);

        if ($request->channel === 'whatsapp') {
            $to = config('lead_qualification.test_whatsapp_number');
            if (!$to) {
                return back()->withErrors(['alert' => 'No LEAD_ALERT_PHONE / LEAD_TEST_WHATSAPP_NUMBER is configured for the WhatsApp test.']);
            }
            try {
                app(MetaWhatsAppService::class)->sendTestText(
                    $to,
                    'HD Accountancy lead system test message — ' . now()->format('d M H:i')
                );
                return back()->with('success', "Test WhatsApp message sent to {$to}.");
            } catch (\Throwable $e) {
                return back()->withErrors(['alert' => 'Test WhatsApp failed: ' . mb_substr($e->getMessage(), 0, 300)]);
            }
        }

        $result = $alerts->sendTestAlert($request->channel);

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->withErrors(['alert' => $result['message']]);
    }

    private function check(string $label, string $state, ?string $detail = null): array
    {
        return ['label' => $label, 'state' => $state, 'detail' => $detail];
    }

    private function mailConfigured(): bool
    {
        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) return false;
        if ($mailer === 'smtp' && !filled(config('mail.mailers.smtp.host'))) return false;
        return filled(config('mail.from.address'));
    }

    private function hasAdIntentMappings(): bool
    {
        return Schema::hasTable('meta_ad_intents') && MetaAdIntent::query()->active()->exists();
    }

    private function pruneIsScheduled(): bool
    {
        try {
            Artisan::call('schedule:list');
            return str_contains(Artisan::output(), 'leads:prune');
        } catch (\Throwable $e) {
            Log::warning('Could not read the scheduler list', ['error' => $e->getMessage()]);
            return false;
        }
    }
};
