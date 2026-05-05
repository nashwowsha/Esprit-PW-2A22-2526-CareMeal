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

    // Matching IA (Ollama local). Keep 0 to use local semantic fallback only.
    'CAREMEAL_OLLAMA_ENABLED' => '0',
    'CAREMEAL_OLLAMA_ENDPOINT' => 'http://127.0.0.1:11434/api/generate',
    'CAREMEAL_OLLAMA_MODEL' => 'llama3.1:8b',
];
