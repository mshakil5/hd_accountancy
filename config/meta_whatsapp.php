<?php

return [
    'graph_version' => env('META_GRAPH_VERSION', 'v24.0'),
    'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
    'ads_access_token' => env('META_ADS_ACCESS_TOKEN'),
    'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('META_WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
    'app_secret' => env('META_APP_SECRET'),
    'base_url' => env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'),
    'reengagement_template' => env('META_WHATSAPP_REENGAGEMENT_TEMPLATE'),
    'reengagement_template_language' => env('META_WHATSAPP_REENGAGEMENT_TEMPLATE_LANGUAGE', 'en_GB'),
];
