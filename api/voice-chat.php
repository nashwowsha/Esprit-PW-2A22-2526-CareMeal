<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../config/env.php';

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        echo '{"error":"json_encode_failed"}';
        exit;
    }
    echo $json;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(405, ['error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
$text = trim((string)($data['text'] ?? ''));
$jsonMode = (bool)($data['json_mode'] ?? false);

if ($text === '') {
    send_json(422, ['error' => 'Text is required']);
}

$apiKeys = AIConfig::geminiApiKeys();
$models = AIConfig::geminiModels();

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $text],
            ],
        ],
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 220,
    ],
];

if ($jsonMode) {
    $payload['generationConfig']['temperature'] = 0.2;
    $payload['generationConfig']['maxOutputTokens'] = 320;
    $payload['generationConfig']['responseMimeType'] = 'application/json';
}

$quotaRetryAfterMax = null;
$quotaFailures = 0;
$authFailures = 0;
$attempts = 0;
$otherFailures = 0;
$lastOtherError = null;
$lastOtherHttpCode = null;
$lastOtherModel = null;

foreach ($models as $model) {
    foreach ($apiKeys as $apiKey) {
        $attempts++;
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            send_json(502, ['error' => 'Gemini request failed', 'details' => $curlErr]);
        }

        $json = json_decode($response, true);

        if ($httpCode < 400) {
            $reply = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (trim((string)$reply) !== '') {
                send_json(200, [
                    'reply' => $reply,
                    'meta' => [
                        'provider' => 'gemini',
                        'model' => $model,
                        'attempts' => $attempts,
                    ],
                ]);
            }
            continue;
        }

        $errMsg = (string)($json['error']['message'] ?? 'Gemini error');
        $errLower = strtolower($errMsg);

        $isQuota = $httpCode === 429 || str_contains($errLower, 'quota') || str_contains($errLower, 'rate limit');
        if ($isQuota) {
            $quotaFailures++;
            if (preg_match('/retry in\s+([0-9.]+)s/i', $errMsg, $m) === 1) {
                $retryAfter = (int)ceil((float)$m[1]);
                if ($quotaRetryAfterMax === null || $retryAfter > $quotaRetryAfterMax) {
                    $quotaRetryAfterMax = $retryAfter;
                }
            }
            continue;
        }

        $isAuth =
            $httpCode === 401 ||
            $httpCode === 403 ||
            str_contains($errLower, 'api key not valid') ||
            str_contains($errLower, 'invalid api key') ||
            str_contains($errLower, 'permission denied');

        if ($isAuth) {
            $authFailures++;
            continue;
        }

        // Non-auth/non-quota errors (ex: invalid model name) should not abort all fallbacks.
        $otherFailures++;
        $lastOtherError = $errMsg;
        $lastOtherHttpCode = $httpCode;
        $lastOtherModel = $model;
        continue;
    }
}

if ($quotaFailures > 0) {
    send_json(429, [
        'error' => 'Gemini quota exceeded for all keys/models.',
        'code' => 'quota_exceeded',
        'retry_after_seconds' => $quotaRetryAfterMax,
        'meta' => [
            'attempts' => $attempts,
            'quota_failures' => $quotaFailures,
            'auth_failures' => $authFailures,
        ],
    ]);
}

if ($authFailures > 0) {
    send_json(401, [
        'error' => 'All Gemini keys are invalid or unauthorized.',
        'code' => 'all_keys_invalid',
        'meta' => [
            'attempts' => $attempts,
            'quota_failures' => $quotaFailures,
            'auth_failures' => $authFailures,
        ],
    ]);
}

if ($otherFailures > 0) {
    send_json(502, [
        'error' => 'Gemini error',
        'code' => 'gemini_error',
        'details' => $lastOtherError,
        'meta' => [
            'attempts' => $attempts,
            'quota_failures' => $quotaFailures,
            'auth_failures' => $authFailures,
            'other_failures' => $otherFailures,
            'last_http_code' => $lastOtherHttpCode,
            'last_model' => $lastOtherModel,
        ],
    ]);
}

send_json(502, [
    'error' => 'No Gemini key/model produced a response.',
    'code' => 'no_response',
    'meta' => [
        'attempts' => $attempts,
        'quota_failures' => $quotaFailures,
        'auth_failures' => $authFailures,
    ],
]);
