<?php

return [
    'brand_name' => env('LEAD_BRAND_NAME', 'HD Accountancy'),
    'alert_email' => env('LEAD_ALERT_EMAIL'),
    'alert_phone' => env('LEAD_ALERT_PHONE'),
    'alert_sms_enabled' => env('LEAD_ALERT_SMS_ENABLED', false),
    'retention_days' => (int) env('LEAD_RETENTION_DAYS', 365),

    'terminal_qualification_statuses' => ['GENUINE', 'REVIEW', 'REJECTED', 'EXISTING_CLIENT', 'NON_SALES'],

    'intent_options' => [
        'change_accountant' => 'Changing / looking to change accountant',
        'need_accountant' => 'Need an accountant',
        'tax_vat_payroll' => 'Accounts / Tax / VAT / Payroll',
        'hmrc_issue' => 'HMRC / urgent tax issue',
        'landlord_property' => 'Landlord / property tax',
        'other' => 'Something else',
    ],

    'person_options' => [
        'owner_director' => 'Business owner / director',
        'self_employed_landlord' => 'Self-employed / landlord',
        'on_behalf' => 'Enquiring for someone else',
    ],

    'accountant_options' => [
        'yes_change' => 'Yes - looking to change',
        'no' => 'No - looking for one',
        'not_applicable' => 'Not applicable',
    ],

    'timeframe_options' => [
        'asap' => 'As soon as possible',
        'few_days' => 'Within a few days',
        'few_weeks' => 'Within the next few weeks',
        'researching' => 'Just looking for information',
    ],
];
