<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/MatchingController.php';

class PlanningCollecteController
{
    private $geoRad = 0.017453292519943295;

    private function db()
    {
        return config::getConnexion();
    }

    private function norm($value)
    {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/', ' ', $value);
        return $value ?? '';
    }

    private function normalizeDateTimeInput($value)
    {
        $value = $this->norm($value);
        if ($value === '') {
            return '';
        }
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        return '';
    }

    private function normalizeTimeInput($value)
    {
        $value = $this->norm($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} (\d{2}:\d{2})(:\d{2})?$/', $value, $m)) {
            return $m[1];
        }
        return '';
    }

    private function normalizeCoordinateInput($value)
    {
        $value = $this->norm($value);
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (float)$value;
    }

    private function normalizePhoneInput($value)
    {
        $value = $this->norm($value);
        if ($value === '') {
            return '';
        }
        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function createTrackingToken()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return sha1(uniqid('track_', true) . mt_rand());
        }
    }

    private function trackingSnapEnabled()
    {
        $flag = strtolower((string)caremeal_env('CAREMEAL_TRACKING_SNAP_ENABLED', '1'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function trackingMaxAcceptAccuracyMeters()
    {
        $value = caremeal_env('CAREMEAL_TRACKING_MAX_ACCEPT_ACCURACY_M', '120');
        $meters = is_numeric($value) ? (float)$value : 120.0;
        return max(20.0, min(500.0, $meters));
    }

    private function trackingPoorAccuracyMeters()
    {
        $value = caremeal_env('CAREMEAL_TRACKING_POOR_ACCURACY_M', '70');
        $meters = is_numeric($value) ? (float)$value : 70.0;
        return max(15.0, min(300.0, $meters));
    }

    private function trackingSnapMaxAccuracyMeters()
    {
        $value = caremeal_env('CAREMEAL_TRACKING_SNAP_MAX_ACCURACY_M', '35');
        $meters = is_numeric($value) ? (float)$value : 35.0;
        return max(5.0, min(120.0, $meters));
    }

    private function trackingMaxSpeedMps()
    {
        $value = caremeal_env('CAREMEAL_TRACKING_MAX_SPEED_MPS', '40');
        $mps = is_numeric($value) ? (float)$value : 40.0;
        return max(8.0, min(100.0, $mps));
    }

    private function trackingTrackerLockTtlSec()
    {
        $value = caremeal_env('CAREMEAL_TRACKING_TRACKER_LOCK_TTL_SEC', '180');
        $sec = is_numeric($value) ? (int)$value : 180;
        return max(30, min(3600, $sec));
    }

    private function normalizeTrackerClientId($value)
    {
        $value = strtolower($this->norm($value));
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^a-z0-9_\-]/', '', $value);
        if (!is_string($value)) {
            return '';
        }
        if (strlen($value) > 80) {
            $value = substr($value, 0, 80);
        }
        return $value;
    }

    private function pusherEnabled()
    {
        $flag = strtolower((string)caremeal_env('CAREMEAL_PUSHER_ENABLED', '0'));
        if (!in_array($flag, ['1', 'true', 'yes', 'on'], true)) {
            return false;
        }
        return $this->pusherKey() !== '' && $this->pusherSecret() !== '' && $this->pusherAppId() !== '' && $this->pusherCluster() !== '';
    }

    private function pusherAppId()
    {
        return trim((string)caremeal_env('CAREMEAL_PUSHER_APP_ID', ''));
    }

    private function pusherKey()
    {
        return trim((string)caremeal_env('CAREMEAL_PUSHER_KEY', ''));
    }

    private function pusherSecret()
    {
        return trim((string)caremeal_env('CAREMEAL_PUSHER_SECRET', ''));
    }

    private function pusherCluster()
    {
        return trim((string)caremeal_env('CAREMEAL_PUSHER_CLUSTER', ''));
    }

    private function pusherApiHost()
    {
        return 'api-' . $this->pusherCluster() . '.pusher.com';
    }

    private function triggerPusherEvent($channels, $eventName, $payload)
    {
        if (!$this->pusherEnabled()) {
            return false;
        }
        $channels = array_values(array_filter(array_unique(array_map(static function ($c) {
            return trim((string)$c);
        }, (array)$channels)), static function ($c) {
            return $c !== '';
        }));
        if (empty($channels)) {
            return false;
        }

        $bodyData = [
            'name' => (string)$eventName,
            'channels' => $channels,
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ];
        $body = json_encode($bodyData, JSON_UNESCAPED_UNICODE);
        if (!is_string($body) || $body === '') {
            return false;
        }

        $authTimestamp = time();
        $authVersion = '1.0';
        $bodyMd5 = md5($body);
        $path = '/apps/' . rawurlencode($this->pusherAppId()) . '/events';
        $queryParams = [
            'auth_key' => $this->pusherKey(),
            'auth_timestamp' => $authTimestamp,
            'auth_version' => $authVersion,
            'body_md5' => $bodyMd5,
        ];
        ksort($queryParams);
        $query = http_build_query($queryParams);
        $stringToSign = "POST\n{$path}\n{$query}";
        $signature = hash_hmac('sha256', $stringToSign, $this->pusherSecret());
        $url = 'https://' . $this->pusherApiHost() . $path . '?' . $query . '&auth_signature=' . $signature;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_USERAGENT, 'CareMeal/1.0');
            curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode >= 200 && $httpCode < 300;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 2,
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
            ],
        ]);
        $result = @file_get_contents($url, false, $ctx);
        return $result !== false;
    }

    private function toRad($deg)
    {
        return ((float)$deg) * $this->geoRad;
    }

    private function distanceMeters($lat1, $lng1, $lat2, $lng2)
    {
        $lat1 = $this->toRad($lat1);
        $lng1 = $this->toRad($lng1);
        $lat2 = $this->toRad($lat2);
        $lng2 = $this->toRad($lng2);
        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;
        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * (sin($dLng / 2) ** 2);
        $c = 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
        return 6371000 * $c;
    }

    private function fetchJson($url, $timeoutSec = 2)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }

        $timeoutSec = max(1, (int)$timeoutSec);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSec);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
            curl_setopt($ch, CURLOPT_USERAGENT, 'CareMeal/1.0');
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
                'method' => 'GET',
                'timeout' => $timeoutSec,
                'header' => "User-Agent: CareMeal/1.0\r\n",
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function snapCoordsToRoad($lat, $lng)
    {
        $lat = (float)$lat;
        $lng = (float)$lng;

        if (!$this->trackingSnapEnabled()) {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => null];
        }

        $url = 'https://router.project-osrm.org/nearest/v1/driving/'
            . rawurlencode((string)$lng) . ',' . rawurlencode((string)$lat)
            . '?number=1';
        $data = $this->fetchJson($url, 2);
        if (!is_array($data) || ($data['code'] ?? '') !== 'Ok') {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => null];
        }

        $waypoint = $data['waypoints'][0] ?? null;
        if (!is_array($waypoint)) {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => null];
        }

        $location = $waypoint['location'] ?? null; // [lng, lat]
        if (!is_array($location) || count($location) < 2) {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => null];
        }

        $snapLng = is_numeric($location[0]) ? (float)$location[0] : null;
        $snapLat = is_numeric($location[1]) ? (float)$location[1] : null;
        if ($snapLat === null || $snapLng === null) {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => null];
        }

        $distance = isset($waypoint['distance']) && is_numeric($waypoint['distance'])
            ? (float)$waypoint['distance']
            : $this->distanceMeters($lat, $lng, $snapLat, $snapLng);

        // If snap is too far from GPS point, keep raw location.
        if ($distance > 120) {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => $distance];
        }

        return ['lat' => $snapLat, 'lng' => $snapLng, 'source' => 'snapped_osrm', 'snap_distance_m' => $distance];
    }

    private function getTrackingPointForUpdate($trackingToken)
    {
        try {
            $query = $this->db()->prepare(
                "SELECT
                    pc.id_collecte,
                    pc.id_user,
                    pc.statut,
                    pc.delivery_tracker_client_id,
                    pc.delivery_driver_first_name,
                    pc.delivery_driver_last_name,
                    pc.delivery_driver_contact,
                    pc.delivery_driver_lat,
                    pc.delivery_driver_lng,
                    pc.delivery_driver_updated_at,
                    r.id_owner AS restaurant_owner_id,
                    r.nom AS restaurant_nom
                 FROM planning_collecte pc
                 LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
                 WHERE delivery_tracking_token = :token
                   AND pc.mode_collecte = 'delivery'
                   AND pc.statut IN ('en_cours_livraison', 'en_attente')
                 LIMIT 1
                 FOR UPDATE"
            );
            $query->execute(['token' => $trackingToken]);
            return $query->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function getTrackingPointByTokenAnyState($trackingToken)
    {
        try {
            $query = $this->db()->prepare(
                "SELECT
                    pc.id_collecte,
                    pc.mode_collecte,
                    pc.statut
                 FROM planning_collecte pc
                 WHERE pc.delivery_tracking_token = :token
                 LIMIT 1"
            );
            $query->execute(['token' => $trackingToken]);
            $row = $query->fetch();
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function shouldRejectTrackingPoint($previousRow, $lat, $lng, $accuracy)
    {
        if ($accuracy !== null && $accuracy > $this->trackingMaxAcceptAccuracyMeters()) {
            return ['reject' => true, 'status' => 'ignored_low_accuracy'];
        }

        if (!$previousRow) {
            return ['reject' => false, 'status' => 'accept'];
        }

        $prevLat = isset($previousRow['delivery_driver_lat']) && is_numeric($previousRow['delivery_driver_lat'])
            ? (float)$previousRow['delivery_driver_lat']
            : null;
        $prevLng = isset($previousRow['delivery_driver_lng']) && is_numeric($previousRow['delivery_driver_lng'])
            ? (float)$previousRow['delivery_driver_lng']
            : null;

        if ($prevLat === null || $prevLng === null) {
            return ['reject' => false, 'status' => 'accept'];
        }

        $distanceMeters = $this->distanceMeters($prevLat, $prevLng, $lat, $lng);
        if ($distanceMeters < 0.4) {
            return ['reject' => true, 'status' => 'ignored_micro_move'];
        }

        // Accuracy-aware jitter filter:
        // if movement is smaller than expected GPS uncertainty, ignore it.
        if ($accuracy !== null) {
            $minMoveFromAccuracy = max(4.0, min(35.0, $accuracy * 0.8));
            if ($distanceMeters < $minMoveFromAccuracy) {
                return ['reject' => true, 'status' => 'ignored_accuracy_jitter'];
            }
        }

        $prevUpdatedAt = (string)($previousRow['delivery_driver_updated_at'] ?? '');
        $dtSeconds = 0.0;
        if ($prevUpdatedAt !== '') {
            $prevTs = strtotime($prevUpdatedAt);
            if ($prevTs !== false) {
                $dtSeconds = max(0.0, time() - $prevTs);
            }
        }

        // Reject large sudden jumps when accuracy is medium/poor and elapsed time is short.
        if ($dtSeconds > 0.0 && $accuracy !== null && $accuracy > 25.0) {
            $maxReasonableJump = max(35.0, $accuracy * 2.5);
            if ($dtSeconds < 3.0 && $distanceMeters > $maxReasonableJump) {
                return ['reject' => true, 'status' => 'ignored_large_jump'];
            }
        }

        if ($dtSeconds > 0.0) {
            $speedMps = $distanceMeters / $dtSeconds;
            $maxMps = $this->trackingMaxSpeedMps();
            if ($speedMps > $maxMps && ($accuracy === null || $accuracy > 20.0)) {
                return ['reject' => true, 'status' => 'ignored_unrealistic_jump'];
            }
        }

        return ['reject' => false, 'status' => 'accept'];
    }

    private function publishDriverPointRealtime($previousRow, $lat, $lng)
    {
        if (!$this->pusherEnabled() || !is_array($previousRow)) {
            return;
        }

        $collecteId = (int)($previousRow['id_collecte'] ?? 0);
        $idUser = (int)($previousRow['id_user'] ?? 0);
        $idOwner = (int)($previousRow['restaurant_owner_id'] ?? 0);
        $status = $this->normalizeStatus((string)($previousRow['statut'] ?? 'en_cours_livraison'));
        $restaurantName = $this->norm((string)($previousRow['restaurant_nom'] ?? ''));
        $driverFirst = $this->norm((string)($previousRow['delivery_driver_first_name'] ?? ''));
        $driverLast = $this->norm((string)($previousRow['delivery_driver_last_name'] ?? ''));
        $driverName = trim($driverFirst . ' ' . $driverLast);
        if ($driverName === '') {
            $driverName = 'Livreur';
        }
        $driverContact = $this->norm((string)($previousRow['delivery_driver_contact'] ?? ''));

        if ($collecteId <= 0) {
            return;
        }

        $channels = ['caremeal-admin-live'];
        if ($idOwner > 0) {
            $channels[] = 'caremeal-partner-' . $idOwner . '-live';
        }
        if ($idUser > 0) {
            $channels[] = 'caremeal-student-' . $idUser . '-live';
        }

        $payload = [
            'collecte_id' => $collecteId,
            'kind' => 'driver',
            'label' => 'Livreur - collecte #' . $collecteId,
            'location' => $restaurantName,
            'status' => $status,
            'driver_name' => $driverName,
            'driver_contact' => $driverContact,
            'lat' => (float)$lat,
            'lng' => (float)$lng,
            'updated_at' => date('Y-m-d H:i:s'),
            'seconds_since_update' => 0,
            'is_live' => true,
        ];

        $this->triggerPusherEvent($channels, 'driver-location', $payload);
    }

    private function parseRestaurantHours($hours)
    {
        $hours = $this->norm($hours);
        if ($hours === '') {
            return null;
        }
        if (!preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $hours, $m)) {
            return null;
        }
        return ['open' => $m[1], 'close' => $m[2]];
    }

    private function getRestaurantContext($idRestaurant)
    {
        $query = $this->db()->prepare(
            "SELECT id_restaurant, horaires, meals_json
             FROM restaurant
             WHERE id_restaurant = :id
             LIMIT 1"
        );
        $query->execute(['id' => (int)$idRestaurant]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        $mealsRaw = json_decode((string)($row['meals_json'] ?? '[]'), true);
        $meals = is_array($mealsRaw) ? $mealsRaw : [];
        return [
            'id_restaurant' => (int)$row['id_restaurant'],
            'horaires' => (string)($row['horaires'] ?? ''),
            'meals' => $meals,
        ];
    }

    private function computeItemsAndTotal($restaurantMeals, $itemsSelection)
    {
        if (!is_array($itemsSelection) || empty($itemsSelection)) {
            return ['ok' => false, 'status' => 'items_empty'];
        }

        $mealIndex = [];
        foreach ($restaurantMeals as $meal) {
            $mealId = $this->norm($meal['meal_id'] ?? '');
            if ($mealId === '') {
                continue;
            }
            $mealIndex[$mealId] = [
                'meal_id' => $mealId,
                'meal_name' => $this->norm($meal['meal_name'] ?? ''),
                'quantity' => (int)($meal['quantity'] ?? 0),
                'pricing_mode' => ($meal['pricing_mode'] ?? 'free') === 'paid' ? 'paid' : 'free',
                'price' => (float)($meal['price'] ?? 0),
            ];
        }

        $requestedMap = [];
        foreach ($itemsSelection as $item) {
            if (!is_array($item)) {
                continue;
            }
            $mealId = $this->norm($item['meal_id'] ?? '');
            $qty = (int)($item['quantity'] ?? 0);
            if ($mealId === '' || $qty <= 0) {
                continue;
            }
            if (!isset($requestedMap[$mealId])) {
                $requestedMap[$mealId] = 0;
            }
            $requestedMap[$mealId] += $qty;
        }

        if (empty($requestedMap)) {
            return ['ok' => false, 'status' => 'items_empty'];
        }

        $normalizedItems = [];
        $total = 0.0;
        foreach ($requestedMap as $mealId => $requestedQty) {
            if (!isset($mealIndex[$mealId])) {
                return ['ok' => false, 'status' => 'items_invalid'];
            }
            $meal = $mealIndex[$mealId];
            if ($requestedQty > (int)$meal['quantity']) {
                return ['ok' => false, 'status' => 'items_unavailable'];
            }
            $unitPrice = $meal['pricing_mode'] === 'paid' ? max(0, (float)$meal['price']) : 0.0;
            $lineTotal = $unitPrice * $requestedQty;
            $total += $lineTotal;
            $normalizedItems[] = [
                'meal_id' => $meal['meal_id'],
                'meal_name' => $meal['meal_name'],
                'quantity' => $requestedQty,
                'pricing_mode' => $meal['pricing_mode'],
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'line_total' => number_format($lineTotal, 2, '.', ''),
            ];
        }

        return [
            'ok' => true,
            'items' => $normalizedItems,
            'total' => number_format($total, 2, '.', ''),
        ];
    }

    private function getReservedMealQuantities($idRestaurant, $excludeCollecteId = 0)
    {
        $idRestaurant = (int)$idRestaurant;
        $excludeCollecteId = (int)$excludeCollecteId;
        if ($idRestaurant <= 0) {
            return [];
        }

        $params = ['id_restaurant' => $idRestaurant];
        $sql = "SELECT items_json
                FROM planning_collecte
                WHERE id_restaurant = :id_restaurant
                  AND statut IN ('en_attente', 'en_cours_livraison')";

        if ($excludeCollecteId > 0) {
            $sql .= " AND id_collecte <> :id_collecte";
            $params['id_collecte'] = $excludeCollecteId;
        }

        try {
            $query = $this->db()->prepare($sql);
            $query->execute($params);
            $rows = $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }

        $reserved = [];
        foreach ($rows as $row) {
            $items = json_decode((string)($row['items_json'] ?? '[]'), true);
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $mealId = strtolower($this->norm($item['meal_id'] ?? ''));
                $qty = (int)($item['quantity'] ?? 0);
                if ($mealId === '' || $qty <= 0) {
                    continue;
                }
                if (!isset($reserved[$mealId])) {
                    $reserved[$mealId] = 0;
                }
                $reserved[$mealId] += $qty;
            }
        }

        return $reserved;
    }

    private function applyReservedQuantities($restaurantMeals, $reservedMap)
    {
        $restaurantMeals = is_array($restaurantMeals) ? $restaurantMeals : [];
        $reservedMap = is_array($reservedMap) ? $reservedMap : [];

        foreach ($restaurantMeals as $i => $meal) {
            $mealId = strtolower($this->norm($meal['meal_id'] ?? ''));
            $baseQty = (int)($meal['quantity'] ?? 0);
            $reservedQty = (int)($reservedMap[$mealId] ?? 0);
            $restaurantMeals[$i]['quantity'] = max(0, $baseQty - $reservedQty);
        }

        return $restaurantMeals;
    }

    private function getRestaurantMealsForUpdate($idRestaurant)
    {
        $query = $this->db()->prepare(
            "SELECT id_restaurant, meals_json
             FROM restaurant
             WHERE id_restaurant = :id
             LIMIT 1
             FOR UPDATE"
        );
        $query->execute(['id' => (int)$idRestaurant]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        $meals = json_decode((string)($row['meals_json'] ?? '[]'), true);
        return [
            'id_restaurant' => (int)$row['id_restaurant'],
            'meals' => is_array($meals) ? $meals : [],
        ];
    }

    private function saveRestaurantMeals($idRestaurant, $meals)
    {
        $query = $this->db()->prepare(
            "UPDATE restaurant
             SET meals_json = :meals_json
             WHERE id_restaurant = :id_restaurant"
        );
        $query->execute([
            'meals_json' => json_encode(array_values($meals), JSON_UNESCAPED_UNICODE),
            'id_restaurant' => (int)$idRestaurant,
        ]);
    }

    private function reserveStockFromMeals($restaurantMeals, $items)
    {
        $restaurantMeals = is_array($restaurantMeals) ? $restaurantMeals : [];
        $items = is_array($items) ? $items : [];
        if (empty($items)) {
            return ['ok' => false, 'status' => 'error_items_empty'];
        }

        $index = [];
        foreach ($restaurantMeals as $i => $meal) {
            $mealId = $this->norm($meal['meal_id'] ?? '');
            if ($mealId === '') {
                continue;
            }
            $index[$mealId] = $i;
        }

        foreach ($items as $item) {
            $mealId = $this->norm($item['meal_id'] ?? '');
            $qty = (int)($item['quantity'] ?? 0);
            if ($mealId === '' || $qty <= 0 || !isset($index[$mealId])) {
                return ['ok' => false, 'status' => 'error_items_invalid'];
            }
            $mealIndex = $index[$mealId];
            $available = (int)($restaurantMeals[$mealIndex]['quantity'] ?? 0);
            if ($qty > $available) {
                return ['ok' => false, 'status' => 'error_items_unavailable'];
            }
        }

        foreach ($items as $item) {
            $mealId = $this->norm($item['meal_id'] ?? '');
            $qty = (int)($item['quantity'] ?? 0);
            $mealIndex = $index[$mealId];
            $available = (int)($restaurantMeals[$mealIndex]['quantity'] ?? 0);
            $restaurantMeals[$mealIndex]['quantity'] = max(0, $available - $qty);
        }

        return ['ok' => true, 'meals' => $restaurantMeals];
    }

    private function releaseStockToMeals($restaurantMeals, $items)
    {
        $restaurantMeals = is_array($restaurantMeals) ? $restaurantMeals : [];
        $items = is_array($items) ? $items : [];
        if (empty($items)) {
            return ['ok' => true, 'meals' => $restaurantMeals];
        }

        $index = [];
        foreach ($restaurantMeals as $i => $meal) {
            $mealId = $this->norm($meal['meal_id'] ?? '');
            if ($mealId === '') {
                continue;
            }
            $index[$mealId] = $i;
        }

        foreach ($items as $item) {
            $mealId = $this->norm($item['meal_id'] ?? '');
            $qty = (int)($item['quantity'] ?? 0);
            if ($mealId === '' || $qty <= 0) {
                continue;
            }

            if (!isset($index[$mealId])) {
                $restaurantMeals[] = [
                    'meal_id' => $mealId,
                    'meal_name' => $this->norm($item['meal_name'] ?? $mealId),
                    'ingredients' => '',
                    'quantity' => 0,
                    'pricing_mode' => ($this->norm($item['pricing_mode'] ?? 'free') === 'paid') ? 'paid' : 'free',
                    'price' => (float)($item['unit_price'] ?? 0),
                    'regime_tags' => '',
                    'allergens' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $index[$mealId] = count($restaurantMeals) - 1;
            }

            $mealIndex = $index[$mealId];
            $current = (int)($restaurantMeals[$mealIndex]['quantity'] ?? 0);
            $restaurantMeals[$mealIndex]['quantity'] = $current + $qty;
        }

        return ['ok' => true, 'meals' => $restaurantMeals];
    }

    private function ensureTable()
    {
        $this->db()->exec(
            "CREATE TABLE IF NOT EXISTS planning_collecte (
                id_collecte INT AUTO_INCREMENT PRIMARY KEY,
                id_restaurant INT NOT NULL,
                id_pref INT NOT NULL,
                id_user INT NOT NULL,
                mode_collecte VARCHAR(20) NOT NULL,
                adresse_livraison VARCHAR(255) DEFAULT NULL,
                adresse_lat DECIMAL(10,7) DEFAULT NULL,
                adresse_lng DECIMAL(10,7) DEFAULT NULL,
                pref_regime_snapshot VARCHAR(1000) NOT NULL DEFAULT '',
                pref_allergies_snapshot VARCHAR(1000) NOT NULL DEFAULT '',
                pref_localisation_snapshot VARCHAR(1000) NOT NULL DEFAULT '',
                delivery_driver_first_name VARCHAR(100) DEFAULT NULL,
                delivery_driver_last_name VARCHAR(100) DEFAULT NULL,
                delivery_driver_contact VARCHAR(40) DEFAULT NULL,
                delivery_tracking_token VARCHAR(120) DEFAULT NULL,
                delivery_tracker_client_id VARCHAR(80) DEFAULT NULL,
                delivery_driver_lat DECIMAL(10,7) DEFAULT NULL,
                delivery_driver_lng DECIMAL(10,7) DEFAULT NULL,
                delivery_driver_updated_at DATETIME DEFAULT NULL,
                heure_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                heure_souhaitee DATETIME NOT NULL,
                statut VARCHAR(30) NOT NULL DEFAULT 'en_attente',
                items_json LONGTEXT NOT NULL,
                montant_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_collecte_restaurant (id_restaurant),
                INDEX idx_collecte_pref (id_pref),
                INDEX idx_collecte_user (id_user),
                INDEX idx_collecte_statut (statut),
                INDEX idx_collecte_heure (heure_souhaitee),
                INDEX idx_collecte_delivery_token (delivery_tracking_token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->ensureSnapshotColumns();
        $this->ensureDeliveryColumns();

        // If the table already existed before, CREATE TABLE IF NOT EXISTS
        // does not add FK constraints. We enforce them separately.
        $this->ensureForeignKeys();
    }

    private function columnExists($columnName)
    {
        $query = $this->db()->prepare(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'planning_collecte'
               AND COLUMN_NAME = :column_name
             LIMIT 1"
        );
        $query->execute(['column_name' => $columnName]);
        return (bool)$query->fetch();
    }

    private function ensureSnapshotColumns()
    {
        $columnsToAdd = [
            'adresse_lat' => "ALTER TABLE planning_collecte ADD COLUMN adresse_lat DECIMAL(10,7) DEFAULT NULL AFTER adresse_livraison",
            'adresse_lng' => "ALTER TABLE planning_collecte ADD COLUMN adresse_lng DECIMAL(10,7) DEFAULT NULL AFTER adresse_lat",
            'pref_regime_snapshot' => "ALTER TABLE planning_collecte ADD COLUMN pref_regime_snapshot VARCHAR(1000) NOT NULL DEFAULT '' AFTER adresse_lng",
            'pref_allergies_snapshot' => "ALTER TABLE planning_collecte ADD COLUMN pref_allergies_snapshot VARCHAR(1000) NOT NULL DEFAULT '' AFTER pref_regime_snapshot",
            'pref_localisation_snapshot' => "ALTER TABLE planning_collecte ADD COLUMN pref_localisation_snapshot VARCHAR(1000) NOT NULL DEFAULT '' AFTER pref_allergies_snapshot",
        ];

        foreach ($columnsToAdd as $column => $alterSql) {
            if (!$this->columnExists($column)) {
                try {
                    $this->db()->exec($alterSql);
                } catch (Exception $e) {
                    // Keep runtime stable if migration cannot run now.
                }
            }
        }
    }

    private function ensureDeliveryColumns()
    {
        $columnsToAdd = [
            'delivery_driver_first_name' => "ALTER TABLE planning_collecte ADD COLUMN delivery_driver_first_name VARCHAR(100) DEFAULT NULL AFTER pref_localisation_snapshot",
            'delivery_driver_last_name' => "ALTER TABLE planning_collecte ADD COLUMN delivery_driver_last_name VARCHAR(100) DEFAULT NULL AFTER delivery_driver_first_name",
            'delivery_driver_contact' => "ALTER TABLE planning_collecte ADD COLUMN delivery_driver_contact VARCHAR(40) DEFAULT NULL AFTER delivery_driver_last_name",
            'delivery_tracking_token' => "ALTER TABLE planning_collecte ADD COLUMN delivery_tracking_token VARCHAR(120) DEFAULT NULL AFTER delivery_driver_contact",
            'delivery_tracker_client_id' => "ALTER TABLE planning_collecte ADD COLUMN delivery_tracker_client_id VARCHAR(80) DEFAULT NULL AFTER delivery_tracking_token",
            'delivery_driver_lat' => "ALTER TABLE planning_collecte ADD COLUMN delivery_driver_lat DECIMAL(10,7) DEFAULT NULL AFTER delivery_tracker_client_id",
            'delivery_driver_lng' => "ALTER TABLE planning_collecte ADD COLUMN delivery_driver_lng DECIMAL(10,7) DEFAULT NULL AFTER delivery_driver_lat",
            'delivery_driver_updated_at' => "ALTER TABLE planning_collecte ADD COLUMN delivery_driver_updated_at DATETIME DEFAULT NULL AFTER delivery_driver_lng",
        ];

        foreach ($columnsToAdd as $column => $alterSql) {
            if (!$this->columnExists($column)) {
                try {
                    $this->db()->exec($alterSql);
                } catch (Exception $e) {
                    // Keep runtime stable if migration cannot run now.
                }
            }
        }
    }

    private function constraintExists($constraintName)
    {
        $query = $this->db()->prepare(
            "SELECT 1
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'planning_collecte'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME = :constraint_name
             LIMIT 1"
        );
        $query->execute(['constraint_name' => $constraintName]);
        return (bool)$query->fetch();
    }

    private function hasOrphans($sql)
    {
        $result = $this->db()->query($sql)->fetch();
        return isset($result['c']) && (int)$result['c'] > 0;
    }

    private function ensureForeignKeys()
    {
        try {
            if (
                !$this->constraintExists('fk_collecte_restaurant')
                && !$this->hasOrphans(
                    "SELECT COUNT(*) AS c
                     FROM planning_collecte pc
                     LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
                     WHERE r.id_restaurant IS NULL"
                )
            ) {
                $this->db()->exec(
                    "ALTER TABLE planning_collecte
                     ADD CONSTRAINT fk_collecte_restaurant
                     FOREIGN KEY (id_restaurant)
                     REFERENCES restaurant(id_restaurant)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE"
                );
            }

            if (
                !$this->constraintExists('fk_collecte_preference')
                && !$this->hasOrphans(
                    "SELECT COUNT(*) AS c
                     FROM planning_collecte pc
                     LEFT JOIN preference p ON p.id_pref = pc.id_pref
                     WHERE p.id_pref IS NULL"
                )
            ) {
                $this->db()->exec(
                    "ALTER TABLE planning_collecte
                     ADD CONSTRAINT fk_collecte_preference
                     FOREIGN KEY (id_pref)
                     REFERENCES preference(id_pref)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE"
                );
            }

            if (
                !$this->constraintExists('fk_collecte_user')
                && !$this->hasOrphans(
                    "SELECT COUNT(*) AS c
                     FROM planning_collecte pc
                     LEFT JOIN utilisateur u ON u.id = pc.id_user
                     WHERE u.id IS NULL"
                )
            ) {
                $this->db()->exec(
                    "ALTER TABLE planning_collecte
                     ADD CONSTRAINT fk_collecte_user
                     FOREIGN KEY (id_user)
                     REFERENCES utilisateur(id)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE"
                );
            }
        } catch (Exception $e) {
            // Keep runtime stable even if constraints cannot be added yet.
        }
    }

    private function restaurantExists($idRestaurant)
    {
        $query = $this->db()->prepare("SELECT id_restaurant FROM restaurant WHERE id_restaurant = :id LIMIT 1");
        $query->execute(['id' => (int)$idRestaurant]);
        return (bool)$query->fetch();
    }

    private function preferenceExists($idPref)
    {
        $query = $this->db()->prepare("SELECT id_pref FROM preference WHERE id_pref = :id LIMIT 1");
        $query->execute(['id' => (int)$idPref]);
        return (bool)$query->fetch();
    }

    private function userExists($idUser)
    {
        $query = $this->db()->prepare("SELECT id FROM utilisateur WHERE id = :id LIMIT 1");
        $query->execute(['id' => (int)$idUser]);
        return (bool)$query->fetch();
    }

    private function getStatusOptionsByMode($mode)
    {
        $mode = strtolower($this->norm($mode));
        if ($mode === 'delivery') {
            return ['en_attente', 'en_cours_livraison', 'livree'];
        }
        return ['en_attente', 'collecte'];
    }

    private function isStatusAllowedForMode($mode, $status)
    {
        if ($status === 'annulee') {
            return true;
        }
        return in_array($status, $this->getStatusOptionsByMode($mode), true);
    }

    private function normalizeStatus($status)
    {
        $status = strtolower($this->norm($status));
        $legacy = [
            'acceptee' => 'collecte',
            'en_preparation' => 'en_attente',
            'en_livraison' => 'en_cours_livraison',
        ];
        if (isset($legacy[$status])) {
            $status = $legacy[$status];
        }

        $allowed = ['en_attente', 'collecte', 'en_cours_livraison', 'livree', 'annulee'];
        return in_array($status, $allowed, true) ? $status : 'en_attente';
    }

    private function isStockCommittedStatus($mode, $status)
    {
        $mode = strtolower($this->norm($mode));
        $status = $this->normalizeStatus($status);

        if ($mode === 'delivery') {
            return $status === 'livree';
        }

        return $status === 'collecte';
    }

    private function isAllowedStatusTransition($mode, $fromStatus, $toStatus)
    {
        $mode = strtolower($this->norm($mode));
        $fromStatus = $this->normalizeStatus($fromStatus);
        $toStatus = $this->normalizeStatus($toStatus);

        if ($fromStatus === $toStatus) {
            return true;
        }
        if ($fromStatus === 'annulee') {
            return false;
        }

        if ($mode === 'delivery') {
            if ($fromStatus === 'en_attente') {
                return in_array($toStatus, ['en_cours_livraison', 'livree', 'annulee'], true);
            }
            if ($fromStatus === 'en_cours_livraison') {
                return in_array($toStatus, ['livree', 'annulee'], true);
            }
            if ($fromStatus === 'livree') {
                return false;
            }
            return false;
        }

        // pickup
        if ($fromStatus === 'en_attente') {
            return in_array($toStatus, ['collecte', 'annulee'], true);
        }
        if ($fromStatus === 'collecte') {
            return false;
        }
        return false;
    }

    private function getPreferenceSnapshot($idPref)
    {
        $query = $this->db()->prepare(
            "SELECT id_pref, id_user, regime_alimentaire, allergies, localisation
             FROM preference
             WHERE id_pref = :id_pref
             LIMIT 1"
        );
        $query->execute(['id_pref' => (int)$idPref]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        return [
            'id_user' => (int)($row['id_user'] ?? 0),
            'regime_alimentaire' => $this->norm($row['regime_alimentaire'] ?? ''),
            'allergies' => $this->norm($row['allergies'] ?? ''),
            'localisation' => $this->norm($row['localisation'] ?? ''),
        ];
    }

    private function allergenSafetyMode()
    {
        $mode = strtolower((string)caremeal_env('CAREMEAL_ALLERGEN_SAFETY_MODE', 'strict'));
        return $mode === 'souple' ? 'souple' : 'strict';
    }

    private function allergenOriginLabel($origin)
    {
        $origin = strtolower($this->norm($origin));
        $map = [
            'vendor_declared' => 'vendor_declared',
            'declared' => 'vendor_declared',
            'ai_from_ingredients' => 'ai_from_ingredients',
            'ingredients' => 'ai_from_ingredients',
            'ai_from_basic_recipe' => 'ai_from_basic_recipe',
            'meal_name_recipe' => 'ai_from_basic_recipe',
        ];
        return $map[$origin] ?? 'ai_from_ingredients';
    }

    private function simpleAllergenAliasMap()
    {
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
            'lactose' => 'lactose',
            'lait' => 'lactose',
            'milk' => 'lactose',
            'fromage' => 'lactose',
            'gluten' => 'gluten',
            'ble' => 'gluten',
            'wheat' => 'gluten',
            'pain' => 'gluten',
            'bread' => 'gluten',
            'bun' => 'gluten',
            'baguette' => 'gluten',
            'sandwich' => 'gluten',
            'soja' => 'soja',
            'soy' => 'soja',
            'sesame' => 'sesame',
            'oeuf' => 'oeuf',
            'egg' => 'oeuf',
            'poisson' => 'poisson',
            'fish' => 'poisson',
            'noix' => 'fruits-a-coque',
            'nuts' => 'fruits-a-coque',
            'moutarde' => 'moutarde',
            'mustard' => 'moutarde',
            'celeri' => 'celeri',
            'celery' => 'celeri',
            'mollusque' => 'mollusques',
            'mollusques' => 'mollusques',
            'mollusk' => 'mollusques',
            'mollusks' => 'mollusques',
            'sulfite' => 'sulfites',
            'sulfites' => 'sulfites',
            'sulphite' => 'sulfites',
            'sulphites' => 'sulfites',
        ];
    }

    private function knownAllergenCanonicalsLite()
    {
        return array_values(array_unique(array_values($this->simpleAllergenAliasMap())));
    }

    private function isKnownAllergenCanonicalLite($token)
    {
        $token = $this->normalizeAllergenTokenLite($token);
        if ($token === '') {
            return false;
        }
        return in_array($token, $this->knownAllergenCanonicalsLite(), true);
    }

    private function extractAllergenCandidatesLite($value)
    {
        $chunks = is_array($value) ? $value : [(string)$value];
        $out = [];

        foreach ($chunks as $chunk) {
            $text = strtolower($this->norm($chunk));
            if ($text === '') {
                continue;
            }

            $parts = preg_split('/[,;]+/', $text);
            if (is_array($parts)) {
                foreach ($parts as $part) {
                    $norm = $this->normalizeAllergenTokenLite($part);
                    if ($norm !== '') {
                        $out[] = $norm;
                    }
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
            for ($i = 0; $i < $count; $i++) {
                $out[] = $this->normalizeAllergenTokenLite($words[$i]);
                if (strlen($words[$i]) > 4 && substr($words[$i], -1) === 's') {
                    $out[] = $this->normalizeAllergenTokenLite(substr($words[$i], 0, -1));
                }
                if ($i + 1 < $count) {
                    $out[] = $this->normalizeAllergenTokenLite($words[$i] . '-' . $words[$i + 1]);
                }
            }
        }

        $out = array_values(array_unique(array_values(array_filter($out, function ($token) {
            return $token !== '' && $this->isKnownAllergenCanonicalLite($token);
        }))));
        return $out;
    }

    private function closestAllergenAliasTokenLite($token)
    {
        if (!function_exists('levenshtein')) {
            return '';
        }
        $token = strtolower((string)$token);
        if ($token === '' || strlen($token) < 4) {
            return '';
        }
        $aliases = array_keys($this->simpleAllergenAliasMap());
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

    private function normalizeAllergenTokenLite($value)
    {
        $token = strtolower($this->norm($value));
        $token = str_replace(['_', ' '], '-', $token);
        $token = preg_replace('/-+/', '-', $token);
        $token = preg_replace('/[^a-z0-9\-]/', '', (string)$token);
        $token = trim((string)$token, '-');
        if ($token === '') {
            return '';
        }
        $aliases = $this->simpleAllergenAliasMap();
        if (isset($aliases[$token])) {
            return $aliases[$token];
        }
        $parts = preg_split('/-+/', $token);
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $part = trim((string)$part);
                if ($part !== '' && isset($aliases[$part])) {
                    return $aliases[$part];
                }
            }
        }
        $closest = $this->closestAllergenAliasTokenLite($token);
        if ($closest !== '' && isset($aliases[$closest])) {
            return $aliases[$closest];
        }
        return $token;
    }

    private function parseAllergensLite($value)
    {
        return $this->extractAllergenCandidatesLite($value);
    }

    private function buildBlockedItemSummary($blockedItems)
    {
        $lines = [];
        foreach ((array)$blockedItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $meal = $this->norm($item['meal_name'] ?? '');
            $allergen = $this->norm($item['allergen'] ?? '');
            $origin = $this->allergenOriginLabel($item['origin'] ?? '');
            $triggers = [];
            if (isset($item['trigger_ingredients']) && is_array($item['trigger_ingredients'])) {
                foreach ($item['trigger_ingredients'] as $t) {
                    $v = $this->norm((string)$t);
                    if ($v !== '') {
                        $triggers[] = $v;
                    }
                }
            }
            $triggerText = empty($triggers) ? 'source incertaine' : implode(', ', array_values(array_unique($triggers)));
            $lines[] = 'Meal: ' . ($meal !== '' ? $meal : '-') . ' | Allergene: ' . ($allergen !== '' ? $allergen : '-') . ' | Source: ' . $origin . ' | Ingredient responsable: ' . $triggerText;
        }
        return implode(' || ', $lines);
    }

    private function validateAllergenSafetyGate($selectedItems, $restaurantMeals, $preferenceSnapshot)
    {
        $mode = $this->allergenSafetyMode();
        $selectedItems = is_array($selectedItems) ? $selectedItems : [];
        $restaurantMeals = is_array($restaurantMeals) ? $restaurantMeals : [];
        if (empty($selectedItems) || empty($restaurantMeals)) {
            return ['ok' => true, 'mode' => $mode, 'blocked_items' => []];
        }

        $mealIndex = [];
        foreach ($restaurantMeals as $meal) {
            if (!is_array($meal)) {
                continue;
            }
            $mealId = $this->norm($meal['meal_id'] ?? '');
            if ($mealId === '') {
                continue;
            }
            $mealIndex[$mealId] = $meal;
        }

        $blocked = [];
        $debug = [];
        $allergyInput = $preferenceSnapshot['allergies'] ?? '';
        try {
            $matchingController = new MatchingController();
            foreach ($selectedItems as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $mealId = $this->norm($item['meal_id'] ?? '');
                if ($mealId === '' || !isset($mealIndex[$mealId])) {
                    continue;
                }
                $meal = $mealIndex[$mealId];
                $risk = $matchingController->assessMealAllergenRisk($meal, $allergyInput, $mode);
                $mealName = $this->norm($meal['meal_name'] ?? $mealId);
                $conflicts = is_array($risk['conflicts'] ?? null) ? $risk['conflicts'] : [];
                $debug[] = [
                    'meal_id' => $mealId,
                    'meal_name' => $mealName,
                    'safe' => !empty($risk['safe']),
                    'conflicts' => $conflicts,
                ];
                if ($mode === 'strict' && !empty($conflicts)) {
                    foreach ($conflicts as $conflict) {
                        if (!is_array($conflict)) {
                            continue;
                        }
                        $blocked[] = [
                            'meal_id' => $mealId,
                            'meal_name' => $mealName,
                            'allergen' => $this->norm($conflict['allergen'] ?? ''),
                            'trigger_ingredients' => isset($conflict['trigger_ingredients']) && is_array($conflict['trigger_ingredients']) ? array_values($conflict['trigger_ingredients']) : [],
                            'origin' => $this->allergenOriginLabel($conflict['origin'] ?? ''),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            // Deterministic fallback when IA engine/controller is unavailable.
            $userTokens = $this->parseAllergensLite($allergyInput);
            foreach ($selectedItems as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $mealId = $this->norm($item['meal_id'] ?? '');
                if ($mealId === '' || !isset($mealIndex[$mealId])) {
                    continue;
                }
                $meal = $mealIndex[$mealId];
                $mealName = $this->norm($meal['meal_name'] ?? $mealId);
                $declaredTokens = $this->parseAllergensLite($meal['allergens'] ?? '');
                $conflicts = array_values(array_intersect($userTokens, $declaredTokens));
                if ($mode === 'strict' && !empty($conflicts)) {
                    foreach ($conflicts as $conflictToken) {
                        $blocked[] = [
                            'meal_id' => $mealId,
                            'meal_name' => $mealName,
                            'allergen' => $conflictToken,
                            'trigger_ingredients' => [],
                            'origin' => 'vendor_declared',
                        ];
                    }
                }
            }
        }

        if (!empty($blocked) && $mode === 'strict') {
            return [
                'ok' => false,
                'status' => 'error_allergen_blocked',
                'errors' => ['items_json'],
                'blocked_items' => $blocked,
                'blocked_items_summary' => $this->buildBlockedItemSummary($blocked),
                'safety_mode' => $mode,
                'matching_debug' => $debug,
            ];
        }

        return [
            'ok' => true,
            'mode' => $mode,
            'blocked_items' => [],
            'matching_debug' => $debug,
        ];
    }

    private function validateCollecte($source)
    {
        $idRestaurant = (int)($source['id_restaurant'] ?? 0);
        $idPref = (int)($source['id_pref'] ?? 0);
        $idUser = (int)($source['id_user'] ?? 0);
        $mode = strtolower($this->norm($source['mode_collecte'] ?? ''));
        $address = $this->norm($source['adresse_livraison'] ?? '');
        $addressLat = $this->normalizeCoordinateInput($source['adresse_lat'] ?? '');
        $addressLng = $this->normalizeCoordinateInput($source['adresse_lng'] ?? '');
        $requestedTime = $this->normalizeTimeInput($source['heure_souhaitee'] ?? '');
        $itemsRaw = trim((string)($source['items_json'] ?? ''));
        // Creation always starts as pending to keep stock lifecycle consistent.
        $status = 'en_attente';
        $errors = [];
        $restaurantContext = null;

        if ($idRestaurant <= 0) {
            return ['ok' => false, 'status' => 'error_restaurant_not_found', 'errors' => ['id_restaurant']];
        } else {
            $restaurantContext = $this->getRestaurantContext($idRestaurant);
            if (!$restaurantContext) {
                return ['ok' => false, 'status' => 'error_restaurant_not_found', 'errors' => ['id_restaurant']];
            }
        }
        if ($idPref <= 0 || !$this->preferenceExists($idPref)) {
            return ['ok' => false, 'status' => 'error_pref_not_found', 'errors' => ['id_pref']];
        }
        $prefSnapshot = $this->getPreferenceSnapshot($idPref);
        if (!$prefSnapshot) {
            return ['ok' => false, 'status' => 'error_pref_not_found', 'errors' => ['id_pref']];
        }
        if ($idUser <= 0 || !$this->userExists($idUser)) {
            return ['ok' => false, 'status' => 'error_user_not_found', 'errors' => ['id_user']];
        }
        if ((int)($prefSnapshot['id_user'] ?? 0) !== $idUser) {
            return ['ok' => false, 'status' => 'error_pref_user_mismatch', 'errors' => ['id_pref', 'id_user']];
        }
        if (!in_array($mode, ['pickup', 'delivery'], true)) {
            $errors[] = 'mode_collecte';
        }
        if ($mode === 'delivery') {
            if (strlen($address) < 5 || strlen($address) > 255) {
                $errors[] = 'adresse_livraison';
            }
            $validCoords = $addressLat !== null
                && $addressLng !== null
                && $addressLat >= -90 && $addressLat <= 90
                && $addressLng >= -180 && $addressLng <= 180;
            if (!$validCoords) {
                $errors[] = 'adresse_livraison';
            }
        }
        if ($requestedTime === '') {
            $errors[] = 'heure_souhaitee';
        } elseif ($restaurantContext) {
            $hours = $this->parseRestaurantHours($restaurantContext['horaires'] ?? '');
            if (!$hours || $hours['open'] >= $hours['close']) {
                $errors[] = 'heure_souhaitee';
            } elseif ($requestedTime < $hours['open'] || $requestedTime > $hours['close']) {
                $errors[] = 'heure_souhaitee';
            }
        }

        $items = json_decode($itemsRaw, true);
        if ($restaurantContext) {
            $reservedMap = $this->getReservedMealQuantities($idRestaurant);
            $effectiveMeals = $this->applyReservedQuantities($restaurantContext['meals'] ?? [], $reservedMap);
            $itemsResult = $this->computeItemsAndTotal($effectiveMeals, $items);
        } else {
            $itemsResult = ['ok' => false];
        }
        if (!$itemsResult['ok']) {
            $itemStatus = $itemsResult['status'] ?? 'items_invalid';
            if ($itemStatus === 'items_unavailable') {
                return ['ok' => false, 'status' => 'error_items_unavailable', 'errors' => ['items_json']];
            }
            if ($itemStatus === 'items_empty') {
                return ['ok' => false, 'status' => 'error_items_empty', 'errors' => ['items_json']];
            }
            return ['ok' => false, 'status' => 'error_items_invalid', 'errors' => ['items_json']];
        }
        $safetyGate = $this->validateAllergenSafetyGate($itemsResult['items'], $effectiveMeals ?? [], $prefSnapshot);
        if (empty($safetyGate['ok'])) {
            return $safetyGate;
        }
        if ($itemsResult['ok'] && (float)$itemsResult['total'] > 10000) {
            $errors[] = 'montant_total';
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_validation',
                'errors' => array_values(array_unique($errors)),
            ];
        }

        $requestedDateTime = date('Y-m-d') . ' ' . $requestedTime . ':00';
        return [
            'ok' => true,
            'payload' => [
                'id_restaurant' => $idRestaurant,
                'id_pref' => $idPref,
                'id_user' => $idUser,
                'mode_collecte' => $mode,
                'adresse_livraison' => ($mode === 'delivery') ? $address : null,
                'adresse_lat' => ($mode === 'delivery') ? $addressLat : null,
                'adresse_lng' => ($mode === 'delivery') ? $addressLng : null,
                'heure_souhaitee' => $requestedDateTime,
                'statut' => $status,
                'items_json' => json_encode($itemsResult['items'], JSON_UNESCAPED_UNICODE),
                'montant_total' => $itemsResult['total'],
            ],
        ];
    }

    public function createCollecte($source)
    {
        $this->ensureTable();
        $valid = $this->validateCollecte($source);
        if (!$valid['ok']) {
            return $valid;
        }

        $db = $this->db();
        try {
            $payload = $valid['payload'];
            $snapshot = $this->getPreferenceSnapshot($payload['id_pref']);
            if (!$snapshot) {
                return [
                    'ok' => false,
                    'status' => 'error_pref_not_found',
                    'errors' => ['id_pref'],
                ];
            }
            if ($snapshot['id_user'] !== (int)$payload['id_user']) {
                return [
                    'ok' => false,
                    'status' => 'error_pref_user_mismatch',
                    'errors' => ['id_pref', 'id_user'],
                ];
            }

            $db->beginTransaction();

            // Re-check availability inside transaction with a restaurant row lock.
            // This prevents race conditions where two users reserve the same last stock
            // at the same time.
            $lockedRestaurant = $this->getRestaurantMealsForUpdate((int)$payload['id_restaurant']);
            if (!$lockedRestaurant) {
                $db->rollBack();
                return [
                    'ok' => false,
                    'status' => 'error_restaurant_not_found',
                    'errors' => ['id_restaurant'],
                ];
            }
            $itemsInput = json_decode(trim((string)($source['items_json'] ?? '[]')), true);
            if (!is_array($itemsInput)) {
                $itemsInput = [];
            }
            $reservedMap = $this->getReservedMealQuantities((int)$payload['id_restaurant']);
            $effectiveMeals = $this->applyReservedQuantities($lockedRestaurant['meals'] ?? [], $reservedMap);
            $itemsResult = $this->computeItemsAndTotal($effectiveMeals, $itemsInput);
            if (empty($itemsResult['ok'])) {
                $db->rollBack();
                $itemStatus = $itemsResult['status'] ?? 'items_invalid';
                if ($itemStatus === 'items_unavailable') {
                    return ['ok' => false, 'status' => 'error_items_unavailable', 'errors' => ['items_json']];
                }
                if ($itemStatus === 'items_empty') {
                    return ['ok' => false, 'status' => 'error_items_empty', 'errors' => ['items_json']];
                }
                return ['ok' => false, 'status' => 'error_items_invalid', 'errors' => ['items_json']];
            }
            $safetyGate = $this->validateAllergenSafetyGate($itemsResult['items'], $effectiveMeals, $snapshot);
            if (empty($safetyGate['ok'])) {
                $db->rollBack();
                return $safetyGate;
            }

            $payload['pref_regime_snapshot'] = $snapshot['regime_alimentaire'];
            $payload['pref_allergies_snapshot'] = $snapshot['allergies'];
            $payload['pref_localisation_snapshot'] = $snapshot['localisation'];
            $payload['statut'] = 'en_attente';
            $payload['items_json'] = json_encode($itemsResult['items'], JSON_UNESCAPED_UNICODE);
            $payload['montant_total'] = $itemsResult['total'];
            $query = $db->prepare(
                "INSERT INTO planning_collecte (
                    id_restaurant, id_pref, id_user, mode_collecte, adresse_livraison, adresse_lat, adresse_lng,
                    pref_regime_snapshot, pref_allergies_snapshot, pref_localisation_snapshot,
                    heure_souhaitee, statut, items_json, montant_total
                ) VALUES (
                    :id_restaurant, :id_pref, :id_user, :mode_collecte, :adresse_livraison, :adresse_lat, :adresse_lng,
                    :pref_regime_snapshot, :pref_allergies_snapshot, :pref_localisation_snapshot,
                    :heure_souhaitee, :statut, :items_json, :montant_total
                )"
            );
            $query->execute($payload);
            $idCollecte = (int)$db->lastInsertId();
            $db->commit();

            return [
                'ok' => true,
                'status' => 'success_collecte_created',
                'id_collecte' => $idCollecte,
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    private function getCollecteContext($idCollecte)
    {
        $query = $this->db()->prepare(
            "SELECT
                pc.id_collecte,
                pc.id_restaurant,
                pc.id_user,
                pc.mode_collecte,
                pc.statut,
                pc.items_json,
                r.id_owner AS restaurant_owner_id
             FROM planning_collecte pc
             LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
             WHERE pc.id_collecte = :id_collecte
             LIMIT 1"
        );
        $query->execute(['id_collecte' => (int)$idCollecte]);
        $row = $query->fetch();
        return $row ?: null;
    }

    private function getCollecteContextForUpdate($idCollecte)
    {
        $query = $this->db()->prepare(
            "SELECT
                pc.id_collecte,
                pc.id_restaurant,
                pc.id_user,
                pc.mode_collecte,
                pc.statut,
                pc.items_json,
                r.id_owner AS restaurant_owner_id
             FROM planning_collecte pc
             LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
             WHERE pc.id_collecte = :id_collecte
             LIMIT 1
             FOR UPDATE"
        );
        $query->execute(['id_collecte' => (int)$idCollecte]);
        $row = $query->fetch();
        return $row ?: null;
    }

    private function updateStatusInternal($idCollecte, $status, $partnerOwnerId = null, $studentUserId = null)
    {
        $this->ensureTable();
        $db = $this->db();
        $idCollecte = (int)$idCollecte;
        if ($idCollecte <= 0) {
            return ['ok' => false, 'status' => 'error_not_found'];
        }

        try {
            $db->beginTransaction();
            $context = $this->getCollecteContextForUpdate($idCollecte);
            if (!$context) {
                $db->rollBack();
                return ['ok' => false, 'status' => 'error_not_found'];
            }

            if ($studentUserId !== null) {
                $studentUserId = (int)$studentUserId;
                if ($studentUserId <= 0 || (int)($context['id_user'] ?? 0) !== $studentUserId) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => 'error_forbidden_collecte',
                        'id_user' => $studentUserId,
                        'id_collecte' => $idCollecte,
                    ];
                }
            }

            if ($partnerOwnerId !== null) {
                $partnerOwnerId = (int)$partnerOwnerId;
                if ($partnerOwnerId <= 0 || (int)($context['restaurant_owner_id'] ?? 0) !== $partnerOwnerId) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => 'error_forbidden_collecte',
                        'id_owner' => $partnerOwnerId,
                        'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                    ];
                }
            }

            $status = $this->normalizeStatus($status);
            $mode = strtolower($this->norm($context['mode_collecte'] ?? ''));
            if (!$this->isStatusAllowedForMode($mode, $status)) {
                $db->rollBack();
                return [
                    'ok' => false,
                    'status' => 'error_invalid_status',
                    'id_collecte' => $idCollecte,
                    'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                    'id_owner' => (int)($context['restaurant_owner_id'] ?? 0),
                ];
            }

            $previousStatus = $this->normalizeStatus((string)($context['statut'] ?? 'en_attente'));
            if (!$this->isAllowedStatusTransition($mode, $previousStatus, $status)) {
                $db->rollBack();
                return [
                    'ok' => false,
                    'status' => 'error_invalid_status',
                    'id_collecte' => $idCollecte,
                    'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                    'id_owner' => (int)($context['restaurant_owner_id'] ?? 0),
                ];
            }
            $wasCommitted = $this->isStockCommittedStatus($mode, $previousStatus);
            $willCommit = $this->isStockCommittedStatus($mode, $status);

            if (!$wasCommitted && $willCommit) {
                $restaurantLocked = $this->getRestaurantMealsForUpdate((int)$context['id_restaurant']);
                if (!$restaurantLocked) {
                    $db->rollBack();
                    return ['ok' => false, 'status' => 'error_restaurant_not_found'];
                }
                $items = json_decode((string)($context['items_json'] ?? '[]'), true);
                $items = is_array($items) ? $items : [];
                $reserveResult = $this->reserveStockFromMeals($restaurantLocked['meals'], $items);
                if (!$reserveResult['ok']) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => $reserveResult['status'] ?? 'error_items_invalid',
                        'errors' => ['items_json'],
                        'id_collecte' => $idCollecte,
                        'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                        'id_owner' => (int)($context['restaurant_owner_id'] ?? 0),
                    ];
                }
                $this->saveRestaurantMeals((int)$context['id_restaurant'], $reserveResult['meals']);
            } elseif ($wasCommitted && !$willCommit) {
                $restaurantLocked = $this->getRestaurantMealsForUpdate((int)$context['id_restaurant']);
                if (!$restaurantLocked) {
                    $db->rollBack();
                    return ['ok' => false, 'status' => 'error_restaurant_not_found'];
                }
                $items = json_decode((string)($context['items_json'] ?? '[]'), true);
                $items = is_array($items) ? $items : [];
                $releaseResult = $this->releaseStockToMeals($restaurantLocked['meals'], $items);
                if (!$releaseResult['ok']) {
                    $db->rollBack();
                    return ['ok' => false, 'status' => 'error_db'];
                }
                $this->saveRestaurantMeals((int)$context['id_restaurant'], $releaseResult['meals']);
            }

            $query = $db->prepare("UPDATE planning_collecte SET statut = :statut WHERE id_collecte = :id_collecte");
            $query->execute([
                'statut' => $status,
                'id_collecte' => $idCollecte,
            ]);
            $db->commit();
            return [
                'ok' => true,
                'status' => 'success_collecte_updated',
                'id_collecte' => $idCollecte,
                'id_user' => (int)($context['id_user'] ?? 0),
                'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                'id_owner' => (int)($context['restaurant_owner_id'] ?? 0),
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    public function updateStatus($idCollecte, $status)
    {
        return $this->updateStatusInternal($idCollecte, $status, null);
    }

    public function partnerUpdateStatus($idCollecte, $status, $idOwner)
    {
        return $this->updateStatusInternal($idCollecte, $status, (int)$idOwner);
    }

    public function partnerAssignDeliveryDriver($idCollecte, $idOwner, $firstName, $lastName, $contact)
    {
        $this->ensureTable();
        $idCollecte = (int)$idCollecte;
        $idOwner = (int)$idOwner;
        $firstName = $this->norm($firstName);
        $lastName = $this->norm($lastName);
        $contact = $this->normalizePhoneInput($contact);

        if ($idCollecte <= 0) {
            return ['ok' => false, 'status' => 'error_not_found', 'id_owner' => $idOwner];
        }
        if ($idOwner <= 0) {
            return ['ok' => false, 'status' => 'error_forbidden_collecte', 'id_owner' => 0];
        }
        if ($firstName === '' || $lastName === '' || $contact === '') {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => ['driver']];
        }
        if (!preg_match("/^[\\p{L}\\s'\\-]{2,100}$/u", $firstName)) {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => ['driver']];
        }
        if (!preg_match("/^[\\p{L}\\s'\\-]{2,100}$/u", $lastName)) {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => ['driver']];
        }
        if (!preg_match('/^\+?[0-9 ]{8,20}$/', $contact)) {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => ['driver']];
        }

        $db = $this->db();
        try {
            $db->beginTransaction();
            $context = $this->getCollecteContextForUpdate($idCollecte);
            if (!$context) {
                $db->rollBack();
                return ['ok' => false, 'status' => 'error_not_found', 'id_owner' => $idOwner];
            }
            if ((int)($context['restaurant_owner_id'] ?? 0) !== $idOwner) {
                $db->rollBack();
                return [
                    'ok' => false,
                    'status' => 'error_forbidden_collecte',
                    'id_owner' => $idOwner,
                    'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                ];
            }

            $mode = strtolower($this->norm($context['mode_collecte'] ?? ''));
            if ($mode !== 'delivery') {
                $db->rollBack();
                return [
                    'ok' => false,
                    'status' => 'error_invalid_status',
                    'id_owner' => $idOwner,
                    'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                    'id_collecte' => $idCollecte,
                ];
            }

            $currentStatus = strtolower($this->norm($context['statut'] ?? ''));
            if (!in_array($currentStatus, ['en_attente', 'en_cours_livraison'], true)) {
                $db->rollBack();
                return [
                    'ok' => false,
                    'status' => 'error_invalid_status',
                    'id_owner' => $idOwner,
                    'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                    'id_collecte' => $idCollecte,
                ];
            }

            $token = $this->createTrackingToken();
            $query = $db->prepare(
                "UPDATE planning_collecte
                 SET delivery_driver_first_name = :first_name,
                     delivery_driver_last_name = :last_name,
                     delivery_driver_contact = :contact,
                     delivery_tracking_token = :token,
                     delivery_tracker_client_id = NULL,
                     delivery_driver_lat = NULL,
                     delivery_driver_lng = NULL,
                     delivery_driver_updated_at = NULL,
                     statut = :status
                 WHERE id_collecte = :id_collecte"
            );
            $query->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'contact' => $contact,
                'token' => $token,
                'status' => 'en_cours_livraison',
                'id_collecte' => $idCollecte,
            ]);
            $db->commit();

            return [
                'ok' => true,
                'status' => 'success_driver_assigned',
                'id_collecte' => $idCollecte,
                'id_owner' => $idOwner,
                'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                'tracking_token' => $token,
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'status' => 'error_db', 'id_owner' => $idOwner];
        }
    }

    public function driverPingLocation($trackingToken, $lat, $lng, $accuracy = null, $trackerClientId = '')
    {
        $this->ensureTable();
        $trackingToken = trim((string)$trackingToken);
        if ($trackingToken === '') {
            return ['ok' => false, 'status' => 'error_invalid_request'];
        }
        $trackerClientId = $this->normalizeTrackerClientId($trackerClientId);
        if ($trackerClientId === '') {
            $trackerClientId = 'legacy';
        }
        $lat = is_numeric($lat) ? (float)$lat : null;
        $lng = is_numeric($lng) ? (float)$lng : null;
        if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => ['coords']];
        }
        $accuracy = is_numeric($accuracy) ? max(0, (float)$accuracy) : null;

        $effective = ['lat' => $lat, 'lng' => $lng, 'source' => 'raw', 'snap_distance_m' => null];
        if ($accuracy === null || $accuracy <= $this->trackingSnapMaxAccuracyMeters()) {
            $effective = $this->snapCoordsToRoad($lat, $lng);
        }

        $db = $this->db();
        try {
            $db->beginTransaction();
            $previous = $this->getTrackingPointForUpdate($trackingToken);
            if (!$previous) {
                $db->rollBack();
                $anyState = $this->getTrackingPointByTokenAnyState($trackingToken);
                if ($anyState) {
                    $modeAny = strtolower($this->norm($anyState['mode_collecte'] ?? ''));
                    $statusAny = $this->normalizeStatus((string)($anyState['statut'] ?? ''));
                    if ($modeAny !== 'delivery') {
                        return ['ok' => false, 'status' => 'error_not_delivery_mode'];
                    }
                    if (!in_array($statusAny, ['en_attente', 'en_cours_livraison'], true)) {
                        return ['ok' => false, 'status' => 'error_not_trackable_status'];
                    }
                }
                return ['ok' => false, 'status' => 'error_not_found'];
            }

            $lockedClientId = $this->normalizeTrackerClientId((string)($previous['delivery_tracker_client_id'] ?? ''));
            if ($lockedClientId !== '' && $lockedClientId !== $trackerClientId) {
                $lastTs = strtotime((string)($previous['delivery_driver_updated_at'] ?? ''));
                $secondsSince = $lastTs !== false ? max(0, time() - (int)$lastTs) : 999999;
                if ($secondsSince < $this->trackingTrackerLockTtlSec()) {
                    $db->rollBack();
                    return ['ok' => false, 'status' => 'error_tracker_locked'];
                }
            }

            $decision = $this->shouldRejectTrackingPoint($previous, $effective['lat'], $effective['lng'], $accuracy);
            // If snapped point causes false micro-lock, fallback to raw GPS point.
            if (!empty($decision['reject'])
                && (string)($decision['status'] ?? '') === 'ignored_micro_move'
                && (string)($effective['source'] ?? '') === 'snapped_osrm') {
                $rawDecision = $this->shouldRejectTrackingPoint($previous, $lat, $lng, $accuracy);
                if (empty($rawDecision['reject'])) {
                    $effective = ['lat' => $lat, 'lng' => $lng, 'source' => 'raw_fallback', 'snap_distance_m' => null];
                    $decision = $rawDecision;
                }
            }
            if (!empty($decision['reject'])) {
                $db->rollBack();
                return [
                    'ok' => true,
                    'status' => (string)$decision['status'],
                    'ignored' => true,
                    'source' => 'raw',
                    'snap_distance_m' => null,
                ];
            }

            $query = $db->prepare(
                "UPDATE planning_collecte
                 SET delivery_driver_lat = :lat,
                     delivery_driver_lng = :lng,
                     delivery_tracker_client_id = :tracker_client_id,
                     delivery_driver_updated_at = NOW()
                 WHERE delivery_tracking_token = :token
                   AND mode_collecte = 'delivery'
                   AND statut IN ('en_cours_livraison', 'en_attente')
                 LIMIT 1"
            );
            $query->execute([
                'lat' => $effective['lat'],
                'lng' => $effective['lng'],
                'tracker_client_id' => $trackerClientId,
                'token' => $trackingToken,
            ]);

            // In MySQL, rowCount can be 0 when values stay identical.
            // Since we already locked/validated the target row above,
            // treat this as a valid ping instead of "not found".
            $db->commit();
            $this->publishDriverPointRealtime($previous, $effective['lat'], $effective['lng']);
            return [
                'ok' => true,
                'status' => 'success_driver_ping',
                'source' => $effective['source'],
                'snap_distance_m' => $effective['snap_distance_m'],
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    public function getLiveDeliveryDriverPoints($scope = 'admin', $idOwner = 0, $idUser = 0)
    {
        $this->ensureTable();
        $scope = strtolower($this->norm($scope));
        $idOwner = (int)$idOwner;
        $idUser = (int)$idUser;

        $where = "WHERE pc.mode_collecte = 'delivery'
                  AND pc.statut IN ('en_attente', 'en_cours_livraison')
                  AND pc.delivery_tracking_token IS NOT NULL
                  AND pc.delivery_tracking_token <> ''
                  AND pc.delivery_driver_lat IS NOT NULL
                  AND pc.delivery_driver_lng IS NOT NULL";
        $params = [];

        if ($scope === 'partner') {
            if ($idOwner <= 0) {
                return [];
            }
            $where .= " AND r.id_owner = :id_owner";
            $params['id_owner'] = $idOwner;
        } elseif ($scope === 'student') {
            if ($idUser <= 0) {
                return [];
            }
            $where .= " AND pc.id_user = :id_user";
            $params['id_user'] = $idUser;
        }

        $sql = "SELECT
                    pc.id_collecte,
                    pc.delivery_driver_first_name,
                    pc.delivery_driver_last_name,
                    pc.delivery_driver_contact,
                    pc.delivery_driver_lat,
                    pc.delivery_driver_lng,
                    pc.delivery_driver_updated_at,
                    TIMESTAMPDIFF(SECOND, pc.delivery_driver_updated_at, NOW()) AS seconds_since_update,
                    pc.statut,
                    r.nom AS restaurant_nom
                FROM planning_collecte pc
                LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
                $where
                ORDER BY pc.id_collecte DESC";

        try {
            $query = $this->db()->prepare($sql);
            $query->execute($params);
            $rows = $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }

        $points = [];
        foreach ($rows as $row) {
            $first = $this->norm($row['delivery_driver_first_name'] ?? '');
            $last = $this->norm($row['delivery_driver_last_name'] ?? '');
            $name = trim($first . ' ' . $last);
            if ($name === '') {
                $name = 'Livreur';
            }
            $secondsSinceUpdate = isset($row['seconds_since_update']) ? (int)$row['seconds_since_update'] : 999999;
            $isLive = $secondsSinceUpdate >= 0 && $secondsSinceUpdate <= 20;
            $points[] = [
                'collecte_id' => (int)($row['id_collecte'] ?? 0),
                'kind' => 'driver',
                'label' => 'Livreur - collecte #' . (int)($row['id_collecte'] ?? 0),
                'location' => (string)($row['restaurant_nom'] ?? ''),
                'status' => $this->normalizeStatus((string)($row['statut'] ?? '')),
                'driver_name' => $name,
                'driver_contact' => (string)($row['delivery_driver_contact'] ?? ''),
                'updated_at' => (string)($row['delivery_driver_updated_at'] ?? ''),
                'seconds_since_update' => $secondsSinceUpdate,
                'is_live' => $isLive,
                'lat' => (float)($row['delivery_driver_lat'] ?? 0),
                'lng' => (float)($row['delivery_driver_lng'] ?? 0),
            ];
        }

        return $points;
    }

    public function getDriverSessionByToken($trackingToken)
    {
        $this->ensureTable();
        $trackingToken = trim((string)$trackingToken);
        if ($trackingToken === '') {
            return null;
        }

        try {
            $query = $this->db()->prepare(
                "SELECT
                    pc.id_collecte,
                    pc.id_restaurant,
                    pc.id_user,
                    pc.mode_collecte,
                    pc.statut,
                    pc.delivery_driver_first_name,
                    pc.delivery_driver_last_name,
                    pc.delivery_driver_contact,
                    pc.delivery_driver_updated_at,
                    pc.delivery_driver_lat,
                    pc.delivery_driver_lng,
                    r.nom AS restaurant_nom,
                    r.localisation AS restaurant_localisation
                 FROM planning_collecte pc
                 LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
                 WHERE pc.delivery_tracking_token = :token
                   AND pc.mode_collecte = 'delivery'
                 LIMIT 1"
            );
            $query->execute(['token' => $trackingToken]);
            $row = $query->fetch();
            if (!$row) {
                return null;
            }
            return $row;
        } catch (Exception $e) {
            return null;
        }
    }

    private function deleteCollecteInternal($idCollecte, $options = [])
    {
        $this->ensureTable();
        $db = $this->db();
        $idCollecte = (int)$idCollecte;
        $partnerOwnerId = isset($options['partner_owner_id']) ? (int)$options['partner_owner_id'] : null;
        $studentUserId = isset($options['student_user_id']) ? (int)$options['student_user_id'] : null;
        $restockOnDelete = !empty($options['restock_on_delete']);

        if ($idCollecte <= 0) {
            return ['ok' => false, 'status' => 'error_not_found'];
        }

        try {
            $db->beginTransaction();
            $context = $this->getCollecteContextForUpdate($idCollecte);
            if (!$context) {
                $db->rollBack();
                return ['ok' => false, 'status' => 'error_not_found'];
            }

            if ($partnerOwnerId !== null) {
                if ($partnerOwnerId <= 0 || (int)($context['restaurant_owner_id'] ?? 0) !== $partnerOwnerId) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => 'error_forbidden_collecte',
                        'id_owner' => $partnerOwnerId,
                        'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                        'id_collecte' => $idCollecte,
                    ];
                }
            }

            if ($studentUserId !== null) {
                if ($studentUserId <= 0 || (int)($context['id_user'] ?? 0) !== $studentUserId) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => 'error_forbidden_collecte',
                        'id_user' => $studentUserId,
                        'id_collecte' => $idCollecte,
                    ];
                }
            }

            $currentStatus = $this->normalizeStatus((string)($context['statut'] ?? 'en_attente'));
            $mode = strtolower($this->norm($context['mode_collecte'] ?? ''));
            $isPendingStatus = in_array($currentStatus, ['en_attente', 'en_cours_livraison'], true);
            $shouldConsumeStock = $isPendingStatus && !$restockOnDelete;

            if ($shouldConsumeStock) {
                $restaurantLocked = $this->getRestaurantMealsForUpdate((int)$context['id_restaurant']);
                if (!$restaurantLocked) {
                    $db->rollBack();
                    return ['ok' => false, 'status' => 'error_restaurant_not_found'];
                }
                $items = json_decode((string)($context['items_json'] ?? '[]'), true);
                $items = is_array($items) ? $items : [];
                $reserveResult = $this->reserveStockFromMeals($restaurantLocked['meals'], $items);
                if (!$reserveResult['ok']) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => $reserveResult['status'] ?? 'error_items_invalid',
                        'errors' => ['items_json'],
                        'id_collecte' => $idCollecte,
                        'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                        'id_owner' => (int)($context['restaurant_owner_id'] ?? 0),
                        'id_user' => (int)($context['id_user'] ?? 0),
                    ];
                }
                $this->saveRestaurantMeals((int)$context['id_restaurant'], $reserveResult['meals']);
            }

            if ($studentUserId !== null) {
                if (!in_array($currentStatus, ['collecte', 'livree', 'annulee'], true)) {
                    $db->rollBack();
                    return [
                        'ok' => false,
                        'status' => 'error_cannot_delete_active_collecte',
                        'id_collecte' => $idCollecte,
                        'id_user' => $studentUserId,
                    ];
                }
            }

            $query = $db->prepare("DELETE FROM planning_collecte WHERE id_collecte = :id_collecte");
            $query->execute(['id_collecte' => $idCollecte]);
            if ($query->rowCount() <= 0) {
                $db->rollBack();
                return ['ok' => false, 'status' => 'error_not_found'];
            }

            $db->commit();
            return [
                'ok' => true,
                'status' => 'success_collecte_deleted',
                'id_collecte' => $idCollecte,
                'id_restaurant' => (int)($context['id_restaurant'] ?? 0),
                'id_owner' => (int)($context['restaurant_owner_id'] ?? 0),
                'id_user' => (int)($context['id_user'] ?? 0),
                'stock_restocked' => $isPendingStatus && $restockOnDelete,
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    public function studentCancelCollecte($idCollecte, $idUser)
    {
        $this->ensureTable();
        $idCollecte = (int)$idCollecte;
        $idUser = (int)$idUser;
        if ($idCollecte <= 0) {
            return ['ok' => false, 'status' => 'error_not_found', 'id_user' => $idUser];
        }
        if ($idUser <= 0) {
            return ['ok' => false, 'status' => 'error_user_not_found', 'id_user' => 0];
        }

        $context = $this->getCollecteContext($idCollecte);
        if (!$context) {
            return ['ok' => false, 'status' => 'error_not_found', 'id_user' => $idUser];
        }
        if ((int)($context['id_user'] ?? 0) !== $idUser) {
            return ['ok' => false, 'status' => 'error_forbidden_collecte', 'id_user' => $idUser];
        }

        $currentStatus = $this->normalizeStatus((string)($context['statut'] ?? 'en_attente'));
        if ($currentStatus === 'annulee') {
            return [
                'ok' => true,
                'status' => 'success_collecte_cancelled',
                'id_collecte' => $idCollecte,
                'id_user' => $idUser,
            ];
        }
        if (in_array($currentStatus, ['collecte', 'livree'], true)) {
            return [
                'ok' => false,
                'status' => 'error_cannot_cancel_collecte',
                'id_collecte' => $idCollecte,
                'id_user' => $idUser,
            ];
        }

        $result = $this->updateStatusInternal($idCollecte, 'annulee', null, $idUser);
        if (!empty($result['ok'])) {
            $result['status'] = 'success_collecte_cancelled';
        }
        if (!isset($result['id_user'])) {
            $result['id_user'] = $idUser;
        }

        return $result;
    }

    public function deleteCollecte($idCollecte)
    {
        return $this->deleteCollecteInternal($idCollecte, [
            'restock_on_delete' => true,
        ]);
    }

    public function deleteCollecteWithOptions($idCollecte, $restockOnDelete)
    {
        return $this->deleteCollecteInternal($idCollecte, [
            'restock_on_delete' => (bool)$restockOnDelete,
        ]);
    }

    public function partnerDeleteCollecte($idCollecte, $idOwner, $restockOnDelete)
    {
        return $this->deleteCollecteInternal($idCollecte, [
            'partner_owner_id' => (int)$idOwner,
            'restock_on_delete' => (bool)$restockOnDelete,
        ]);
    }

    public function studentClearCollectes($idUser, $scope)
    {
        $this->ensureTable();
        $idUser = (int)$idUser;
        $scope = strtolower($this->norm($scope));
        if ($idUser <= 0) {
            return ['ok' => false, 'status' => 'error_user_not_found', 'id_user' => 0];
        }

        $statusMap = [
            'completed' => ['collecte', 'livree'],
            'cancelled' => ['annulee'],
            'completed_cancelled' => ['collecte', 'livree', 'annulee'],
        ];
        if (!isset($statusMap[$scope])) {
            return ['ok' => false, 'status' => 'error_invalid_request', 'id_user' => $idUser];
        }

        $statuses = $statusMap[$scope];
        $placeholders = [];
        $params = ['id_user' => $idUser];
        foreach ($statuses as $i => $status) {
            $key = 's' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $status;
        }

        try {
            $query = $this->db()->prepare(
                "DELETE FROM planning_collecte
                 WHERE id_user = :id_user
                   AND statut IN (" . implode(', ', $placeholders) . ")"
            );
            $query->execute($params);
            return [
                'ok' => true,
                'status' => 'success_collectes_cleared',
                'id_user' => $idUser,
                'deleted_count' => (int)$query->rowCount(),
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'status' => 'error_db', 'id_user' => $idUser];
        }
    }

    private function normalizeCollecteSort($sortBy)
    {
        $sortBy = strtolower($this->norm($sortBy));
        $allowed = [
            'newest',
            'oldest',
            'montant_desc',
            'montant_asc',
            'status_asc',
            'status_desc',
            'heure_desc',
            'heure_asc',
            'restaurant_asc',
            'restaurant_desc',
        ];
        return in_array($sortBy, $allowed, true) ? $sortBy : 'newest';
    }

    private function collecteSortSql($sortBy)
    {
        $sortBy = $this->normalizeCollecteSort($sortBy);
        switch ($sortBy) {
            case 'oldest':
                return 'pc.id_collecte ASC';
            case 'montant_desc':
                return 'pc.montant_total DESC, pc.id_collecte DESC';
            case 'montant_asc':
                return 'pc.montant_total ASC, pc.id_collecte DESC';
            case 'status_asc':
                return 'pc.statut ASC, pc.id_collecte DESC';
            case 'status_desc':
                return 'pc.statut DESC, pc.id_collecte DESC';
            case 'heure_desc':
                return 'pc.heure_souhaitee DESC, pc.id_collecte DESC';
            case 'heure_asc':
                return 'pc.heure_souhaitee ASC, pc.id_collecte DESC';
            case 'restaurant_asc':
                return 'COALESCE(r.nom, \'\') ASC, pc.id_collecte DESC';
            case 'restaurant_desc':
                return 'COALESCE(r.nom, \'\') DESC, pc.id_collecte DESC';
            case 'newest':
            default:
                return 'pc.id_collecte DESC';
        }
    }

    public function getAllWithJoin($search = '', $sortBy = 'newest')
    {
        $this->ensureTable();
        $search = $this->norm($search);
        $orderBy = $this->collecteSortSql($sortBy);
        if ($search === '') {
            $rows = $this->db()->query(
                "SELECT
                    pc.*,
                    r.nom AS restaurant_nom,
                    r.localisation AS restaurant_localisation,
                    r.id_owner AS restaurant_owner_id,
                    p.regime_alimentaire,
                    p.allergies,
                    p.localisation AS preference_localisation,
                    u.nom AS user_nom,
                    u.email AS user_email
                 FROM planning_collecte pc
                 LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
                 LEFT JOIN preference p ON p.id_pref = pc.id_pref
                 LEFT JOIN utilisateur u ON u.id = pc.id_user
                 ORDER BY $orderBy"
            )->fetchAll();
        } else {
            $query = $this->db()->prepare(
                "SELECT
                    pc.*,
                    r.nom AS restaurant_nom,
                    r.localisation AS restaurant_localisation,
                    r.id_owner AS restaurant_owner_id,
                    p.regime_alimentaire,
                    p.allergies,
                    p.localisation AS preference_localisation,
                    u.nom AS user_nom,
                    u.email AS user_email
                 FROM planning_collecte pc
                 LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
                 LEFT JOIN preference p ON p.id_pref = pc.id_pref
                 LEFT JOIN utilisateur u ON u.id = pc.id_user
                 WHERE LOWER(COALESCE(r.nom, '')) LIKE LOWER(:q)
                    OR CAST(pc.id_collecte AS CHAR) LIKE :q
                    OR CAST(pc.id_user AS CHAR) LIKE :q
                    OR CAST(pc.id_restaurant AS CHAR) LIKE :q
                 ORDER BY $orderBy"
            );
            $query->execute(['q' => '%' . $search . '%']);
            $rows = $query->fetchAll();
        }

        foreach ($rows as &$row) {
            $decoded = json_decode((string)($row['items_json'] ?? '[]'), true);
            $row['items'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    public function getPartnerCollectesWithJoin($idOwner, $idRestaurant = 0, $search = '', $sortBy = 'newest')
    {
        $this->ensureTable();
        $idOwner = (int)$idOwner;
        $idRestaurant = (int)$idRestaurant;
        if ($idOwner <= 0) {
            return [];
        }

        $search = $this->norm($search);
        $orderBy = $this->collecteSortSql($sortBy);
        $where = "WHERE r.id_owner = :id_owner";
        $params = ['id_owner' => $idOwner];

        if ($idRestaurant > 0) {
            $where .= " AND pc.id_restaurant = :id_restaurant";
            $params['id_restaurant'] = $idRestaurant;
        }

        if ($search !== '') {
            $where .= " AND (
                LOWER(COALESCE(r.nom, '')) LIKE LOWER(:q)
                OR LOWER(COALESCE(u.nom, '')) LIKE LOWER(:q)
                OR CAST(pc.id_collecte AS CHAR) LIKE :q
                OR CAST(pc.id_user AS CHAR) LIKE :q
            )";
            $params['q'] = '%' . $search . '%';
        }

        $query = $this->db()->prepare(
            "SELECT
                pc.*,
                r.nom AS restaurant_nom,
                r.localisation AS restaurant_localisation,
                r.id_owner AS restaurant_owner_id,
                p.regime_alimentaire,
                p.allergies,
                p.localisation AS preference_localisation,
                u.nom AS user_nom,
                u.email AS user_email
             FROM planning_collecte pc
             LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
             LEFT JOIN preference p ON p.id_pref = pc.id_pref
             LEFT JOIN utilisateur u ON u.id = pc.id_user
             $where
             ORDER BY $orderBy"
        );
        $query->execute($params);
        $rows = $query->fetchAll();

        foreach ($rows as &$row) {
            $decoded = json_decode((string)($row['items_json'] ?? '[]'), true);
            $row['items'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    public function getStudentCollectesWithJoin($idUser, $statusFilter = '', $search = '', $sortBy = 'newest')
    {
        $this->ensureTable();
        $idUser = (int)$idUser;
        if ($idUser <= 0) {
            return [];
        }

        $rawStatusFilter = strtolower($this->norm($statusFilter));
        $allowedStatusFilters = ['en_attente', 'collecte', 'en_cours_livraison', 'livree', 'annulee'];
        $useStatusFilter = in_array($rawStatusFilter, $allowedStatusFilters, true);
        $normalizedStatusFilter = $useStatusFilter ? $this->normalizeStatus($rawStatusFilter) : '';

        $search = $this->norm($search);
        $orderBy = $this->collecteSortSql($sortBy);
        $where = "WHERE pc.id_user = :id_user";
        $params = ['id_user' => $idUser];

        if ($useStatusFilter) {
            $where .= " AND pc.statut = :status_filter";
            $params['status_filter'] = $normalizedStatusFilter;
        }

        if ($search !== '') {
            $where .= " AND (
                LOWER(COALESCE(r.nom, '')) LIKE LOWER(:q)
                OR CAST(pc.id_collecte AS CHAR) LIKE :q
                OR CAST(pc.id_restaurant AS CHAR) LIKE :q
                OR LOWER(COALESCE(pc.statut, '')) LIKE LOWER(:q)
            )";
            $params['q'] = '%' . $search . '%';
        }

        $query = $this->db()->prepare(
            "SELECT
                pc.*,
                r.nom AS restaurant_nom,
                r.localisation AS restaurant_localisation
             FROM planning_collecte pc
             LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
             $where
             ORDER BY $orderBy"
        );
        $query->execute($params);
        $rows = $query->fetchAll();

        foreach ($rows as &$row) {
            $decoded = json_decode((string)($row['items_json'] ?? '[]'), true);
            $row['items'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    private function getCollecteDetailRow($idCollecte)
    {
        $this->ensureTable();
        $idCollecte = (int)$idCollecte;
        if ($idCollecte <= 0) {
            return null;
        }

        $query = $this->db()->prepare(
            "SELECT
                pc.*,
                r.nom AS restaurant_nom,
                r.localisation AS restaurant_localisation,
                r.horaires AS restaurant_horaires,
                r.image_path AS restaurant_image_path,
                r.id_owner AS restaurant_owner_id,
                p.regime_alimentaire,
                p.allergies,
                p.localisation AS preference_localisation,
                u.nom AS user_nom,
                u.email AS user_email
             FROM planning_collecte pc
             LEFT JOIN restaurant r ON r.id_restaurant = pc.id_restaurant
             LEFT JOIN preference p ON p.id_pref = pc.id_pref
             LEFT JOIN utilisateur u ON u.id = pc.id_user
             WHERE pc.id_collecte = :id_collecte
             LIMIT 1"
        );
        $query->execute(['id_collecte' => $idCollecte]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        $decoded = json_decode((string)($row['items_json'] ?? '[]'), true);
        $row['items'] = is_array($decoded) ? $decoded : [];
        return $row;
    }

    public function getCollecteDetail($idCollecte)
    {
        return $this->getCollecteDetailRow((int)$idCollecte);
    }

    public function getPartnerCollecteDetail($idCollecte, $idOwner)
    {
        $idOwner = (int)$idOwner;
        if ($idOwner <= 0) {
            return null;
        }

        $row = $this->getCollecteDetailRow((int)$idCollecte);
        if (!$row) {
            return null;
        }
        if ((int)($row['restaurant_owner_id'] ?? 0) !== $idOwner) {
            return null;
        }

        return $row;
    }

    public function getStudentCollecteDetail($idCollecte, $idUser)
    {
        $idUser = (int)$idUser;
        if ($idUser <= 0) {
            return null;
        }

        $row = $this->getCollecteDetailRow((int)$idCollecte);
        if (!$row) {
            return null;
        }
        if ((int)($row['id_user'] ?? 0) !== $idUser) {
            return null;
        }

        return $row;
    }

}
