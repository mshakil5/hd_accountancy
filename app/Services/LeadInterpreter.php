<?php

namespace App\Services;

class LeadInterpreter
{
    /**
     * Deterministic interpreter for inbound WhatsApp replies. Button/list
     * answers arrive as option IDs; free text is matched against common
     * accountancy enquiry phrases. No external AI service is used.
     */
    public function interpret(string $message): array
    {
        $m = mb_strtolower(trim($message));
        $out = ['intent' => 'unknown', 'person_type' => 'unknown', 'current_accountant' => 'unknown', 'contact_timeframe' => 'unknown', 'classification_hint' => 'unknown', 'summary' => ''];

        if (preg_match('/already (have )?(an? )?(hd )?(client|customer)|existing (hd )?(client|customer)|(?:i am|i\'m) an? (?:hd )?(?:client|customer)|your client/', $m)) {
            $out['intent'] = 'existing_client'; $out['classification_hint'] = 'existing_client';
        } elseif (preg_match('/\bseo\b|digital marketing|recruit|job vacancy|job application|\bcv\b|supplier|web design|marketing services/', $m)) {
            $out['intent'] = 'non_sales'; $out['classification_hint'] = 'non_sales';
        } elseif (preg_match('/change.*accountant|switch.*accountant|new accountant|current accountant|accountant.*not respond|accountant.*expensive/', $m)) {
            $out['intent'] = 'change_accountant'; $out['current_accountant'] = 'yes_change';
        } elseif (preg_match('/need.*accountant|looking for.*accountant|find.*accountant/', $m)) {
            $out['intent'] = 'need_accountant';
        } elseif (preg_match('/hmrc|tax investigation|penalty|tax debt|overdue/', $m)) {
            $out['intent'] = 'hmrc_issue';
        } elseif (preg_match('/landlord|rental|property tax|capital gains|cgt/', $m)) {
            $out['intent'] = 'landlord_property';
        } elseif (preg_match('/\bvat\b|payroll|bookkeep|tax return|self assessment|accounts|corporation tax|\bcis\b/', $m)) {
            $out['intent'] = 'tax_vat_payroll';
        }

        if (preg_match('/owner|director|my company|my business|i run|i own/', $m)) $out['person_type'] = 'owner_director';
        elseif (preg_match('/self[- ]?employed|landlord/', $m)) $out['person_type'] = 'self_employed_landlord';
        elseif (preg_match('/on behalf|for my (wife|husband|partner|boss|friend|company)/', $m)) $out['person_type'] = 'on_behalf';

        if (preg_match('/asap|urgent|straight away|immediately|today/', $m)) $out['contact_timeframe'] = 'asap';
        elseif (preg_match('/few days|this week|next week|next few days/', $m)) $out['contact_timeframe'] = 'few_days';
        elseif (preg_match('/few weeks|this month|next month/', $m)) $out['contact_timeframe'] = 'few_weeks';
        elseif (preg_match('/research|just looking|information|exploring/', $m)) $out['contact_timeframe'] = 'researching';

        return $out;
    }
}
