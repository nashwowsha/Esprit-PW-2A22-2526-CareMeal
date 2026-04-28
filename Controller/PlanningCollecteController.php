<?php
require_once __DIR__ . '/../config/database.php';

class PlanningCollecteController
{
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
                INDEX idx_collecte_heure (heure_souhaitee)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->ensureSnapshotColumns();

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
        if ($idUser <= 0 || !$this->userExists($idUser)) {
            return ['ok' => false, 'status' => 'error_user_not_found', 'errors' => ['id_user']];
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
