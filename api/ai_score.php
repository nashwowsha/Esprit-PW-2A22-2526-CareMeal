<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

require_once __DIR__ . '/../config/env.php';

function ai_score_send_json(int $status, array $payload): void
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ai_score_send_json(405, ['error' => 'Method not allowed']);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput ?: '', true);
if (!is_array($input)) {
    ai_score_send_json(400, ['error' => 'Invalid JSON']);
}

$score = isset($input['score']) ? (int)$input['score'] : null;
$products = $input['product'] ?? [];
if ($score === null || $score < 0 || $score > 100) {
    ai_score_send_json(400, ['error' => 'Invalid score']);
}
if (!is_array($products) || empty($products)) {
    ai_score_send_json(400, ['error' => 'Product list required']);
}

$apiKey = trim((string)(Env::get('GEMINI_API_KEY', '') ?? ''));
if ($apiKey === '') {
    ai_score_send_json(500, ['error' => 'Missing API key']);
}

$productList = implode(', ', array_map(static function ($item): string {
    return trim((string)$item);
}, $products));
$prompt = "You are an expert in food waste reduction. Explain briefly why this order has a waste reduction score of {$score}/100. Products: {$productList}. Keep it under 2 sentences.";

$requestBody = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt],
            ],
        ],
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 150,
    ],
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($requestBody === false) {
    ai_score_send_json(500, ['error' => 'Request encoding failed']);
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($apiKey);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    ai_score_send_json(502, [
        'error' => 'curl failed',
        'curl_error' => $curlError,
        'curl_errno' => $curlErrno,
    ]);
}
curl_close($ch);

$responseData = json_decode((string)$response, true);
if ($httpCode !== 200 || !isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    ai_score_send_json($httpCode > 0 ? $httpCode : 502, [
        'error' => 'Gemini error',
        'details' => is_array($responseData) ? $responseData : ['raw' => (string)$response],
    ]);
}

ai_score_send_json(200, [
    'score' => $score,
    'explanation' => trim((string)$responseData['candidates'][0]['content']['parts'][0]['text']),
]);
