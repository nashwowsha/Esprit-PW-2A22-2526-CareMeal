<?php
/**
 * Copy this file to `config/env.local.php` on each machine
 * and replace placeholders with real values.
 */
return [
    // Public URL base (optional). Leave empty for auto-detect.
    'CAREMEAL_PUBLIC_BASE_URL' => '',

    // Realtime tracking (Pusher) - set to 1 to enable.
    'CAREMEAL_PUSHER_ENABLED' => '0',
    'CAREMEAL_PUSHER_APP_ID' => '',
    'CAREMEAL_PUSHER_KEY' => '',
    'CAREMEAL_PUSHER_SECRET' => '',
    'CAREMEAL_PUSHER_CLUSTER' => 'eu',

    // Tracking tuning (optional defaults shown).
    'CAREMEAL_TRACKING_SNAP_ENABLED' => '1',
    'CAREMEAL_TRACKING_SNAP_MAX_ACCURACY_M' => '35',
    'CAREMEAL_TRACKING_MAX_ACCEPT_ACCURACY_M' => '120',
    'CAREMEAL_TRACKING_POOR_ACCURACY_M' => '70',
    'CAREMEAL_TRACKING_MAX_SPEED_MPS' => '40',
    'CAREMEAL_TRACKING_TRACKER_LOCK_TTL_SEC' => '180',

    // Matching IA provider (Gemini)
    'CAREMEAL_GEMINI_ENABLED' => '0',
    'CAREMEAL_GEMINI_API_KEY' => '',
    'CAREMEAL_GEMINI_MODEL' => 'gemini-3-flash-preview',
    'CAREMEAL_GEMINI_FALLBACK_MODEL' => 'gemini-1.5-flash',
    'CAREMEAL_GEMINI_ENDPOINT' => 'https://generativelanguage.googleapis.com/v1beta/models',
    'CAREMEAL_GEMINI_TIMEOUT_SEC' => '30',
    'CAREMEAL_GEMINI_RETRY_COUNT' => '1',
    'CAREMEAL_GEMINI_WEB_SEARCH' => '1',
    'CAREMEAL_GEMINI_WEB_SEARCH_SYNC' => '0',

    // Optional legacy Ollama local support.
    'CAREMEAL_OLLAMA_ENABLED' => '0',
    'CAREMEAL_OLLAMA_ENDPOINT' => 'http://127.0.0.1:11434/api/generate',
    'CAREMEAL_OLLAMA_MODEL' => 'llama3.1:8b',
    'CAREMEAL_OLLAMA_TIMEOUT_SEC' => '18',

    // Async meal AI pipeline executed when meals are added/updated.
    'CAREMEAL_ASYNC_MEAL_AI_ENABLED' => '1',
    'CAREMEAL_MATCHING_SYNC_AI_ENABLED' => '0',
    'CAREMEAL_OLLAMA_ASYNC_MODEL' => 'llama3.2:3b-instruct-q4_K_M',
    // Optional: force PHP binary for background job on Windows.
    'CAREMEAL_PHP_BIN' => '',

    // Debug endpoint for DB/UI AI consistency checks (local/dev only).
    'CAREMEAL_AI_DEBUG_ENABLED' => '0',
    'CAREMEAL_AI_DEBUG_TOKEN' => '',
];
