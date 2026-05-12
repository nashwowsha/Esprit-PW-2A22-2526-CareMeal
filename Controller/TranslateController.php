<?php
/* ============================================================
   CAREMEAL — TRANSLATE CONTROLLER
   API : MyMemory (100% gratuit, sans clé, 5000 mots/jour)
   URL : https://mymemory.translated.net
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');

$data       = json_decode(file_get_contents('php://input'), true);
$text       = isset($data['q'])      ? trim($data['q'])      : '';
$targetLang = isset($data['target']) ? trim($data['target']) : 'en';
$sourceLang = isset($data['source']) ? trim($data['source']) : 'fr';

// ── Validation ────────────────────────────────────────────────
if (empty($text)) {
    echo json_encode(['success' => false, 'message' => 'Texte vide.']);
    exit;
}

$allowedLangs = ['en', 'ar', 'es', 'de', 'it', 'pt', 'tr'];
if (!in_array($targetLang, $allowedLangs)) {
    echo json_encode(['success' => false, 'message' => 'Langue non supportée.']);
    exit;
}

// ── Appel MyMemory API (gratuit, sans clé API) ────────────────
// Format langpair : "fr|en" ou "fr|ar"
$langPair = urlencode($sourceLang . '|' . $targetLang);
$query    = urlencode($text);
$apiUrl   = "https://api.mymemory.translated.net/get?q={$query}&langpair={$langPair}";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'CareMeal/1.0',
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'Erreur réseau : ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

// ── Traitement de la réponse MyMemory ─────────────────────────
// responseStatus 200 = succès
if (
    isset($result['responseStatus']) &&
    $result['responseStatus'] == 200 &&
    isset($result['responseData']['translatedText'])
) {
    $translated = $result['responseData']['translatedText'];

    // MyMemory retourne parfois "PLEASE SELECT TWO DISTINCT LANGUAGES" si langpair invalide
    if (stripos($translated, 'PLEASE SELECT') !== false) {
        echo json_encode(['success' => false, 'message' => 'Paire de langues invalide.']);
        exit;
    }

    echo json_encode([
        'success'        => true,
        'translatedText' => $translated,
        'source'         => $sourceLang,
        'target'         => $targetLang
    ]);
} else {
    $errorMsg = $result['responseDetails'] ?? 'Traduction échouée.';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
}
?>
