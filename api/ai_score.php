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
$data = json_decode($raw_input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$score = isset($data['score']) ? (int)$data['score'] : null;
$products = $data['product'] ?? [];

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

$api_key = getenv("OPENAI_API_KEY");

if (!$api_key) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing API key']);
    exit;
}

$product_list = implode(', ', array_map('trim', $products));
$prompt = "Explain briefly why this order has a waste reduction score of $score/100. Products: $product_list";

$request_body = json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are an expert in food waste reduction.'],
        ['role' => 'user',   'content' => $prompt]
    ],
    'temperature' => 0.7,
    'max_tokens'  => 150
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            'https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST,           true);
curl_setopt($ch, CURLOPT_POSTFIELDS,     $request_body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);

// ✅ FIX 1: Add timeout so the request doesn't hang forever
curl_setopt($ch, CURLOPT_TIMEOUT,        15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

// ✅ FIX 2: SSL verification (set to true in production, false only for local XAMPP)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Change to true on a real server
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// ✅ FIX 3: Catch curl-level failures (SSL, DNS, timeout, etc.)
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

// ✅ FIX 4: Always close the curl handle
curl_close($ch);

$response_data = json_decode($response, true);

if ($http_code !== 200 || !isset($response_data['choices'][0]['message']['content'])) {
    http_response_code($http_code ?: 502);
    echo json_encode([
        'error'   => 'OpenAI error',
        'details' => $response_data
    ]);
    exit;
}

echo json_encode([
    'score'       => $score,
    'explanation' => trim($response_data['choices'][0]['message']['content'])
]);
?>