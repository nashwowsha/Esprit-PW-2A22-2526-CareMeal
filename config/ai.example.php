<?php
return [
    'provider' => 'gemini',
    'api_key' => 'PUT_YOUR_GEMINI_API_KEY_HERE',
    'api_keys' => [
        'PUT_YOUR_PRIMARY_GEMINI_API_KEY_HERE',
        'PUT_YOUR_FALLBACK_GEMINI_API_KEY_HERE'
    ],
    'enabled' => true,
    'model' => 'gemini-2.5-flash-lite',
    'timeout' => 25,
    'retry_attempts' => 3,
    'retry_delay_ms' => 450
];
