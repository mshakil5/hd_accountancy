<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadMessage;
use Illuminate\Support\Facades\DB;

class LeadConversationService
{
    public function __construct(
        private MetaWhatsAppService $whatsApp,
        private OpenAILeadInterpreter $interpreter,
        private LeadQualificationService $qualifier,
        private LeadAlertService $alerts,
        private MetaAdAttributionService $attribution,
    ) {}

    public function process(LeadMessage $message): void
    {
        $lead = $message->lead()->firstOrFail();
        if ($lead->human_takeover) return;
        $this->attribution->enrich($lead);
        $lead->refresh();

        $answer = $this->extractAnswer($message);
        $analysis = $this->interpreter->interpret($lead, $answer);

        DB::transaction(function () use ($lead, $analysis, $answer) {
            $this->applyAnalysis($lead, $analysis);
            $this->applyExpectedStepAnswer($lead, $answer);

            $status = $this->qualifier->classify($lead, $analysis);
            if ($status !== 'NEW') {
                $wasTerminal = in_array($lead->qualification_status, config('lead_qualification.terminal_qualification_statuses'), true);
                $lead->qualification_status = $status;
                if (in_array($status, ['GENUINE','REVIEW'], true) && !$lead->qualified_at) $lead->qualified_at = now();
                $lead->conversation_step = 'complete';
                if (!empty($analysis['summary'])) $lead->ai_summary = $analysis['summary'];
                $lead->save();
                if (!$wasTerminal) $lead->setAttribute('_just_qualified', true);
                return;
            }

            $next = $this->qualifier->nextStep($lead);
            if (!$next) {
                $lead->qualification_status = 'GENUINE';
                $lead->qualified_at = $lead->qualified_at ?: now();
                $lead->conversation_step = 'complete';
                $lead->save();
                $lead->setAttribute('_just_qualified', true);
            } else {
                $lead->qualification_status = 'QUALIFYING';
                $lead->conversation_step = $next;
                $lead->save();
            }
        });

        $lead->refresh();
        if ($lead->getAttribute('_just_qualified')) {
            $this->finish($lead);
            return;
        }

        if (in_array($lead->qualification_status, ['GENUINE','REVIEW','EXISTING_CLIENT','NON_SALES','REJECTED'], true)) {
            $this->finish($lead);
            return;
        }

        $this->askNext($lead);
    }

    private function applyAnalysis(Lead $lead, array $analysis): void
    {
        foreach (['intent','person_type','current_accountant','contact_timeframe'] as $field) {
            if (!empty($analysis[$field]) && $analysis[$field] !== 'unknown') $lead->{$field} = $analysis[$field];
        }
        if (!$lead->name && !empty($analysis['name'])) $lead->name = $analysis['name'];
        $data = $lead->qualification_data ?: [];
        foreach ($analysis as $k => $v) {
            if ($v !== null && $v !== '' && $v !== 'unknown') $data[$k] = $v;
        }
        $lead->qualification_data = $data;
        if (!empty($analysis['summary'])) $lead->ai_summary = $analysis['summary'];
        $lead->save();
    }

    private function applyExpectedStepAnswer(Lead $lead, string $answer): void
    {
        $step = $lead->conversation_step;
        $maps = [
            'intent' => array_keys(config('lead_qualification.intent_options')),
            'person_type' => array_keys(config('lead_qualification.person_options')),
            'current_accountant' => array_keys(config('lead_qualification.accountant_options')),
            'contact_timeframe' => array_keys(config('lead_qualification.timeframe_options')),
        ];
        if (isset($maps[$step]) && in_array($answer, $maps[$step], true)) {
            $lead->{$step} = $answer;
            $data = $lead->qualification_data ?: [];
            $data[$step] = $answer;
            $lead->qualification_data = $data;
            $lead->save();
        }
    }

    private function askNext(Lead $lead): void
    {
        $step = $lead->conversation_step;
        if ($step === 'intent') {
            $this->whatsApp->sendList($lead,
                'Hi 👋 Thanks for contacting HD Accountancy. What would you like help with?',
                'Choose',
                config('lead_qualification.intent_options')
            );
        } elseif ($step === 'person_type') {
            $this->whatsApp->sendButtons($lead,
                'And which best describes you?',
                config('lead_qualification.person_options')
            );
        } elseif ($step === 'current_accountant') {
            $this->whatsApp->sendButtons($lead,
                'Do you currently have an accountant?',
                config('lead_qualification.accountant_options')
            );
        } elseif ($step === 'contact_timeframe') {
            $options = config('lead_qualification.timeframe_options');
            $this->whatsApp->sendList($lead,
                'Thanks. How soon would you like someone from HD Accountancy to contact you?',
                'Choose',
                $options
            );
        }
    }

    private function finish(Lead $lead): void
    {
        if ($lead->qualification_status === 'GENUINE') {
            $this->whatsApp->sendText($lead, 'Thanks — that’s everything we need. A member of the HD Accountancy team will contact you shortly.');
            $this->alerts->notify($lead);
            $lead->update(['human_takeover' => true]);
        } elseif ($lead->qualification_status === 'REVIEW') {
            $this->whatsApp->sendText($lead, 'Thanks for the information. I’ll pass this to the HD Accountancy team so they can review your enquiry and get back to you.');
            $this->alerts->notify($lead);
            $lead->update(['human_takeover' => true]);
        } elseif ($lead->qualification_status === 'EXISTING_CLIENT') {
            $this->whatsApp->sendText($lead, 'Thanks for letting me know. As you’re already an HD Accountancy client, I’ll pass your message to the team.');
            $this->alerts->notify($lead);
            $lead->update(['human_takeover' => true]);
        } elseif ($lead->qualification_status === 'NON_SALES') {
            $this->whatsApp->sendText($lead, 'Thanks for your message. This WhatsApp line is primarily for client and accountancy enquiries. Your message has been recorded.');
        } elseif ($lead->qualification_status === 'REJECTED') {
            // No further automated engagement with obvious spam/bots.
        }
    }

    private function extractAnswer(LeadMessage $message): string
    {
        $payload = $message->interactive_payload ?: [];
        return (string) ($payload['id'] ?? $message->body ?? '');
    }
};
