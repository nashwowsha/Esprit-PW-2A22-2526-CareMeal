<?php
require_once __DIR__ . '/PreferenceController.php';
require_once __DIR__ . '/../Model/Matching.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class MatchingController {
    private $semanticMemo = [];
    private $mealInferenceMemo = [];

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
            'milk' => 'lait',
            'lait' => 'lait',
            'butter' => 'beurre',
            'beurre' => 'beurre',
            'bread' => 'pain',
            'bun' => 'pain',
            'pain' => 'pain',
            'tomato' => 'tomate',
            'tomate' => 'tomate',
            'lettuce' => 'laitue',
            'laitue' => 'laitue',
            'beef' => 'viande',
            'meat' => 'viande',
            'viande' => 'viande',
            'chicken' => 'poulet',
            'poulet' => 'poulet',
            'egg' => 'oeuf',
            'oeuf' => 'oeuf',
            'eggs' => 'oeuf',
            'mayonnaise' => 'mayonnaise',
            'mayo' => 'mayonnaise',
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
            'farine' => 'farine',
            'ble' => 'ble',
        ];
        return $labels[$concept] ?? $this->normalizeText($concept);
    }

    private function ingredientConceptSet($tokens) {
        $set = [];
        foreach ((array)$tokens as $token) {
            $concept = $this->normalizeIngredientConcept($token);
            if ($concept !== '') {
                $set[$concept] = true;
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

    private function buildCausalAllergenDetails($allergens, $sourceMap, $origin, $allergenInferenceConfidence, $dataQualityConfidence) {
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
            . "- use short ingredient/allergen tokens\n"
            . "- confidence from 0 to 1\n"
            . "- no extra text.";

        $response = $this->httpPostJson($this->ollamaEndpoint(), [
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0.1],
        ], 8);
        if (!is_array($response)) {
            return null;
        }

        $jsonRaw = $response['response'] ?? null;
        if (!is_string($jsonRaw) || $jsonRaw === '') {
            return null;
        }
        $decoded = json_decode($jsonRaw, true);
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
            'engine' => 'ollama',
        ];
    }

    private function inferMealAllergenProfile($meal) {
        if (!is_array($meal)) {
            return [
                'declared_allergens' => [],
                'inferred_ingredients' => [],
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
                'causal_allergen_details' => [],
                'ingredient_completeness_score' => 0,
                'data_quality_confidence' => 0.0,
                'allergen_inference_confidence' => 0.0,
                'ai_check_passed' => false,
                'engine' => 'deterministic',
            ];
        }

        $mealName = $this->normalizeText($meal['meal_name'] ?? '');
        $ingredients = $this->normalizeText($meal['ingredients'] ?? '');
        $disclosureMode = $this->normalizeMealAllergenDisclosureMode($meal);
        $declaredAllergens = $this->semanticNormalizeList('allergen', $meal['allergens'] ?? '');

        if ($disclosureMode === 'declared') {
            $providedIngredients = $this->splitIngredientTokens($ingredients);
            $declaredSourceMap = $this->inferAllergenSourcesFromIngredientTokens($providedIngredients, $declaredAllergens);
            if (empty($declaredSourceMap) && $ingredients !== '') {
                $declaredSourceMap = $this->inferAllergenEvidenceFromText($ingredients, $declaredAllergens);
            }
            return [
                'declared_allergens' => $declaredAllergens,
                'inferred_ingredients' => $providedIngredients,
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
                'causal_allergen_details' => $this->buildCausalAllergenDetails(
                    $declaredAllergens,
                    $declaredSourceMap,
                    'vendor_declared',
                    1.0,
                    1.0
                ),
                'ingredient_completeness_score' => 100,
                'data_quality_confidence' => 1.0,
                'allergen_inference_confidence' => 1.0,
                'ai_check_passed' => true,
                'engine' => $this->ollamaEnabled() ? 'hybrid' : 'deterministic',
            ];
        }

        $memoKey = md5($this->jsonEncodeSafe([
            'n' => $mealName,
            'i' => $ingredients,
            'm' => $disclosureMode,
            'a' => $meal['allergens'] ?? '',
        ], JSON_UNESCAPED_UNICODE));
        if (isset($this->mealInferenceMemo[$memoKey])) {
            return $this->mealInferenceMemo[$memoKey];
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
        $inferredAllergens = [];
        $confidence = 0.55;
        $explanation = '';
        $engine = 'deterministic';
        $providedIngredients = $this->splitIngredientTokens($ingredients);
        $recipeHints = $this->localRecipeHintsByMealName($mealName);
        $basicRecipeIngredients = $this->uniqueTokens(array_map(function ($token) {
            return strtolower($this->normalizeText((string)$token));
        }, (array)($recipeHints['ingredients'] ?? [])));
        $basicRecipeAllergens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($recipeHints['allergens'] ?? [])));
        $possibleMissingIngredients = false;

        if (is_array($ia)) {
            $inferredIngredients = (array)$ia['inferred_ingredients'];
            $inferredAllergens = (array)$ia['inferred_allergens'];
            $confidence = (float)$ia['confidence'];
            $explanation = (string)$ia['explanation'];
            $source = (string)$ia['source'];
            $engine = 'ollama';
        }

        if (empty($inferredIngredients) && $ingredients !== '') {
            $inferredIngredients = $this->splitIngredientTokens($ingredients);
        }
        if (empty($inferredAllergens) && $ingredients !== '') {
            $inferredAllergens = $this->inferAllergensFromText($ingredients);
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
            if ($explanation === '') {
                $explanation = 'La description ingredients semble incomplete; l IA a compare avec la recette de base.';
            }
            if ($confidence < 0.50) {
                $confidence = 0.50;
            }
        }

        // Vendor-first but safety-first: if a known base recipe indicates missing core parts,
        // we enrich potential allergens from that base recipe (especially useful for gluten in burgers/sandwiches).
        if ($ingredients !== '' && !empty($basicRecipeIngredients) && !empty($basicRecipeAllergens)) {
            $missingFromRecipe = $this->deriveMissingIngredients($providedIngredients, $basicRecipeIngredients);
            if (!empty($missingFromRecipe)) {
                $possibleMissingIngredients = true;
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
        if (!empty($basicRecipeIngredients)) {
            $recipeEvidence = $this->inferAllergenSourcesFromIngredientTokens($basicRecipeIngredients, (array)$profile['semantic_allergens']);
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
        $profile['causal_allergen_details'] = $this->buildCausalAllergenDetails(
            (array)$profile['semantic_allergens'],
            (array)$profile['allergen_sources'],
            (string)($profile['origin_code'] ?? 'ai_from_ingredients'),
            (float)$profile['allergen_inference_confidence'],
            (float)$profile['data_quality_confidence']
        );
        $profile['ai_check_passed'] = ((int)$profile['ingredient_completeness_score'] >= 80) && empty($profile['missing_ingredients']);

        $this->mealInferenceMemo[$memoKey] = $profile;
        return $profile;
    }

    private function ollamaEnabled() {
        $flag = strtolower((string)caremeal_env('CAREMEAL_OLLAMA_ENABLED', '0'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function ollamaEndpoint() {
        return trim((string)caremeal_env('CAREMEAL_OLLAMA_ENDPOINT', 'http://127.0.0.1:11434/api/generate'));
    }

    private function ollamaModel() {
        return trim((string)caremeal_env('CAREMEAL_OLLAMA_MODEL', 'llama3.1:8b'));
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
        $memoKey = $type . ':' . md5($this->jsonEncodeSafe($tokens));
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

        $response = $this->httpPostJson($this->ollamaEndpoint(), [
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0],
        ], 6);
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

        $memoKey = 'allergen-free-text:' . md5($text);
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

        $response = $this->httpPostJson($this->ollamaEndpoint(), [
            'model' => $this->ollamaModel(),
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => ['temperature' => 0],
        ], 6);

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

    private function buildUserAllergyInputMap($rawAllergies)
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

        $map = [];
        foreach ($entries as $entry) {
            $map[$entry] = $this->semanticNormalizeList('allergen', $entry);
        }
        return $map;
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

    private function decodeMeals($json) {
        $json = trim((string)$json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
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
        $userAllergies = $this->semanticNormalizeList('allergen', $userAllergiesRaw);
        $userAllergyInputMap = $this->buildUserAllergyInputMap($userAllergiesRaw);
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
            $hasFreeSafe = false;
            $hasPaidSafe = false;
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
                if ($hasRegimePreference && $regimeOverlap <= 0) {
                    continue;
                }

                $pricingMode = (($meal['pricing_mode'] ?? 'free') === 'paid') ? 'paid' : 'free';
                $aiProfile = $this->inferMealAllergenProfile($meal);
                $declaredAllergens = (array)($aiProfile['declared_allergens'] ?? []);
                $inferredAllergens = (array)($aiProfile['inferred_allergens'] ?? []);
                $semanticAllergens = $this->uniqueTokens(array_merge($declaredAllergens, $inferredAllergens));
                $declaredConflict = false;
                $potentialConflict = false;
                $conflictTokens = [];
                foreach ($userAllergies as $userAllergyToken) {
                    if (in_array($userAllergyToken, $declaredAllergens, true)) {
                        $declaredConflict = true;
                        $conflictTokens[] = $userAllergyToken;
                    }
                    if (in_array($userAllergyToken, $inferredAllergens, true)) {
                        $potentialConflict = true;
                        $conflictTokens[] = $userAllergyToken;
                    }
                }
                $conflictTokens = $this->uniqueTokens($conflictTokens);

                if ($declaredConflict) {
                    $allergyConflictMeals += 1;
                    continue;
                }
                if ($potentialConflict && $safetyMode === 'strict') {
                    $allergyConflictMeals += 1;
                    continue;
                }

                $safeMealsCount += 1;
                $regimeHits += $regimeOverlap;
                $semanticCoverage += !empty($semanticAllergens) ? 1 : 0;
                $traceRisk = $this->hasTraceRisk($this->normalizeText(($meal['meal_name'] ?? '') . ' ' . ($meal['ingredients'] ?? '') . ' ' . ($meal['allergens'] ?? '')));
                if ($traceRisk) {
                    $traceRiskMeals += 1;
                }
                if ($potentialConflict && $safetyMode === 'souple') {
                    $aiPotentialConflictMeals += 1;
                }
                if ($pricingMode === 'paid') {
                    $hasPaidSafe = true;
                } else {
                    $hasFreeSafe = true;
                }

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
                    'inferred_ingredients' => (array)($aiProfile['inferred_ingredients'] ?? []),
                    'missing_ingredients' => (array)($aiProfile['missing_ingredients'] ?? []),
                    'allergen_disclosure_mode' => $this->normalizeMealAllergenDisclosureMode($meal),
                    'ai_source' => (string)($aiProfile['source'] ?? 'ingredients'),
                    'ai_confidence' => (float)($aiProfile['confidence'] ?? 0),
                    'ai_explanation' => (string)($aiProfile['explanation'] ?? ''),
                    'ai_origin_badge' => (string)($aiProfile['origin_badge'] ?? ''),
                    'ai_origin_code' => (string)($aiProfile['origin_code'] ?? ''),
                    'ai_warning' => !empty($aiProfile['warning']),
                    'ai_legal_warning' => !empty($aiProfile['warning'])
                        ? 'Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.'
                        : '',
                    'ai_allergen_sources' => (array)($aiProfile['allergen_sources'] ?? []),
                    'ai_causal_allergen_details' => (array)($aiProfile['causal_allergen_details'] ?? []),
                    'ai_possible_missing_ingredients' => !empty($aiProfile['possible_missing_ingredients']),
                    'ai_basic_recipe_ingredients' => (array)($aiProfile['basic_recipe_ingredients'] ?? []),
                    'ai_basic_recipe_allergens' => (array)($aiProfile['basic_recipe_allergens'] ?? []),
                    'ai_ingredient_completeness_score' => (int)($aiProfile['ingredient_completeness_score'] ?? 0),
                    'ai_data_quality_confidence' => (float)($aiProfile['data_quality_confidence'] ?? 0),
                    'ai_allergen_inference_confidence' => (float)($aiProfile['allergen_inference_confidence'] ?? 0),
                    'ai_check_passed' => !empty($aiProfile['ai_check_passed']),
                    'ai_potential_conflict' => $potentialConflict && $safetyMode === 'souple',
                    'ai_conflict_tokens' => $conflictTokens,
                    'trace_risk' => $traceRisk,
                ];
            }

            if (empty($matchedMeals)) {
                continue;
            }

            if ($priceMode === 'free' && !$hasFreeSafe) {
                continue;
            }
            if ($priceMode === 'paid' && !$hasPaidSafe) {
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
                'has_free_options' => $hasFreeSafe,
                'has_paid_options' => $hasPaidSafe,
                'matched_meals' => $matchedMeals,
                'score' => $score,
                'matching_brief' => [
                    'engine' => $this->ollamaEnabled() ? 'caremeal-hybrid-ai' : 'caremeal-local-semantics',
                    'safety_mode' => $safetyMode,
                    'user_allergies_raw' => $userAllergiesRaw,
                    'user_allergies_semantic' => $userAllergies,
                    'user_allergy_input_map' => $userAllergyInputMap,
                    'user_regimes_semantic' => $userRegimes,
                    'score_reasons' => $scoreReasons,
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
        $mealTokens = $this->uniqueTokens(array_map(function ($token) {
            return $this->normalizeAllergenToken((string)$token);
        }, (array)($profile['semantic_allergens'] ?? [])));
        $safetyMode = strtolower($this->normalizeText($safetyMode));
        if ($safetyMode !== 'souple') {
            $safetyMode = 'strict';
        }

        $detailMap = [];
        foreach ((array)($profile['causal_allergen_details'] ?? []) as $detail) {
            if (!is_array($detail)) {
                continue;
            }
            $token = $this->normalizeAllergenToken((string)($detail['allergen'] ?? ''));
            if ($token === '') {
                continue;
            }
            $detailMap[$token] = $detail;
        }

        $sourceMap = is_array($profile['allergen_sources'] ?? null) ? $profile['allergen_sources'] : [];
        $conflicts = [];
        foreach ($userTokens as $allergyToken) {
            if (!in_array($allergyToken, $mealTokens, true)) {
                continue;
            }

            $detail = $detailMap[$allergyToken] ?? [];
            $triggers = [];
            if (isset($detail['trigger_ingredients']) && is_array($detail['trigger_ingredients'])) {
                $triggers = $detail['trigger_ingredients'];
            } elseif (isset($sourceMap[$allergyToken]) && is_array($sourceMap[$allergyToken])) {
                $triggers = $sourceMap[$allergyToken];
            }
            $triggers = array_values(array_unique(array_values(array_filter(array_map(function ($v) {
                return $this->normalizeText((string)$v);
            }, $triggers), static function ($v) {
                return $v !== '';
            }))));

            $origin = (string)($detail['origin'] ?? '');
            if ($origin === '') {
                $origin = (string)($profile['origin_code'] ?? 'ai_from_ingredients');
            }

            $conflicts[] = [
                'allergen' => $allergyToken,
                'trigger_ingredients' => $triggers,
                'origin' => $origin,
                'confidence' => isset($detail['confidence']) ? (float)$detail['confidence'] : (float)($profile['allergen_inference_confidence'] ?? 0),
            ];
        }

        return [
            'safe' => empty($conflicts),
            'safety_mode' => $safetyMode,
            'user_allergies_semantic' => $userTokens,
            'meal_allergens_semantic' => $mealTokens,
            'profile' => $profile,
            'conflicts' => $conflicts,
        ];
    }

    public function getMatchedRestaurantsForPreference($idUser, $idPref, $options = []) {
        $idUser = (int)$idUser;
        $idPref = (int)$idPref;
        if ($idUser <= 0) {
            return [
                'ok' => false,
                'status' => 'error_invalid_user',
                'restaurants' => [],
            ];
        }
        if ($idPref <= 0) {
            return [
                'ok' => false,
                'status' => 'error_invalid_preference',
                'restaurants' => [],
            ];
        }

        $preferenceController = new PreferenceController();
        $preference = $preferenceController->getById($idPref);
        if (!$preference) {
            return [
                'ok' => false,
                'status' => 'error_preference_not_found',
                'restaurants' => [],
            ];
        }

        if ((int)($preference['id_user'] ?? 0) !== $idUser) {
            return [
                'ok' => false,
                'status' => 'error_forbidden_preference',
                'restaurants' => [],
            ];
        }

        $restaurants = $this->getRestaurantsForMatching();
        $matches = $this->buildRestaurantMatches($preference, $restaurants, $options);

        return [
            'ok' => true,
            'status' => empty($matches) ? 'no_match' : 'success',
            'preference' => $preference,
            'restaurants' => $matches,
            'applied_filters' => [
                'price_mode' => strtolower($this->normalizeText($options['price_mode'] ?? 'all')),
                'sort_by' => strtolower($this->normalizeText($options['sort_by'] ?? 'score')),
                'safety_mode' => strtolower($this->normalizeText($options['safety_mode'] ?? 'strict')),
                'semantic_engine' => $this->ollamaEnabled() ? 'caremeal-hybrid-ai' : 'caremeal-local-semantics',
            ],
        ];
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

        $forwarded = is_array($options) ? $options : [];
        $forwarded['id_restaurant'] = $idRestaurant;
        $result = $this->getMatchedRestaurantsForPreference($idUser, $idPref, $forwarded);
        if (!$result['ok']) {
            return [
                'ok' => false,
                'status' => $result['status'],
                'restaurant' => null,
                'preference' => $result['preference'] ?? null,
            ];
        }

        if (!empty($result['restaurants'][0])) {
            return [
                'ok' => true,
                'status' => 'success',
                'preference' => $result['preference'],
                'restaurant' => $result['restaurants'][0],
            ];
        }

        return [
            'ok' => false,
            'status' => 'error_restaurant_not_matched',
            'preference' => $result['preference'] ?? null,
            'restaurant' => null,
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
        $controller = new MatchingController();
        $result = $controller->getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant, [
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

