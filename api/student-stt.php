<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/env.php';

function student_stt_send_json(int $status, array $payload): void
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

function student_stt_log_groq_error(int $httpCode, string $responseBody, string $curlError = ''): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $entry = [
        'timestamp' => date('c'),
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'response_body' => $responseBody,
    ];

    @file_put_contents(
        $logDir . '/stt_error.log',
        json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    student_stt_send_json(405, ['error' => 'Method not allowed']);
}

if (!isset($_FILES['audio']) || !is_array($_FILES['audio'])) {
    student_stt_send_json(422, ['error' => 'Audio file is required']);
}

$audio = $_FILES['audio'];
if (($audio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    student_stt_send_json(422, ['error' => 'Audio upload failed']);
}

$tmpPath = (string)($audio['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    student_stt_send_json(422, ['error' => 'Invalid uploaded file']);
}

$apiKey = trim((string)(Env::get('STUDENT_GROQ_API_KEY', '') ?? ''));
if ($apiKey === '') {
    $apiKey = trim((string)(Env::get('GROQ_API_KEY', '') ?? ''));
}
if ($apiKey === '') {
    student_stt_send_json(500, ['error' => 'STUDENT_GROQ_API_KEY/GROQ_API_KEY missing in .env']);
}

$model = trim((string)(Env::get('STUDENT_GROQ_STT_MODEL', '') ?? ''));
if ($model === '') {
    $model = trim((string)(Env::get('GROQ_STT_MODEL', 'whisper-large-v3-turbo') ?? 'whisper-large-v3-turbo'));
}
if ($model === '') {
    $model = 'whisper-large-v3-turbo';
}

$language = trim((string)($_POST['language'] ?? Env::get('STUDENT_GROQ_STT_LANGUAGE', '') ?? ''));
if ($language === '') {
    $language = trim((string)(Env::get('GROQ_STT_LANGUAGE', 'fr') ?? 'fr'));
}
if ($language === '') {
    $language = 'fr';
}

$prompt = trim((string)($_POST['prompt'] ?? 'Transcription vocale en francais claire avec ponctuation simple.'));

$mime = (string)($audio['type'] ?? 'audio/webm');
$name = (string)($audio['name'] ?? 'speech.webm');
$curlFile = curl_file_create($tmpPath, $mime, $name);

$postFields = [
    'model' => $model,
    'file' => $curlFile,
    'language' => $language,
    'temperature' => '0',
    'response_format' => 'json',
];

if ($prompt !== '') {
    $postFields['prompt'] = $prompt;
}

$ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    student_stt_log_groq_error($httpCode, '', $curlErr);
    student_stt_send_json(502, [
        'error' => 'Groq STT request failed',
        'details' => $curlErr,
        'groq_http_code' => $httpCode,
        'groq_response' => '',
    ]);
}

$json = json_decode($response, true);
if ($httpCode !== 200) {
    student_stt_log_groq_error($httpCode, (string)$response, $curlErr);
    $msg = (string)($json['error']['message'] ?? 'Groq STT error');
    student_stt_send_json($httpCode, [
        'error' => $msg,
        'groq_http_code' => $httpCode,
        'groq_response' => $json ?? $response,
    ]);
}

$text = trim((string)($json['text'] ?? ''));
$text = str_replace(['!', '¡'], '', $text);
$text = preg_replace('/\s+/u', ' ', (string)$text) ?? (string)$text;
$text = trim((string)$text);

student_stt_send_json(200, [
    'text' => $text,
    'meta' => ['provider' => 'groq', 'model' => $model, 'mode' => 'student_whisper'],
]);

