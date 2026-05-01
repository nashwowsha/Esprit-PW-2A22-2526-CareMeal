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

$apiKey = AIConfig::geminiApiKey();
$model = Env::get('GEMINI_MODEL', 'gemini-2.0-flash');
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $text],
            ],
        ],
    ],
];

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

if ($httpCode >= 400) {
    $errMsg = (string)($json['error']['message'] ?? 'Gemini error');
    $errLower = strtolower($errMsg);

    if ($httpCode === 429 || str_contains($errLower, 'quota') || str_contains($errLower, 'rate limit')) {
        $retryAfter = null;
        if (preg_match('/retry in\s+([0-9.]+)s/i', $errMsg, $m) === 1) {
            $retryAfter = (int)ceil((float)$m[1]);
        }

        http_response_code(429);
        echo json_encode([
            'error' => 'Quota Gemini dépassé pour le moment.',
            'code' => 'quota_exceeded',
            'retry_after_seconds' => $retryAfter,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code($httpCode);
    echo json_encode([
        'error' => 'Erreur Gemini: impossible de générer une réponse pour le moment.',
        'code' => 'gemini_error',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$reply = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
if (trim((string)$reply) === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Empty response from Gemini']);
    exit;
}

echo json_encode([
    'reply' => $reply,
], JSON_UNESCAPED_UNICODE);
