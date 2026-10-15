<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_LEAD_MODEL', 'gpt-6-luna'),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'enabled' => env('OPENAI_LEAD_ENABLED', true),
];
