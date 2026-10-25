<?php

namespace App\Services;

use App\Models\Lead;

class LeadQualificationService
{
    public function classify(Lead $lead, array $analysis = []): string
    {
        $hint = $analysis['classification_hint'] ?? null;
        if ($hint === 'existing_client' || $lead->intent === 'existing_client') return 'EXISTING_CLIENT';
        if ($hint === 'non_sales' || $lead->intent === 'non_sales') return 'NON_SALES';
        if ($hint === 'rejected') return 'REJECTED';
        if ($hint === 'review') return 'REVIEW';

        $intent = $lead->intent;
        $person = $lead->person_type;
        $timeframe = $lead->contact_timeframe;

        $validIntent = in_array($intent, ['change_accountant','need_accountant','tax_vat_payroll','hmrc_issue','landlord_property','other'], true);
        $validPerson = in_array($person, ['owner_director','self_employed_landlord','on_behalf'], true);
        $validTime = in_array($timeframe, ['asap','few_days','few_weeks','researching'], true);

        if ($validIntent && $validPerson && $validTime) {
            return $intent === 'other' ? 'REVIEW' : 'GENUINE';
        }

        return 'NEW';
    }

    public function nextStep(Lead $lead): ?string
    {
        if (in_array($lead->qualification_status, ['GENUINE','REVIEW','REJECTED','EXISTING_CLIENT','NON_SALES'], true)) return null;
        if (!$lead->intent || $lead->intent === 'unknown') return 'intent';
        if (!$lead->person_type || $lead->person_type === 'unknown') return 'person_type';
        if (!$lead->current_accountant || $lead->current_accountant === 'unknown') {
            if ($lead->intent === 'change_accountant') {
                $lead->current_accountant = 'yes_change'; $lead->save();
            } elseif ($lead->intent === 'need_accountant') {
                $lead->current_accountant = 'no'; $lead->save();
            } else {
                $lead->current_accountant = 'not_applicable'; $lead->save();
            }
        }
        if (!$lead->contact_timeframe || $lead->contact_timeframe === 'unknown') return 'contact_timeframe';
        return null;
    }
};
