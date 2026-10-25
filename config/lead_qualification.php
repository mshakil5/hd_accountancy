<?php

return [
    'brand_name' => env('LEAD_BRAND_NAME', 'HD Accountancy'),
    'alert_email' => env('LEAD_ALERT_EMAIL'),
    'alert_phone' => env('LEAD_ALERT_PHONE'),
    'alert_sms_enabled' => env('LEAD_ALERT_SMS_ENABLED', false),
    'retention_days' => (int) env('LEAD_RETENTION_DAYS', 365),
    'follow_up_alert_throttle_minutes' => (int) env('LEAD_FOLLOW_UP_THROTTLE_MINUTES', 15),

    'twilio_sid' => env('TWILIO_ACCOUNT_SID'),
    'twilio_token' => env('TWILIO_AUTH_TOKEN'),
    'twilio_from' => env('TWILIO_SMS_FROM'),

    'terminal_qualification_statuses' => ['GENUINE', 'REVIEW', 'REJECTED', 'EXISTING_CLIENT', 'NON_SALES'],

    'intent_options' => [
        'change_accountant' => 'Change accountant',
        'need_accountant' => 'Need an accountant',
        'tax_vat_payroll' => 'Tax / VAT / Payroll',
        'hmrc_issue' => 'HMRC / tax issue',
        'landlord_property' => 'Landlord / property',
        'other' => 'Something else',
    ],

    'person_options' => [
        'owner_director' => 'Owner / director',
        'self_employed_landlord' => 'Self-employed',
        'on_behalf' => 'For someone else',
    ],

    'accountant_options' => [
        'yes_change' => 'Yes, changing',
        'no' => 'No, looking',
        'not_applicable' => 'Not applicable',
    ],

    'timeframe_options' => [
        'asap' => 'As soon as possible',
        'few_days' => 'Within a few days',
        'few_weeks' => 'Within a few weeks',
        'researching' => 'Just researching',
    ],
];
