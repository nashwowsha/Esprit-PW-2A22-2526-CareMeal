<?php
class Matching {
    public static function getMockOffers() {
        return [
            [
                'id_offer' => 1,
                'title' => 'Poulet Halal Bowl',
                'localisation' => 'Ariana',
                'pickup_start' => '12:00',
                'pickup_end' => '14:00',
                'price' => 7.5,
                'diet_tags' => ['halal', 'equilibre'],
                'allergens' => ['gluten'],
            ],
            [
                'id_offer' => 2,
                'title' => 'Salade Vegan Fraiche',
                'localisation' => 'Ariana',
                'pickup_start' => '11:30',
                'pickup_end' => '13:30',
                'price' => 6.0,
                'diet_tags' => ['vegan', 'bio', 'sans-lactose'],
                'allergens' => ['soja'],
            ],
            [
                'id_offer' => 3,
                'title' => 'Pasta Vegetarienne',
                'localisation' => 'Tunis Centre',
                'pickup_start' => '18:00',
                'pickup_end' => '20:00',
                'price' => 8.0,
                'diet_tags' => ['vegetarien', 'equilibre'],
                'allergens' => ['gluten', 'lactose'],
            ],
            [
                'id_offer' => 4,
                'title' => 'Wrap Sans Gluten',
                'localisation' => 'Ariana',
                'pickup_start' => '12:15',
                'pickup_end' => '13:45',
                'price' => 7.0,
                'diet_tags' => ['sans-gluten', 'halal'],
                'allergens' => ['sesame'],
            ],
            [
                'id_offer' => 5,
                'title' => 'Menu Petit Budget',
                'localisation' => 'Ariana',
                'pickup_start' => '12:30',
                'pickup_end' => '15:00',
                'price' => 5.0,
                'diet_tags' => ['budget', 'equilibre'],
                'allergens' => ['arachide'],
            ],
            [
                'id_offer' => 6,
                'title' => 'Plat Bio Sans Lactose',
                'localisation' => 'Ariana',
                'pickup_start' => '17:00',
                'pickup_end' => '19:00',
                'price' => 9.0,
                'diet_tags' => ['bio', 'sans-lactose', 'equilibre'],
                'allergens' => ['fruits-a-coque'],
            ],
        ];
    }

    public static function rankOffers($preference, $offers) {
        $userRegimes = self::parseList($preference['regime_alimentaire'] ?? '');
        $userAllergies = self::parseList($preference['allergies'] ?? '');
        $userLocation = trim((string)($preference['localisation'] ?? ''));
        $requestTime = self::extractRequestTime($preference['date_demande'] ?? '');

        $matches = [];

        foreach ($offers as $offer) {
            if (!self::locationMatches($userLocation, $offer['localisation'] ?? '')) {
                continue;
            }

            if (!self::scheduleMatches($requestTime, $offer['pickup_start'] ?? '', $offer['pickup_end'] ?? '')) {
                continue;
            }

            if (self::hasAllergyConflict($userAllergies, $offer['allergens'] ?? [])) {
                continue;
            }

            if (!self::dietMatches($userRegimes, $offer['diet_tags'] ?? [])) {
                continue;
            }

            $offer['score'] = self::calculateScore($offer, $userRegimes, $requestTime);
            $matches[] = $offer;
        }

        usort($matches, static function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            $timeCmp = strcmp((string)$a['pickup_start'], (string)$b['pickup_start']);
            if ($timeCmp !== 0) {
                return $timeCmp;
            }

            return $a['price'] <=> $b['price'];
        });

        return $matches;
    }

    private static function parseList($value) {
        if ($value === null) {
            return [];
        }

        $parts = preg_split('/[,;]+/', (string)$value);
        $clean = array_values(array_filter(array_map(static function ($item) {
            return strtolower(trim((string)$item));
        }, $parts), static function ($item) {
            return $item !== '';
        }));

        return array_values(array_unique($clean));
    }

    private static function extractRequestTime($dateDemande) {
        if (!$dateDemande) {
            return null;
        }

        $timestamp = strtotime((string)$dateDemande);
        if ($timestamp === false) {
            return null;
        }

        return date('H:i', $timestamp);
    }

    private static function locationMatches($userLocation, $offerLocation) {
        return strtolower(trim((string)$userLocation)) === strtolower(trim((string)$offerLocation));
    }

    private static function scheduleMatches($requestTime, $start, $end) {
        if ($requestTime === null || $start === '' || $end === '') {
            return false;
        }

        $requestMinutes = self::toMinutes($requestTime);
        $startMinutes = self::toMinutes($start);
        $endMinutes = self::toMinutes($end);

        if ($requestMinutes === null || $startMinutes === null || $endMinutes === null) {
            return false;
        }

        return $requestMinutes >= $startMinutes && $requestMinutes <= $endMinutes;
    }

    private static function hasAllergyConflict($userAllergies, $offerAllergens) {
        $offerTokens = array_values(array_filter(array_map(static function ($item) {
            return strtolower(trim((string)$item));
        }, (array)$offerAllergens), static function ($item) {
            return $item !== '';
        }));

        foreach ($userAllergies as $allergy) {
            foreach ($offerTokens as $offerAllergen) {
                if ($allergy === $offerAllergen) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function dietMatches($userRegimes, $offerDietTags) {
        if (empty($userRegimes)) {
            return true;
        }

        $offerTags = array_map(static function ($item) {
            return strtolower(trim((string)$item));
        }, (array)$offerDietTags);

        foreach ($userRegimes as $regime) {
            if (!in_array($regime, $offerTags, true)) {
                return false;
            }
        }

        return true;
    }

    private static function calculateScore($offer, $userRegimes, $requestTime) {
        $baseScore = 80;

        $offerTags = array_map(static function ($item) {
            return strtolower(trim((string)$item));
        }, (array)($offer['diet_tags'] ?? []));

        $matchedRegimes = array_intersect($userRegimes, $offerTags);
        $regimeScore = count($matchedRegimes) * 10;

        $timeBonus = 0;
        if ($requestTime !== null) {
            $requestMinutes = self::toMinutes($requestTime);
            $startMinutes = self::toMinutes((string)($offer['pickup_start'] ?? ''));

            if ($requestMinutes !== null && $startMinutes !== null) {
                $distance = abs($requestMinutes - $startMinutes);
                $timeBonus = max(0, 20 - (int)floor($distance / 10));
            }
        }

        return $baseScore + $regimeScore + $timeBonus;
    }

    private static function toMinutes($hhmm) {
        $parts = explode(':', (string)$hhmm);
        if (count($parts) < 2) {
            return null;
        }

        $hours = (int)$parts[0];
        $minutes = (int)$parts[1];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }
}

