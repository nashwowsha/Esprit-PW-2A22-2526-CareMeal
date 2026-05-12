<?php
/**
 * Copy this file to `config/env.local.php` on each machine
 * and replace placeholders with real values.
 */
return [
    // Public URL base (optional). Leave empty for auto-detect.
    'CAREMEAL_PUBLIC_BASE_URL' => '',

    // Realtime tracking (Pusher) - set to 1 to enable.
    'CAREMEAL_PUSHER_ENABLED' => '1',
    'CAREMEAL_PUSHER_APP_ID' => '2149837',
    'CAREMEAL_PUSHER_KEY' => '3b17e2052c7ba1c49a7d',
    'CAREMEAL_PUSHER_SECRET' => '4c465a36d1182cdfdc0a',
    'CAREMEAL_PUSHER_CLUSTER' => 'eu',

    // Tracking tuning (optional defaults shown).
    'CAREMEAL_TRACKING_SNAP_ENABLED' => '1',
    'CAREMEAL_TRACKING_MAX_ACCEPT_ACCURACY_M' => '120',
    'CAREMEAL_TRACKING_POOR_ACCURACY_M' => '70',
    'CAREMEAL_TRACKING_MAX_SPEED_MPS' => '40',
];

