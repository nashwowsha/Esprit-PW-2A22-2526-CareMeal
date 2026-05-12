<?php
/**
 * Local machine overrides. ASCII-only comments to avoid encoding issues.
 */
return [
    // Public URL base (optional). Leave empty for auto-detect.
    'CAREMEAL_PUBLIC_BASE_URL' => '',

    // Realtime tracking (Pusher).
    'CAREMEAL_PUSHER_ENABLED' => '0',
    'CAREMEAL_PUSHER_APP_ID' => '',
    'CAREMEAL_PUSHER_KEY' => '',
    'CAREMEAL_PUSHER_SECRET' => '',
    'CAREMEAL_PUSHER_CLUSTER' => 'eu',

    // Tracking tuning.
    'CAREMEAL_TRACKING_SNAP_ENABLED' => '1',
    'CAREMEAL_TRACKING_SNAP_MAX_ACCURACY_M' => '30',
    'CAREMEAL_TRACKING_MAX_ACCEPT_ACCURACY_M' => '180',
    'CAREMEAL_TRACKING_POOR_ACCURACY_M' => '90',
    'CAREMEAL_TRACKING_MAX_SPEED_MPS' => '45',

    // Matching AI (Gemini async + sync fallback option).
    'CAREMEAL_GEMINI_ENABLED' => '1',
    'CAREMEAL_GEMINI_API_KEY' => '',
    'CAREMEAL_GEMINI_MODEL' => 'gemini-2.5-flash',
    'CAREMEAL_GEMINI_FALLBACK_MODEL' => 'gemini-3-flash-preview',
    'CAREMEAL_GEMINI_ENDPOINT' => 'https://generativelanguage.googleapis.com/v1beta/models',
    'CAREMEAL_GEMINI_TIMEOUT_SEC' => '30',
    'CAREMEAL_GEMINI_RETRY_COUNT' => '1',
    'CAREMEAL_GEMINI_WEB_SEARCH' => '1',
    'CAREMEAL_GEMINI_WEB_SEARCH_SYNC' => '0',

    // Async meal AI pipeline.
    'CAREMEAL_ASYNC_MEAL_AI_ENABLED' => '1',
    'CAREMEAL_MATCHING_SYNC_AI_ENABLED' => '0',
    'CAREMEAL_PHP_BIN' => 'C:\\xampp\\php\\php.exe',

    // Optional local debug.
    'CAREMEAL_AI_DEBUG_ENABLED' => '1',
    'CAREMEAL_AI_DEBUG_TOKEN' => 'change_me_debug_token',

    // Disable Ollama to avoid mixed providers in Gemini demos.
    'CAREMEAL_OLLAMA_ENABLED' => '0',
    'CAREMEAL_OLLAMA_ENDPOINT' => 'http://127.0.0.1:11434/api/generate',
    'CAREMEAL_OLLAMA_MODEL' => 'qwen2.5:3b',
    'CAREMEAL_OLLAMA_TIMEOUT_SEC' => '12',
    'CAREMEAL_OLLAMA_RETRY_COUNT' => '0',
    'CAREMEAL_OLLAMA_CACHE_TTL_SEC' => '86400',
];
