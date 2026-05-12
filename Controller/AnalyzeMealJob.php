<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/MatchingSnapshotStore.php';

class AnalyzeMealJob
{
    private $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function run($restaurantId, $mealId)
    {
        $restaurantId = (int)$restaurantId;
        $mealId = trim((string)$mealId);
        if ($restaurantId <= 0 || $mealId === '') {
            return;
        }

        $restaurant = $this->fetchRestaurant($restaurantId);
        if (!$restaurant) {
            return;
        }

        $meals = $this->decodeMeals($restaurant['meals_json'] ?? '[]');
        $mealIndex = $this->findMealIndex($meals, $mealId);
        if ($mealIndex < 0) {
            return;
        }

        $meal = is_array($meals[$mealIndex]) ? $meals[$mealIndex] : [];
        $mealName = $this->norm($meal['meal_name'] ?? '');
        $vendorIngredients = $this->norm($meal['ingredients'] ?? '');

        $analysis = $this->analyzeMeal($mealName, $vendorIngredients);

        $meals[$mealIndex]['analyse_ia'] = (int)$analysis['analyse_ia'];
        $meals[$mealIndex]['analyse_ia_model'] = (string)$analysis['model'];
        $meals[$mealIndex]['analyse_ia_engine'] = (string)$analysis['engine'];
        $meals[$mealIndex]['analyse_ia_updated_at'] = date('Y-m-d H:i:s');
        $meals[$mealIndex]['analyse_ia_error'] = (string)$analysis['error'];
        $meals[$mealIndex]['ingredients_standardises'] = array_values((array)$analysis['ingredients_standardises']);
        $meals[$mealIndex]['ingredients_manquants_probables'] = array_values((array)$analysis['ingredients_manquants_probables']);
        $meals[$mealIndex]['allergenes_finaux'] = array_values((array)$analysis['allergenes_finaux']);

        $this->saveMeals($restaurantId, $meals);
    }

    private function fetchRestaurant($idRestaurant)
    {
        $q = $this->db->prepare('SELECT id_restaurant, meals_json FROM restaurant WHERE id_restaurant = :id LIMIT 1');
        $q->execute(['id' => (int)$idRestaurant]);
        $row = $q->fetch();
        return is_array($row) ? $row : null;
    }

    private function decodeMeals($json)
    {
        $arr = json_decode((string)$json, true);
        return is_array($arr) ? $arr : [];
    }

    private function findMealIndex($meals, $mealId)
    {
        foreach ((array)$meals as $i => $meal) {
            if (!is_array($meal)) {
                continue;
            }
            if ($this->norm($meal['meal_id'] ?? '') === $mealId) {
                return (int)$i;
            }
        }
        return -1;
    }

    private function saveMeals($idRestaurant, $meals)
    {
        $json = json_encode(array_values((array)$meals), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($json)) {
            $json = '[]';
        }

        $q = $this->db->prepare('UPDATE restaurant SET meals_json = :meals_json WHERE id_restaurant = :id');
        $q->execute([
            'meals_json' => $json,
            'id' => (int)$idRestaurant,
        ]);
        try {
            MatchingSnapshotStore::bumpSourceVersion('meal_ai_async_updated');
        } catch (Exception $e) {
            // Keep background job stable if cache invalidation fails.
        }
    }

    private function analyzeMeal($mealName, $vendorIngredients)
    {
        $primaryModel = trim((string)caremeal_env('CAREMEAL_GEMINI_MODEL', 'gemini-3-flash-preview'));
        if ($primaryModel === '') {
            $primaryModel = 'gemini-3-flash-preview';
        }
        $engine = 'gemini';

        try {
            $geminiResult = $this->callGeminiWithFallback($mealName, $vendorIngredients, $primaryModel);
            $gemini = is_array($geminiResult['data'] ?? null) ? $geminiResult['data'] : null;
            $effectiveModel = (string)($geminiResult['model'] ?? $primaryModel);
            if (is_array($gemini) && isset($gemini['ingredients_standardises'], $gemini['ingredients_manquants_probables'], $gemini['allergenes_finaux'])) {
                $standardized = $this->normalizeTokenList($gemini['ingredients_standardises']);
                $missing = $this->normalizeTokenList($gemini['ingredients_manquants_probables']);
                $allergens = $this->normalizeAllergenList($gemini['allergenes_finaux']);

                return [
                    'analyse_ia' => 1,
                    'model' => $effectiveModel,
                    'engine' => $engine,
                    'error' => '',
                    'ingredients_standardises' => $standardized,
                    'ingredients_manquants_probables' => $missing,
                    'allergenes_finaux' => $allergens,
                ];
            }

            $fallback = $this->deterministicFallback($mealName, $vendorIngredients);
            return [
                'analyse_ia' => -1,
                'model' => (string)($geminiResult['model'] ?? $primaryModel),
                'engine' => 'fallback_local',
                'error' => $this->normalizeAiError((string)($geminiResult['error'] ?? 'invalid_ai_json')),
                'ingredients_standardises' => $fallback['ingredients_standardises'],
                'ingredients_manquants_probables' => $fallback['ingredients_manquants_probables'],
                'allergenes_finaux' => $fallback['allergenes_finaux'],
            ];
        } catch (Exception $e) {
            $fallback = $this->deterministicFallback($mealName, $vendorIngredients);
            return [
                'analyse_ia' => -1,
                'model' => $primaryModel,
                'engine' => 'fallback_local',
                'error' => $this->normalizeAiError($e->getMessage()),
                'ingredients_standardises' => $fallback['ingredients_standardises'],
                'ingredients_manquants_probables' => $fallback['ingredients_manquants_probables'],
                'allergenes_finaux' => $fallback['allergenes_finaux'],
            ];
        }
    }

    private function geminiEnabled()
    {
        $flag = strtolower((string)caremeal_env('CAREMEAL_GEMINI_ENABLED', '1'));
        if (!in_array($flag, ['1', 'true', 'yes', 'on'], true)) {
            return false;
        }
        return trim((string)caremeal_env('CAREMEAL_GEMINI_API_KEY', '')) !== '';
    }

    private function geminiAsyncTimeoutSec()
    {
        $raw = (int)caremeal_env('CAREMEAL_GEMINI_TIMEOUT_SEC', '30');
        if ($raw < 20) {
            $raw = 20;
        }
        if ($raw > 30) {
            $raw = 30;
        }
        return $raw;
    }

    private function geminiRetryCount()
    {
        $raw = (int)caremeal_env('CAREMEAL_GEMINI_RETRY_COUNT', '1');
        if ($raw < 0) {
            $raw = 0;
        }
        if ($raw > 2) {
            $raw = 2;
        }
        return $raw;
    }

    private function callGeminiWithFallback($mealName, $vendorIngredients, $primaryModel)
    {
        $fallbackModel = trim((string)caremeal_env('CAREMEAL_GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash'));
        if ($fallbackModel === '') {
            $fallbackModel = 'gemini-2.5-flash';
        }
        $models = [$primaryModel];
        if (strtolower($fallbackModel) !== strtolower($primaryModel)) {
            $models[] = $fallbackModel;
        }

        $lastError = 'gemini_response_empty';
        $lastModel = $primaryModel;
        foreach ($models as $model) {
            $lastModel = $model;
            try {
                $data = $this->callGemini($mealName, $vendorIngredients, $model);
                if (is_array($data)) {
                    return ['data' => $data, 'model' => $model, 'error' => ''];
                }
                $lastError = 'invalid_ai_json';
            } catch (Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        return ['data' => null, 'model' => $lastModel, 'error' => $lastError];
    }

    private function callGemini($mealName, $vendorIngredients, $model)
    {
        if (!$this->geminiEnabled()) {
            return null;
        }

        $endpoint = rtrim((string)caremeal_env('CAREMEAL_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
        $apiKey = trim((string)caremeal_env('CAREMEAL_GEMINI_API_KEY', ''));
        if ($endpoint === '' || $apiKey === '') {
            return null;
        }

        $systemPrompt = "Tu es un moteur d'analyse allergenes strict pour CareMeal.\n"
            . "Regles obligatoires:\n"
            . "1) Identifie une recette de base consensuelle + variantes courantes du meal.\n"
            . "2) Gere synonymes et multilingue (tomate/tomatoes etc.) sans dupliquer.\n"
            . "3) Si vendeur a des ingredients, deduis uniquement les manquants reels vs recette de base.\n"
            . "4) Si vendeur n'a rien, base-toi sur la recette standard la plus courante.\n"
            . "5) Deduis allergenes finaux en concepts canoniques (gluten,lactose,arachide,oeuf,soja,sesame,poisson,crustaces,mollusques,fruits-a-coque,celeri,moutarde,sulfites,lupin).\n"
            . "6) Sortie strictement JSON valide, aucune phrase additionnelle.\n"
            . "7) Cles exactes obligatoires: ingredients_standardises, ingredients_manquants_probables, allergenes_finaux.\n";

        $payloadData = [
            'meal_name' => $mealName,
            'vendor_ingredients' => $vendorIngredients,
        ];

        $userPrompt = "Analyse ce meal et renvoie uniquement le JSON demande.\nDATA:\n"
            . json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $request = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $userPrompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ],
        ];

        $webSearch = strtolower((string)caremeal_env('CAREMEAL_GEMINI_WEB_SEARCH', '1'));
        if (in_array($webSearch, ['1', 'true', 'yes', 'on'], true)) {
            $request['tools'] = [['google_search' => (object)[]]];
        }

        $url = $endpoint . '/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
        $asyncTimeout = $this->geminiAsyncTimeoutSec();
        $maxAttempts = 1 + $this->geminiRetryCount();
        $lastError = 'gemini_response_empty';

        $attempt = 0;
        while ($attempt < $maxAttempts) {
            try {
                $response = $this->httpPostJson($url, $request, $asyncTimeout);
                if (is_array($response)) {
                    $raw = trim((string)$this->extractGeminiText($response));
                    if ($raw !== '') {
                        $decoded = $this->decodeJsonLoose($raw);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                        $lastError = 'invalid_ai_json';
                    } else {
                        $lastError = 'empty_ai_response';
                    }
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
            }
            $attempt += 1;
            if ($attempt < $maxAttempts) {
                usleep(220000 * $attempt);
            }
        }

        throw new Exception($this->normalizeAiError($lastError));
    }

    private function extractGeminiText($response)
    {
        if (!is_array($response)) {
            return '';
        }
        $candidates = $response['candidates'] ?? null;
        if (!is_array($candidates) || empty($candidates)) {
            return '';
        }
        $first = $candidates[0] ?? null;
        if (!is_array($first)) {
            return '';
        }
        $content = $first['content'] ?? null;
        if (!is_array($content)) {
            return '';
        }
        $parts = $content['parts'] ?? null;
        if (!is_array($parts) || empty($parts)) {
            return '';
        }
        $out = [];
        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }
            $text = trim((string)($part['text'] ?? ''));
            if ($text !== '') {
                $out[] = $text;
            }
        }
        return trim(implode("\n", $out));
    }
    private function deterministicFallback($mealName, $vendorIngredients)
    {
        $ingredients = $this->normalizeTokenList($this->splitIngredientTokens($vendorIngredients));
        $allergens = $this->inferAllergensFromText($mealName . ', ' . $vendorIngredients);

        return [
            'ingredients_standardises' => $ingredients,
            'ingredients_manquants_probables' => [],
            'allergenes_finaux' => $allergens,
        ];
    }

    private function splitIngredientTokens($value)
    {
        $parts = preg_split('/[,;]+/', (string)$value);
        if (!$parts) {
            return [];
        }
        $out = [];
        foreach ($parts as $part) {
            $clean = $this->norm($part);
            if ($clean !== '') {
                $out[] = strtolower($clean);
            }
        }
        return array_values(array_unique($out));
    }

    private function normalizeTokenList($value)
    {
        $items = is_array($value) ? $value : [];
        $out = [];
        foreach ($items as $item) {
            $clean = strtolower($this->norm((string)$item));
            if ($clean !== '') {
                $out[] = $clean;
            }
        }
        return array_values(array_unique($out));
    }

    private function normalizeAllergenList($value)
    {
        $aliases = $this->allergenAliases();
        $tokens = $this->normalizeTokenList($value);
        $out = [];
        foreach ($tokens as $token) {
            $key = str_replace(' ', '-', $token);
            $out[] = $aliases[$key] ?? $token;
        }
        return array_values(array_unique(array_values(array_filter($out, function ($x) {
            return $x !== '';
        }))));
    }

    private function allergenAliases()
    {
        return [
            'peanut' => 'arachide', 'peanuts' => 'arachide', 'cacahuete' => 'arachide',
            'milk' => 'lactose', 'dairy' => 'lactose', 'fromage' => 'lactose',
            'gluten' => 'gluten', 'wheat' => 'gluten', 'bread' => 'gluten', 'pain' => 'gluten',
            'egg' => 'oeuf', 'eggs' => 'oeuf', 'oeuf' => 'oeuf',
            'soy' => 'soja', 'soja' => 'soja',
            'sesame' => 'sesame',
            'fish' => 'poisson', 'poisson' => 'poisson',
            'shellfish' => 'crustaces', 'shrimp' => 'crustaces', 'crustaces' => 'crustaces',
            'mollusks' => 'mollusques', 'mollusques' => 'mollusques',
            'nuts' => 'fruits-a-coque', 'noix' => 'fruits-a-coque',
            'celery' => 'celeri', 'celeri' => 'celeri',
            'mustard' => 'moutarde', 'moutarde' => 'moutarde',
            'sulfites' => 'sulfites', 'sulphites' => 'sulfites',
            'lupin' => 'lupin',
        ];
    }

    private function inferAllergensFromText($text)
    {
        $text = strtolower($this->norm($text));
        if ($text === '') {
            return [];
        }

        $hits = [];
        foreach ($this->allergenAliases() as $needle => $canonical) {
            if (strpos($text, str_replace('-', ' ', $needle)) !== false || strpos($text, $needle) !== false) {
                $hits[] = $canonical;
            }
        }
        return array_values(array_unique($hits));
    }

    private function httpGetJson($url, $timeoutSec)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('curl_not_available');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(2, (int)$timeoutSec));
        curl_setopt($ch, CURLOPT_TIMEOUT, max(2, (int)$timeoutSec));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            throw new Exception($error !== '' ? $error : 'http_get_empty_response');
        }
        if ($status < 200 || $status >= 300) {
            throw new Exception('http_get_status_' . $status);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('http_get_invalid_json');
        }
        return $decoded;
    }

    private function httpPostJson($url, $payload, $timeoutSec)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('curl_not_available');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($json) || $json === '') {
            throw new Exception('http_post_json_encode_failed');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(2, (int)$timeoutSec));
        curl_setopt($ch, CURLOPT_TIMEOUT, max(2, (int)$timeoutSec));
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            throw new Exception($error !== '' ? $error : 'http_post_empty_response');
        }
        if ($status < 200 || $status >= 300) {
            throw new Exception('http_post_status_' . $status);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('http_post_invalid_json');
        }
        return $decoded;
    }

    private function normalizeAiError($message)
    {
        $msg = strtolower($this->norm((string)$message));
        if ($msg === '') {
            return 'ai_error';
        }
        if (strpos($msg, 'http_post_status_') !== false) {
            if (preg_match('/http_post_status_(\d{3})/', $msg, $m)) {
                return 'http_' . $m[1];
            }
            return 'http_error';
        }
        if (strpos($msg, 'http_get_status_') !== false) {
            if (preg_match('/http_get_status_(\d{3})/', $msg, $m)) {
                return 'http_' . $m[1];
            }
            return 'http_error';
        }
        if (strpos($msg, 'timed out') !== false || strpos($msg, 'timeout') !== false) {
            return 'timeout';
        }
        if (strpos($msg, 'could not resolve host') !== false || strpos($msg, 'failed to connect') !== false) {
            return 'network_error';
        }
        if (strpos($msg, 'invalid_ai_json') !== false || strpos($msg, 'invalid json') !== false) {
            return 'invalid_ai_json';
        }
        if (strpos($msg, 'empty_ai_response') !== false || strpos($msg, 'response_empty') !== false) {
            return 'empty_ai_response';
        }
        if (strpos($msg, 'curl_not_available') !== false) {
            return 'curl_not_available';
        }
        return substr($msg, 0, 120);
    }

    private function decodeJsonLoose($raw)
    {
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\{[\s\S]*\}/', (string)$raw, $m)) {
            $decoded2 = json_decode((string)$m[0], true);
            if (is_array($decoded2)) {
                return $decoded2;
            }
        }
        return null;
    }

    private function norm($value)
    {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/', ' ', $value);
        return $value ?? '';
    }
}

function caremeal_job_arg($name, $default = '')
{
    global $argv;
    $prefix = '--' . trim((string)$name) . '=';
    foreach ((array)$argv as $arg) {
        $arg = (string)$arg;
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

$restaurantId = (int)caremeal_job_arg('restaurant_id', '0');
$mealId = trim((string)caremeal_job_arg('meal_id', ''));
if ($restaurantId > 0 && $mealId !== '') {
    $job = new AnalyzeMealJob();
    $job->run($restaurantId, $mealId);
}

