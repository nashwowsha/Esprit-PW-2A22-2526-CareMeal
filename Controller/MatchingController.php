<?php
require_once __DIR__ . '/PreferenceController.php';
require_once __DIR__ . '/../Model/Matching.php';
require_once __DIR__ . '/../config/database.php';

class MatchingController {
    private function db() {
        return config::getConnexion();
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

    private function normalizeToken($value) {
        $value = strtolower($this->normalizeText($value));
        $value = str_replace(['_', ' '], '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $value = preg_replace('/[^a-z0-9\-]/', '', $value);
        return trim((string)$value, '-');
    }

    private function normalizeAllergenToken($value) {
        $token = $this->normalizeToken($value);
        if ($token === '') {
            return '';
        }

        $aliases = [
            'peanut' => 'arachide',
            'peanuts' => 'arachide',
            'groundnut' => 'arachide',
            'groundnuts' => 'arachide',
            'arachides' => 'arachide',
            'nuts' => 'fruits-a-coque',
            'nut' => 'fruits-a-coque',
            'tree-nut' => 'fruits-a-coque',
            'tree-nuts' => 'fruits-a-coque',
            'lait' => 'lactose',
            'milk' => 'lactose',
            'sesame' => 'sesame',
            'sesame-seed' => 'sesame',
            'soy' => 'soja',
            'soybean' => 'soja',
            'gluten' => 'gluten',
            'lactose' => 'lactose',
            'soja' => 'soja',
            'arachide' => 'arachide',
            'fruits-a-coque' => 'fruits-a-coque',
        ];

        return $aliases[$token] ?? $token;
    }

    private function parseAllergenList($value) {
        $raw = $this->parseList($value);
        $tokens = array_values(array_filter(array_map(function ($item) {
            return $this->normalizeAllergenToken($item);
        }, $raw), static function ($token) {
            return $token !== '';
        }));
        return array_values(array_unique($tokens));
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
        $mealTokens = $this->parseAllergenList($mealAllergens);
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

        $mealTokens = array_values(array_unique(array_map(function ($token) {
            return $this->normalizeToken($token);
        }, $this->parseList($mealRegimes))));

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

    private function computeRestaurantScore($stats) {
        $safeMeals = (int)($stats['safe_meals_count'] ?? 0);
        if ($safeMeals <= 0) {
            return 0;
        }

        $regimeHits = (int)($stats['regime_hits'] ?? 0);
        $allergyConflicts = (int)($stats['allergy_conflict_meals'] ?? 0);
        $locationMatch = !empty($stats['location_match']);

        // Priorite 1: regime
        $score = 0;
        $score += min(70, ($regimeHits * 16) + ($safeMeals * 3));
        // Priorite 2: allergies (bonus si plus de meals safe, penalty si conflits)
        $score += min(20, $safeMeals * 2);
        $score -= min(25, $allergyConflicts * 5);
        // Priorite 3: localisation
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

        $userRegimes = array_values(array_unique(array_map(function ($token) {
            return $this->normalizeToken($token);
        }, $this->parseList($preference['regime_alimentaire'] ?? ''))));
        $hasRegimePreference = !empty($userRegimes);
        $userAllergies = $this->parseAllergenList($preference['allergies'] ?? '');
        $userLocation = $this->normalizeText($preference['localisation'] ?? '');
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

            $rawMeals = $this->decodeMeals($restaurant['meals_json'] ?? '[]');
            $matchedMeals = [];
            $safeMealsCount = 0;
            $allergyConflictMeals = 0;
            $regimeHits = 0;
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

                $regimeOverlap = $this->getRegimeOverlapCount($userRegimes, $meal['regime_tags'] ?? '');
                if ($hasRegimePreference && $regimeOverlap <= 0) {
                    continue;
                }

                $pricingMode = (($meal['pricing_mode'] ?? 'free') === 'paid') ? 'paid' : 'free';
                $allergyConflict = $this->hasAllergyConflict($userAllergies, $meal['allergens'] ?? '');
                if ($allergyConflict) {
                    $allergyConflictMeals += 1;
                    continue;
                }

                $safeMealsCount += 1;
                $regimeHits += $regimeOverlap;
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
            ];

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
                'matched_meals_count' => count($matchedMeals),
                'safe_meals_count' => $safeMealsCount,
                'allergy_conflict_meals' => $allergyConflictMeals,
                'has_free_options' => $hasFreeSafe,
                'has_paid_options' => $hasPaidSafe,
                'matched_meals' => $matchedMeals,
                'score' => $this->computeRestaurantScore($stats),
            ];
        }

        usort($matches, function ($a, $b) {
            if ($sortBy === 'location') {
                $locCmp = ((int)!empty($b['location_match'])) <=> ((int)!empty($a['location_match']));
                if ($locCmp !== 0) {
                    return $locCmp;
                }
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
            ],
        ];
    }

    public function getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant) {
        $idRestaurant = (int)$idRestaurant;
        if ($idRestaurant <= 0) {
            return [
                'ok' => false,
                'status' => 'error_restaurant_not_found',
                'restaurant' => null,
            ];
        }

        $result = $this->getMatchedRestaurantsForPreference($idUser, $idPref, [
            'id_restaurant' => $idRestaurant,
        ]);
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
        $controller = new MatchingController();
        $result = $controller->getMatchedRestaurantsForPreference($idUser, $idPref, [
            'price_mode' => $priceMode,
            'sort_by' => $sortBy,
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

    if ($action === 'student_restaurant_detail') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
        $idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;
        $controller = new MatchingController();
        $result = $controller->getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
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
        echo json_encode($result);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'status' => 'error_unknown_action',
        'matches' => [],
    ]);
    exit;
}

