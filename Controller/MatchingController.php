<?php
require_once __DIR__ . '/PreferenceController.php';
require_once __DIR__ . '/MatchingSnapshotStore.php';
require_once __DIR__ . '/AnalyzeMealJob.php';
require_once __DIR__ . '/../Model/Matching.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class MatchingController {
    private $semanticMemo = [];
    private $mealInferenceMemo = [];
    private const AI_PIPELINE_VERSION = 'ai-conservative-v3';

    private function ollamaTimeoutSec() {
        $raw = (int)caremeal_env('CAREMEAL_OLLAMA_TIMEOUT_SEC', '10');
        if ($raw < 3) $raw = 3;
        if ($raw > 45) $raw = 45;
        return $raw;
    }

    private function ollamaRetryCount() {
        $raw = (int)caremeal_env('CAREMEAL_OLLAMA_RETRY_COUNT', '0');
        if ($raw < 0) $raw = 0;
        if ($raw > 2) $raw = 2;
        return $raw;
    }

    private function mealInferenceCacheDir() {
        return __DIR__ . '/../tmp/meal_ai_cache';
    }

    private function mealInferenceCacheTtlSec() {
        $raw = (int)caremeal_env('CAREMEAL_OLLAMA_CACHE_TTL_SEC', '86400');
        if ($raw < 60) $raw = 60;
        if ($raw > 604800) $raw = 604800;
        return $raw;
    }

    private function aiPipelineVersion() {
        return self::AI_PIPELINE_VERSION;
    }

    private function ollamaSemanticTimeoutSec() {
        $base = $this->ollamaTimeoutSec();
        if ($base > 12) {
            $base = 12;
        }
        if ($base < 4) {
            $base = 4;
        }
        return $base;
    }

    private function ollamaSemanticRetryCount() {
        return min(1, $this->ollamaRetryCount());
    }

    private function readMealInferenceCache($memoKey) {
        $memoKey = trim((string)$memoKey);
        if ($memoKey === '') {
            return null;
        }
        $file = $this->mealInferenceCacheDir() . '/' . preg_replace('/[^a-f0-9]/i', '', $memoKey) . '.json';
        if (!is_file($file)) {
            return null;
        }
        $mtime = @filemtime($file);
        if (!is_int($mtime) || (time() - $mtime) > $this->mealInferenceCacheTtlSec()) {
            return null;
        }
        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function writeMealInferenceCache($memoKey, $profile) {
        $memoKey = trim((string)$memoKey);
        if ($memoKey === '' || !is_array($profile)) {
            return;
        }
        $analysisMode = strtolower((string)($profile['analysis_mode'] ?? 'fallback_local'));
        $hasUsefulAi = !empty($profile['inferred_ingredients']) || !empty($profile['inferred_allergens']) || !empty($profile['basic_recipe_ingredients']);
        if ($analysisMode !== 'ai_live' && !$hasUsefulAi) {
            return;
        }
        $dir = $this->mealInferenceCacheDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!is_dir($dir)) {
            return;
        }
        $file = $dir . '/' . preg_replace('/[^a-f0-9]/i', '', $memoKey) . '.json';
        $json = $this->jsonEncodeSafe($profile, JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            return;
        }
        @file_put_contents($file, $json);
    }

    private function decodeJsonObjectLoose($jsonRaw) {
        if (!is_string($jsonRaw) || trim($jsonRaw) === '') {
            return null;
        }
        $decoded = json_decode($jsonRaw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\{[\s\S]*\}/', $jsonRaw, $m)) {
            $decoded2 = json_decode((string)$m[0], true);
            if (is_array($decoded2)) {
                return $decoded2;
            }
        }
        return null;
    }

    private function jsonEncodeSafe($value, $flags = 0)
    {
        $flags = $flags | JSON_INVALID_UTF8_SUBSTITUTE;
        $json = json_encode($value, $flags);
        if (!is_string($json)) {
            return '{}';
        }
        return $json;
    }

    private function db() {
        return config::getConnexion();
    }

    public static function invalidateMatchingSnapshots($reason = '')
    {
        try {
            MatchingSnapshotStore::bumpSourceVersion((string)$reason);
        } catch (Exception $e) {
            // Keep runtime stable if invalidation cannot run now.
        }
    }

    private function normalizeMatchingFilters($options = [])
    {
        return MatchingSnapshotStore::normalizeFilterOptions($options);
    }

    private function buildSnapshotPayload($preference, $matches, $appliedFilters)
    {
        return [
            'preference' => is_array($preference) ? $preference : null,
            'restaurants' => array_values(is_array($matches) ? $matches : []),
            'applied_filters' => is_array($appliedFilters) ? $appliedFilters : [],
        ];
    }

    private function readOrBuildMatchingSnapshot($idUser, $idPref, $options = [])
    {
        $filters = $this->normalizeMatchingFilters($options);
        $idUser = (int)$idUser;
        $idPref = (int)$idPref;
        if ($idUser <= 0 || $idPref <= 0) {
            return [
                'ok' => false,
                'status' => $idUser <= 0 ? 'error_invalid_user' : 'error_invalid_preference',
                'restaurants' => [],
                'cache_hit' => false,
                'snapshot_version' => 0,
            ];
        }

        $preferenceController = new PreferenceController();
        $preference = $preferenceController->getById($idPref);
        if (!$preference) {
            return [
                'ok' => false,
                'status' => 'error_preference_not_found',
                'restaurants' => [],
                'cache_hit' => false,
                'snapshot_version' => 0,
            ];
        }
        if ((int)($preference['id_user'] ?? 0) !== $idUser) {
            return [
                'ok' => false,
                'status' => 'error_forbidden_preference',
                'restaurants' => [],
                'cache_hit' => false,
                'snapshot_version' => 0,
            ];
        }

        $readiness = $this->matchingReadinessSnapshot();
        if (empty($readiness['ready'])) {
            return [
                'ok' => false,
                'status' => 'analysis_required',
                'restaurants' => [],
                'cache_hit' => false,
                'snapshot_version' => MatchingSnapshotStore::currentSourceVersion(),
                'analysis_summary' => $readiness,
            ];
        }

        $cache = MatchingSnapshotStore::readSnapshot($idUser, $idPref, $filters);
        if (!empty($cache['hit']) && is_array($cache['payload'] ?? null)) {
            $payload = $cache['payload'];
            $restaurants = is_array($payload['restaurants'] ?? null) ? $payload['restaurants'] : [];
            $applied = is_array($payload['applied_filters'] ?? null) ? $payload['applied_filters'] : [];
            return [
                'ok' => true,
                'status' => empty($restaurants) ? 'no_match' : 'success',
                'preference' => is_array($payload['preference'] ?? null) ? $payload['preference'] : $preference,
                'restaurants' => $restaurants,
                'applied_filters' => $applied,
                'cache_hit' => true,
                'snapshot_version' => (int)($cache['source_version'] ?? 0),
                'analysis_summary' => $readiness,
            ];
        }

        $restaurants = $this->getRestaurantsForMatching();
        $matches = $this->buildRestaurantMatches($preference, $restaurants, $filters);
        $appliedFilters = [
            'price_mode' => $filters['price_mode'],
            'sort_by' => $filters['sort_by'],
            'safety_mode' => $filters['safety_mode'],
            'semantic_engine' => $this->ollamaEnabled() ? 'caremeal-hybrid-ai-' . $this->aiProviderName() : 'caremeal-local-semantics',
        ];
        $snapshotVersion = (int)($cache['source_version'] ?? MatchingSnapshotStore::currentSourceVersion());
        $snapshotPayload = $this->buildSnapshotPayload($preference, $matches, $appliedFilters);
        MatchingSnapshotStore::writeSnapshot($idUser, $idPref, $filters, $snapshotVersion, $snapshotPayload);

        return [
            'ok' => true,
            'status' => empty($matches) ? 'no_match' : 'success',
            'preference' => $preference,
            'restaurants' => $matches,
            'applied_filters' => $appliedFilters,
            'cache_hit' => false,
            'snapshot_version' => $snapshotVersion,
            'analysis_summary' => $readiness,
        ];
    }

    private function matchingOnDemandSyncEnabled()
    {
        $flag = strtolower((string)caremeal_env('CAREMEAL_MATCHING_SYNC_ANALYZE_ON_DEMAND', '1'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function matchingOnDemandSyncMaxMeals()
    {
        $raw = (int)caremeal_env('CAREMEAL_MATCHING_SYNC_ANALYZE_MAX_MEALS', '6');
        if ($raw < 1) $raw = 1;
        if ($raw > 30) $raw = 30;
        return $raw;
    }

    private function matchingOnDemandSyncBudgetMs()
    {
        $raw = (int)caremeal_env('CAREMEAL_MATCHING_SYNC_ANALYZE_BUDGET_MS', '4500');
        if ($raw < 500) $raw = 500;
        if ($raw > 12000) $raw = 12000;
        return $raw;
    }

    private function scanMatchingReadiness()
    {
        $restaurants = $this->getRestaurantsForMatching();
        $pendingCount = 0;
        $unknownCount = 0;
        $totalMeals = 0;
        $pendingEntries = [];

        foreach ((array)$restaurants as $restaurant) {
            if (!is_array($restaurant)) {
                continue;
            }
            $restaurantId = (int)($restaurant['id_restaurant'] ?? 0);
            $restaurantName = $this->normalizeText($restaurant['nom'] ?? '');
            $meals = $this->decodeMeals($restaurant['meals_json'] ?? '[]');

            foreach ((array)$meals as $meal) {
                if (!is_array($meal)) {
                    continue;
                }
                $mealId = $this->normalizeText($meal['meal_id'] ?? '');
                $mealName = $this->normalizeText($meal['meal_name'] ?? '');
                if ($mealId === '' || $mealName === '') {
                    continue;
                }
                $totalMeals += 1;
                $state = $this->analysisStateForMeal($meal);
                if ($state === 'pending') {
                    $pendingCount += 1;
                    $pendingEntries[] = [
                        'id_restaurant' => $restaurantId,
                        'restaurant' => $restaurantName,
                        'meal_id' => $mealId,
                        'meal_name' => $mealName,
                    ];
                } elseif ($state === 'unknown') {
                    $unknownCount += 1;
                }
            }
        }

        return [
            'pending_meals_count' => $pendingCount,
            'unknown_meals_count' => $unknownCount,
            'total_meals_count' => $totalMeals,
            'pending_entries' => $pendingEntries,
        ];
    }

    private function runOnDemandSyncAnalysis($pendingEntries)
    {
        if (!$this->matchingOnDemandSyncEnabled()) {
            return 0;
        }
        $entries = array_values((array)$pendingEntries);
        if (empty($entries)) {
            return 0;
        }

        $maxMeals = $this->matchingOnDemandSyncMaxMeals();
        $budgetMs = $this->matchingOnDemandSyncBudgetMs();
        $start = microtime(true);
        $processed = 0;
        $job = new AnalyzeMealJob();

        foreach ($entries as $entry) {
            if ($processed >= $maxMeals) {
                break;
            }
            $elapsedMs = (microtime(true) - $start) * 1000;
            if ($elapsedMs >= $budgetMs) {
                break;
            }
            if (!is_array($entry)) {
                continue;
            }
            $restaurantId = (int)($entry['id_restaurant'] ?? 0);
            $mealId = $this->normalizeText($entry['meal_id'] ?? '');
            if ($restaurantId <= 0 || $mealId === '') {
                continue;
            }
            try {
                $job->run($restaurantId, $mealId);
                $processed += 1;
            } catch (Exception $e) {
                // Continue processing other meals within time budget.
            }
        }
        return $processed;
    }

    private function matchingReadinessSnapshot()
    {
        $before = $this->scanMatchingReadiness();
        $pendingEntries = is_array($before['pending_entries'] ?? null) ? $before['pending_entries'] : [];
        $processed = 0;
        if (!empty($pendingEntries)) {
            $processed = $this->runOnDemandSyncAnalysis($pendingEntries);
        }
        $after = $this->scanMatchingReadiness();
        $pendingAfter = is_array($after['pending_entries'] ?? null) ? $after['pending_entries'] : [];

        return [
            'ready' => (int)($after['pending_meals_count'] ?? 0) === 0,
            'pending_meals_count' => (int)($after['pending_meals_count'] ?? 0),
            'unknown_meals_count' => (int)($after['unknown_meals_count'] ?? 0),
            'total_meals_count' => (int)($after['total_meals_count'] ?? 0),
            'pending_samples' => array_values(array_slice($pendingAfter, 0, 8)),
            'sync_attempted' => !empty($pendingEntries),
            'sync_processed_count' => (int)$processed,
        ];
    }

    private function toAscii($value) {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }
        return $value;
    }

    private function normalizeText($value) {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/', ' ', $value);
        return $value ?? '';
    }

    private function parseList($value) {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[,;]+/', (string)$value);
        }

        if (!$parts) {
            return [];
        }

        $tokens = array_values(array_filter(array_map(function ($item) {
            return strtolower($this->normalizeText($item));
        }, $parts), static function ($item) {
            return $item !== '';
        }));

        return array_values(array_unique($tokens));
    }

    private function extractLexicalCandidates($value, $maxNgram = 3) {
        $chunks = is_array($value) ? $value : [(string)$value];
        $out = [];

        foreach ($chunks as $chunk) {
            $text = strtolower($this->toAscii($this->normalizeText($chunk)));
            if ($text === '') {
                continue;
            }

            // Keep explicit comma/semicolon chunks first.
            foreach ($this->parseList($text) as $listToken) {
                $norm = $this->normalizeToken($listToken);
                if ($norm !== '') {
                    $out[] = $norm;
                }
            }

            $clean = preg_replace('/[^a-z0-9]+/', ' ', $text);
            $clean = trim((string)$clean);
            if ($clean === '') {
                continue;
            }

            $words = array_values(array_filter(explode(' ', $clean), static function ($w) {
                return $w !== '' && strlen($w) >= 3;
            }));
            $count = count($words);
            if ($count === 0) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $w = $words[$i];
                $out[] = $w;
                if (strlen($w) > 4 && substr($w, -1) === 's') {
                    $out[] = substr($w, 0, -1);
                }

                $max = min($maxNgram, $count - $i);
                for ($n = 2; $n <= $max; $n++) {
                    $ngram = implode('-', array_slice($words, $i, $n));
                    $ngram = $this->normalizeToken($ngram);
                    if ($ngram !== '') {
                        $out[] = $ngram;
                    }
                }
            }
        }

        return $this->uniqueTokens($out);
    }

    private function normalizeToken($value) {
        $value = strtolower($this->toAscii($this->normalizeText($value)));
        $value = str_replace(['_', ' '], '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $value = preg_replace('/[^a-z0-9\-]/', '', $value);
        return trim((string)$value, '-');
    }

    private function uniqueTokens($tokens) {
        if (!is_array($tokens)) {
            return [];
        }
        return array_values(array_unique(array_values(array_filter($tokens, static function ($t) {
            return is_string($t) && $t !== '';
        }))));
    }

    private function allergenAliasMap() {
        return [
            'arachide' => 'arachide',
            'arachides' => 'arachide',
            'cacahuete' => 'arachide',
            'cacahuetes' => 'arachide',
            'peanut' => 'arachide',
            'peanuts' => 'arachide',
            'penut' => 'arachide',
            'peenut' => 'arachide',
            'pnut' => 'arachide',
            'groundnut' => 'arachide',
            'groundnuts' => 'arachide',
            'pinda' => 'arachide',
            'lactose' => 'lactose',
            'lait' => 'lactose',
            'milk' => 'lactose',
            'dairy' => 'lactose',
            'fromage' => 'lactose',
            'beurre' => 'lactose',
            'gluten' => 'gluten',
            'wheat' => 'gluten',
            'ble' => 'gluten',
            'farine' => 'gluten',
            'orge' => 'gluten',
            'seigle' => 'gluten',
            'pain' => 'gluten',
            'bread' => 'gluten',
            'bun' => 'gluten',
            'baguette' => 'gluten',
            'sandwich' => 'gluten',
            'chocolat' => 'chocolat',
            'chocolate' => 'chocolat',
            'chocola' => 'chocolat',
            'cacao' => 'chocolat',
            'cocoa' => 'chocolat',
            'soja' => 'soja',
            'soy' => 'soja',
            'soybean' => 'soja',
            'tofu' => 'soja',
            'sesame' => 'sesame',
            'sesame-seed' => 'sesame',
            'tahini' => 'sesame',
            'noix' => 'fruits-a-coque',
            'nuts' => 'fruits-a-coque',
            'nut' => 'fruits-a-coque',
            'almond' => 'fruits-a-coque',
            'hazelnut' => 'fruits-a-coque',
            'walnut' => 'fruits-a-coque',
            'pistachio' => 'fruits-a-coque',
            'cashew' => 'fruits-a-coque',
            'fruits-a-coque' => 'fruits-a-coque',
            'crustace' => 'crustaces',
            'shrimp' => 'crustaces',
            'crevette' => 'crustaces',
            'crab' => 'crustaces',
            'lobster' => 'crustaces',
            'mollusque' => 'mollusques',
            'mollusques' => 'mollusques',
            'mollusk' => 'mollusques',
            'mollusks' => 'mollusques',
            'octopus' => 'mollusques',
            'squid' => 'mollusques',
            'calamar' => 'mollusques',
            'oeuf' => 'oeuf',
            'oeufs' => 'oeuf',
            'egg' => 'oeuf',
            'eggs' => 'oeuf',
            'poisson' => 'poisson',
            'fish' => 'poisson',
            'celeri' => 'celeri',
            'celery' => 'celeri',
            'mustard' => 'moutarde',
            'moutarde' => 'moutarde',
            'sulfite' => 'sulfites',
            'sulfites' => 'sulfites',
            'sulphite' => 'sulfites',
            'sulphites' => 'sulfites',
            'lupin' => 'lupin',
            'lupine' => 'lupin',
        ];
    }

    private function aiOpenAllergenMode() {
        $flag = strtolower((string)caremeal_env('CAREMEAL_AI_OPEN_ALLERGENS', '1'));
        return $this->ollamaEnabled() && in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function knownAllergenCanonicals() {
        return array_values(array_unique(array_values($this->allergenAliasMap())));
    }

    private function isKnownAllergenCanonical($token) {
        $token = $this->normalizeAllergenToken($token);
        if ($token === '') {
            return false;
        }
        return in_array($token, $this->knownAllergenCanonicals(), true);
    }

    private function allergenFoodHintMap() {
        return [
            'cheesecake' => ['lactose', 'gluten', 'oeuf'],
            'cake' => ['gluten', 'oeuf', 'lactose'],
            'cookie' => ['gluten', 'oeuf', 'lactose'],
            'biscuit' => ['gluten'],
            'burger' => ['gluten'],
            'hamburger' => ['gluten'],
            'pizza' => ['gluten', 'lactose'],
            'croissant' => ['gluten', 'lactose', 'oeuf'],
            'brioch' => ['gluten', 'oeuf', 'lactose'],
            'patisser' => ['gluten', 'oeuf', 'lactose'],
            'patisserie' => ['gluten', 'oeuf', 'lactose'],
            'pastry' => ['gluten', 'oeuf', 'lactose'],
            'brownie' => ['gluten', 'oeuf', 'lactose'],
            'mayo' => ['oeuf'],
            'mayonnaise' => ['oeuf'],
            'crepe' => ['gluten', 'oeuf', 'lactose'],
            'pancake' => ['gluten', 'oeuf', 'lactose'],
        ];
    }

    private function expandFoodLikeAllergenHints($rawTokens, $currentTokens) {
        $hintMap = $this->allergenFoodHintMap();
        $expanded = $this->uniqueTokens((array)$currentTokens);

        foreach ((array)$rawTokens as $raw) {
            $rawNorm = $this->normalizeToken($raw);
            if ($rawNorm === '') {
                continue;
            }
            foreach ($hintMap as $needle => $mappedAllergens) {
                $needleNorm = $this->normalizeToken($needle);
                if ($needleNorm === '') {
                    continue;
                }
                $isMatch = ($rawNorm === $needleNorm) || (strpos($rawNorm, $needleNorm) !== false);
                if (!$isMatch) {
                    continue;
                }
                foreach ((array)$mappedAllergens as $a) {
                    $canon = $this->normalizeAllergenToken($a);
                    if ($canon !== '') {
                        $expanded[] = $canon;
                    }
                }
            }
        }

        return $this->uniqueTokens($expanded);
    }

    private function closestAllergenAliasToken($token) {
        if (!function_exists('levenshtein')) {
            return '';
        }
        $token = $this->normalizeToken($token);
        if ($token === '' || strlen($token) < 4) {
            return '';
        }
        $aliases = array_keys($this->allergenAliasMap());
        $best = '';
        $bestDist = 999;
        foreach ($aliases as $alias) {
            $dist = levenshtein($token, (string)$alias);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = (string)$alias;
            }
        }
        if ($best === '') {
            return '';
        }
        $maxDist = strlen($token) <= 6 ? 2 : 3;
        $ratio = $bestDist / max(1, strlen($token));
        if ($bestDist <= $maxDist && $ratio <= 0.34) {
            return $best;
        }
        return '';
    }

    private function regimeAliasMap() {
        return [
            'halal' => 'halal',
            'vegan' => 'vegan',
            'vegetalien' => 'vegan',
            '100-vegetal' => 'vegan',
            'vegetarien' => 'vegetarien',
            'vegetarian' => 'vegetarien',
            'sans-viande' => 'vegetarien',
            'sans-gluten' => 'sans-gluten',
            'gluten-free' => 'sans-gluten',
            'sans-lactose' => 'sans-lactose',
            'lactose-free' => 'sans-lactose',
            'bio' => 'bio',
            'organic' => 'bio',
            'equilibre' => 'equilibre',
            'balanced' => 'equilibre',
            'petit-budget' => 'budget',
            'budget' => 'budget',
            'economique' => 'budget',
            'cheap' => 'budget',
        ];
    }

    private function normalizeAllergenToken($value) {
        $token = $this->normalizeToken($value);
        if ($token === '') {
            return '';
        }
        $aliases = $this->allergenAliasMap();
        if (isset($aliases[$token])) {
            return $aliases[$token];
        }

        // Support multi-word inputs where one part is an allergen clue.
        $parts = preg_split('/-+/', $token);
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $part = trim((string)$part);
                if ($part !== '' && isset($aliases[$part])) {
                    return $aliases[$part];
                }
            }
        }

        // Typo-tolerance: penut -> peanut, etc.
        $closest = $this->closestAllergenAliasToken($token);
        if ($closest !== '' && isset($aliases[$closest])) {
            return $aliases[$closest];
        }

        return $token;
    }

    private function parseAllergenList($value) {
        if ($this->aiOpenAllergenMode()) {
            return $this->semanticNormalizeList('allergen', $value);
        }
        $raw = $this->extractLexicalCandidates($value);
        $tokens = array_values(array_filter(array_map(function ($item) {
            return $this->normalizeAllergenToken($item);
        }, $raw), static function ($token) {
            return $token !== '';
        }));
        return $this->uniqueTokens(array_values(array_filter($tokens, function ($token) {
            return $this->isKnownAllergenCanonical($token);
        })));
    }

    private function parseAllergenListFast($value) {
        $raw = $this->extractLexicalCandidates($value);
        $tokens = array_values(array_filter(array_map(function ($item) {
            return $this->normalizeAllergenToken($item);
        }, $raw), function ($token) {
            return $token !== '' && $this->isKnownAllergenCanonical($token);
        }));
        return $this->uniqueTokens($tokens);
    }

    private function normalizeRegimeToken($value) {
        $token = $this->normalizeToken($value);
        if ($token === '') {
            return '';
        }
        $aliases = $this->regimeAliasMap();
        return $aliases[$token] ?? $token;
    }

    private function parseRegimeList($value) {
        $raw = $this->parseList($value);
        $tokens = array_values(array_filter(array_map(function ($item) {
            return $this->normalizeRegimeToken($item);
        }, $raw), static function ($token) {
            return $token !== '';
        }));
        return $this->uniqueTokens($tokens);
    }

    private function normalizeMealAllergenDisclosureMode($meal) {
        if (!is_array($meal)) {
            return 'none';
        }
        $mode = strtolower($this->normalizeText($meal['allergen_disclosure_mode'] ?? ''));
        if ($mode === 'declared') {
            return 'declared';
        }
        $allergens = $this->normalizeText($meal['allergens'] ?? '');
        $noInfo = in_array(
            strtolower(preg_replace('/\s+/', '', $allergens)),
            ['', 'aucun', 'none', 'na', 'n/a', '-', 'pasdallergene', 'sansallergene'],
            true
        );
        return $noInfo ? 'none' : 'declared';
    }

    private function splitIngredientTokens($text) {
        $parts = preg_split('/[,;]+/', (string)$text);
        if (!$parts) {
            return [];
        }
        $tokens = [];
        foreach ($parts as $part) {
            $clean = $this->normalizeText($part);
            if ($clean !== '') {
                $tokens[] = $clean;
            }
        }
        return array_values(array_unique($tokens));
    }

    private function ingredientSynonymMap() {
        return [
            'cheese' => 'fromage',
            'fromage' => 'fromage',
            'cheddar' => 'fromage',
            'fromages' => 'fromage',
            'milk' => 'lait',
            'lait' => 'lait',
            'laits' => 'lait',
            'butter' => 'beurre',
            'beurre' => 'beurre',
            'butters' => 'beurre',
            'bread' => 'pain',
            'bun' => 'pain',
            'pain' => 'pain',
            'pains' => 'pain',
            'chocolate' => 'chocolat',
            'chocolat' => 'chocolat',
            'cacao' => 'chocolat',
            'cocoa' => 'chocolat',
            'pita' => 'pain',
            'pita-bread' => 'pain',
            'pain-libanais' => 'pain',
            'khobz' => 'pain',
            'tomato' => 'tomate',
            'tomate' => 'tomate',
            'tomatoes' => 'tomate',
            'tomates' => 'tomate',
            'lettuce' => 'laitue',
            'laitue' => 'laitue',
            'salade' => 'laitue',
            'beef' => 'viande',
            'meat' => 'viande',
            'viande' => 'viande',
            'viandes' => 'viande',
            'lamb' => 'viande',
            'chicken' => 'poulet',
            'poulet' => 'poulet',
            'egg' => 'oeuf',
            'oeuf' => 'oeuf',
            'eggs' => 'oeuf',
            'mayonnaise' => 'mayonnaise',
            'mayo' => 'mayonnaise',
            'onion' => 'oignon',
            'onions' => 'oignon',
            'oignon' => 'oignon',
            'oignons' => 'oignon',
            'flour' => 'farine',
            'farine' => 'farine',
            'wheat' => 'ble',
            'ble' => 'ble',
        ];
    }

    private function normalizeIngredientConcept($value) {
        $token = $this->normalizeToken($value);
        if ($token === '') {
            return '';
        }
        $map = $this->ingredientSynonymMap();
        if (isset($map[$token])) {
            return $map[$token];
        }
        $parts = preg_split('/-+/', $token);
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $part = trim((string)$part);
                if ($part === '') {
                    continue;
                }
                if (isset($map[$part])) {
                    return $map[$part];
                }
                if (strlen($part) > 4 && substr($part, -1) === 's') {
                    $singular = substr($part, 0, -1);
                    if (isset($map[$singular])) {
                        return $map[$singular];
                    }
                }
            }
        }
        if (strlen($token) > 4 && substr($token, -1) === 's') {
            $singular = substr($token, 0, -1);
            if (isset($map[$singular])) {
                return $map[$singular];
            }
        }
        return $token;
    }

    private function ingredientConceptLabel($concept) {
        $concept = $this->normalizeIngredientConcept($concept);
        $labels = [
            'fromage' => 'fromage',
            'lait' => 'lait',
            'beurre' => 'beurre',
            'pain' => 'pain',
            'tomate' => 'tomate',
            'laitue' => 'laitue',
            'viande' => 'viande',
            'poulet' => 'poulet',
            'oeuf' => 'oeuf',
            'mayonnaise' => 'mayonnaise',
            'oignon' => 'oignon',
            'farine' => 'farine',
            'ble' => 'ble',
        ];
        return $labels[$concept] ?? $this->normalizeText($concept);
    }

    private function ingredientConceptSet($tokens) {
        $set = [];
        foreach ((array)$tokens as $token) {
            $cleanToken = $this->normalizeText((string)$token);
            if ($cleanToken === '') {
                continue;
            }
            $concept = $this->normalizeIngredientConcept($cleanToken);
            if ($concept !== '') {
                $set[$concept] = true;
            }
            $lexical = $this->extractLexicalCandidates($cleanToken, 2);
            foreach ($lexical as $candidate) {
                $candidateConcept = $this->normalizeIngredientConcept($candidate);
                if ($candidateConcept !== '') {
                    $set[$candidateConcept] = true;
                }
            }
        }
        return $set;
    }

    private function deriveMissingIngredients($providedTokens, $inferredTokens) {
        $providedSet = $this->ingredientConceptSet($providedTokens);
        $missing = [];
        foreach ((array)$inferredTokens as $token) {
            $concept = $this->normalizeIngredientConcept($token);
            if ($concept === '' || isset($providedSet[$concept])) {
                continue;
            }
            $missing[] = $this->ingredientConceptLabel($concept);
        }
        return $this->uniqueTokens($missing);
    }

    private function mergeTriggerIngredients($existing, $incoming) {
        $out = [];
        $seenConcept = [];

        foreach ((array)$existing as $token) {
            $clean = $this->normalizeText((string)$token);
            if ($clean === '') {
                continue;
            }
            $out[] = $clean;
            $concept = $this->normalizeIngredientConcept($clean);
            if ($concept !== '') {
                $seenConcept[$concept] = true;
            }
        }

        foreach ((array)$incoming as $token) {
            $clean = $this->normalizeText((string)$token);
            if ($clean === '') {
                continue;
            }
            $concept = $this->normalizeIngredientConcept($clean);
            if ($concept !== '' && isset($seenConcept[$concept])) {
                continue;
            }
            $out[] = $clean;
            if ($concept !== '') {
                $seenConcept[$concept] = true;
            }
        }

        return array_values(array_unique($out));
    }

    private function constrainAllergenSourcesToTrustedIngredients($sourceMap, $vendorIngredients = [], $missingBasicIngredients = []) {
        $vendorSet = $this->ingredientConceptSet((array)$vendorIngredients);
        $missingSet = $this->ingredientConceptSet((array)$missingBasicIngredients);
        $cleanSources = [];
        $scopeFlags = [];

        foreach ((array)$sourceMap as $allergen => $triggers) {
            $token = $this->normalizeAllergenToken((string)$allergen);
            if ($token === '') {
                continue;
            }
            foreach ((array)$triggers as $trigger) {
                $cleanTrigger = $this->normalizeText((string)$trigger);
                if ($cleanTrigger === '') {
                    continue;
                }
                $concept = $this->normalizeIngredientConcept($cleanTrigger);
                if ($concept === '') {
                    continue;
                }
                $inVendor = isset($vendorSet[$concept]);
                $inMissing = isset($missingSet[$concept]);
                if (!$inVendor && !$inMissing) {
                    continue;
                }
                if (!isset($cleanSources[$token])) {
                    $cleanSources[$token] = [];
                }
                $cleanSources[$token][] = $cleanTrigger;
                if (!isset($scopeFlags[$token])) {
                    $scopeFlags[$token] = [];
                }
                if ($inVendor) {
                    $scopeFlags[$token]['vendor_ingredients'] = true;
                }
                if ($inMissing) {
                    $scopeFlags[$token]['basic_missing_ingredients'] = true;
                }
            }
        }

        foreach ($cleanSources as $allergen => $list) {
            $cleanSources[$allergen] = array_values(array_unique($list));
        }

        $scopes = [];
        foreach ($scopeFlags as $allergen => $flags) {
            $scopes[$allergen] = array_values(array_keys(array_filter((array)$flags)));
        }

        return [
            'sources' => $cleanSources,
            'scopes' => $scopes,
        ];
    }

    private function keepAllergensWithTrustedSources($allergens, $trustedSourceMap, $alwaysKeep = []) {
        $always = [];
        foreach ((array)$alwaysKeep as $token) {
            $norm = $this->normalizeAllergenToken((string)$token);
            if ($norm !== '') {
                $always[$norm] = true;
            }
        }

        $out = [];
        foreach ((array)$allergens as $token) {
            $norm = $this->normalizeAllergenToken((string)$token);
            if ($norm === '') {
                continue;
            }
            if (isset($always[$norm]) || !empty((array)($trustedSourceMap[$norm] ?? []))) {
                $out[] = $norm;
            }
        }
        return $this->uniqueTokens($out);
    }

    private function ensureDeclaredAllergenSourceCitations($declaredAllergens, $sourceMap, $scopeMap) {
        foreach ((array)$declaredAllergens as $token) {
            $norm = $this->normalizeAllergenToken((string)$token);
            if ($norm === '') {
                continue;
            }
            if (empty((array)($sourceMap[$norm] ?? []))) {
                $sourceMap[$norm] = ['allergene declare par le vendeur'];
            }
            if (empty((array)($scopeMap[$norm] ?? []))) {
                $scopeMap[$norm] = ['vendor_declared'];
            }
        }
        return [
            'sources' => (array)$sourceMap,
            'scopes' => (array)$scopeMap,
        ];
    }

    private function inferAllergenSourcesFromIngredientTokens($ingredientTokens, $targetAllergens = []) {
        $targets = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)$targetAllergens));
        $patterns = $this->allergenPatternMap();
        $sources = [];

        foreach ((array)$ingredientTokens as $ingredient) {
            $ingredientText = $this->normalizeText($ingredient);
            $ingredientNorm = $this->normalizeToken($ingredientText);
            if ($ingredientNorm === '') {
                continue;
            }
            $ingredientWords = preg_split('/-+/', $ingredientNorm);
            if (!is_array($ingredientWords)) {
                $ingredientWords = [$ingredientNorm];
            }
            $ingredientWords = array_values(array_filter(array_map('trim', $ingredientWords), static function ($w) {
                return $w !== '';
            }));
            foreach ($patterns as $allergen => $keywords) {
                if (!empty($targets) && !in_array($allergen, $targets, true)) {
                    continue;
                }
                foreach ($keywords as $kw) {
                    $kwNorm = $this->normalizeToken($kw);
                    if ($kwNorm === '') {
                        continue;
                    }
                    $matched = false;
                    if (strpos($kwNorm, '-') !== false) {
                        // For multi-word canonical keywords we keep a direct contains check.
                        $matched = strpos($ingredientNorm, $kwNorm) !== false;
                    } elseif (in_array($kwNorm, $ingredientWords, true)) {
                        // Exact word match prevents false positives like "lait" in "laitue".
                        $matched = true;
                    } elseif (strlen($kwNorm) >= 6) {
                        // Long stems are allowed as prefix (e.g. arachid -> arachide).
                        foreach ($ingredientWords as $word) {
                            if (strpos($word, $kwNorm) === 0) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                    if ($matched) {
                        if (!isset($sources[$allergen])) {
                            $sources[$allergen] = [];
                        }
                        $sources[$allergen][] = $ingredientText;
                        break;
                    }
                }
            }
        }

        foreach ($sources as $allergen => $list) {
            $sources[$allergen] = array_values(array_unique($list));
        }

        return $sources;
    }

    private function computeIngredientCompletenessScore($providedTokens, $basicRecipeTokens, $source) {
        $provided = array_values(array_filter(array_map(function ($v) {
            return $this->normalizeText($v);
        }, (array)$providedTokens), static function ($v) {
            return $v !== '';
        }));

        if (empty($provided)) {
            return 15;
        }

        $score = 100;
        $count = count($provided);
        if ($count <= 1) {
            $score -= 35;
        } elseif ($count <= 2) {
            $score -= 20;
        }

        $genericTokens = [
            'viande', 'meat', 'sauce', 'sauces', 'ingredient', 'ingredients',
            'legume', 'legumes', 'vegetable', 'vegetables', 'fruit', 'fruits',
            'epices', 'spices', 'mix',
        ];
        $genericHits = 0;
        foreach ($provided as $token) {
            $norm = $this->normalizeToken($token);
            foreach ($genericTokens as $g) {
                if ($norm === $this->normalizeToken($g)) {
                    $genericHits += 1;
                    break;
                }
            }
        }
        if ($count > 0) {
            $genericRatio = $genericHits / $count;
            if ($genericRatio >= 0.6) {
                $score -= 25;
            } elseif ($genericRatio >= 0.3) {
                $score -= 12;
            }
        }

        $basic = array_values(array_filter(array_map(function ($v) {
            return $this->normalizeText($v);
        }, (array)$basicRecipeTokens), static function ($v) {
            return $v !== '';
        }));
        if (!empty($basic)) {
            $providedSet = $this->ingredientConceptSet($provided);
            $basicSet = $this->ingredientConceptSet($basic);
            $expected = count($basicSet);
            if ($expected > 0) {
                $covered = 0;
                foreach (array_keys($basicSet) as $concept) {
                    if (isset($providedSet[$concept])) {
                        $covered += 1;
                    }
                }
                $coverage = $covered / $expected;
                if ($coverage < 0.2) {
                    $score -= 35;
                } elseif ($coverage < 0.4) {
                    $score -= 20;
                } elseif ($coverage < 0.6) {
                    $score -= 8;
                }
            }
        }

        if ($source === 'meal_name_recipe') {
            $score -= 10;
        }

        if ($score < 0) {
            $score = 0;
        }
        if ($score > 100) {
            $score = 100;
        }
        return (int)$score;
    }

    private function buildCausalAllergenDetails($allergens, $sourceMap, $origin, $allergenInferenceConfidence, $dataQualityConfidence, $scopeMap = []) {
        $out = [];
        foreach ((array)$allergens as $allergen) {
            $token = $this->normalizeAllergenToken($allergen);
            if ($token === '') {
                continue;
            }
            $triggers = [];
            if (isset($sourceMap[$token]) && is_array($sourceMap[$token])) {
                $triggers = array_values(array_unique(array_map(function ($v) {
                    return $this->normalizeText($v);
                }, $sourceMap[$token])));
            }
            $out[] = [
                'allergen' => $token,
                'trigger_ingredients' => $triggers,
                'source_scopes' => array_values((array)($scopeMap[$token] ?? [])),
                'confidence' => (float)$allergenInferenceConfidence,
                'data_quality_confidence' => (float)$dataQualityConfidence,
                'origin' => (string)$origin,
            ];
        }
        return $out;
    }

    private function localRecipeHintsByMealName($mealName) {
        $name = $this->normalizeToken($mealName);
        if ($name === '') {
            return ['ingredients' => [], 'allergens' => []];
        }

        $recipes = [
            'pad-thai' => [
                'ingredients' => ['rice noodles', 'soy sauce', 'peanut', 'egg', 'fish sauce'],
                'allergens' => ['arachide', 'oeuf', 'poisson', 'soja'],
            ],
            'pizza' => [
                'ingredients' => ['wheat flour', 'cheese', 'tomato sauce'],
                'allergens' => ['gluten', 'lactose'],
            ],
            'burger' => [
                'ingredients' => ['bread', 'cheese', 'sauce'],
                'allergens' => ['gluten', 'lactose', 'oeuf'],
            ],
            'hamburger' => [
                'ingredients' => ['bread', 'beef', 'lettuce', 'tomato', 'cheese'],
                'allergens' => ['gluten', 'lactose'],
            ],
            'sandwich' => [
                'ingredients' => ['bread', 'cheese', 'sauce'],
                'allergens' => ['gluten', 'lactose', 'oeuf'],
            ],
            'tacos' => [
                'ingredients' => ['wheat tortilla', 'sauce'],
                'allergens' => ['gluten'],
            ],
            'pasta' => [
                'ingredients' => ['wheat pasta', 'cream'],
                'allergens' => ['gluten', 'lactose'],
            ],
            'salade-cesar' => [
                'ingredients' => ['anchovy sauce', 'parmesan', 'egg'],
                'allergens' => ['poisson', 'lactose', 'oeuf'],
            ],
            'falafel' => [
                'ingredients' => ['chickpea', 'sesame tahini'],
                'allergens' => ['sesame'],
            ],
            'sushi' => [
                'ingredients' => ['rice', 'fish', 'soy sauce'],
                'allergens' => ['poisson', 'soja'],
            ],
            'couscous' => [
                'ingredients' => ['semolina', 'vegetables'],
                'allergens' => ['gluten'],
            ],
            'shawarma' => [
                'ingredients' => ['bread', 'chicken', 'sauce'],
                'allergens' => ['gluten', 'oeuf'],
            ],
            'chawarma' => [
                'ingredients' => ['bread', 'chicken', 'sauce'],
                'allergens' => ['gluten', 'oeuf'],
            ],
        ];

        foreach ($recipes as $key => $hint) {
            if ($name === $key || strpos($name, $key) !== false) {
                return $hint;
            }
        }

        return ['ingredients' => [], 'allergens' => []];
    }

    private function allergenPatternMap() {
        return [
            'arachide' => ['peanut', 'arachid', 'cacahuet', 'groundnut', 'satay'],
            'lactose' => ['milk', 'dairy', 'fromage', 'beurre', 'cream', 'cheese', 'lait', 'yaourt'],
            'gluten' => ['gluten', 'wheat', 'ble', 'semoule', 'pasta', 'farine', 'pain', 'bread', 'bun'],
            'soja' => ['soja', 'soy', 'tofu', 'soybean', 'soja-sauce'],
            'sesame' => ['sesame', 'tahini'],
            'fruits-a-coque' => ['almond', 'hazelnut', 'walnut', 'pistachio', 'cashew', 'noix', 'nut'],
            'oeuf' => ['egg', 'oeuf', 'omelette', 'mayonnaise'],
            'poisson' => ['fish', 'poisson', 'thon', 'saumon', 'anchois'],
            'crustaces' => ['shrimp', 'crevette', 'crab', 'lobster', 'crustace'],
        ];
    }

    private function ollamaInferBasicRecipeHintsByMealName($mealName) {
        $mealName = $this->normalizeText($mealName);
        if ($mealName === '' || !$this->ollamaEnabled()) {
            return [
                'ingredients' => [],
                'variants' => [],
                'allergens' => [],
                'confidence' => 0.0,
                'source' => 'none',
                'analysis_mode' => 'fallback_local',
            ];
        }

        $prompt = "You are CareMealRecipeBaseline.\n"
            . "Task: infer a very basic/common recipe (+ common variants) for a meal name and likely allergens.\n"
            . "Meal name: " . $mealName . "\n"
            . "Return strict JSON only:\n"
            . "{\"ingredients\":[\"...\"],\"variants\":[\"...\"],\"allergens\":[\"...\"],\"confidence\":0.0}\n"
            . "Rules:\n"
            . "- ingredients must be short food tokens\n"
            . "- keep only core/common ingredients (3 to 6 max)\n"
            . "- avoid rare/regional extras unless universally present\n"
            . "- do not include cross-contamination/traces-only allergens\n"
            . "- variants are common optional ingredients only\n"
            . "- allergens must be canonical allergy concepts if possible\n"
            . "- confidence between 0 and 1\n"
            . "- no extra text.";

        $response = $this->ollamaPostJson([
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0.1],
        ], $this->ollamaTimeoutSec(), $this->ollamaRetryCount());
        if (!is_array($response)) {
            return [
                'ingredients' => [],
                'variants' => [],
                'allergens' => [],
                'confidence' => 0.0,
                'source' => 'none',
                'analysis_mode' => 'fallback_local',
            ];
        }

        $jsonRaw = $response['response'] ?? null;
        if (!is_string($jsonRaw) || $jsonRaw === '') {
            return [
                'ingredients' => [],
                'variants' => [],
                'allergens' => [],
                'confidence' => 0.0,
                'source' => 'none',
                'analysis_mode' => 'fallback_local',
            ];
        }
        $decoded = $this->decodeJsonObjectLoose($jsonRaw);
        if (!is_array($decoded)) {
            return [
                'ingredients' => [],
                'variants' => [],
                'allergens' => [],
                'confidence' => 0.0,
                'source' => 'none',
                'analysis_mode' => 'fallback_local',
            ];
        }

        $ingredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)($decoded['ingredients'] ?? [])));
        $variants = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)($decoded['variants'] ?? [])));
        $allergens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($decoded['allergens'] ?? [])));
        $allergens = array_values(array_filter($allergens, static function ($t) {
            return is_string($t) && $t !== '';
        }));
        $confidence = (float)($decoded['confidence'] ?? 0.0);
        if ($confidence < 0) $confidence = 0;
        if ($confidence > 1) $confidence = 1;

        return [
            'ingredients' => $ingredients,
            'variants' => $variants,
            'allergens' => $allergens,
            'confidence' => $confidence,
            'source' => (!empty($ingredients) || !empty($allergens)) ? ($this->geminiEnabled() ? 'gemini_recipe' : 'ollama_recipe') : 'none',
            'analysis_mode' => (!empty($ingredients) || !empty($allergens)) ? 'ai_live' : 'fallback_local',
        ];
    }

    private function ollamaInferMealProfile($payload) {
        if (!$this->ollamaEnabled()) {
            return null;
        }

        $prompt = "You are CareMealAllergenAnalyst.\n"
            . "Return strict JSON only with this schema:\n"
            . "{\"inferred_ingredients\":[\"...\"],\"inferred_allergens\":[\"...\"],\"confidence\":0.0,\"explanation\":\"...\",\"source\":\"ingredients|meal_name_recipe\"}\n"
            . "Input:\n"
            . $this->jsonEncodeSafe($payload, JSON_UNESCAPED_UNICODE) . "\n"
            . "Rules:\n"
            . "- infer likely allergens from ingredients or meal basic recipe\n"
            . "- if vendor ingredients are already detailed, do not add unrelated proteins/sauces\n"
            . "- never replace explicit vendor proteins with different proteins\n"
            . "- do not output translated duplicates when ingredient concept already exists\n"
            . "- use short ingredient/allergen tokens\n"
            . "- confidence from 0 to 1\n"
            . "- no extra text.";

        $response = $this->ollamaPostJson([
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0.1],
        ], $this->ollamaTimeoutSec(), $this->ollamaRetryCount());
        if (!is_array($response)) {
            return null;
        }

        $jsonRaw = $response['response'] ?? null;
        if (!is_string($jsonRaw) || $jsonRaw === '') {
            return null;
        }
        $decoded = $this->decodeJsonObjectLoose($jsonRaw);
        if (!is_array($decoded)) {
            return null;
        }

        $ingredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)($decoded['inferred_ingredients'] ?? [])));

        $rawAllergens = array_map(function ($token) {
            return $this->normalizeAllergenToken($token);
        }, (array)($decoded['inferred_allergens'] ?? []));
        $allergens = $this->uniqueTokens(array_values(array_filter($rawAllergens, static function ($token) {
            return is_string($token) && $token !== '';
        })));

        $confidence = (float)($decoded['confidence'] ?? 0.0);
        if ($confidence < 0) $confidence = 0;
        if ($confidence > 1) $confidence = 1;
        $source = $this->normalizeText($decoded['source'] ?? '');
        if (!in_array($source, ['ingredients', 'meal_name_recipe'], true)) {
            $source = !empty($payload['ingredients']) ? 'ingredients' : 'meal_name_recipe';
        }

        return [
            'inferred_ingredients' => $ingredients,
            'inferred_allergens' => $allergens,
            'confidence' => $confidence,
            'explanation' => $this->normalizeText($decoded['explanation'] ?? ''),
            'source' => $source,
            'engine' => $this->aiProviderName(),
            'analysis_mode' => 'ai_live',
        ];
    }

    private function ollamaPostJson($payload, $timeoutSec = 6, $retry = 1) {
        if ($this->geminiEnabled()) {
            return $this->geminiPostCompat($payload, $timeoutSec, $retry);
        }

        $attempt = 0;
        $maxAttempts = max(1, 1 + (int)$retry);
        while ($attempt < $maxAttempts) {
            $response = $this->httpPostJson($this->ollamaEndpoint(), $payload, $timeoutSec);
            if (is_array($response)) {
                return $response;
            }
            $attempt += 1;
            if ($attempt < $maxAttempts) {
                usleep(150000);
            }
        }
        return null;
    }

    private function geminiPostCompat($payload, $timeoutSec = 6, $retry = 1) {
        $payload = is_array($payload) ? $payload : [];
        $system = $this->normalizeText($payload['system'] ?? '');
        $prompt = $this->normalizeText($payload['prompt'] ?? '');
        if ($prompt === '') {
            return null;
        }

        $temperature = 0.1;
        if (isset($payload['options']) && is_array($payload['options']) && isset($payload['options']['temperature'])) {
            $temperature = (float)$payload['options']['temperature'];
        }
        if ($temperature < 0) $temperature = 0;
        if ($temperature > 1) $temperature = 1;

        $request = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => $temperature,
                'responseMimeType' => 'application/json',
            ],
        ];
        if ($system !== '') {
            $request['systemInstruction'] = [
                'parts' => [['text' => $system]],
            ];
        }
        if ($this->geminiWebSearchEnabled()) {
            $request['tools'] = [['google_search' => (object)[]]];
        }

        $key = trim((string)caremeal_env('CAREMEAL_GEMINI_API_KEY', ''));
        $url = $this->geminiApiEndpoint()
            . '/' . rawurlencode($this->geminiModel())
            . ':generateContent?key=' . rawurlencode($key);

        $attempt = 0;
        $maxAttempts = max(1, 1 + (int)$retry);
        while ($attempt < $maxAttempts) {
            $response = $this->httpPostJson($url, $request, $timeoutSec);
            if (is_array($response)) {
                $text = $this->extractGeminiText($response);
                if ($text !== '') {
                    return ['response' => $text];
                }
            }
            $attempt += 1;
            if ($attempt < $maxAttempts) {
                usleep(150000);
            }
        }
        return null;
    }

    private function extractGeminiText($response) {
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
        $chunks = [];
        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }
            $text = trim((string)($part['text'] ?? ''));
            if ($text !== '') {
                $chunks[] = $text;
            }
        }
        return trim(implode("\n", $chunks));
    }

    private function asyncMealAiEnabled() {
        $flag = strtolower((string)caremeal_env('CAREMEAL_ASYNC_MEAL_AI_ENABLED', '1'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function matchingSyncAiEnabled() {
        $flag = strtolower((string)caremeal_env('CAREMEAL_MATCHING_SYNC_AI_ENABLED', '0'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function profileFromPrecomputedMealAnalysis($meal, $mealName, $ingredients, $disclosureMode, $declaredAllergens) {
        if (!is_array($meal)) {
            return null;
        }

        $analysisStatus = (int)($meal['analyse_ia'] ?? 0);
        $rawAllergens = is_array($meal['allergenes_finaux'] ?? null) ? $meal['allergenes_finaux'] : [];
        $rawStandardized = is_array($meal['ingredients_standardises'] ?? null) ? $meal['ingredients_standardises'] : [];
        $rawMissing = is_array($meal['ingredients_manquants_probables'] ?? null) ? $meal['ingredients_manquants_probables'] : [];

        if ($analysisStatus === 0 && empty($rawAllergens) && empty($rawStandardized) && empty($rawMissing)) {
            return null;
        }

        $inferredIngredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)$rawStandardized));
        if (empty($inferredIngredients) && $ingredients !== '') {
            $inferredIngredients = $this->splitIngredientTokens($ingredients);
        }

        $missingIngredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)$rawMissing));
        $providedIngredients = $this->splitIngredientTokens($ingredients);

        $inferredAllergens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)$rawAllergens));

        $possibleMissing = !empty($missingIngredients);
        $completeness = 100;
        if (!empty($inferredIngredients)) {
            $completeness = max(35, min(100, 100 - (int)floor((count($missingIngredients) / max(1, count($inferredIngredients))) * 55)));
        }

        $source = $ingredients !== '' ? 'ingredients' : 'meal_name_recipe';
        $originCode = $source === 'meal_name_recipe' ? 'ai_from_basic_recipe' : 'ai_from_ingredients';
        $originBadge = $source === 'meal_name_recipe' ? 'AI inferred from basic recipe' : 'AI inferred from ingredients';
        $analysisMode = 'ai_live';
        if ($analysisStatus === 0) {
            $analysisMode = 'pending';
            $originCode = 'ai_pending';
            $originBadge = 'AI analysis pending';
        } elseif ($analysisStatus < 0) {
            $analysisMode = 'fallback_local';
            $originCode = 'fallback_local';
            $originBadge = 'Fallback local (IA indisponible)';
        }
        $semanticAllergens = $this->uniqueTokens(array_merge(
            $disclosureMode === 'declared' ? $declaredAllergens : [],
            $inferredAllergens
        ));
        $evidenceBase = $ingredients !== '' ? $ingredients : implode(', ', $inferredIngredients);
        $allergenSources = $this->inferAllergenEvidenceFromText($evidenceBase, $semanticAllergens);

        if (!empty($inferredIngredients)) {
            $tokenEvidence = $this->inferAllergenSourcesFromIngredientTokens($inferredIngredients, $semanticAllergens);
            foreach ($tokenEvidence as $allergen => $src) {
                if (!isset($allergenSources[$allergen])) {
                    $allergenSources[$allergen] = [];
                }
                $allergenSources[$allergen] = $this->mergeTriggerIngredients((array)$allergenSources[$allergen], (array)$src);
            }
        }

        $trustedEvidence = $this->constrainAllergenSourcesToTrustedIngredients(
            $allergenSources,
            $providedIngredients,
            $missingIngredients
        );
        $allergenSources = (array)($trustedEvidence['sources'] ?? []);
        $allergenSourceScopes = (array)($trustedEvidence['scopes'] ?? []);
        $inferredAllergens = $this->keepAllergensWithTrustedSources($inferredAllergens, $allergenSources);
        $semanticAllergens = $this->uniqueTokens(array_merge(
            $disclosureMode === 'declared' ? $declaredAllergens : [],
            $inferredAllergens
        ));
        $declaredCitations = $this->ensureDeclaredAllergenSourceCitations($declaredAllergens, $allergenSources, $allergenSourceScopes);
        $allergenSources = (array)($declaredCitations['sources'] ?? []);
        $allergenSourceScopes = (array)($declaredCitations['scopes'] ?? []);

        $dataQualityConfidence = max(0.2, min(1.0, ((float)$completeness) / 100.0));
        $inferenceConfidence = $analysisStatus === 1 ? 0.82 : 0.55;
        $globalConfidence = max(0.25, min(1.0, ($dataQualityConfidence * 0.55) + ($inferenceConfidence * 0.45)));

        return [
            'declared_allergens' => $declaredAllergens,
            'inferred_ingredients' => $inferredIngredients,
            'variant_ingredients' => [],
            'missing_ingredients' => $missingIngredients,
            'inferred_allergens' => $inferredAllergens,
            'semantic_allergens' => $semanticAllergens,
            'confidence' => $globalConfidence,
            'explanation' => 'Analyse IA pre-calculee chargee depuis la base.',
            'source' => $source,
            'origin_badge' => $originBadge,
            'origin_code' => $originCode,
            'warning' => true,
            'possible_missing_ingredients' => $possibleMissing,
            'basic_recipe_ingredients' => [],
            'basic_recipe_allergens' => [],
            'allergen_sources' => $allergenSources,
            'allergen_source_scopes' => $allergenSourceScopes,
            'causal_allergen_details' => $this->buildCausalAllergenDetails(
                $semanticAllergens,
                $allergenSources,
                $originCode,
                $inferenceConfidence,
                $dataQualityConfidence,
                $allergenSourceScopes
            ),
            'ingredient_completeness_score' => $completeness,
            'data_quality_confidence' => $dataQualityConfidence,
            'allergen_inference_confidence' => $inferenceConfidence,
            'ai_check_passed' => $analysisStatus === 1,
            'engine' => $analysisStatus === 1 ? ($this->geminiEnabled() ? 'gemini-async' : 'ollama+searxng-async') : ($analysisStatus < 0 ? 'fallback_local' : 'deterministic-pending'),
            'analysis_mode' => $analysisMode,
        ];
    }

    private function inferMealAllergenProfile($meal) {
        if (!is_array($meal)) {
            return [
                'declared_allergens' => [],
                'inferred_ingredients' => [],
                'variant_ingredients' => [],
                'missing_ingredients' => [],
                'inferred_allergens' => [],
                'semantic_allergens' => [],
                'confidence' => 0.0,
                'explanation' => '',
                'source' => 'ingredients',
                'origin_badge' => 'Vendor declared',
                'origin_code' => 'vendor_declared',
                'warning' => false,
                'possible_missing_ingredients' => false,
                'basic_recipe_ingredients' => [],
                'basic_recipe_allergens' => [],
                'allergen_sources' => [],
                'allergen_source_scopes' => [],
                'causal_allergen_details' => [],
                'ingredient_completeness_score' => 0,
                'data_quality_confidence' => 0.0,
                'allergen_inference_confidence' => 0.0,
                'ai_check_passed' => false,
                'engine' => 'deterministic',
                'analysis_mode' => 'fallback_local',
            ];
        }

        $mealName = $this->normalizeText($meal['meal_name'] ?? '');
        $ingredients = $this->normalizeText($meal['ingredients'] ?? '');
        $disclosureMode = $this->normalizeMealAllergenDisclosureMode($meal);
        $declaredAllergens = $this->semanticNormalizeList('allergen', $meal['allergens'] ?? '');

        if ($this->asyncMealAiEnabled()) {
            $precomputedProfile = $this->profileFromPrecomputedMealAnalysis(
                $meal,
                $mealName,
                $ingredients,
                $disclosureMode,
                $declaredAllergens
            );
            if (is_array($precomputedProfile)) {
                return $precomputedProfile;
            }

            if (!$this->matchingSyncAiEnabled()) {
                $providedIngredients = $this->splitIngredientTokens($ingredients);
                $detectedAllergens = $declaredAllergens;
                if (empty($detectedAllergens) && $ingredients !== '') {
                    $detectedAllergens = $this->inferAllergensFromText($ingredients);
                }
                $detectedAllergens = $this->uniqueTokens(array_map(function ($token) {
                    return $this->normalizeAllergenToken((string)$token);
                }, (array)$detectedAllergens));

                $sourceMap = $this->inferAllergenSourcesFromIngredientTokens($providedIngredients, $detectedAllergens);
                if (empty($sourceMap) && $ingredients !== '') {
                    $sourceMap = $this->inferAllergenEvidenceFromText($ingredients, $detectedAllergens);
                }
                $trustedEvidence = $this->constrainAllergenSourcesToTrustedIngredients($sourceMap, $providedIngredients, []);
                $sourceMap = (array)($trustedEvidence['sources'] ?? []);
                $sourceScopes = (array)($trustedEvidence['scopes'] ?? []);
                $detectedAllergens = $this->keepAllergensWithTrustedSources($detectedAllergens, $sourceMap, $declaredAllergens);
                $semanticAllergens = $this->uniqueTokens(array_merge($declaredAllergens, $detectedAllergens));
                $declaredCitations = $this->ensureDeclaredAllergenSourceCitations($declaredAllergens, $sourceMap, $sourceScopes);
                $sourceMap = (array)($declaredCitations['sources'] ?? []);
                $sourceScopes = (array)($declaredCitations['scopes'] ?? []);

                $completeness = max(15, min(100, count($providedIngredients) * 18));
                $dataQualityConfidence = max(0.15, min(1.0, ((float)$completeness) / 100.0));

                return [
                    'declared_allergens' => $declaredAllergens,
                    'inferred_ingredients' => $providedIngredients,
                    'variant_ingredients' => [],
                    'missing_ingredients' => [],
                    'inferred_allergens' => $detectedAllergens,
                    'semantic_allergens' => $semanticAllergens,
                    'confidence' => 0.45,
                    'explanation' => 'Analyse IA detaillee en cours de preparation en arriere-plan.',
                    'source' => $ingredients !== '' ? 'ingredients' : 'meal_name_recipe',
                    'origin_badge' => 'AI analysis pending',
                    'origin_code' => 'ai_pending',
                    'warning' => true,
                    'possible_missing_ingredients' => false,
                    'basic_recipe_ingredients' => [],
                    'basic_recipe_allergens' => [],
                    'allergen_sources' => $sourceMap,
                    'allergen_source_scopes' => $sourceScopes,
                    'causal_allergen_details' => $this->buildCausalAllergenDetails(
                        $semanticAllergens,
                        $sourceMap,
                        'ai_pending',
                        0.45,
                        $dataQualityConfidence,
                        $sourceScopes
                    ),
                    'ingredient_completeness_score' => $completeness,
                    'data_quality_confidence' => $dataQualityConfidence,
                    'allergen_inference_confidence' => 0.45,
                    'ai_check_passed' => false,
                    'engine' => 'deterministic-pending',
                    'analysis_mode' => 'pending',
                ];
            }
        }

        if ($disclosureMode === 'declared') {
            $providedIngredients = $this->splitIngredientTokens($ingredients);
            $declaredSourceMap = $this->inferAllergenSourcesFromIngredientTokens($providedIngredients, $declaredAllergens);
            if (empty($declaredSourceMap) && $ingredients !== '') {
                $declaredSourceMap = $this->inferAllergenEvidenceFromText($ingredients, $declaredAllergens);
            }
            $trustedEvidence = $this->constrainAllergenSourcesToTrustedIngredients($declaredSourceMap, $providedIngredients, []);
            $declaredSourceMap = (array)($trustedEvidence['sources'] ?? []);
            $declaredSourceScopes = (array)($trustedEvidence['scopes'] ?? []);
            $declaredCitations = $this->ensureDeclaredAllergenSourceCitations($declaredAllergens, $declaredSourceMap, $declaredSourceScopes);
            $declaredSourceMap = (array)($declaredCitations['sources'] ?? []);
            $declaredSourceScopes = (array)($declaredCitations['scopes'] ?? []);
            return [
                'declared_allergens' => $declaredAllergens,
                'inferred_ingredients' => $providedIngredients,
                'variant_ingredients' => [],
                'missing_ingredients' => [],
                'inferred_allergens' => [],
                'semantic_allergens' => $declaredAllergens,
                'confidence' => 1.0,
                'explanation' => 'Allergenes declares par le vendeur. Verification IA: OK.',
                'source' => 'declared',
                'origin_badge' => 'Vendor declared',
                'origin_code' => 'vendor_declared',
                'warning' => false,
                'possible_missing_ingredients' => false,
                'basic_recipe_ingredients' => [],
                'basic_recipe_allergens' => [],
                'allergen_sources' => $declaredSourceMap,
                'allergen_source_scopes' => $declaredSourceScopes,
                'causal_allergen_details' => $this->buildCausalAllergenDetails(
                    $declaredAllergens,
                    $declaredSourceMap,
                    'vendor_declared',
                    1.0,
                    1.0,
                    $declaredSourceScopes
                ),
                'ingredient_completeness_score' => 100,
                'data_quality_confidence' => 1.0,
                'allergen_inference_confidence' => 1.0,
                'ai_check_passed' => true,
                'engine' => $this->ollamaEnabled() ? 'hybrid' : 'deterministic',
                'analysis_mode' => $this->ollamaEnabled() ? 'ai_live' : 'fallback_local',
            ];
        }

        $memoKey = md5($this->jsonEncodeSafe([
            'v' => $this->aiPipelineVersion(),
            'model' => $this->ollamaModel(),
            'n' => $mealName,
            'i' => $ingredients,
            'mode' => $disclosureMode,
            'a' => $meal['allergens'] ?? '',
        ], JSON_UNESCAPED_UNICODE));
        if (isset($this->mealInferenceMemo[$memoKey])) {
            return $this->mealInferenceMemo[$memoKey];
        }
        $cached = $this->readMealInferenceCache($memoKey);
        if (is_array($cached)) {
            $this->mealInferenceMemo[$memoKey] = $cached;
            return $cached;
        }

        $payload = [
            'meal_name' => $mealName,
            'ingredients' => $ingredients,
            'declared_allergens' => '',
            'allergen_disclosure_mode' => $disclosureMode,
        ];
        $ia = $this->ollamaInferMealProfile($payload);

        $source = $ingredients !== '' ? 'ingredients' : 'meal_name_recipe';
        $inferredIngredients = [];
        $variantIngredients = [];
        $inferredAllergens = [];
        $confidence = 0.55;
        $explanation = '';
        $engine = 'deterministic';
        $providedIngredients = $this->splitIngredientTokens($ingredients);
        $analysisMode = 'fallback_local';

        // IA-first baseline recipe + variants (dynamic, any meal name).
        $aiRecipe = $this->ollamaInferBasicRecipeHintsByMealName($mealName);
        $basicRecipeIngredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)($aiRecipe['ingredients'] ?? [])));
        $variantIngredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)($aiRecipe['variants'] ?? [])));
        $basicRecipeAllergens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($aiRecipe['allergens'] ?? [])));
        $possibleMissingIngredients = false;
        if (($aiRecipe['analysis_mode'] ?? 'fallback_local') === 'ai_live') {
            $analysisMode = 'ai_live';
        }

        // Technical fallback only when IA did not return usable baseline.
        if (empty($basicRecipeIngredients) && empty($basicRecipeAllergens)) {
            $recipeHints = $this->localRecipeHintsByMealName($mealName);
            $basicRecipeIngredients = $this->uniqueTokens(array_map(function ($token) {
                return strtolower($this->normalizeText((string)$token));
            }, (array)($recipeHints['ingredients'] ?? [])));
            $basicRecipeAllergens = $this->uniqueTokens(array_map(function ($token) {
                return $this->normalizeAllergenToken((string)$token);
            }, (array)($recipeHints['allergens'] ?? [])));
        }

        if (is_array($ia)) {
            $inferredIngredients = (array)$ia['inferred_ingredients'];
            $inferredAllergens = (array)$ia['inferred_allergens'];
            $confidence = (float)$ia['confidence'];
            $explanation = (string)$ia['explanation'];
            $source = (string)$ia['source'];
            $engine = $this->aiProviderName();
            $analysisMode = 'ai_live';
        }

        if (empty($inferredIngredients) && $ingredients !== '') {
            $inferredIngredients = $this->splitIngredientTokens($ingredients);
        }
        if (empty($inferredAllergens) && $ingredients !== '') {
            $inferredAllergens = $this->inferAllergensFromText($ingredients);
        }

        // Vendor-first hardening: when vendor ingredients are reasonably complete,
        // lock inference close to vendor data and avoid noisy recipe/variant expansion.
        $vendorCompletenessPre = $this->computeIngredientCompletenessScore(
            $providedIngredients,
            $basicRecipeIngredients,
            $source
        );
        if ($ingredients !== '' && $vendorCompletenessPre >= 70 && count($providedIngredients) >= 3) {
            $source = 'ingredients';
            $inferredIngredients = $this->uniqueTokens($providedIngredients);
            $variantIngredients = [];
            $possibleMissingIngredients = false;
            $strictEvidence = $this->inferAllergenSourcesFromIngredientTokens($providedIngredients);
            $strictAllergens = array_keys($strictEvidence);
            if (empty($strictAllergens)) {
                $strictAllergens = $this->inferAllergensFromText($ingredients);
            }
            $inferredAllergens = $this->uniqueTokens(array_map(function ($token) {
                return $this->normalizeAllergenToken((string)$token);
            }, (array)$strictAllergens));
            if ($explanation === '') {
                $explanation = 'Donnees vendeur detaillees; l IA reste alignee sur les ingredients fournis.';
            }
            if ($confidence < 0.65) {
                $confidence = 0.65;
            }
        }

        if ($ingredients === '' && empty($inferredIngredients) && empty($inferredAllergens)) {
            $inferredIngredients = $basicRecipeIngredients;
            $inferredAllergens = $basicRecipeAllergens;
            $source = 'meal_name_recipe';
            $possibleMissingIngredients = true;
            if ($confidence < 0.45) {
                $confidence = 0.45;
            }
        }

        if ($ingredients !== '' && count($providedIngredients) <= 1 && !empty($basicRecipeIngredients)) {
            $possibleMissingIngredients = true;
            $inferredAllergens = $this->uniqueTokens(array_merge($inferredAllergens, $basicRecipeAllergens));
            $inferredIngredients = $this->uniqueTokens(array_merge($inferredIngredients, $basicRecipeIngredients));
            if ($explanation === '') {
                $explanation = 'La description ingredients semble incomplete; l IA a compare avec la recette de base.';
            }
            if ($confidence < 0.50) {
                $confidence = 0.50;
            }
        }

        // Vendor-first but safety-first: if a known base recipe indicates missing core parts,
        // we enrich potential allergens from that base recipe (especially useful for gluten in burgers/sandwiches).
        if ($ingredients !== '' && !empty($basicRecipeIngredients) && !empty($basicRecipeAllergens) && $vendorCompletenessPre < 70) {
            $missingFromRecipe = $this->deriveMissingIngredients($providedIngredients, $basicRecipeIngredients);
            if (!empty($missingFromRecipe)) {
                $possibleMissingIngredients = true;
                $inferredIngredients = $this->uniqueTokens(array_merge($inferredIngredients, $basicRecipeIngredients));
                $inferredAllergens = $this->uniqueTokens(array_merge($inferredAllergens, $basicRecipeAllergens));
                if ($explanation === '') {
                    $explanation = 'Des elements de recette de base semblent absents du descriptif vendeur; l IA a complete les risques allergenes potentiels.';
                } else {
                    $explanation .= ' Recette de base utilisee pour completer les risques potentiels.';
                }
                if ($confidence < 0.58) {
                    $confidence = 0.58;
                }
            }
        }

        if ($explanation === '') {
            if ($source === 'meal_name_recipe') {
                $explanation = 'Inference IA basee sur le nom du meal (recette standard).';
            } else {
                $explanation = 'Inference IA basee sur les ingredients fournis.';
            }
        }

        $profile = [
            'declared_allergens' => [],
            'inferred_ingredients' => $this->uniqueTokens($inferredIngredients),
            'variant_ingredients' => $this->uniqueTokens($variantIngredients),
            'missing_ingredients' => [],
            'inferred_allergens' => $this->uniqueTokens(array_values(array_filter($inferredAllergens, static function ($t) {
                return is_string($t) && $t !== '';
            }))),
            'semantic_allergens' => $this->uniqueTokens(array_merge([], $inferredAllergens)),
            'confidence' => $confidence,
            'explanation' => $explanation,
            'source' => $source,
            'origin_badge' => $source === 'meal_name_recipe' ? 'AI inferred from basic recipe' : 'AI inferred from ingredients',
            'origin_code' => $source === 'meal_name_recipe' ? 'ai_from_basic_recipe' : 'ai_from_ingredients',
            'warning' => true,
            'engine' => $engine,
            'analysis_mode' => $analysisMode,
        ];

        $profile['semantic_allergens'] = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)$profile['semantic_allergens']));
        $profile['possible_missing_ingredients'] = $possibleMissingIngredients;
        $profile['basic_recipe_ingredients'] = $basicRecipeIngredients;
        $profile['basic_recipe_allergens'] = $basicRecipeAllergens;
        $profile['ingredient_completeness_score'] = $this->computeIngredientCompletenessScore(
            $providedIngredients,
            $basicRecipeIngredients,
            $source
        );
        $profile['data_quality_confidence'] = max(0.05, min(1.0, ((float)$profile['ingredient_completeness_score']) / 100.0));
        $profile['allergen_inference_confidence'] = max(
            0.05,
            min(1.0, ((float)$confidence * 0.75) + (((float)$profile['data_quality_confidence']) * 0.25))
        );

        $missingIngredients = $this->deriveMissingIngredients($providedIngredients, (array)$profile['inferred_ingredients']);
        if (empty($missingIngredients) && !empty($basicRecipeIngredients) && !empty($providedIngredients)) {
            $missingIngredients = $this->deriveMissingIngredients($providedIngredients, $basicRecipeIngredients);
        }
        $profile['missing_ingredients'] = $this->uniqueTokens($missingIngredients);
        if (!empty($profile['missing_ingredients'])) {
            $profile['possible_missing_ingredients'] = true;
        }

        $evidenceBase = $ingredients !== ''
            ? $ingredients
            : implode(', ', (array)$profile['inferred_ingredients']);
        $profile['allergen_sources'] = $this->inferAllergenEvidenceFromText(
            $evidenceBase,
            (array)$profile['semantic_allergens']
        );
        if (!empty($providedIngredients)) {
            $tokenEvidence = $this->inferAllergenSourcesFromIngredientTokens($providedIngredients, (array)$profile['semantic_allergens']);
            if (!empty($tokenEvidence)) {
                foreach ($tokenEvidence as $allergen => $src) {
                    if (!isset($profile['allergen_sources'][$allergen])) {
                        $profile['allergen_sources'][$allergen] = [];
                    }
                    $profile['allergen_sources'][$allergen] = $this->mergeTriggerIngredients(
                        (array)$profile['allergen_sources'][$allergen],
                        (array)$src
                    );
                }
            }
        }
        $missingEvidenceTokens = (array)$profile['missing_ingredients'];
        if (!empty($missingEvidenceTokens)) {
            $recipeEvidence = $this->inferAllergenSourcesFromIngredientTokens($missingEvidenceTokens, (array)$profile['semantic_allergens']);
            if (!empty($recipeEvidence)) {
                foreach ($recipeEvidence as $allergen => $src) {
                    if (!isset($profile['allergen_sources'][$allergen])) {
                        $profile['allergen_sources'][$allergen] = [];
                    }
                    $profile['allergen_sources'][$allergen] = $this->mergeTriggerIngredients(
                        (array)$profile['allergen_sources'][$allergen],
                        (array)$src
                    );
                }
            }
        }
        $trustedEvidence = $this->constrainAllergenSourcesToTrustedIngredients(
            (array)$profile['allergen_sources'],
            $providedIngredients,
            (array)$profile['missing_ingredients']
        );
        $profile['allergen_sources'] = (array)($trustedEvidence['sources'] ?? []);
        $profile['allergen_source_scopes'] = (array)($trustedEvidence['scopes'] ?? []);
        $profile['inferred_allergens'] = $this->keepAllergensWithTrustedSources(
            (array)$profile['inferred_allergens'],
            (array)$profile['allergen_sources']
        );
        $profile['semantic_allergens'] = $this->uniqueTokens(array_merge(
            (array)$profile['declared_allergens'],
            (array)$profile['inferred_allergens']
        ));
        $declaredCitations = $this->ensureDeclaredAllergenSourceCitations(
            (array)$profile['declared_allergens'],
            (array)$profile['allergen_sources'],
            (array)$profile['allergen_source_scopes']
        );
        $profile['allergen_sources'] = (array)($declaredCitations['sources'] ?? []);
        $profile['allergen_source_scopes'] = (array)($declaredCitations['scopes'] ?? []);
        $profile['causal_allergen_details'] = $this->buildCausalAllergenDetails(
            (array)$profile['semantic_allergens'],
            (array)$profile['allergen_sources'],
            (string)($profile['origin_code'] ?? 'ai_from_ingredients'),
            (float)$profile['allergen_inference_confidence'],
            (float)$profile['data_quality_confidence'],
            (array)($profile['allergen_source_scopes'] ?? [])
        );
        $profile['ai_check_passed'] = ((int)$profile['ingredient_completeness_score'] >= 80) && empty($profile['missing_ingredients']);

        $this->mealInferenceMemo[$memoKey] = $profile;
        $this->writeMealInferenceCache($memoKey, $profile);
        return $profile;
    }

    private function ollamaEnabled() {
        $ollamaFlag = strtolower((string)caremeal_env('CAREMEAL_OLLAMA_ENABLED', '0'));
        $geminiFlag = strtolower((string)caremeal_env('CAREMEAL_GEMINI_ENABLED', '0'));
        $ollamaOn = in_array($ollamaFlag, ['1', 'true', 'yes', 'on'], true);
        $geminiOn = in_array($geminiFlag, ['1', 'true', 'yes', 'on'], true)
            && trim((string)caremeal_env('CAREMEAL_GEMINI_API_KEY', '')) !== '';
        return $ollamaOn || $geminiOn;
    }

    private function geminiEnabled() {
        $flag = strtolower((string)caremeal_env('CAREMEAL_GEMINI_ENABLED', '0'));
        if (!in_array($flag, ['1', 'true', 'yes', 'on'], true)) {
            return false;
        }
        return trim((string)caremeal_env('CAREMEAL_GEMINI_API_KEY', '')) !== '';
    }

    private function ollamaEndpoint() {
        return trim((string)caremeal_env('CAREMEAL_OLLAMA_ENDPOINT', 'http://127.0.0.1:11434/api/generate'));
    }

    private function ollamaModel() {
        if ($this->geminiEnabled()) {
            return $this->geminiModel();
        }
        return trim((string)caremeal_env('CAREMEAL_OLLAMA_MODEL', 'llama3.1:8b'));
    }

    private function geminiModel() {
        return trim((string)caremeal_env('CAREMEAL_GEMINI_MODEL', 'gemini-3-flash-preview'));
    }

    private function geminiApiEndpoint() {
        $base = trim((string)caremeal_env('CAREMEAL_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'));
        if ($base === '') {
            $base = 'https://generativelanguage.googleapis.com/v1beta/models';
        }
        return rtrim($base, '/');
    }

    private function geminiWebSearchEnabled($context = 'sync') {
        $context = strtolower($this->normalizeText($context));
        if ($context === 'sync') {
            $flag = strtolower((string)caremeal_env('CAREMEAL_GEMINI_WEB_SEARCH_SYNC', '0'));
        } else {
            $flag = strtolower((string)caremeal_env('CAREMEAL_GEMINI_WEB_SEARCH', '0'));
        }
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function aiProviderName() {
        return $this->geminiEnabled() ? 'gemini' : 'ollama';
    }

    private function httpPostJson($url, $payload, $timeoutSec = 4) {
        $body = $this->jsonEncodeSafe($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($body) || $body === '') {
            return null;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSec);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
            $raw = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
                return null;
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => $timeoutSec,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function ollamaCanonicalizeTokens($type, $tokens) {
        $tokens = $this->uniqueTokens(array_map(function ($t) {
            return $this->normalizeText($t);
        }, (array)$tokens));
        if (empty($tokens) || !$this->ollamaEnabled()) {
            return [];
        }

        $type = $type === 'regime' ? 'regime' : 'allergen';
        $memoKey = $type . ':' . $this->aiPipelineVersion() . ':' . md5($this->jsonEncodeSafe([$this->ollamaModel(), $tokens]));
        if (isset($this->semanticMemo[$memoKey])) {
            return $this->semanticMemo[$memoKey];
        }

        $prompt = '';
        if ($type === 'allergen' && $this->aiOpenAllergenMode()) {
            $prompt = "You are CareMealSemanticGuard.\n"
                . "Task: normalize free food allergy tokens in multilingual text (French/English/Arabic transliteration possible).\n"
                . "Input tokens: " . $this->jsonEncodeSafe($tokens, JSON_UNESCAPED_UNICODE) . "\n"
                . "Return strict JSON only with this schema:\n"
                . "{\"pairs\":[{\"input\":\"...\",\"canonical\":[\"...\"]}],\"notes\":[\"...\"]}\n"
                . "Rules:\n"
                . "- Fix typos (penut -> peanut).\n"
                . "- Map food words to allergy concepts when relevant (fromage/cheese -> lactose, bread/pain -> gluten, crevette/shrimp -> crustaces).\n"
                . "- If no allergy concept can be inferred, keep one normalized concept token from input.\n"
                . "- canonical values must be lowercase, short, without punctuation.\n"
                . "- no extra text.";
        } else {
            $allowed = $type === 'regime'
                ? array_values(array_unique(array_values($this->regimeAliasMap())))
                : array_values(array_unique(array_values($this->allergenAliasMap())));
            $prompt = "You are CareMealSemanticGuard.\n"
                . "Task: normalize food {$type} tokens to canonical labels.\n"
                . "Input tokens: " . $this->jsonEncodeSafe($tokens, JSON_UNESCAPED_UNICODE) . "\n"
                . "Allowed canonical labels only: " . $this->jsonEncodeSafe($allowed, JSON_UNESCAPED_UNICODE) . "\n"
                . "Return strict JSON only with this schema:\n"
                . "{\"pairs\":[{\"input\":\"...\",\"canonical\":[\"...\"]}],\"notes\":[\"...\"]}\n"
                . "Rules: if unknown, canonical must be []. no extra text.";
        }

        $response = $this->ollamaPostJson([
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0],
        ], $this->ollamaSemanticTimeoutSec(), $this->ollamaSemanticRetryCount());
        $result = [];

        $jsonRaw = is_array($response) ? ($response['response'] ?? null) : null;
        if (is_string($jsonRaw) && $jsonRaw !== '') {
            $decoded = json_decode($jsonRaw, true);
            if (is_array($decoded) && isset($decoded['pairs']) && is_array($decoded['pairs'])) {
                foreach ($decoded['pairs'] as $pair) {
                    if (!is_array($pair)) {
                        continue;
                    }
                    $in = $this->normalizeText($pair['input'] ?? '');
                    if ($in === '') {
                        continue;
                    }
                    $canon = [];
                    foreach ((array)($pair['canonical'] ?? []) as $c) {
                        $c = $type === 'regime' ? $this->normalizeRegimeToken($c) : $this->normalizeAllergenToken($c);
                        if ($c !== '') {
                            $canon[] = $c;
                        }
                    }
                    $result[$in] = $this->uniqueTokens($canon);
                }
            }
        }

        $this->semanticMemo[$memoKey] = $result;
        return $result;
    }

    private function ollamaExtractAllergenConceptsFromText($value) {
        $text = is_array($value) ? implode(', ', array_map(function ($v) {
            return $this->normalizeText($v);
        }, $value)) : $this->normalizeText($value);

        if ($text === '' || !$this->aiOpenAllergenMode()) {
            return [];
        }

        $memoKey = 'allergen-free-text:' . $this->aiPipelineVersion() . ':' . md5($this->ollamaModel() . '|' . $text);
        if (isset($this->semanticMemo[$memoKey]) && is_array($this->semanticMemo[$memoKey])) {
            return $this->semanticMemo[$memoKey];
        }

        $prompt = "You are CareMealAllergenExtractor.\n"
            . "Extract allergy concepts from this user text (multilingual, typo tolerant):\n"
            . $text . "\n"
            . "Return strict JSON only:\n"
            . "{\"concepts\":[\"...\"],\"notes\":[\"...\"]}\n"
            . "Rules:\n"
            . "- normalize to canonical lowercase concepts.\n"
            . "- food names are allowed if they imply allergy bucket (peanut->arachide, bread->gluten, cheese->lactose).\n"
            . "- if uncertain, keep best normalized concept token.\n"
            . "- no extra text.";

        $response = $this->ollamaPostJson([
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0],
        ], $this->ollamaSemanticTimeoutSec(), $this->ollamaSemanticRetryCount());

        $result = [];
        $jsonRaw = is_array($response) ? ($response['response'] ?? null) : null;
        if (is_string($jsonRaw) && $jsonRaw !== '') {
            $decoded = json_decode($jsonRaw, true);
            if (is_array($decoded) && isset($decoded['concepts']) && is_array($decoded['concepts'])) {
                foreach ($decoded['concepts'] as $c) {
                    $canon = $this->normalizeAllergenToken((string)$c);
                    if ($canon !== '') {
                        $result[] = $canon;
                    }
                }
            }
        }

        $result = $this->uniqueTokens($result);
        $this->semanticMemo[$memoKey] = $result;
        return $result;
    }

    private function semanticNormalizeList($type, $value) {
        $rawTokens = $type === 'allergen'
            ? $this->extractLexicalCandidates($value)
            : $this->parseList($value);
        if (empty($rawTokens)) {
            return [];
        }
        $normalized = [];
        $unknown = [];

        if ($type === 'allergen' && $this->aiOpenAllergenMode()) {
            $aiDirect = $this->ollamaExtractAllergenConceptsFromText($value);
            foreach ($aiDirect as $token) {
                $normalized[] = $token;
            }
        }

        foreach ($rawTokens as $token) {
            $canon = $type === 'regime' ? $this->normalizeRegimeToken($token) : $this->normalizeAllergenToken($token);
            if ($canon === '' || $canon === $this->normalizeToken($token)) {
                $unknown[] = $token;
            } else {
                $normalized[] = $canon;
            }
        }

        if (!empty($unknown)) {
            $iaMap = $this->ollamaCanonicalizeTokens($type, $unknown);
            foreach ($unknown as $u) {
                $k = $this->normalizeText($u);
                if (isset($iaMap[$k]) && is_array($iaMap[$k])) {
                    foreach ($iaMap[$k] as $iaCanon) {
                        $normalized[] = $iaCanon;
                    }
                } else {
                    $fallbackCanon = $type === 'regime' ? $this->normalizeRegimeToken($u) : $this->normalizeAllergenToken($u);
                    if ($fallbackCanon !== '') {
                        if ($type === 'allergen'
                            && $fallbackCanon === $this->normalizeToken($u)
                            && !$this->isKnownAllergenCanonical($fallbackCanon)) {
                            continue;
                        }
                        $normalized[] = $fallbackCanon;
                    }
                }
            }
        }
        $normalized = $this->uniqueTokens($normalized);
        if ($type === 'allergen') {
            // Safety-oriented expansion: food words like "cheesecake" imply likely allergen buckets.
            $normalized = $this->expandFoodLikeAllergenHints($rawTokens, $normalized);
            if (!$this->aiOpenAllergenMode()) {
                $normalized = array_values(array_filter($normalized, function ($token) {
                    return $this->isKnownAllergenCanonical($token);
                }));
            }
        }
        return $normalized;
    }

    private function allergyKeywordStopwords() {
        return [
            'allergie', 'allergies', 'allergic', 'allergy', 'allergique', 'allergiques',
            'je', 'suis', 'au', 'aux', 'a', 'la', 'le', 'les', 'de', 'du', 'des', 'et',
            'with', 'to', 'for', 'the', 'and', 'or', 'sans', 'contre',
            'profil', 'preference', 'pref', 'food', 'aliment', 'aliments',
        ];
    }

    private function extractUserAllergyKeywords($rawAllergies) {
        $candidates = $this->extractLexicalCandidates($rawAllergies, 2);
        if (empty($candidates)) {
            return [];
        }
        $stopSet = array_flip(array_map(function ($w) {
            return $this->normalizeToken($w);
        }, $this->allergyKeywordStopwords()));

        $out = [];
        foreach ($candidates as $token) {
            $norm = $this->normalizeToken($token);
            if ($norm === '' || strlen($norm) < 4) {
                continue;
            }
            if (isset($stopSet[$norm])) {
                continue;
            }
            $out[] = $norm;
        }
        return $this->uniqueTokens($out);
    }

    private function extractMealSearchKeywords($meal, $aiProfile = null) {
        $parts = [];
        if (is_array($meal)) {
            $parts[] = (string)($meal['meal_name'] ?? '');
            $parts[] = (string)($meal['ingredients'] ?? '');
            $parts[] = (string)($meal['allergens'] ?? '');
            $parts[] = (string)($meal['regime_tags'] ?? '');
        }
        if (is_array($aiProfile)) {
            $parts[] = implode(', ', (array)($aiProfile['inferred_ingredients'] ?? []));
            $parts[] = implode(', ', (array)($aiProfile['basic_recipe_ingredients'] ?? []));
            $parts[] = implode(', ', (array)($aiProfile['inferred_allergens'] ?? []));
        }
        return $this->extractLexicalCandidates($parts, 2);
    }

    private function detectKeywordAllergyConflicts($userKeywords, $mealKeywords) {
        $userKeywords = $this->uniqueTokens(array_map(function ($t) {
            return $this->normalizeToken($t);
        }, (array)$userKeywords));
        $mealKeywords = $this->uniqueTokens(array_map(function ($t) {
            return $this->normalizeToken($t);
        }, (array)$mealKeywords));
        if (empty($userKeywords) || empty($mealKeywords)) {
            return [];
        }

        $mealSet = array_flip($mealKeywords);
        $conflicts = [];
        foreach ($userKeywords as $u) {
            if ($u === '') {
                continue;
            }
            if (isset($mealSet[$u])) {
                $conflicts[] = $u;
                continue;
            }
            // light fuzzy for typo close words (e.g. chocolat/choclat).
            if (function_exists('levenshtein') && strlen($u) >= 5) {
                foreach ($mealKeywords as $m) {
                    if (strlen($m) < 5) {
                        continue;
                    }
                    $dist = levenshtein($u, $m);
                    $ratio = $dist / max(strlen($u), strlen($m));
                    if ($dist <= 1 || ($dist <= 2 && $ratio <= 0.22)) {
                        $conflicts[] = $u;
                        break;
                    }
                }
            }
        }
        return $this->uniqueTokens($conflicts);
    }

    private function buildUserAllergyInputMap($rawAllergies)
    {
        $entries = $this->extractAllergyEntries($rawAllergies);
        $map = [];
        foreach ($entries as $entry) {
            $resolved = $this->resolveUserAllergyTermConservative($entry);
            $map[$entry] = (array)($resolved['mapped_allergens'] ?? []);
        }
        return $map;
    }

    private function extractAllergyEntries($rawAllergies)
    {
        $rawText = $this->normalizeText($rawAllergies);
        if ($rawText === '') {
            return [];
        }

        $parts = preg_split('/[,;]+/', (string)$rawAllergies);
        $entries = [];
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $p = $this->normalizeText($part);
                if ($p !== '') {
                    $entries[] = $p;
                }
            }
        }
        if (empty($entries)) {
            $entries[] = $rawText;
        }
        return array_values(array_unique($entries));
    }

    private function resolveUserAllergyTermConservative($entry)
    {
        $entry = $this->normalizeText($entry);
        if ($entry === '') {
            return [
                'raw_term' => '',
                'exact_keyword' => '',
                'mapped_allergens' => [],
                'mapped_evidence' => [],
            ];
        }

        $memoKey = 'user-allergy-term:' . $this->aiPipelineVersion() . ':' . md5($entry);
        if (isset($this->semanticMemo[$memoKey]) && is_array($this->semanticMemo[$memoKey])) {
            return $this->semanticMemo[$memoKey];
        }

        $exactKeyword = $this->normalizeToken($entry);
        $exactCanonical = $this->normalizeAllergenToken($entry);
        if ($exactCanonical !== '' && $this->isKnownAllergenCanonical($exactCanonical)) {
            $exactKeyword = $exactCanonical;
        }
        $candidateSet = [];

        $lexicalTokens = $this->extractLexicalCandidates($entry, 2);
        foreach ($lexicalTokens as $token) {
            $canon = $this->normalizeAllergenToken($token);
            if ($this->isKnownAllergenCanonical($canon)) {
                $candidateSet[$canon] = true;
            }
        }

        foreach ($this->ollamaExtractAllergenConceptsFromText($entry) as $canon) {
            $canon = $this->normalizeAllergenToken($canon);
            if ($this->isKnownAllergenCanonical($canon)) {
                $candidateSet[$canon] = true;
            }
        }

        $directEvidence = $this->inferAllergenEvidenceFromText($entry);
        $allowedByEvidence = [];
        foreach (array_keys($directEvidence) as $a) {
            $a = $this->normalizeAllergenToken($a);
            if ($this->isKnownAllergenCanonical($a)) {
                $allowedByEvidence[$a] = true;
            }
        }

        $recipeHints = $this->ollamaInferBasicRecipeHintsByMealName($entry);
        $recipeAllergens = array_map(function ($t) {
            return $this->normalizeAllergenToken((string)$t);
        }, (array)($recipeHints['allergens'] ?? []));
        $recipeAllergens = array_values(array_filter($recipeAllergens, function ($t) {
            return $this->isKnownAllergenCanonical($t);
        }));
        if (empty($recipeAllergens)) {
            $local = $this->localRecipeHintsByMealName($entry);
            $recipeAllergens = array_map(function ($t) {
                return $this->normalizeAllergenToken((string)$t);
            }, (array)($local['allergens'] ?? []));
            $recipeAllergens = array_values(array_filter($recipeAllergens, function ($t) {
                return $this->isKnownAllergenCanonical($t);
            }));
        }
        foreach ($recipeAllergens as $a) {
            $allowedByEvidence[$a] = true;
            $candidateSet[$a] = true;
        }

        $rawCanonical = $this->normalizeAllergenToken($entry);
        if ($this->isKnownAllergenCanonical($rawCanonical)) {
            $allowedByEvidence[$rawCanonical] = true;
            $candidateSet[$rawCanonical] = true;
        }

        $recipeIngredients = $this->uniqueTokens(array_map(function ($t) {
            return strtolower($this->normalizeText((string)$t));
        }, array_merge((array)($recipeHints['ingredients'] ?? []), (array)($recipeHints['variants'] ?? []))));
        $recipeEvidence = $this->inferAllergenSourcesFromIngredientTokens($recipeIngredients, array_keys($allowedByEvidence));

        $mapped = [];
        $mappedEvidence = [];
        foreach (array_keys($candidateSet) as $candidate) {
            if (!isset($allowedByEvidence[$candidate])) {
                continue;
            }
            $mapped[] = $candidate;
            $evidence = [];
            if (isset($directEvidence[$candidate]) && is_array($directEvidence[$candidate])) {
                $evidence = $this->mergeTriggerIngredients($evidence, $directEvidence[$candidate]);
            }
            if (isset($recipeEvidence[$candidate]) && is_array($recipeEvidence[$candidate])) {
                $evidence = $this->mergeTriggerIngredients($evidence, $recipeEvidence[$candidate]);
            }
            $mappedEvidence[$candidate] = $evidence;
        }

        $result = [
            'raw_term' => $entry,
            'exact_keyword' => $exactKeyword,
            'mapped_allergens' => $this->uniqueTokens($mapped),
            'mapped_evidence' => $mappedEvidence,
        ];
        $this->semanticMemo[$memoKey] = $result;
        return $result;
    }

    private function buildUserAllergyInputDetails($rawAllergies)
    {
        $entries = $this->extractAllergyEntries($rawAllergies);
        $details = [];
        foreach ($entries as $entry) {
            $resolved = $this->resolveUserAllergyTermConservative($entry);
            $details[] = [
                'raw_term' => (string)($resolved['raw_term'] ?? $entry),
                'exact_keyword' => (string)($resolved['exact_keyword'] ?? $this->normalizeToken($entry)),
                'mapped_allergens' => array_values(array_unique((array)($resolved['mapped_allergens'] ?? []))),
                'mapped_evidence' => (array)($resolved['mapped_evidence'] ?? []),
                'mapped_all_tokens' => array_values(array_unique((array)($resolved['mapped_allergens'] ?? []))),
            ];
        }

        return $details;
    }

    private function buildUserAllergyInputDetailsQuick($rawAllergies)
    {
        $entries = $this->extractAllergyEntries($rawAllergies);
        $details = [];

        foreach ($entries as $entry) {
            $exactKeyword = $this->normalizeToken($entry);
            $exactCanonical = $this->normalizeAllergenToken($entry);
            if ($exactCanonical !== '' && $this->isKnownAllergenCanonical($exactCanonical)) {
                $exactKeyword = $exactCanonical;
            }
            $mapped = $this->parseAllergenListFast($entry);
            $details[] = [
                'raw_term' => $entry,
                'exact_keyword' => $exactKeyword,
                'mapped_allergens' => $mapped,
                'mapped_evidence' => [],
                'mapped_all_tokens' => $mapped,
            ];
        }
        return $details;
    }

    private function conflictOriginLabel($origin)
    {
        $origin = strtolower($this->normalizeText((string)$origin));
        $labels = [
            'text_direct' => 'Correspondance preference',
            'vendor_declared' => 'Declare par vendeur',
            'ai_from_ingredients' => 'Inference IA (ingredients)',
            'ai_from_basic_recipe' => 'Inference IA (recette de base)',
            'ai_pending' => 'Analyse IA en attente',
            'ai_unknown' => 'Meal inconnu pour l IA',
            'fallback_local' => 'Analyse locale',
            'ai_precomputed' => 'Analyse IA pre-calculee',
        ];
        return $labels[$origin] ?? 'Inference IA';
    }

    private function classifyMealConflicts($meal, $aiProfile, $userInputDetails)
    {
        $declaredAllergens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($aiProfile['declared_allergens'] ?? [])));
        $inferredAllergens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($aiProfile['inferred_allergens'] ?? [])));
        $searchKeywords = $this->extractMealSearchKeywords(is_array($meal) ? $meal : [], is_array($aiProfile) ? $aiProfile : []);
        $searchSet = array_flip($this->uniqueTokens(array_map(function ($t) {
            return $this->normalizeToken($t);
        }, (array)$searchKeywords)));
        $detailMap = [];
        foreach ((array)($aiProfile['causal_allergen_details'] ?? []) as $d) {
            if (!is_array($d)) {
                continue;
            }
            $a = $this->normalizeAllergenToken((string)($d['allergen'] ?? ''));
            if ($a !== '') {
                $detailMap[$a] = $d;
            }
        }
        $sourceMap = is_array($aiProfile['allergen_sources'] ?? null) ? $aiProfile['allergen_sources'] : [];

        $hard = [];
        $soft = [];
        $userKeywordsRaw = [];
        foreach ((array)$userInputDetails as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $rawTerm = $this->normalizeText((string)($entry['raw_term'] ?? ''));
            $exactKeyword = $this->normalizeToken((string)($entry['exact_keyword'] ?? ''));
            $entryEvidenceMap = is_array($entry['mapped_evidence'] ?? null) ? $entry['mapped_evidence'] : [];
            if ($exactKeyword !== '' && strlen($exactKeyword) >= 4) {
                $userKeywordsRaw[] = $exactKeyword;
            }
            $mappedAllergens = $this->uniqueTokens(array_map(function ($t) {
                return $this->normalizeAllergenToken((string)$t);
            }, (array)($entry['mapped_allergens'] ?? [])));

            if ($exactKeyword !== '' && isset($searchSet[$exactKeyword])) {
                $hard[] = [
                    'type' => 'keyword_exact',
                    'raw_term' => $rawTerm,
                    'allergen' => $exactKeyword,
                    'origin' => 'text_direct',
                    'origin_label' => $this->conflictOriginLabel('text_direct'),
                    'trigger_ingredients' => [$exactKeyword],
                    'source_scopes' => ['text_direct'],
                    'confidence' => 1.0,
                ];
            }

            foreach ($mappedAllergens as $mapped) {
                $detail = $detailMap[$mapped] ?? [];
                $triggers = [];
                if (isset($detail['trigger_ingredients']) && is_array($detail['trigger_ingredients'])) {
                    $triggers = $detail['trigger_ingredients'];
                } elseif (isset($sourceMap[$mapped]) && is_array($sourceMap[$mapped])) {
                    $triggers = $sourceMap[$mapped];
                } elseif (isset($entryEvidenceMap[$mapped]) && is_array($entryEvidenceMap[$mapped])) {
                    $triggers = $entryEvidenceMap[$mapped];
                }
                $triggers = array_values(array_unique(array_values(array_filter(array_map(function ($v) {
                    return $this->normalizeText((string)$v);
                }, $triggers), static function ($v) {
                    return $v !== '';
                }))));
                $origin = (string)($detail['origin'] ?? ($aiProfile['origin_code'] ?? 'ai_from_ingredients'));
                $confidence = isset($detail['confidence']) ? (float)$detail['confidence'] : (float)($aiProfile['allergen_inference_confidence'] ?? 0.0);
                $sourceScopes = array_values(array_unique(array_values(array_filter(array_map(function ($scope) {
                    return $this->normalizeText((string)$scope);
                }, (array)($detail['source_scopes'] ?? [])), static function ($scope) {
                    return $scope !== '';
                }))));

                if (in_array($mapped, $declaredAllergens, true)) {
                    $hard[] = [
                        'type' => 'vendor_declared',
                        'raw_term' => $rawTerm,
                        'allergen' => $mapped,
                        'origin' => 'vendor_declared',
                        'origin_label' => $this->conflictOriginLabel('vendor_declared'),
                        'trigger_ingredients' => $triggers,
                        'source_scopes' => !empty($sourceScopes) ? $sourceScopes : ['vendor_declared'],
                        'confidence' => 1.0,
                    ];
                    continue;
                }
                if (in_array($mapped, $inferredAllergens, true)) {
                    $soft[] = [
                        'type' => 'ai_inferred',
                        'raw_term' => $rawTerm,
                        'allergen' => $mapped,
                        'origin' => $origin,
                        'origin_label' => $this->conflictOriginLabel($origin),
                        'trigger_ingredients' => $triggers,
                        'source_scopes' => $sourceScopes,
                        'confidence' => $confidence,
                    ];
                }
            }
        }
        $lexicalConflicts = $this->detectKeywordAllergyConflicts($userKeywordsRaw, array_keys($searchSet));
        foreach ($lexicalConflicts as $kw) {
            $hard[] = [
                'type' => 'keyword_exact',
                'raw_term' => $kw,
                'allergen' => $kw,
                'origin' => 'text_direct',
                'origin_label' => $this->conflictOriginLabel('text_direct'),
                'trigger_ingredients' => [$kw],
                'source_scopes' => ['text_direct'],
                'confidence' => 0.95,
            ];
        }

        $dedupe = function ($rows) {
            $seen = [];
            $out = [];
            foreach ((array)$rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $key = strtolower(
                    (string)($row['type'] ?? '') . '|' .
                    (string)($row['allergen'] ?? '') . '|' .
                    (string)($row['raw_term'] ?? '')
                );
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = $row;
            }
            return $out;
        };

        return [
            'hard' => $dedupe($hard),
            'soft' => $dedupe($soft),
            'declared_allergens' => $declaredAllergens,
            'inferred_allergens' => $inferredAllergens,
        ];
    }

    private function inferAllergensFromText($text) {
        $evidence = $this->inferAllergenEvidenceFromText($text);
        return $this->uniqueTokens(array_keys($evidence));
    }

    private function inferAllergenEvidenceFromText($text, $targetAllergens = []) {
        $normalizedText = strtolower($this->toAscii((string)$text));
        if ($normalizedText === '') {
            return [];
        }

        $targets = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)$targetAllergens));
        $ingredientTokens = $this->splitIngredientTokens($normalizedText);
        if (empty($ingredientTokens)) {
            $ingredientTokens = [$normalizedText];
        }
        $evidence = $this->inferAllergenSourcesFromIngredientTokens($ingredientTokens, $targets);

        // Fallback regex check on full text for long keywords only.
        $patterns = $this->allergenPatternMap();
        foreach ($patterns as $allergen => $keywords) {
            if (!empty($targets) && !in_array($allergen, $targets, true)) {
                continue;
            }
            foreach ($keywords as $kw) {
                $needle = strtolower($this->toAscii((string)$kw));
                $needleNorm = $this->normalizeToken($needle);
                if ($needleNorm === '' || strlen($needleNorm) < 6) {
                    continue;
                }
                $regex = '/(^|[^a-z0-9])' . preg_quote($needleNorm, '/') . '([^-a-z0-9]|$)/';
                $hay = $this->normalizeToken($normalizedText);
                if ($hay !== '' && preg_match($regex, $hay)) {
                    if (!isset($evidence[$allergen])) {
                        $evidence[$allergen] = [];
                    }
                    $evidence[$allergen][] = $kw;
                }
            }
            if (isset($evidence[$allergen])) {
                $evidence[$allergen] = array_values(array_unique($evidence[$allergen]));
            }
        }

        return $evidence;
    }

    private function hasTraceRisk($text) {
        $token = $this->normalizeToken($this->toAscii($text));
        if ($token === '') {
            return false;
        }
        $traceKeywords = [
            'trace',
            'traces',
            'may-contain',
            'peut-contenir',
            'possible-traces',
            'cross-contamination',
            'cross-contact',
        ];
        foreach ($traceKeywords as $kw) {
            if (strpos($token, $this->normalizeToken($kw)) !== false) {
                return true;
            }
        }
        return false;
    }

    private function buildQuickMealProfile($meal) {
        $meal = is_array($meal) ? $meal : [];
        $ingredients = $this->normalizeText($meal['ingredients'] ?? '');
        $mealName = $this->normalizeText($meal['meal_name'] ?? '');
        $allergensText = $this->normalizeText($meal['allergens'] ?? '');
        $disclosureMode = $this->normalizeMealAllergenDisclosureMode($meal);
        $analysisStatus = (int)($meal['analyse_ia'] ?? 0);

        $declaredAllergens = $disclosureMode === 'declared'
            ? $this->parseAllergenListFast($allergensText)
            : [];

        $scanText = trim($mealName . ', ' . $ingredients . ', ' . $allergensText);
        $inferredAllergens = $this->parseAllergenListFast($scanText);
        if ($analysisStatus === 1 && is_array($meal['allergenes_finaux'] ?? null) && !empty($meal['allergenes_finaux'])) {
            $inferredAllergens = $this->uniqueTokens(array_map(function ($token) {
                return $this->normalizeAllergenToken((string)$token);
            }, (array)$meal['allergenes_finaux']));
        }
        if (!empty($declaredAllergens)) {
            $inferredAllergens = array_values(array_diff($inferredAllergens, $declaredAllergens));
        }

        $providedIngredients = $this->splitIngredientTokens($ingredients);
        $allergenSources = $this->inferAllergenEvidenceFromText($scanText, array_merge($declaredAllergens, $inferredAllergens));
        $trustedEvidence = $this->constrainAllergenSourcesToTrustedIngredients($allergenSources, $providedIngredients, []);
        $allergenSources = (array)($trustedEvidence['sources'] ?? []);
        $allergenSourceScopes = (array)($trustedEvidence['scopes'] ?? []);
        $inferredAllergens = $this->keepAllergensWithTrustedSources($inferredAllergens, $allergenSources);
        $declaredCitations = $this->ensureDeclaredAllergenSourceCitations($declaredAllergens, $allergenSources, $allergenSourceScopes);
        $allergenSources = (array)($declaredCitations['sources'] ?? []);
        $allergenSourceScopes = (array)($declaredCitations['scopes'] ?? []);
        $semanticAllergens = $this->uniqueTokens(array_merge($declaredAllergens, $inferredAllergens));
        $analysisMode = $this->analysisStateForMeal($meal);
        $originCode = !empty($declaredAllergens) ? 'vendor_declared' : 'ai_from_ingredients';
        $originBadge = !empty($declaredAllergens) ? 'Vendor declared' : 'Fast safety scan';
        if ($analysisMode === 'pending') {
            $originCode = 'ai_pending';
            $originBadge = 'AI analysis pending';
        } elseif ($analysisMode === 'unknown') {
            $originCode = 'ai_unknown';
            $originBadge = 'AI meal unknown';
        } elseif ($analysisStatus < 0) {
            $originCode = 'fallback_local';
            $originBadge = 'Fallback local (IA indisponible)';
        } elseif ($analysisStatus === 1) {
            $originCode = 'ai_precomputed';
            $originBadge = 'AI pre-computed';
        }

        return [
            'declared_allergens' => $declaredAllergens,
            'inferred_allergens' => $inferredAllergens,
            'semantic_allergens' => $semanticAllergens,
            'allergen_sources' => $allergenSources,
            'allergen_source_scopes' => $allergenSourceScopes,
            'causal_allergen_details' => $this->buildCausalAllergenDetails(
                $semanticAllergens,
                $allergenSources,
                $originCode,
                !empty($declaredAllergens) ? 1.0 : 0.55,
                !empty($declaredAllergens) ? 1.0 : 0.55,
                $allergenSourceScopes
            ),
            'allergen_inference_confidence' => !empty($declaredAllergens) ? 1.0 : 0.55,
            'origin_code' => $originCode,
            'origin_badge' => $originBadge,
            'warning' => empty($declaredAllergens) || $analysisMode === 'unknown',
            'source' => !empty($declaredAllergens) ? 'declared' : 'ingredients',
            'analysis_mode' => $analysisMode,
        ];
    }

    private function decodeMeals($json) {
        $json = trim((string)$json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function findMealFromMeals($meals, $mealId) {
        $mealId = $this->normalizeText($mealId);
        if ($mealId === '') {
            return null;
        }
        foreach ((array)$meals as $meal) {
            if (!is_array($meal)) {
                continue;
            }
            if ($this->normalizeText($meal['meal_id'] ?? '') === $mealId) {
                return $meal;
            }
        }
        return null;
    }

    private function getRestaurantMealFromDb($idRestaurant, $mealId) {
        $idRestaurant = (int)$idRestaurant;
        $mealId = $this->normalizeText($mealId);
        if ($idRestaurant <= 0 || $mealId === '') {
            return null;
        }
        try {
            $query = $this->db()->prepare(
                "SELECT meals_json FROM restaurant WHERE id_restaurant = :id_restaurant LIMIT 1"
            );
            $query->execute(['id_restaurant' => $idRestaurant]);
            $row = $query->fetch();
        } catch (Exception $e) {
            return null;
        }
        if (!is_array($row)) {
            return null;
        }
        $meals = $this->decodeMeals($row['meals_json'] ?? '[]');
        $meal = $this->findMealFromMeals($meals, $mealId);
        return is_array($meal) ? $meal : null;
    }

    private function buildAnalysisMealPayload($matchedMeal, $dbMeal = null) {
        $base = is_array($matchedMeal) ? $matchedMeal : [];
        $source = is_array($dbMeal) ? $dbMeal : [];

        return [
            'meal_id' => (string)($base['meal_id'] ?? $source['meal_id'] ?? ''),
            'meal_name' => (string)($source['meal_name'] ?? $base['meal_name'] ?? ''),
            'ingredients' => (string)($source['ingredients'] ?? $base['ingredients'] ?? ''),
            'allergens' => (string)($source['allergens'] ?? $base['allergens'] ?? ''),
            'allergen_disclosure_mode' => (string)($source['allergen_disclosure_mode'] ?? $base['allergen_disclosure_mode'] ?? 'none'),
            'regime_tags' => (string)($source['regime_tags'] ?? $base['regime_tags'] ?? ''),
            'pricing_mode' => (string)($base['pricing_mode'] ?? $source['pricing_mode'] ?? 'free'),
            'price' => (float)($base['price'] ?? $source['price'] ?? 0),
            'quantity' => (int)($base['quantity'] ?? $source['quantity'] ?? 0),
            'analyse_ia' => (int)($source['analyse_ia'] ?? $base['analyse_ia'] ?? 0),
            'analyse_ia_engine' => (string)($source['analyse_ia_engine'] ?? $base['analyse_ia_engine'] ?? ''),
            'analyse_ia_model' => (string)($source['analyse_ia_model'] ?? $base['analyse_ia_model'] ?? ''),
            'analyse_ia_error' => (string)($source['analyse_ia_error'] ?? $base['analyse_ia_error'] ?? ''),
            'analyse_ia_updated_at' => (string)($source['analyse_ia_updated_at'] ?? $base['analyse_ia_updated_at'] ?? ''),
            'ingredients_standardises' => is_array($source['ingredients_standardises'] ?? null)
                ? (array)$source['ingredients_standardises']
                : (is_array($base['ingredients_standardises'] ?? null) ? (array)$base['ingredients_standardises'] : []),
            'ingredients_manquants_probables' => is_array($source['ingredients_manquants_probables'] ?? null)
                ? (array)$source['ingredients_manquants_probables']
                : (is_array($base['ingredients_manquants_probables'] ?? null) ? (array)$base['ingredients_manquants_probables'] : []),
            'allergenes_finaux' => is_array($source['allergenes_finaux'] ?? null)
                ? (array)$source['allergenes_finaux']
                : (is_array($base['allergenes_finaux'] ?? null) ? (array)$base['allergenes_finaux'] : []),
        ];
    }

    private function looksLikeUnknownMeal($mealName, $ingredients, $declaredAllergens, $inferredAllergens, $analysisStatus)
    {
        $declaredAllergens = (array)$declaredAllergens;
        $inferredAllergens = (array)$inferredAllergens;
        $mealName = $this->normalizeText($mealName);
        $ingredients = $this->normalizeText($ingredients);
        if (!empty($declaredAllergens) || !empty($inferredAllergens)) {
            return false;
        }
        if ($ingredients !== '') {
            return false;
        }
        $recipeHints = $this->localRecipeHintsByMealName($mealName);
        if (!empty((array)($recipeHints['ingredients'] ?? [])) || !empty((array)($recipeHints['allergens'] ?? []))) {
            return false;
        }
        return (int)$analysisStatus !== 0;
    }

    private function analysisStateForMeal($meal)
    {
        $meal = is_array($meal) ? $meal : [];
        $status = (int)($meal['analyse_ia'] ?? 0);
        if ($status === 0) {
            return 'pending';
        }

        $disclosureMode = $this->normalizeMealAllergenDisclosureMode($meal);
        $declaredAllergens = $disclosureMode === 'declared'
            ? $this->parseAllergenListFast((string)($meal['allergens'] ?? ''))
            : [];
        $inferredAllergens = is_array($meal['allergenes_finaux'] ?? null)
            ? $this->uniqueTokens(array_map(function ($token) {
                return $this->normalizeAllergenToken((string)$token);
            }, (array)$meal['allergenes_finaux']))
            : [];
        $ingredients = (string)($meal['ingredients'] ?? '');
        $mealName = (string)($meal['meal_name'] ?? '');

        if ($this->looksLikeUnknownMeal($mealName, $ingredients, $declaredAllergens, $inferredAllergens, $status)) {
            return 'unknown';
        }

        if ($status > 0) {
            return 'ai_live';
        }
        return 'fallback_local';
    }

    private function expectedAnalysisModeForStatus($status) {
        $status = (int)$status;
        if ($status === 1) {
            return 'ai_live';
        }
        if ($status === 0) {
            return 'pending';
        }
        if ($status < 0) {
            return 'fallback_local';
        }
        return 'fallback_local';
    }

    private function isAiProfilePayloadEmpty($profile) {
        if (!is_array($profile)) {
            return true;
        }
        $ingredients = is_array($profile['inferred_ingredients'] ?? null) ? $profile['inferred_ingredients'] : [];
        $allergens = is_array($profile['semantic_allergens'] ?? null) ? $profile['semantic_allergens'] : [];
        $basicRecipe = is_array($profile['basic_recipe_ingredients'] ?? null) ? $profile['basic_recipe_ingredients'] : [];
        return empty($ingredients) && empty($allergens) && empty($basicRecipe);
    }

    private function computeMealAiConsistency($meal, $profile) {
        $meal = is_array($meal) ? $meal : [];
        $profile = is_array($profile) ? $profile : [];
        $storedStatus = (int)($meal['analyse_ia'] ?? 0);
        $expectedMode = $this->expectedAnalysisModeForStatus($storedStatus);
        $actualMode = strtolower((string)($profile['analysis_mode'] ?? 'fallback_local'));
        $mealId = $this->normalizeText($meal['meal_id'] ?? '');
        $missingMealId = ($mealId === '');
        $emptyPayloadWithAi = ($storedStatus === 1) && $this->isAiProfilePayloadEmpty($profile);
        $modeMismatch = $actualMode !== $expectedMode;

        return [
            'stored_status' => $storedStatus,
            'expected_mode' => $expectedMode,
            'actual_mode' => $actualMode,
            'missing_meal_id' => $missingMealId,
            'empty_payload_with_ai' => $emptyPayloadWithAi,
            'mode_mismatch' => $modeMismatch,
            'has_inconsistency' => $missingMealId || $emptyPayloadWithAi || $modeMismatch,
        ];
    }

    public function aiDebugEndpointEnabled() {
        $flag = strtolower((string)caremeal_env('CAREMEAL_AI_DEBUG_ENABLED', '0'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    public function aiDebugTokenIsValid($providedToken) {
        $expected = trim((string)caremeal_env('CAREMEAL_AI_DEBUG_TOKEN', ''));
        if ($expected === '') {
            return false;
        }
        $provided = trim((string)$providedToken);
        if ($provided === '') {
            return false;
        }
        return hash_equals($expected, $provided);
    }

    private function exactLocationMatch($preferenceLocation, $restaurantLocation) {
        $a = $this->normalizeToken($preferenceLocation);
        $b = $this->normalizeToken($restaurantLocation);
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        return strpos($a, $b) !== false || strpos($b, $a) !== false;
    }

    private function hasAllergyConflict($userAllergies, $mealAllergens) {
        $mealTokens = $this->semanticNormalizeList('allergen', $mealAllergens);
        foreach ($userAllergies as $allergy) {
            if (in_array($allergy, $mealTokens, true)) {
                return true;
            }
        }
        return false;
    }

    private function getRegimeOverlapCount($userRegimes, $mealRegimes) {
        if (empty($userRegimes)) {
            return 1;
        }

        $mealTokens = $this->semanticNormalizeList('regime', $mealRegimes);

        $overlap = 0;
        foreach ($userRegimes as $regime) {
            if (in_array($regime, $mealTokens, true)) {
                $overlap += 1;
            }
        }
        return $overlap;
    }

    private function parseRestaurantOpenTime($hours) {
        $hours = $this->normalizeText($hours);
        if ($hours === '') {
            return '99:99';
        }
        if (!preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $hours, $m)) {
            return '99:99';
        }
        return $m[1];
    }

    private function extractCoordsFromLocation($rawLocation) {
        $raw = $this->normalizeText($rawLocation);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/', $raw, $m)) {
            $lat = (float)$m[1];
            $lng = (float)$m[2];
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return ['lat' => $lat, 'lng' => $lng];
            }
        }

        $token = $this->normalizeToken($raw);
        $aliases = [
            'bardo' => ['lat' => 36.8092, 'lng' => 10.1407],
            'khaznadar' => ['lat' => 36.8059, 'lng' => 10.1286],
            'el-menzah-5' => ['lat' => 36.8489, 'lng' => 10.1650],
            'menzah-5' => ['lat' => 36.8489, 'lng' => 10.1650],
            'menzah' => ['lat' => 36.8490, 'lng' => 10.1670],
            'ariana' => ['lat' => 36.8665, 'lng' => 10.1647],
            'nasr' => ['lat' => 36.8625, 'lng' => 10.1686],
            'tunis' => ['lat' => 36.8065, 'lng' => 10.1815],
            'manar' => ['lat' => 36.8088, 'lng' => 10.1321],
            'marsa' => ['lat' => 36.8782, 'lng' => 10.3247],
            'lac' => ['lat' => 36.8403, 'lng' => 10.2713],
            'sfax' => ['lat' => 34.7406, 'lng' => 10.7603],
            'sousse' => ['lat' => 35.8256, 'lng' => 10.6369],
        ];

        foreach ($aliases as $key => $coords) {
            if ($token === $key || strpos($token, $key) !== false || strpos($key, $token) !== false) {
                return $coords;
            }
        }

        return null;
    }

    private function computeDistanceKm($coordsA, $coordsB) {
        if (!is_array($coordsA) || !is_array($coordsB)) {
            return null;
        }
        if (!isset($coordsA['lat'], $coordsA['lng'], $coordsB['lat'], $coordsB['lng'])) {
            return null;
        }

        $lat1 = deg2rad((float)$coordsA['lat']);
        $lng1 = deg2rad((float)$coordsA['lng']);
        $lat2 = deg2rad((float)$coordsB['lat']);
        $lng2 = deg2rad((float)$coordsB['lng']);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $earthKm = 6371.0;
        return $earthKm * $c;
    }

    private function computeRestaurantScore($stats) {
        $safeMeals = (int)($stats['safe_meals_count'] ?? 0);
        if ($safeMeals <= 0) {
            return 0;
        }

        $regimeHits = (int)($stats['regime_hits'] ?? 0);
        $allergyConflicts = (int)($stats['allergy_conflict_meals'] ?? 0);
        $locationMatch = !empty($stats['location_match']);
        $traceRiskMeals = (int)($stats['trace_risk_meals'] ?? 0);
        $semanticCoverage = (int)($stats['semantic_coverage'] ?? 0);
        $aiPotentialConflictMeals = (int)($stats['ai_potential_conflict_meals'] ?? 0);
        $securityMode = strtolower($this->normalizeText($stats['security_mode'] ?? 'strict'));

        // CareMeal custom scoring: regime > allergy safety > proximity > trust.
        $score = 0;
        $score += min(70, ($regimeHits * 16) + ($safeMeals * 3));
        $score += min(20, $safeMeals * 2);
        $score -= min(25, $allergyConflicts * 5);
        if ($securityMode === 'souple') {
            $score -= min(30, $aiPotentialConflictMeals * 8);
        }
        $score -= min(12, $traceRiskMeals * 3);
        $score += min(10, $semanticCoverage * 2);
        if ($locationMatch) {
            $score += 10;
        }

        return max(0, (int)$score);
    }

    private function getRestaurantsForMatching() {
        try {
            $query = $this->db()->query(
                "SELECT id_restaurant, id_owner, nom, localisation, image_path, description, telephone, horaires, meals_json, actif
                 FROM restaurant
                 WHERE actif = 1
                 ORDER BY id_restaurant DESC"
            );
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private function getReservedQuantitiesByRestaurantIds($restaurantIds) {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$restaurantIds), static function ($id) {
            return $id > 0;
        })));
        if (empty($ids)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = 'id_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $sql = "SELECT id_restaurant, items_json
                FROM planning_collecte
                WHERE id_restaurant IN (" . implode(', ', $placeholders) . ")
                  AND statut IN ('en_attente', 'en_cours_livraison')";

        try {
            $query = $this->db()->prepare($sql);
            $query->execute($params);
            $rows = $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }

        $reserved = [];
        foreach ($rows as $row) {
            $idRestaurant = (int)($row['id_restaurant'] ?? 0);
            if ($idRestaurant <= 0) {
                continue;
            }
            if (!isset($reserved[$idRestaurant])) {
                $reserved[$idRestaurant] = [];
            }

            $items = json_decode((string)($row['items_json'] ?? '[]'), true);
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $mealId = strtolower($this->normalizeText($item['meal_id'] ?? ''));
                $qty = (int)($item['quantity'] ?? 0);
                if ($mealId === '' || $qty <= 0) {
                    continue;
                }
                if (!isset($reserved[$idRestaurant][$mealId])) {
                    $reserved[$idRestaurant][$mealId] = 0;
                }
                $reserved[$idRestaurant][$mealId] += $qty;
            }
        }

        return $reserved;
    }

    private function buildRestaurantMatches($preference, $restaurants, $options = []) {
        $idRestaurantFilter = isset($options['id_restaurant']) ? (int)$options['id_restaurant'] : 0;
        $priceMode = strtolower($this->normalizeText($options['price_mode'] ?? 'all'));
        if ($priceMode === 'both') {
            // Backward compatibility: old UI value now maps to all.
            $priceMode = 'all';
        }
        if (!in_array($priceMode, ['all', 'free', 'paid'], true)) {
            $priceMode = 'all';
        }
        $sortBy = strtolower($this->normalizeText($options['sort_by'] ?? 'score'));
        if (!in_array($sortBy, ['score', 'location', 'name'], true)) {
            $sortBy = 'score';
        }
        $safetyMode = strtolower($this->normalizeText($options['safety_mode'] ?? 'strict'));
        if (!in_array($safetyMode, ['strict', 'souple'], true)) {
            $safetyMode = 'strict';
        }

        $userRegimes = $this->semanticNormalizeList('regime', $preference['regime_alimentaire'] ?? '');
        $hasRegimePreference = !empty($userRegimes);
        $userAllergiesRaw = (string)($preference['allergies'] ?? '');
        $userAllergies = $this->parseAllergenListFast($userAllergiesRaw);
        $userAllergyKeywords = $this->extractUserAllergyKeywords($userAllergiesRaw);
        $userAllergyInputMap = $this->buildUserAllergyInputMap($userAllergiesRaw);
        $userAllergyInputDetails = $this->buildUserAllergyInputDetailsQuick($userAllergiesRaw);
        $userLocation = $this->normalizeText($preference['localisation'] ?? '');
        $userCoords = $this->extractCoordsFromLocation($userLocation);
        $reservedByRestaurant = $this->getReservedQuantitiesByRestaurantIds(array_map(static function ($restaurant) {
            return (int)($restaurant['id_restaurant'] ?? 0);
        }, $restaurants));

        $matches = [];
        foreach ($restaurants as $restaurant) {
            $idRestaurant = (int)($restaurant['id_restaurant'] ?? 0);
            if ($idRestaurant <= 0) {
                continue;
            }
            if ($idRestaurantFilter > 0 && $idRestaurant !== (int)$idRestaurantFilter) {
                continue;
            }

            $locationMatch = $this->exactLocationMatch($userLocation, $restaurant['localisation'] ?? '');
            $restaurantCoords = $this->extractCoordsFromLocation($restaurant['localisation'] ?? '');
            $distanceKm = $this->computeDistanceKm($userCoords, $restaurantCoords);

            $rawMeals = $this->decodeMeals($restaurant['meals_json'] ?? '[]');
            $matchedMeals = [];
            $safeMealsCount = 0;
            $allergyConflictMeals = 0;
            $regimeHits = 0;
            $traceRiskMeals = 0;
            $semanticCoverage = 0;
            $aiPotentialConflictMeals = 0;
            $regimeWarningMeals = 0;
            $unknownMealsCount = 0;
            $analysisFallbackMeals = 0;
            $hasFreeVisible = false;
            $hasPaidVisible = false;
            $reservedForRestaurant = $reservedByRestaurant[$idRestaurant] ?? [];

            foreach ($rawMeals as $meal) {
                if (!is_array($meal)) {
                    continue;
                }

                $mealId = $this->normalizeText($meal['meal_id'] ?? '');
                $mealName = $this->normalizeText($meal['meal_name'] ?? '');
                $mealQuantityBase = (int)($meal['quantity'] ?? 0);
                $reservedQty = (int)($reservedForRestaurant[strtolower($mealId)] ?? 0);
                $mealQuantity = max(0, $mealQuantityBase - $reservedQty);

                if ($mealId === '' || $mealName === '' || $mealQuantity <= 0) {
                    continue;
                }

                $regimeTokens = $this->semanticNormalizeList('regime', $meal['regime_tags'] ?? '');
                $regimeOverlap = 0;
                foreach ($userRegimes as $regimeToken) {
                    if (in_array($regimeToken, $regimeTokens, true)) {
                        $regimeOverlap += 1;
                    }
                }
                $regimeCompatible = !$hasRegimePreference || $regimeOverlap > 0;
                if ($safetyMode === 'strict' && !$regimeCompatible) {
                    continue;
                }

                $pricingMode = (($meal['pricing_mode'] ?? 'free') === 'paid') ? 'paid' : 'free';
                $aiProfile = $this->buildQuickMealProfile($meal);
                $analysisMode = strtolower((string)($aiProfile['analysis_mode'] ?? 'fallback_local'));
                $isUnknownMeal = $analysisMode === 'unknown';
                $classified = $this->classifyMealConflicts($meal, $aiProfile, $userAllergyInputDetails);
                $declaredAllergens = (array)($classified['declared_allergens'] ?? []);
                $inferredAllergens = (array)($classified['inferred_allergens'] ?? []);
                $semanticAllergens = $this->uniqueTokens(array_merge($declaredAllergens, $inferredAllergens));
                $hardConflicts = is_array($classified['hard'] ?? null) ? $classified['hard'] : [];
                $softConflicts = is_array($classified['soft'] ?? null) ? $classified['soft'] : [];
                $hardConflict = !empty($hardConflicts);
                $softConflict = !empty($softConflicts);
                $strictCompatibleMeal = $regimeCompatible && !$hardConflict;
                $conflictTokens = $this->uniqueTokens(array_map(function ($c) {
                    return $this->normalizeAllergenToken((string)($c['allergen'] ?? ''));
                }, array_merge($hardConflicts, $softConflicts)));

                if ($hardConflict && $safetyMode === 'strict') {
                    $allergyConflictMeals += 1;
                    continue;
                }

                if ($strictCompatibleMeal) {
                    $safeMealsCount += 1;
                }
                if ($hardConflict) {
                    $allergyConflictMeals += 1;
                }
                if (!$regimeCompatible) {
                    $regimeWarningMeals += 1;
                }
                $regimeHits += $regimeOverlap;
                $semanticCoverage += !empty($semanticAllergens) ? 1 : 0;
                $traceRisk = $this->hasTraceRisk($this->normalizeText(($meal['meal_name'] ?? '') . ' ' . ($meal['ingredients'] ?? '') . ' ' . ($meal['allergens'] ?? '')));
                if ($traceRisk) {
                    $traceRiskMeals += 1;
                }
                if (($aiProfile['analysis_mode'] ?? 'fallback_local') !== 'ai_live') {
                    $analysisFallbackMeals += 1;
                }
                if ($softConflict) {
                    $aiPotentialConflictMeals += 1;
                }
                if ($pricingMode === 'paid') {
                    $hasPaidVisible = true;
                } else {
                    $hasFreeVisible = true;
                }
                $warningRegime = !$regimeCompatible;
                $warningAllergeneHard = $hardConflict;
                $warningAllergeneSoft = $softConflict;
                $warningUnknown = $isUnknownMeal;
                $warningUnknownMessage = $warningUnknown
                    ? 'Meal inconnu: l IA n a pas pu cerner clairement cet aliment.'
                    : '';
                if ($isUnknownMeal && $safetyMode === 'strict') {
                    continue;
                }
                if ($warningUnknown) {
                    $unknownMealsCount += 1;
                }
                $hasAnyWarning = $warningRegime || $warningAllergeneHard || $warningAllergeneSoft || $warningUnknown;

                $matchedMeals[] = [
                    'meal_id' => $mealId,
                    'meal_name' => $mealName,
                    'ingredients' => $this->normalizeText($meal['ingredients'] ?? ''),
                    'quantity' => $mealQuantity,
                    'pricing_mode' => $pricingMode,
                    'price' => (float)($meal['price'] ?? 0),
                    'regime_tags' => $this->normalizeText($meal['regime_tags'] ?? ''),
                    'allergens' => $this->normalizeText($meal['allergens'] ?? ''),
                    'regime_overlap' => $regimeOverlap,
                    'semantic_regimes' => $regimeTokens,
                    'semantic_allergens' => $semanticAllergens,
                    'declared_allergens_semantic' => $declaredAllergens,
                    'inferred_allergens_semantic' => $inferredAllergens,
                    'inferred_ingredients' => [],
                    'missing_ingredients' => [],
                    'allergen_disclosure_mode' => $this->normalizeMealAllergenDisclosureMode($meal),
                    'regime_compatible' => $regimeCompatible,
                    'warning_regime' => $warningRegime,
                    'warning_allergene_hard' => $warningAllergeneHard,
                    'warning_allergene_soft' => $warningAllergeneSoft,
                    'warning_unknown' => $warningUnknown,
                    'warning_unknown_message' => $warningUnknownMessage,
                    'has_any_warning' => $hasAnyWarning,
                    'ai_source' => (string)($aiProfile['source'] ?? 'ingredients'),
                    'ai_confidence' => (float)($aiProfile['allergen_inference_confidence'] ?? 0.55),
                    'ai_explanation' => (string)($aiProfile['warning'] ? 'Scan rapide de securite applique. Cliquez pour details IA complets.' : 'Allergenes declares par le vendeur. Cliquez pour details IA complets.'),
                    'ai_origin_badge' => (string)($aiProfile['origin_badge'] ?? ''),
                    'ai_origin_code' => (string)($aiProfile['origin_code'] ?? ''),
                    'ai_warning' => !empty($aiProfile['warning']),
                    'ai_legal_warning' => !empty($aiProfile['warning'])
                        ? 'Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.'
                        : '',
                    'ai_allergen_sources' => (array)($aiProfile['allergen_sources'] ?? []),
                    'ai_allergen_source_scopes' => (array)($aiProfile['allergen_source_scopes'] ?? []),
                    'ai_causal_allergen_details' => (array)($aiProfile['causal_allergen_details'] ?? []),
                    'ai_possible_missing_ingredients' => false,
                    'ai_basic_recipe_ingredients' => [],
                    'ai_basic_recipe_allergens' => [],
                    'ai_ingredient_completeness_score' => !empty($aiProfile['warning']) ? 55 : 100,
                    'ai_data_quality_confidence' => !empty($aiProfile['warning']) ? 0.55 : 1.0,
                    'ai_allergen_inference_confidence' => (float)($aiProfile['allergen_inference_confidence'] ?? 0),
                    'ai_check_passed' => empty($aiProfile['warning']),
                    'analysis_mode' => $analysisMode,
                    'recipe_baseline' => [],
                    'recipe_variants' => [],
                    'confidence_split' => [
                        'data_quality_confidence' => !empty($aiProfile['warning']) ? 0.55 : 1.0,
                        'inference_confidence' => (float)($aiProfile['allergen_inference_confidence'] ?? 0),
                    ],
                    'conflict_hard' => $hardConflicts,
                    'conflict_soft' => $softConflicts,
                    'ai_potential_conflict' => $softConflict,
                    'ai_conflict_tokens' => $conflictTokens,
                    'trace_risk' => $traceRisk,
                ];
            }

            if (empty($matchedMeals)) {
                continue;
            }

            if ($priceMode === 'free' && !$hasFreeVisible) {
                continue;
            }
            if ($priceMode === 'paid' && !$hasPaidVisible) {
                continue;
            }
            $stats = [
                'safe_meals_count' => $safeMealsCount,
                'allergy_conflict_meals' => $allergyConflictMeals,
                'regime_hits' => $regimeHits,
                'location_match' => $locationMatch,
                'trace_risk_meals' => $traceRiskMeals,
                'semantic_coverage' => $semanticCoverage,
                'ai_potential_conflict_meals' => $aiPotentialConflictMeals,
                'security_mode' => $safetyMode,
            ];
            $score = $this->computeRestaurantScore($stats);
            $scoreReasons = [];
            $scoreReasons[] = '+' . min(70, ($regimeHits * 16) + ($safeMealsCount * 3)) . ' regime compatibility';
            $scoreReasons[] = '+' . min(20, $safeMealsCount * 2) . ' safe meals count';
            if ($allergyConflictMeals > 0) {
                $scoreReasons[] = '-' . min(25, $allergyConflictMeals * 5) . ' allergy conflicts excluded';
            }
            if ($traceRiskMeals > 0) {
                $scoreReasons[] = '-' . min(12, $traceRiskMeals * 3) . ' trace-risk meals';
            }
            if ($locationMatch) {
                $scoreReasons[] = '+10 same/near location';
            }
            if ($semanticCoverage > 0) {
                $scoreReasons[] = '+' . min(10, $semanticCoverage * 2) . ' semantic coverage';
            }
            if ($aiPotentialConflictMeals > 0 && $safetyMode === 'souple') {
                $scoreReasons[] = '-' . min(30, $aiPotentialConflictMeals * 8) . ' allergenes potentiels IA (mode souple)';
            }
            if ($regimeWarningMeals > 0 && $safetyMode === 'souple') {
                $scoreReasons[] = '-' . min(18, $regimeWarningMeals * 4) . ' meals hors regime (mode souple)';
            }
            if ($unknownMealsCount > 0 && $safetyMode === 'souple') {
                $scoreReasons[] = '-' . min(20, $unknownMealsCount * 6) . ' meals inconnus (mode souple)';
            }

            $matches[] = [
                'id_restaurant' => $idRestaurant,
                'id_owner' => (int)($restaurant['id_owner'] ?? 0),
                'nom' => $this->normalizeText($restaurant['nom'] ?? ''),
                'localisation' => $this->normalizeText($restaurant['localisation'] ?? ''),
                'image_path' => $this->normalizeText($restaurant['image_path'] ?? ''),
                'description' => $this->normalizeText($restaurant['description'] ?? ''),
                'telephone' => $this->normalizeText($restaurant['telephone'] ?? ''),
                'horaires' => $this->normalizeText($restaurant['horaires'] ?? ''),
                'location_match' => $locationMatch,
                'distance_km' => $distanceKm !== null ? round($distanceKm, 2) : null,
                'matched_meals_count' => count($matchedMeals),
                'safe_meals_count' => $safeMealsCount,
                'allergy_conflict_meals' => $allergyConflictMeals,
                'trace_risk_meals' => $traceRiskMeals,
                'ai_potential_conflict_meals' => $aiPotentialConflictMeals,
                'regime_warning_meals' => $regimeWarningMeals,
                'unknown_meals_count' => $unknownMealsCount,
                'has_free_options' => $hasFreeVisible,
                'has_paid_options' => $hasPaidVisible,
                'matched_meals' => $matchedMeals,
                'score' => $score,
                'matching_brief' => [
                    'engine' => $this->ollamaEnabled() ? 'caremeal-hybrid-ai-' . $this->aiProviderName() : 'caremeal-local-semantics',
                    'safety_mode' => $safetyMode,
                    'user_allergies_raw' => $userAllergiesRaw,
                    'user_allergies_semantic' => $userAllergies,
                    'user_allergy_keywords' => $userAllergyKeywords,
                    'user_allergy_input_map' => $userAllergyInputMap,
                    'user_input_mapping' => $userAllergyInputDetails,
                    'user_regimes_semantic' => $userRegimes,
                    'score_reasons' => $scoreReasons,
                    'analysis_mode' => $analysisFallbackMeals > 0 ? 'fallback_local' : 'ai_live',
                ],
            ];
        }

        usort($matches, function ($a, $b) use ($sortBy) {
            if ($sortBy === 'location') {
                $aDist = isset($a['distance_km']) && $a['distance_km'] !== null ? (float)$a['distance_km'] : INF;
                $bDist = isset($b['distance_km']) && $b['distance_km'] !== null ? (float)$b['distance_km'] : INF;
                if ($aDist !== $bDist) {
                    return $aDist <=> $bDist;
                }
                $locCmp = ((int)!empty($b['location_match'])) <=> ((int)!empty($a['location_match']));
                if ($locCmp !== 0) return $locCmp;
            }
            if ($sortBy === 'name') {
                $nameCmp = strcmp((string)$a['nom'], (string)$b['nom']);
                if ($nameCmp !== 0) {
                    return $nameCmp;
                }
            }

            if ((int)$a['score'] !== (int)$b['score']) {
                return (int)$b['score'] <=> (int)$a['score'];
            }

            $timeCmp = strcmp(
                $this->parseRestaurantOpenTime($a['horaires'] ?? ''),
                $this->parseRestaurantOpenTime($b['horaires'] ?? '')
            );
            if ($timeCmp !== 0) {
                return $timeCmp;
            }

            return strcmp((string)$a['nom'], (string)$b['nom']);
        });

        return $matches;
    }

    public function getRankedOffersForUser($idUser) {
        $idUser = (int)$idUser;
        if ($idUser <= 0) {
            return [
                'ok' => false,
                'status' => 'error_invalid_user',
                'matches' => [],
            ];
        }

        $preferenceController = new PreferenceController();
        $preference = $preferenceController->getByUserId($idUser);
        if (!$preference) {
            return [
                'ok' => true,
                'status' => 'no_preference',
                'preference' => null,
                'matches' => [],
            ];
        }

        $offers = Matching::getMockOffers();
        $matches = Matching::rankOffers($preference, $offers);

        return [
            'ok' => true,
            'status' => empty($matches) ? 'no_match' : 'success',
            'preference' => $preference,
            'matches' => $matches,
        ];
    }

    public function assessMealAllergenRisk($meal, $userAllergies, $safetyMode = 'strict')
    {
        $profile = $this->inferMealAllergenProfile(is_array($meal) ? $meal : []);
        $userTokens = $this->semanticNormalizeList('allergen', $userAllergies);
        $userKeywords = $this->extractUserAllergyKeywords($userAllergies);
        $userInputDetails = $this->buildUserAllergyInputDetails($userAllergies);
        $classified = $this->classifyMealConflicts(is_array($meal) ? $meal : [], $profile, $userInputDetails);
        $mealTokens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($profile['semantic_allergens'] ?? [])));
        $safetyMode = strtolower($this->normalizeText($safetyMode));
        if ($safetyMode !== 'souple') {
            $safetyMode = 'strict';
        }

        $hard = is_array($classified['hard'] ?? null) ? $classified['hard'] : [];
        $soft = is_array($classified['soft'] ?? null) ? $classified['soft'] : [];
        $effective = $safetyMode === 'strict' ? $hard : array_merge($hard, $soft);

        return [
            'safe' => empty($effective),
            'safety_mode' => $safetyMode,
            'user_allergies_semantic' => $userTokens,
            'user_allergy_keywords' => $userKeywords,
            'user_input_mapping' => $userInputDetails,
            'meal_allergens_semantic' => $mealTokens,
            'profile' => $profile,
            'conflicts_hard' => $hard,
            'conflicts_soft' => $soft,
            'conflicts' => $effective,
        ];
    }

    public function getMatchedRestaurantsForPreference($idUser, $idPref, $options = []) {
        $result = $this->readOrBuildMatchingSnapshot($idUser, $idPref, $options);
        if (empty($result['ok'])) {
            return [
                'ok' => false,
                'status' => (string)($result['status'] ?? 'error_matching'),
                'restaurants' => [],
                'cache_hit' => false,
                'snapshot_version' => (int)($result['snapshot_version'] ?? 0),
                'analysis_summary' => is_array($result['analysis_summary'] ?? null) ? $result['analysis_summary'] : null,
            ];
        }
        return [
            'ok' => true,
            'status' => (string)($result['status'] ?? 'success'),
            'preference' => $result['preference'] ?? null,
            'restaurants' => is_array($result['restaurants'] ?? null) ? $result['restaurants'] : [],
            'applied_filters' => is_array($result['applied_filters'] ?? null) ? $result['applied_filters'] : [],
            'cache_hit' => !empty($result['cache_hit']),
            'snapshot_version' => (int)($result['snapshot_version'] ?? 0),
            'analysis_summary' => is_array($result['analysis_summary'] ?? null) ? $result['analysis_summary'] : null,
        ];
    }

    public function warmDefaultSnapshotForPreference($idUser, $idPref)
    {
        $result = $this->readOrBuildMatchingSnapshot((int)$idUser, (int)$idPref, [
            'price_mode' => 'all',
            'sort_by' => 'score',
            'safety_mode' => 'strict',
        ]);
        return !empty($result['ok']);
    }

    public function getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant, $options = []) {
        $idRestaurant = (int)$idRestaurant;
        if ($idRestaurant <= 0) {
            return [
                'ok' => false,
                'status' => 'error_restaurant_not_found',
                'restaurant' => null,
            ];
        }

        $filters = $this->normalizeMatchingFilters($options);
        $result = $this->readOrBuildMatchingSnapshot($idUser, $idPref, $filters);
        if (!$result['ok']) {
            return [
                'ok' => false,
                'status' => (string)($result['status'] ?? 'error_matching'),
                'restaurant' => null,
                'preference' => $result['preference'] ?? null,
                'cache_hit' => !empty($result['cache_hit']),
                'snapshot_version' => (int)($result['snapshot_version'] ?? 0),
                'analysis_summary' => is_array($result['analysis_summary'] ?? null) ? $result['analysis_summary'] : null,
            ];
        }
        $matchedRestaurant = MatchingSnapshotStore::findRestaurantInSnapshot((array)($result['restaurants'] ?? []), $idRestaurant);
        if (is_array($matchedRestaurant)) {
            return [
                'ok' => true,
                'status' => 'success',
                'preference' => $result['preference'] ?? null,
                'restaurant' => $matchedRestaurant,
                'cache_hit' => !empty($result['cache_hit']),
                'snapshot_version' => (int)($result['snapshot_version'] ?? 0),
            ];
        }

        return [
            'ok' => false,
            'status' => 'error_restaurant_not_matched',
            'preference' => $result['preference'] ?? null,
            'restaurant' => null,
            'cache_hit' => !empty($result['cache_hit']),
            'snapshot_version' => (int)($result['snapshot_version'] ?? 0),
        ];
    }

    public function getMatchedMealAiDetailForPreference($idUser, $idPref, $idRestaurant, $mealId, $options = []) {
        $mealId = $this->normalizeText($mealId);
        if ($mealId === '') {
            return [
                'ok' => false,
                'status' => 'error_invalid_meal',
                'meal' => null,
            ];
        }

        $filters = $this->normalizeMatchingFilters($options);
        $snapshot = $this->readOrBuildMatchingSnapshot($idUser, $idPref, $filters);
        if (empty($snapshot['ok'])) {
            return [
                'ok' => false,
                'status' => (string)($snapshot['status'] ?? 'error_restaurant_not_matched'),
                'meal' => null,
                'preference' => $snapshot['preference'] ?? null,
                'analysis_summary' => is_array($snapshot['analysis_summary'] ?? null) ? $snapshot['analysis_summary'] : null,
            ];
        }

        $restaurant = MatchingSnapshotStore::findRestaurantInSnapshot((array)($snapshot['restaurants'] ?? []), (int)$idRestaurant);
        $preference = is_array($snapshot['preference'] ?? null) ? $snapshot['preference'] : null;
        if (!$restaurant || !$preference) {
            return [
                'ok' => false,
                'status' => 'error_restaurant_not_matched',
                'meal' => null,
            ];
        }

        $selectedMeal = MatchingSnapshotStore::findMealInSnapshotRestaurant($restaurant, $mealId);
        if (!is_array($selectedMeal)) {
            return [
                'ok' => false,
                'status' => 'error_meal_not_found',
                'meal' => null,
            ];
        }

        $mealFromDb = $this->getRestaurantMealFromDb((int)($restaurant['id_restaurant'] ?? 0), $mealId);
        $fullMeal = $this->buildAnalysisMealPayload($selectedMeal, $mealFromDb);

        $safetyMode = strtolower($this->normalizeText($options['safety_mode'] ?? 'strict'));
        if (!in_array($safetyMode, ['strict', 'souple'], true)) {
            $safetyMode = 'strict';
        }
        $userAllergiesRaw = (string)($preference['allergies'] ?? '');
        $matchingBrief = is_array($restaurant['matching_brief'] ?? null) ? $restaurant['matching_brief'] : [];
        $userInputDetails = is_array($matchingBrief['user_input_mapping'] ?? null)
            ? array_values((array)$matchingBrief['user_input_mapping'])
            : $this->buildUserAllergyInputDetailsQuick($userAllergiesRaw);
        $userAllergyInputMap = is_array($matchingBrief['user_allergy_input_map'] ?? null)
            ? (array)$matchingBrief['user_allergy_input_map']
            : [];
        $aiProfile = $this->inferMealAllergenProfile($fullMeal);
        $consistency = $this->computeMealAiConsistency($fullMeal, $aiProfile);
        if (!empty($consistency['has_inconsistency'])) {
            $freshMealFromDb = $this->getRestaurantMealFromDb((int)($restaurant['id_restaurant'] ?? 0), $mealId);
            if (is_array($freshMealFromDb)) {
                $fullMeal = $this->buildAnalysisMealPayload($selectedMeal, $freshMealFromDb);
                $aiProfile = $this->inferMealAllergenProfile($fullMeal);
                $consistency = $this->computeMealAiConsistency($fullMeal, $aiProfile);
            }
        }
        $classified = $this->classifyMealConflicts($fullMeal, $aiProfile, $userInputDetails);
        $declaredAllergens = (array)($classified['declared_allergens'] ?? []);
        $inferredAllergens = (array)($classified['inferred_allergens'] ?? []);
        $semanticAllergens = $this->uniqueTokens(array_merge($declaredAllergens, $inferredAllergens));
        $hardConflicts = is_array($classified['hard'] ?? null) ? $classified['hard'] : [];
        $softConflicts = is_array($classified['soft'] ?? null) ? $classified['soft'] : [];
        $conflictTokens = $this->uniqueTokens(array_map(function ($c) {
            return $this->normalizeAllergenToken((string)($c['allergen'] ?? ''));
        }, array_merge($hardConflicts, $softConflicts)));

        $payload = [
            'meal_id' => (string)($fullMeal['meal_id'] ?? ''),
            'meal_name' => (string)($fullMeal['meal_name'] ?? ''),
            'ingredients' => (string)($fullMeal['ingredients'] ?? ''),
            'regime_compatible' => !empty($selectedMeal['regime_compatible']),
            'warning_regime' => !empty($selectedMeal['warning_regime']),
            'warning_allergene_hard' => !empty($selectedMeal['warning_allergene_hard']),
            'warning_allergene_soft' => !empty($selectedMeal['warning_allergene_soft']),
            'warning_unknown' => !empty($selectedMeal['warning_unknown']),
            'warning_unknown_message' => (string)($selectedMeal['warning_unknown_message'] ?? ''),
            'has_any_warning' => !empty($selectedMeal['has_any_warning']),
            'semantic_allergens' => $semanticAllergens,
            'trace_risk' => $this->hasTraceRisk($this->normalizeText(($fullMeal['meal_name'] ?? '') . ' ' . ($fullMeal['ingredients'] ?? '') . ' ' . ($fullMeal['allergens'] ?? ''))),
            'origin_badge' => (string)($aiProfile['origin_badge'] ?? 'Vendor declared'),
            'source' => (string)($aiProfile['source'] ?? 'ingredients'),
            'confidence' => (float)($aiProfile['confidence'] ?? 0),
            'data_quality_confidence' => (float)($aiProfile['data_quality_confidence'] ?? 0),
            'allergen_inference_confidence' => (float)($aiProfile['allergen_inference_confidence'] ?? 0),
            'ingredient_completeness_score' => (int)($aiProfile['ingredient_completeness_score'] ?? 0),
            'explanation' => (string)($aiProfile['explanation'] ?? ''),
            'inferred_ingredients' => array_values((array)($aiProfile['inferred_ingredients'] ?? [])),
            'missing_ingredients' => array_values((array)($aiProfile['missing_ingredients'] ?? [])),
            'inferred_allergens' => array_values((array)$inferredAllergens),
            'legal_warning' => !empty($aiProfile['warning'])
                ? 'Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.'
                : '',
            'allergen_sources' => (array)($aiProfile['allergen_sources'] ?? []),
            'allergen_source_scopes' => (array)($aiProfile['allergen_source_scopes'] ?? []),
            'causal_allergen_details' => array_values((array)($aiProfile['causal_allergen_details'] ?? [])),
            'possible_missing_ingredients' => !empty($aiProfile['possible_missing_ingredients']),
            'basic_recipe_ingredients' => array_values((array)($aiProfile['basic_recipe_ingredients'] ?? [])),
            'recipe_variants' => array_values((array)($aiProfile['variant_ingredients'] ?? [])),
            'basic_recipe_allergens' => array_values((array)($aiProfile['basic_recipe_allergens'] ?? [])),
            'ai_check_passed' => !empty($aiProfile['ai_check_passed']),
            'potential_conflict' => !empty($softConflicts) || !empty($hardConflicts),
            'conflict_tokens' => $conflictTokens,
            'conflict_hard' => array_values($hardConflicts),
            'conflict_soft' => array_values($softConflicts),
            'user_allergies_raw' => $userAllergiesRaw,
            'user_allergy_input_map' => $userAllergyInputMap,
            'user_input_mapping' => array_values($userInputDetails),
            'analysis_mode' => (string)($aiProfile['analysis_mode'] ?? $this->expectedAnalysisModeForStatus((int)($fullMeal['analyse_ia'] ?? 0))),
            'analyse_ia_status' => (int)($fullMeal['analyse_ia'] ?? 0),
            'analyse_ia_engine' => (string)($fullMeal['analyse_ia_engine'] ?? ''),
            'analyse_ia_model' => (string)($fullMeal['analyse_ia_model'] ?? ''),
            'analyse_ia_error' => (string)($fullMeal['analyse_ia_error'] ?? ''),
            'confidence_split' => [
                'data_quality_confidence' => (float)($aiProfile['data_quality_confidence'] ?? 0),
                'inference_confidence' => (float)($aiProfile['allergen_inference_confidence'] ?? 0),
            ],
            'safety_mode' => $safetyMode,
            'blocked_in_strict' => $safetyMode === 'strict' && !empty($hardConflicts),
            'consistency' => $consistency,
        ];

        return [
            'ok' => true,
            'status' => 'success',
            'preference' => $preference,
            'restaurant' => [
                'id_restaurant' => (int)($restaurant['id_restaurant'] ?? 0),
                'nom' => (string)($restaurant['nom'] ?? ''),
            ],
            'meal' => $payload,
            'cache_hit' => !empty($snapshot['cache_hit']),
            'snapshot_version' => (int)($snapshot['snapshot_version'] ?? 0),
        ];
    }

    public function debugMealAiConsistency($idUser, $idPref, $idRestaurant, $mealId, $options = []) {
        $idRestaurant = (int)$idRestaurant;
        $mealId = $this->normalizeText($mealId);
        if ($idRestaurant <= 0 || $mealId === '') {
            return [
                'ok' => false,
                'status' => 'error_invalid_debug_payload',
            ];
        }

        $detail = $this->getMatchedMealAiDetailForPreference($idUser, $idPref, $idRestaurant, $mealId, $options);
        $mealDb = $this->getRestaurantMealFromDb($idRestaurant, $mealId);
        $storedStatus = (int)($mealDb['analyse_ia'] ?? 0);
        $reportedMode = strtolower((string)($detail['meal']['analysis_mode'] ?? 'fallback_local'));
        $expectedMode = $this->expectedAnalysisModeForStatus($storedStatus);
        $mealPayload = is_array($detail['meal'] ?? null) ? $detail['meal'] : [];
        $missingMealId = $this->normalizeText($mealPayload['meal_id'] ?? '') === '';
        $emptyPayloadWithAi = $storedStatus === 1
            && empty((array)($mealPayload['inferred_ingredients'] ?? []))
            && empty((array)($mealPayload['semantic_allergens'] ?? []))
            && empty((array)($mealPayload['basic_recipe_ingredients'] ?? []));
        $modeMismatch = $reportedMode !== $expectedMode;
        $inconsistency = $missingMealId || $emptyPayloadWithAi || $modeMismatch;

        return [
            'ok' => true,
            'status' => 'success',
            'detail_ok' => !empty($detail['ok']),
            'detail_status' => (string)($detail['status'] ?? ''),
            'check' => [
                'meal_id' => $mealId,
                'stored_status' => $storedStatus,
                'expected_mode' => $expectedMode,
                'reported_mode' => $reportedMode,
                'missing_meal_id' => $missingMealId,
                'empty_payload_with_ai' => $emptyPayloadWithAi,
                'mode_mismatch' => $modeMismatch,
                'has_inconsistency' => $inconsistency,
            ],
            'db_snapshot' => [
                'analyse_ia' => (int)($mealDb['analyse_ia'] ?? 0),
                'analyse_ia_engine' => (string)($mealDb['analyse_ia_engine'] ?? ''),
                'analyse_ia_model' => (string)($mealDb['analyse_ia_model'] ?? ''),
                'analyse_ia_error' => (string)($mealDb['analyse_ia_error'] ?? ''),
                'analyse_ia_updated_at' => (string)($mealDb['analyse_ia_updated_at'] ?? ''),
                'meal_found' => is_array($mealDb),
            ],
            'ui_snapshot' => [
                'analysis_mode' => (string)($mealPayload['analysis_mode'] ?? ''),
                'origin_badge' => (string)($mealPayload['origin_badge'] ?? ''),
            ],
        ];
    }

    public function getRankedOffersForPreference($idUser, $idPref) {
        $idUser = (int)$idUser;
        $idPref = (int)$idPref;
        if ($idUser <= 0) {
            return [
                'ok' => false,
                'status' => 'error_invalid_user',
                'matches' => [],
            ];
        }
        if ($idPref <= 0) {
            return [
                'ok' => false,
                'status' => 'error_invalid_preference',
                'matches' => [],
            ];
        }

        $preferenceController = new PreferenceController();
        $preference = $preferenceController->getById($idPref);
        if (!$preference) {
            return [
                'ok' => false,
                'status' => 'error_preference_not_found',
                'matches' => [],
            ];
        }

        if ((int)($preference['id_user'] ?? 0) !== $idUser) {
            return [
                'ok' => false,
                'status' => 'error_forbidden_preference',
                'matches' => [],
            ];
        }

        $offers = Matching::getMockOffers();
        $matches = Matching::rankOffers($preference, $offers);

        return [
            'ok' => true,
            'status' => empty($matches) ? 'no_match' : 'success',
            'preference' => $preference,
            'matches' => $matches,
        ];
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $action = $_GET['action'] ?? '';

    if ($action === 'student_restaurants') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
        $priceMode = isset($_GET['price_mode']) ? (string)$_GET['price_mode'] : 'all';
        $sortBy = isset($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'score';
        $safetyMode = isset($_GET['safety_mode']) ? (string)$_GET['safety_mode'] : 'strict';
        $controller = new MatchingController();
        $result = $controller->getMatchedRestaurantsForPreference($idUser, $idPref, [
            'price_mode' => $priceMode,
            'sort_by' => $sortBy,
            'safety_mode' => $safetyMode,
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($action === 'student_restaurant_detail') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
        $idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;
        $safetyMode = isset($_GET['safety_mode']) ? (string)$_GET['safety_mode'] : 'strict';
        $priceMode = isset($_GET['price_mode']) ? (string)$_GET['price_mode'] : 'all';
        $sortBy = isset($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'score';
        $controller = new MatchingController();
        $result = $controller->getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant, [
            'safety_mode' => $safetyMode,
            'price_mode' => $priceMode,
            'sort_by' => $sortBy,
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($action === 'student_meal_ai_detail') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
        $idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;
        $mealId = isset($_GET['meal_id']) ? (string)$_GET['meal_id'] : '';
        $safetyMode = isset($_GET['safety_mode']) ? (string)$_GET['safety_mode'] : 'strict';
        $priceMode = isset($_GET['price_mode']) ? (string)$_GET['price_mode'] : 'all';
        $sortBy = isset($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'score';
        $controller = new MatchingController();
        $result = $controller->getMatchedMealAiDetailForPreference($idUser, $idPref, $idRestaurant, $mealId, [
            'safety_mode' => $safetyMode,
            'price_mode' => $priceMode,
            'sort_by' => $sortBy,
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($action === 'student_meal_ai_debug') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
        $idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;
        $mealId = isset($_GET['meal_id']) ? (string)$_GET['meal_id'] : '';
        $safetyMode = isset($_GET['safety_mode']) ? (string)$_GET['safety_mode'] : 'strict';
        $token = isset($_GET['debug_token']) ? (string)$_GET['debug_token'] : '';
        $controller = new MatchingController();
        if (!$controller->aiDebugEndpointEnabled() || !$controller->aiDebugTokenIsValid($token)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'status' => 'error_debug_forbidden',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        $result = $controller->debugMealAiConsistency($idUser, $idPref, $idRestaurant, $mealId, [
            'safety_mode' => $safetyMode,
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($action === 'student_matches') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
        $controller = new MatchingController();
        if ($idPref > 0) {
            $result = $controller->getRankedOffersForPreference($idUser, $idPref);
        } else {
            $result = $controller->getRankedOffersForUser($idUser);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'status' => 'error_unknown_action',
        'matches' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

