<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/MatchingSnapshotStore.php';

class RestaurantController
{
    private function invalidateMatchingSnapshots($reason = '')
    {
        try {
            MatchingSnapshotStore::bumpSourceVersion((string)$reason);
        } catch (Exception $e) {
            // Keep user flow stable if invalidation fails.
        }
    }

    private function db()
    {
        return config::getConnexion();
    }

    private function norm($v)
    {
        $v = trim((string)$v);
        $v = preg_replace('/\s+/', ' ', $v);
        return $v ?? '';
    }

    private function normList($v)
    {
        $parts = preg_split('/[,;]+/', (string)$v);
        if (!$parts) {
            return '';
        }
        $out = [];
        foreach ($parts as $p) {
            $p = strtolower($this->norm($p));
            if ($p !== '') {
                $out[] = $p;
            }
        }
        return implode(', ', array_values(array_unique($out)));
    }

    private function normalizeDisclosureMode($value)
    {
        $value = strtolower($this->norm($value));
        if ($value === 'declared') {
            return 'declared';
        }
        return 'none';
    }

    private function isLegacyNoneAllergenText($value)
    {
        $text = strtolower($this->norm((string)$value));
        if ($text === '') {
            return true;
        }
        $token = preg_replace('/\s+/', '', $text);
        return in_array($token, ['aucun', 'none', 'na', 'n/a', '-', 'pasdallergene', 'sansallergene'], true);
    }

    private function normalizeMealCompatibility($meal)
    {
        if (!is_array($meal)) {
            return [];
        }

        $allergens = $this->normList($meal['allergens'] ?? '');
        $modeRaw = $meal['allergen_disclosure_mode'] ?? '';
        $mode = $this->normalizeDisclosureMode($modeRaw);
        if (($modeRaw === '' || $modeRaw === null) && !$this->isLegacyNoneAllergenText($allergens)) {
            $mode = 'declared';
        }
        if ($this->isLegacyNoneAllergenText($allergens)) {
            $mode = 'none';
            $allergens = '';
        }

        $meal['allergen_disclosure_mode'] = $mode;
        $meal['allergens'] = ($mode === 'declared') ? $allergens : '';
        return $meal;
    }

    private function normalizeMealsCompatibilityArray($meals)
    {
        $out = [];
        foreach ((array)$meals as $meal) {
            $row = $this->normalizeMealCompatibility($meal);
            if (!empty($row)) {
                $out[] = $row;
            }
        }
        return $out;
    }

    private function normalizeCoordinateInput($value)
    {
        $value = $this->norm($value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        return (float)$value;
    }

    private function ensureTable()
    {
        $this->db()->exec(
            "CREATE TABLE IF NOT EXISTS restaurant (
                id_restaurant INT AUTO_INCREMENT PRIMARY KEY,
                id_owner INT NOT NULL,
                nom VARCHAR(120) NOT NULL,
                localisation VARCHAR(180) NOT NULL,
                localisation_lat DECIMAL(10,7) DEFAULT NULL,
                localisation_lng DECIMAL(10,7) DEFAULT NULL,
                image_path VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                telephone VARCHAR(30) NOT NULL,
                horaires VARCHAR(120) NOT NULL,
                meals_json LONGTEXT DEFAULT NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_owner (id_owner),
                INDEX idx_nom (nom)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $this->ensureGeoColumns();
    }

    private function columnExists($columnName)
    {
        $query = $this->db()->prepare(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'restaurant'
               AND COLUMN_NAME = :column_name
             LIMIT 1"
        );
        $query->execute(['column_name' => $columnName]);
        return (bool)$query->fetch();
    }

    private function ensureGeoColumns()
    {
        $columnsToAdd = [
            'localisation_lat' => "ALTER TABLE restaurant ADD COLUMN localisation_lat DECIMAL(10,7) DEFAULT NULL AFTER localisation",
            'localisation_lng' => "ALTER TABLE restaurant ADD COLUMN localisation_lng DECIMAL(10,7) DEFAULT NULL AFTER localisation_lat",
        ];

        foreach ($columnsToAdd as $column => $alterSql) {
            if (!$this->columnExists($column)) {
                try {
                    $this->db()->exec($alterSql);
                } catch (Exception $e) {
                    // keep runtime stable if migration cannot run now
                }
            }
        }
    }

    private function mealsDecode($json)
    {
        $json = trim((string)$json);
        if ($json === '') {
            return [];
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return [];
        }
        return $this->normalizeMealsCompatibilityArray($arr);
    }

    private function mealsEncode($arr)
    {
        $safe = $this->normalizeMealsCompatibilityArray((array)$arr);
        return json_encode(array_values($safe), JSON_UNESCAPED_UNICODE);
    }

    private function uploadDir()
    {
        return __DIR__ . '/../assets/restaurant-signs';
    }

    private function uploadImage($file)
    {
        if (!is_array($file) || !isset($file['tmp_name']) || (int)($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'status' => 'error_restaurant_image_invalid'];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'status' => 'error_restaurant_image_invalid'];
        }
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return ['ok' => false, 'status' => 'error_restaurant_image_invalid'];
        }
        if ((int)($file['size'] ?? 0) > 3 * 1024 * 1024) {
            return ['ok' => false, 'status' => 'error_restaurant_image_invalid'];
        }
        $dir = $this->uploadDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!is_dir($dir)) {
            return ['ok' => false, 'status' => 'error_db'];
        }
        $name = 'restaurant_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['ok' => false, 'status' => 'error_db'];
        }
        return ['ok' => true, 'path' => 'assets/restaurant-signs/' . $name];
    }

    private function deleteImage($webPath)
    {
        $webPath = trim((string)$webPath);
        if (strpos($webPath, 'assets/restaurant-signs/') !== 0) {
            return;
        }
        $file = basename($webPath);
        $abs = $this->uploadDir() . '/' . $file;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    private function findById($id)
    {
        $this->ensureTable();
        $q = $this->db()->prepare(
            "SELECT r.*, u.nom AS owner_nom, u.email AS owner_email
             FROM restaurant r
             LEFT JOIN utilisateur u ON u.id = r.id_owner
             WHERE r.id_restaurant = :id
             LIMIT 1"
        );
        $q->execute(['id' => (int)$id]);
        $row = $q->fetch();
        if (!$row) {
            return null;
        }
        $row['meals'] = $this->mealsDecode($row['meals_json'] ?? '[]');
        return $row;
    }

    private function existsSameName($idOwner, $nom, $exclude = 0)
    {
        $sql = "SELECT id_restaurant FROM restaurant WHERE id_owner = :id_owner AND LOWER(TRIM(nom)) = LOWER(TRIM(:nom))";
        if ($exclude > 0) {
            $sql .= " AND id_restaurant <> :exclude";
        }
        $sql .= " LIMIT 1";
        $q = $this->db()->prepare($sql);
        $params = ['id_owner' => (int)$idOwner, 'nom' => $nom];
        if ($exclude > 0) {
            $params['exclude'] = (int)$exclude;
        }
        $q->execute($params);
        return $q->fetch() ? true : false;
    }

    private function validateRestaurant($src, $files, $isUpdate = false, $existing = null, $isAdmin = false)
    {
        $idOwner = (int)($src['id_owner'] ?? 0);
        $nom = $this->norm($src['nom'] ?? '');
        $loc = $this->norm($src['localisation'] ?? '');
        $locLat = $this->normalizeCoordinateInput($src['localisation_lat'] ?? '');
        $locLng = $this->normalizeCoordinateInput($src['localisation_lng'] ?? '');
        $desc = $this->norm($src['description'] ?? '');
        $tel = $this->norm($src['telephone'] ?? '');
        $hours = $this->norm($src['horaires'] ?? '');
        $openTime = $this->norm($src['open_time'] ?? '');
        $closeTime = $this->norm($src['close_time'] ?? '');
        if ($openTime !== '' || $closeTime !== '') {
            $hours = $openTime . '-' . $closeTime;
        }
        $actif = isset($src['actif']) ? 1 : 0;
        $errors = [];

        if ($idOwner <= 0) { $errors[] = 'id_owner'; }
        if ($nom === '' || strlen($nom) < 2 || strlen($nom) > 100 || !preg_match("/^[\\p{L}\\p{N}\\s'\\-]+$/u", $nom)) { $errors[] = 'nom'; }
        if ($loc === '' || strlen($loc) < 2 || strlen($loc) > 150) { $errors[] = 'localisation'; }
        $validCoords = $locLat !== null
            && $locLng !== null
            && $locLat >= -90 && $locLat <= 90
            && $locLng >= -180 && $locLng <= 180;
        if (!$validCoords) { $errors[] = 'localisation'; }
        if ($desc === '' || strlen($desc) < 10 || strlen($desc) > 1000) { $errors[] = 'description'; }
        if ($tel === '' || !preg_match('/^\+?[0-9 ]{8,15}$/', $tel)) { $errors[] = 'telephone'; }
        if ($hours === '' || !preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $hours, $matches)) {
            $errors[] = 'horaires';
        } else {
            $start = $matches[1];
            $end = $matches[2];
            if ($start < '09:00' || $end > '22:00' || $start >= $end) {
                $errors[] = 'horaires';
            }
        }

        $img = $files['image_file'] ?? null;
        $hasNewImg = is_array($img) && isset($img['error']) && (int)$img['error'] !== UPLOAD_ERR_NO_FILE;
        $needImg = !$isUpdate || empty($existing['image_path']);
        if ($needImg && !$hasNewImg) { $errors[] = 'image_file'; }
        if (!empty($errors)) {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => array_values(array_unique($errors)), 'id_owner' => $idOwner];
        }
        return [
            'ok' => true,
            'payload' => [
                'id_owner' => $idOwner,
                'nom' => $nom,
                'localisation' => $loc,
                'localisation_lat' => $locLat,
                'localisation_lng' => $locLng,
                'description' => $desc,
                'telephone' => $tel,
                'horaires' => $hours,
                'actif' => $isAdmin ? $actif : 1,
            ],
            'has_new_image' => $hasNewImg,
            'image_info' => $img,
        ];
    }

    private function validateMeal($src)
    {
        $name = $this->norm($src['meal_name'] ?? '');
        $ing = $this->norm($src['ingredients'] ?? '');
        $qty = (int)($src['quantity'] ?? 0);
        $mode = $src['pricing_mode'] ?? '';
        $price = (float)($src['price'] ?? 0);
        $regInput = $src['regime_tags'] ?? '';
        if (is_array($regInput)) {
            $regParts = [];
            foreach ($regInput as $item) {
                $clean = strtolower($this->norm($item));
                if ($clean !== '') {
                    $regParts[] = $clean;
                }
            }
            $reg = implode(', ', array_values(array_unique($regParts)));
        } else {
            $reg = $this->normList($regInput);
        }
        $disclosureMode = $this->normalizeDisclosureMode($src['allergen_disclosure_mode'] ?? 'none');
        $alg = $this->normList($src['allergens'] ?? '');
        $errors = [];
        if ($name === '' || strlen($name) < 2 || strlen($name) > 100 || !preg_match("/^[\\p{L}\\p{N}\\s'\\-]+$/u", $name)) { $errors[] = 'meal_name'; }
        if ($ing === '' || strlen($ing) < 2 || strlen($ing) > 1000 || !preg_match("/^[\\p{L}\\s,'\\-]+$/u", $ing)) { $errors[] = 'ingredients'; }
        if ($qty <= 0 || $qty > 1000) { $errors[] = 'quantity'; }
        if ($reg === '') { $errors[] = 'regime_tags'; }
        if ($disclosureMode === 'declared') {
            if ($alg === '') {
                $errors[] = 'allergens';
            } elseif (!preg_match("/^[\\p{L}\\s,'\\-]+$/u", $alg)) {
                $errors[] = 'allergens';
            }
        } else {
            $alg = '';
        }
        if (!in_array($mode, ['free', 'paid'], true)) { $errors[] = 'pricing_mode'; }
        if ($mode === 'paid' && ($price <= 0 || $price > 500)) { $errors[] = 'price'; }
        if ($mode === 'free') { $price = 0; }
        if (!empty($errors)) {
            return ['ok' => false, 'status' => 'error_validation', 'errors' => array_values(array_unique($errors))];
        }
        return [
            'ok' => true,
            'meal' => [
                'meal_name' => $name,
                'ingredients' => $ing,
                'quantity' => $qty,
                'pricing_mode' => $mode,
                'price' => $price,
                'regime_tags' => $reg,
                'allergen_disclosure_mode' => $disclosureMode,
                'allergens' => $alg,
            ],
        ];
    }

    public function getPartnerRestaurants($idOwner)
    {
        $this->ensureTable();
        $q = $this->db()->prepare(
            "SELECT r.*, u.nom AS owner_nom, u.email AS owner_email
             FROM restaurant r LEFT JOIN utilisateur u ON u.id = r.id_owner
             WHERE r.id_owner = :id_owner
             ORDER BY r.id_restaurant DESC"
        );
        $q->execute(['id_owner' => (int)$idOwner]);
        $rows = $q->fetchAll();
        foreach ($rows as &$r) { $r['meals'] = $this->mealsDecode($r['meals_json'] ?? '[]'); }
        return $rows;
    }

    public function getAllRestaurantsForAdmin($search = '')
    {
        $this->ensureTable();
        $search = $this->norm($search);
        if ($search === '') {
            $rows = $this->db()->query(
                "SELECT r.*, u.nom AS owner_nom, u.email AS owner_email
                 FROM restaurant r LEFT JOIN utilisateur u ON u.id = r.id_owner
                 ORDER BY r.id_restaurant DESC"
            )->fetchAll();
        } else {
            $q = $this->db()->prepare(
                "SELECT r.*, u.nom AS owner_nom, u.email AS owner_email
                 FROM restaurant r LEFT JOIN utilisateur u ON u.id = r.id_owner
                 WHERE LOWER(r.nom) LIKE LOWER(:q) OR CAST(r.id_owner AS CHAR) LIKE :q
                 ORDER BY r.id_restaurant DESC"
            );
            $q->execute(['q' => '%' . $search . '%']);
            $rows = $q->fetchAll();
        }
        foreach ($rows as &$r) { $r['meals'] = $this->mealsDecode($r['meals_json'] ?? '[]'); }
        return $rows;
    }

    public function getRestaurantById($idRestaurant)
    {
        return $this->findById((int)$idRestaurant);
    }

    private function createOrUpdateRestaurant($src, $files, $isAdmin, $isUpdate)
    {
        $idRestaurant = (int)($src['id_restaurant'] ?? 0);
        $existing = $isUpdate ? $this->findById($idRestaurant) : null;
        if ($isUpdate && !$existing) { return ['ok' => false, 'status' => 'error_not_found']; }
        if ($isUpdate && !$isAdmin && (int)($src['id_owner'] ?? 0) !== (int)$existing['id_owner']) { return ['ok' => false, 'status' => 'error_forbidden']; }

        $valid = $this->validateRestaurant($src, $files, $isUpdate, $existing, $isAdmin);
        if (!$valid['ok']) { return $valid; }
        $p = $valid['payload'];
        if (!$isAdmin && $isUpdate) { $p['id_owner'] = (int)$existing['id_owner']; }

        if ($this->existsSameName($p['id_owner'], $p['nom'], $isUpdate ? $idRestaurant : 0)) {
            return ['ok' => false, 'status' => 'error_restaurant_exists', 'id_owner' => (int)$p['id_owner']];
        }

        $imgPath = $isUpdate ? (string)$existing['image_path'] : '';
        if ($valid['has_new_image']) {
            $upload = $this->uploadImage($valid['image_info']);
            if (!$upload['ok']) { return ['ok' => false, 'status' => $upload['status'], 'id_owner' => (int)$p['id_owner']]; }
            $imgPath = $upload['path'];
        }

        try {
            if ($isUpdate) {
                $q = $this->db()->prepare(
                    "UPDATE restaurant
                     SET id_owner=:id_owner, nom=:nom, localisation=:localisation, localisation_lat=:localisation_lat, localisation_lng=:localisation_lng, image_path=:image_path, description=:description, telephone=:telephone, horaires=:horaires, actif=:actif
                     WHERE id_restaurant=:id_restaurant"
                );
                $q->execute(['id_owner' => $p['id_owner'], 'nom' => $p['nom'], 'localisation' => $p['localisation'], 'localisation_lat' => $p['localisation_lat'], 'localisation_lng' => $p['localisation_lng'], 'image_path' => $imgPath, 'description' => $p['description'], 'telephone' => $p['telephone'], 'horaires' => $p['horaires'], 'actif' => $p['actif'], 'id_restaurant' => $idRestaurant]);
                if ($valid['has_new_image'] && $imgPath !== (string)$existing['image_path']) { $this->deleteImage((string)$existing['image_path']); }
                $this->invalidateMatchingSnapshots('restaurant_updated');
                return ['ok' => true, 'status' => 'success_restaurant_updated', 'id_owner' => (int)$p['id_owner'], 'selected_id' => $idRestaurant];
            }

            $q = $this->db()->prepare(
                "INSERT INTO restaurant (id_owner, nom, localisation, localisation_lat, localisation_lng, image_path, description, telephone, horaires, meals_json, actif)
                 VALUES (:id_owner,:nom,:localisation,:localisation_lat,:localisation_lng,:image_path,:description,:telephone,:horaires,:meals_json,:actif)"
            );
            $q->execute(['id_owner' => $p['id_owner'], 'nom' => $p['nom'], 'localisation' => $p['localisation'], 'localisation_lat' => $p['localisation_lat'], 'localisation_lng' => $p['localisation_lng'], 'image_path' => $imgPath, 'description' => $p['description'], 'telephone' => $p['telephone'], 'horaires' => $p['horaires'], 'meals_json' => '[]', 'actif' => $p['actif']]);
            $id = (int)$this->db()->lastInsertId();
            $this->invalidateMatchingSnapshots('restaurant_created');
            return ['ok' => true, 'status' => 'success_restaurant_created', 'id_owner' => (int)$p['id_owner'], 'selected_id' => $id];
        } catch (Exception $e) {
            return ['ok' => false, 'status' => 'error_db', 'id_owner' => (int)$p['id_owner']];
        }
    }

    public function partnerCreateRestaurant($src, $files = []) { return $this->createOrUpdateRestaurant($src, $files, false, false); }
    public function partnerUpdateRestaurant($src, $files = []) { return $this->createOrUpdateRestaurant($src, $files, false, true); }
    public function adminCreateRestaurant($src, $files = []) { return $this->createOrUpdateRestaurant($src, $files, true, false); }
    public function adminUpdateRestaurant($src, $files = []) { return $this->createOrUpdateRestaurant($src, $files, true, true); }

    private function deleteRestaurant($src, $isAdmin)
    {
        $idRestaurant = (int)($src['id_restaurant'] ?? 0);
        $idOwnerSource = (int)($src['id_owner'] ?? 0);
        $existing = $this->findById($idRestaurant);
        if (!$existing) { return ['ok' => false, 'status' => 'error_not_found', 'id_owner' => $idOwnerSource]; }
        if (!$isAdmin && $idOwnerSource !== (int)$existing['id_owner']) { return ['ok' => false, 'status' => 'error_forbidden', 'id_owner' => $idOwnerSource]; }
        try {
            $q = $this->db()->prepare("DELETE FROM restaurant WHERE id_restaurant = :id");
            $q->execute(['id' => $idRestaurant]);
            if ($q->rowCount() > 0) {
                $this->deleteImage((string)$existing['image_path']);
                $this->invalidateMatchingSnapshots('restaurant_deleted');
            }
            return ['ok' => true, 'status' => 'success_restaurant_deleted', 'id_owner' => (int)$existing['id_owner']];
        } catch (Exception $e) {
            return ['ok' => false, 'status' => 'error_db', 'id_owner' => (int)$existing['id_owner']];
        }
    }

    public function partnerDeleteRestaurant($src) { return $this->deleteRestaurant($src, false); }
    public function adminDeleteRestaurant($src) { return $this->deleteRestaurant($src, true); }

    private function saveMeals($idRestaurant, $meals)
    {
        $q = $this->db()->prepare("UPDATE restaurant SET meals_json = :meals_json WHERE id_restaurant = :id");
        $q->execute(['meals_json' => $this->mealsEncode($meals), 'id' => (int)$idRestaurant]);
        $this->invalidateMatchingSnapshots('meal_mutation');
    }

    private function asyncMealAiEnabled()
    {
        $flag = strtolower((string)caremeal_env('CAREMEAL_ASYNC_MEAL_AI_ENABLED', '1'));
        return in_array($flag, ['1', 'true', 'yes', 'on'], true);
    }

    private function phpCliBinary()
    {
        $configured = trim((string)caremeal_env('CAREMEAL_PHP_BIN', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '' && is_file(PHP_BINARY)) {
            return PHP_BINARY;
        }

        $xamppPhp = 'C:\\xampp\\php\\php.exe';
        if (is_file($xamppPhp)) {
            return $xamppPhp;
        }

        return 'php';
    }

    private function queueMealAiAnalysis($idRestaurant, $mealId)
    {
        if (!$this->asyncMealAiEnabled()) {
            return;
        }

        $idRestaurant = (int)$idRestaurant;
        $mealId = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$mealId);
        if ($idRestaurant <= 0 || $mealId === '') {
            return;
        }

        $script = realpath(__DIR__ . '/AnalyzeMealJob.php');
        if ($script === false || !is_file($script)) {
            return;
        }

        $phpBin = $this->phpCliBinary();
        $argRestaurant = '--restaurant_id=' . $idRestaurant;
        $argMeal = '--meal_id=' . $mealId;

        if (PHP_OS_FAMILY === 'Windows') {
            $phpEscaped = str_replace('"', '\"', $phpBin);
            $scriptEscaped = str_replace('"', '\"', $script);
            $command = 'cmd /c start "" /B "' . $phpEscaped . '" "' . $scriptEscaped . '" ' . $argRestaurant . ' ' . $argMeal . ' >NUL 2>&1';
            @pclose(@popen($command, 'r'));
            return;
        }

        $command = escapeshellcmd($phpBin)
            . ' ' . escapeshellarg($script)
            . ' ' . escapeshellarg($argRestaurant)
            . ' ' . escapeshellarg($argMeal)
            . ' > /dev/null 2>&1 &';
        @exec($command);
    }

    private function buildPendingMealAiPayload($meal)
    {
        if (!is_array($meal)) {
            return [];
        }

        $meal['analyse_ia'] = 0;
        $meal['analyse_ia_model'] = trim((string)caremeal_env('CAREMEAL_GEMINI_MODEL', 'gemini-3-flash-preview'));
        $meal['analyse_ia_engine'] = 'queued';
        $meal['analyse_ia_updated_at'] = date('Y-m-d H:i:s');
        $meal['analyse_ia_error'] = '';
        $meal['ingredients_standardises'] = [];
        $meal['ingredients_manquants_probables'] = [];
        $meal['allergenes_finaux'] = [];

        return $meal;
    }

    private function mealCrud($src, $isAdmin, $mode)
    {
        $idRestaurant = (int)($src['id_restaurant'] ?? 0);
        $idOwnerSource = (int)($src['id_owner'] ?? 0);
        $mealId = $this->norm($src['meal_id'] ?? '');
        $restaurant = $this->findById($idRestaurant);
        if (!$restaurant) { return ['ok' => false, 'status' => 'error_not_found', 'id_owner' => $idOwnerSource, 'selected_id' => $idRestaurant]; }
        if (!$isAdmin && $idOwnerSource !== (int)$restaurant['id_owner']) { return ['ok' => false, 'status' => 'error_forbidden', 'id_owner' => $idOwnerSource, 'selected_id' => $idRestaurant]; }

        $meals = $restaurant['meals'] ?? [];
        if ($mode === 'delete') {
            $newMeals = [];
            $deleted = false;
            foreach ($meals as $m) { if (($m['meal_id'] ?? '') === $mealId) { $deleted = true; continue; } $newMeals[] = $m; }
            if (!$deleted) { return ['ok' => false, 'status' => 'error_not_found', 'id_owner' => (int)$restaurant['id_owner'], 'selected_id' => $idRestaurant]; }
            $this->saveMeals($idRestaurant, $newMeals);
            return ['ok' => true, 'status' => 'success_meal_deleted', 'id_owner' => (int)$restaurant['id_owner'], 'selected_id' => $idRestaurant];
        }

        $valid = $this->validateMeal($src);
        if (!$valid['ok']) { return ['ok' => false, 'status' => $valid['status'], 'errors' => $valid['errors'], 'id_owner' => (int)$restaurant['id_owner'], 'selected_id' => $idRestaurant]; }
        $meal = $valid['meal'];

        if ($mode === 'add') {
            $meal['meal_id'] = 'meal_' . date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 6);
            $meal['created_at'] = date('Y-m-d H:i:s');
            $meal = $this->buildPendingMealAiPayload($meal);
            $meals[] = $meal;
            $this->saveMeals($idRestaurant, $meals);
            $this->queueMealAiAnalysis($idRestaurant, (string)$meal['meal_id']);
            return ['ok' => true, 'status' => 'success_meal_added', 'id_owner' => (int)$restaurant['id_owner'], 'selected_id' => $idRestaurant];
        }

        $updated = false;
        foreach ($meals as $i => $m) {
            if (($m['meal_id'] ?? '') !== $mealId) { continue; }
            $meal['meal_id'] = $mealId;
            $meal['created_at'] = $m['created_at'] ?? date('Y-m-d H:i:s');
            $meal = $this->buildPendingMealAiPayload($meal);
            $meals[$i] = $meal;
            $updated = true;
            break;
        }
        if (!$updated) { return ['ok' => false, 'status' => 'error_not_found', 'id_owner' => (int)$restaurant['id_owner'], 'selected_id' => $idRestaurant]; }
        $this->saveMeals($idRestaurant, $meals);
        $this->queueMealAiAnalysis($idRestaurant, $mealId);
        return ['ok' => true, 'status' => 'success_meal_updated', 'id_owner' => (int)$restaurant['id_owner'], 'selected_id' => $idRestaurant];
    }

    public function partnerAddMeal($src) { return $this->mealCrud($src, false, 'add'); }
    public function partnerUpdateMeal($src) { return $this->mealCrud($src, false, 'update'); }
    public function partnerDeleteMeal($src) { return $this->mealCrud($src, false, 'delete'); }
    public function adminAddMeal($src) { return $this->mealCrud($src, true, 'add'); }
    public function adminUpdateMeal($src) { return $this->mealCrud($src, true, 'update'); }
    public function adminDeleteMeal($src) { return $this->mealCrud($src, true, 'delete'); }
}
