<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

$text = trim((string)($data['text'] ?? ''));
if ($text === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Text is required']);
    exit;
}

$apiKeys = AIConfig::geminiApiKeys();
$models = AIConfig::geminiModels();
$xaiKey = AIConfig::xaiApiKey();
$xaiModel = AIConfig::xaiModel();

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

$quotaRetryAfterMax = null;
$quotaFailures = 0;
$authFailures = 0;
$attempts = 0;

foreach ($models as $model) {
    foreach ($apiKeys as $apiKey) {
        $attempts++;
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            http_response_code(502);
            echo json_encode(['error' => 'Gemini request failed', 'details' => $curlErr]);
            exit;
        }

        $json = json_decode($response, true);

        if ($httpCode < 400) {
            $reply = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (trim((string)$reply) !== '') {
                echo json_encode([
                    'reply' => $reply,
                    'meta' => [
                        'attempts' => $attempts,
                        'model' => $model,
                    ],
                ], JSON_UNESCAPED_UNICODE);
                exit;
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

        http_response_code($httpCode);
        echo json_encode([
            'error' => 'Erreur Gemini: impossible de générer une réponse pour le moment.',
            'code' => 'gemini_error',
            'meta' => [
                'attempts' => $attempts,
                'model' => $model,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($quotaFailures > 0) {
    // Fallback to xAI (Grok) when Gemini quota is exhausted.
    if ($xaiKey !== '') {
        $xaiUrl = 'https://api.x.ai/v1/chat/completions';
        $xaiPayload = [
            'model' => $xaiModel,
            'messages' => [
                ['role' => 'user', 'content' => $text],
            ],
            'temperature' => 0.4,
            'max_tokens' => 220,
            'stream' => false,
        ];

        $xch = curl_init($xaiUrl);
        curl_setopt_array($xch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $xaiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($xaiPayload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $xResponse = curl_exec($xch);
        $xCurlErr = curl_error($xch);
        $xHttpCode = (int)curl_getinfo($xch, CURLINFO_HTTP_CODE);
        curl_close($xch);

        if ($xResponse !== false) {
            $xJson = json_decode($xResponse, true);
            if ($xHttpCode < 400) {
                $xReply = $xJson['choices'][0]['message']['content'] ?? '';
                if (trim((string)$xReply) !== '') {
                    echo json_encode([
                        'reply' => $xReply,
                        'meta' => [
                            'provider' => 'xai',
                            'model' => $xaiModel,
                            'attempts' => $attempts,
                        ],
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        }
    }

    http_response_code(429);
    echo json_encode([
        'error' => 'Quota Gemini dépassé pour toutes les clés/modèles disponibles.',
        'code' => 'quota_exceeded',
        'retry_after_seconds' => $quotaRetryAfterMax,
        'meta' => [
            'attempts' => $attempts,
            'quota_failures' => $quotaFailures,
            'auth_failures' => $authFailures,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($authFailures > 0) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Toutes les clés Gemini sont invalides ou non autorisées.',
        'code' => 'all_keys_invalid',
        'meta' => [
            'attempts' => $attempts,
            'quota_failures' => $quotaFailures,
            'auth_failures' => $authFailures,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(502);
echo json_encode([
    'error' => 'Aucune clé/modèle Gemini n a pu produire une réponse.',
    'code' => 'no_response',
    'meta' => [
        'attempts' => $attempts,
        'quota_failures' => $quotaFailures,
        'auth_failures' => $authFailures,
    ],
], JSON_UNESCAPED_UNICODE);
