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

        // A genuine business can mention recruitment, suppliers or vacancies
        // while asking HD for accounting work ("I run a recruitment agency and
        // need an accountant"). Never reject those, so the non-sales wording is
        // only decisive when no accounting need is present.
        $accountingNeed = (bool) preg_match('/accountant|accounting|bookkeep|payroll|\bvat\b|self[- ]assessment|tax return|corporation tax|company accounts|\bcis\b|\bpaye\b|hmrc|year[- ]end|\bltd\b|limited company/', $m);
        $nonSalesWords = (bool) preg_match('/\bseo\b|digital marketing|recruit|job vacancy|job application|\bcv\b|supplier|web design|marketing services|\bvacanc(?:y|ies)\b|\blooking for work\b|\bjob available\b|\binternship\b|employment agency|recruitment agency/', $m);
        $ambiguousWords = (bool) preg_match('/\bhiring\b|\b(?<!self-)employment\b|\bcareer\b/', $m);

        if (preg_match('/already (have )?(an? )?(hd )?(client|customer)|existing (hd )?(client|customer)|(?:i am|i\'m) an? (?:hd )?(?:client|customer)|your client/', $m)) {
            $out['intent'] = 'existing_client'; $out['classification_hint'] = 'existing_client';
        } elseif ($nonSalesWords && !$accountingNeed) {
            $out['intent'] = 'non_sales'; $out['classification_hint'] = 'non_sales';
        } elseif ($ambiguousWords && !$accountingNeed) {
            // Deliberately NOT non_sales: these words also appear in genuine
            // client enquiries ("we are hiring and need payroll help", "help
            // with employment status for my staff"). A human decides.
            $out['classification_hint'] = 'review';
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

        // Ambiguous non-sales wording plus an accounting need that no rule
        // above could route: this is exactly the "uncertain" case, so a human
        // reviews it rather than the prospect being silently discarded.
        if (($nonSalesWords || $ambiguousWords) && $accountingNeed
            && $out['intent'] === 'unknown' && $out['classification_hint'] === 'unknown') {
            $out['classification_hint'] = 'review';
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
