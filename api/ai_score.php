<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$score = isset($input['score']) ? (int)$input['score'] : null;
$products = $input['product'] ?? [];

if ($score === null || $score < 0 || $score > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid score']);
    exit;
}

if (!is_array($products) || empty($products)) {
    http_response_code(400);
    echo json_encode(['error' => 'Product list required']);
    exit;
}

$api_key = getenv("GEMINI_API_KEY");

if (!$api_key) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing API key']);
    exit;
}

$product_list = implode(', ', array_map('trim', $products));
$prompt = "You are an expert in food waste reduction. Explain briefly why this order has a waste reduction score of $score/100. Products: $product_list. Keep it under 2 sentences.";

$request_body = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 150
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST,           true);
curl_setopt($ch, CURLOPT_POSTFIELDS,     $request_body);
curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT,        15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    http_response_code(502);
    echo json_encode([
        'error'      => 'curl failed',
        'curl_error' => $curl_error,
        'curl_errno' => $curl_errno
    ]);
    exit;
}

curl_close($ch);

$response_data = json_decode($response, true);

if ($http_code !== 200 || !isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
    http_response_code($http_code ?: 502);
    echo json_encode([
        'error'   => 'Gemini error',
        'details' => $response_data
    ]);
    exit;
}

echo json_encode([
    'score'       => $score,
    'explanation' => trim($response_data['candidates'][0]['content']['parts'][0]['text'])
]);
?>