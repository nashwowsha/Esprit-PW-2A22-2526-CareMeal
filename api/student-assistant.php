<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/env.php';

function send_json_response(int $status, array $payload): void
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

function parse_csv_env(string $raw): array
{
    if ($raw === '') {
        return [];
    }

    $parts = explode(',', $raw);
    $out = [];
    foreach ($parts as $part) {
        $value = trim($part);
        if ($value !== '') {
            $out[] = $value;
        }
    }
    return array_values(array_unique($out));
}

function student_api_keys(): array
{
    $keys = parse_csv_env((string)Env::get('STUDENT_GEMINI_API_KEYS', ''));
    $single = trim((string)Env::get('STUDENT_GEMINI_API_KEY', ''));
    if ($single !== '') {
        $keys[] = $single;
    }
    $keys = array_values(array_unique($keys));
    if (empty($keys)) {
        throw new RuntimeException('STUDENT_GEMINI_API_KEY is missing in .env');
    }
    return $keys;
}

function student_models(): array
{
    $models = parse_csv_env((string)Env::get('STUDENT_GEMINI_MODELS', ''));
    $single = trim((string)Env::get('STUDENT_GEMINI_MODEL', 'gemini-2.5-flash-lite'));
    if ($single !== '') {
        $models[] = strtolower($single);
    }
    $models = array_values(array_unique($models));
    if (empty($models)) {
        $models = ['gemini-2.5-flash-lite', 'gemini-2.5-flash'];
    }
    return $models;
}

function extract_gemini_text(array $json): string
{
    $parts = $json['candidates'][0]['content']['parts'] ?? [];
    if (!is_array($parts)) {
        return '';
    }

    $texts = [];
    foreach ($parts as $part) {
        $piece = trim((string)($part['text'] ?? ''));
        if ($piece !== '') {
            $texts[] = $piece;
        }
    }

    return trim(implode("\n", $texts));
}

function reply_looks_incomplete(string $reply, string $finishReason = ''): bool
{
    $text = trim($reply);
    if ($text === '') {
        return true;
    }

    if (in_array(strtoupper($finishReason), ['MAX_TOKENS', 'RECITATION', 'OTHER'], true)) {
        return true;
    }

    $lastChar = mb_substr($text, -1);
    if (preg_match('/[.!?…]/u', $lastChar) === 1) {
        return false;
    }

    if (preg_match('/[\'"\\-:]$/u', $text) === 1) {
        return true;
    }

    if (preg_match('/\b(comment|puis|pour|avec|dans|de|du|des|au|aux|et|ou|mais|si|que|qui|sur|par|a|aide|aider)\s*$/iu', $text) === 1) {
        return true;
    }

    return true;
}

function complete_truncated_reply(string $reply, string $model, string $apiKey): string
{
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
    $completionPrompt = "Complete ce texte en francais naturel, sans changer son sens. "
        . "Donne uniquement la version finale complete en 1-2 phrases.\n"
        . "Texte actuel: " . $reply;

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $completionPrompt],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 140,
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return '';
    }

    $json = json_decode($response, true);
    $completed = extract_gemini_text(is_array($json) ? $json : []);
    return trim($completed);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
$text = trim((string)($data['text'] ?? ''));
$context = $data['context'] ?? [];

if ($text === '') {
    send_json_response(422, ['error' => 'Text is required']);
}

try {
    $apiKeys = student_api_keys();
    $models = student_models();
} catch (Throwable $e) {
    send_json_response(500, ['error' => $e->getMessage()]);
}

$page = (string)($context['page'] ?? '');
$visibleOffers = (int)($context['visible_offers'] ?? 0);
$totalOffers = (int)($context['total_offers'] ?? 0);

$systemPrompt = "Tu es l'assistant vocal ETUDIANT de CareMeal. "
    . "Tu n'as AUCUN droit admin, aucune suppression/modification de donnees, aucun ordre admin. "
    . "Tu aides seulement l'etudiant: navigation, offres disponibles, explications simples sur l'usage du site. "
    . "Reponds en francais simple, court, naturel. "
    . "Ne dis jamais 'j'ai reconnu'. "
    . "Si la demande parle d'actions admin (supprimer utilisateur, modifier categorie admin, etc), "
    . "refuse poliment et redirige vers l'administrateur. "
    . "Contexte actuel: page={$page}, offres_visibles={$visibleOffers}, offres_total={$totalOffers}.";

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $systemPrompt],
            ],
        ],
        [
            'role' => 'user',
            'parts' => [
                ['text' => $text],
            ],
        ],
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 420,
    ],
];

$attempts = 0;
$quotaFailures = 0;
$authFailures = 0;
$quotaRetryAfterMax = null;
$otherFailures = 0;
$lastError = 'Gemini error';
$lastHttpCode = 0;
$lastModel = '';

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
            send_json_response(502, ['error' => 'Gemini request failed', 'details' => $curlErr]);
        }

        $json = json_decode($response, true);
        if ($httpCode < 400) {
            $reply = extract_gemini_text($json);
            $finishReason = (string)($json['candidates'][0]['finishReason'] ?? '');
            if ($reply !== '' && reply_looks_incomplete($reply, $finishReason)) {
                $completed = complete_truncated_reply($reply, (string)$model, (string)$apiKey);
                if ($completed !== '') {
                    $reply = $completed;
                }
            }
            if ($reply !== '') {
                send_json_response(200, [
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
        $lastError = $errMsg;
        $lastHttpCode = $httpCode;
        $lastModel = $model;

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

        $otherFailures++;
    }
}

if ($quotaFailures > 0) {
    send_json_response(429, [
        'error' => 'Gemini quota exceeded for all student keys/models.',
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
    send_json_response(401, [
        'error' => 'All student Gemini keys are invalid or unauthorized.',
        'code' => 'all_keys_invalid',
        'meta' => [
            'attempts' => $attempts,
            'quota_failures' => $quotaFailures,
            'auth_failures' => $authFailures,
        ],
    ]);
}

send_json_response(502, [
    'error' => $lastError,
    'code' => 'gemini_error',
    'meta' => [
        'attempts' => $attempts,
        'quota_failures' => $quotaFailures,
        'auth_failures' => $authFailures,
        'other_failures' => $otherFailures,
        'last_http_code' => $lastHttpCode,
        'last_model' => $lastModel,
    ],
]);
