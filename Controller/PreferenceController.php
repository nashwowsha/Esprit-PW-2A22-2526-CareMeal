<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Preference.php';
require_once __DIR__ . '/MatchingSnapshotStore.php';

class PreferenceController {
    private function getDb() {
        return config::getConnexion();
    }

    private function getSessionStudentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    }

    private function invalidateMatchingSnapshotsForPreference($idUser, $idPref)
    {
        try {
            MatchingSnapshotStore::purgeSnapshotsByPreference((int)$idUser, (int)$idPref);
        } catch (Exception $e) {
            // Keep preference flow stable if invalidation fails.
        }
    }

    private function normalizePlainText($value) {
        $text = trim((string)$value);
        $text = preg_replace('/\s+/', ' ', $text);

        return $text ?? '';
    }

    private function getAllowedRegimeIcons() {
        return [
            'fa-star-and-crescent',
            'fa-leaf',
            'fa-seedling',
            'fa-wheat-awn',
            'fa-spa',
            'fa-glass-water',
            'fa-coins',
            'fa-scale-balanced',
            'fa-bowl-food',
            'fa-carrot',
            'fa-utensils',
            'fa-apple-whole',
        ];
    }

    private function normalizeRegimeOptionLabel($label) {
        $label = $this->normalizePlainText($label);
        if ($label === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($label));
    }

    private function isValidRegimeOptionLabel($label) {
        if ($label === '') {
            return false;
        }

        if (function_exists('mb_strlen')) {
            $length = mb_strlen($label, 'UTF-8');
        } else {
            $length = strlen($label);
        }

        if ($length < 2 || $length > 50) {
            return false;
        }

        if (preg_match('/\d/', $label)) {
            return false;
        }

        return (bool)preg_match('/^[\p{L}\s]+$/u', $label);
    }

    private function isValidRegimeIcon($iconClass) {
        return in_array((string)$iconClass, $this->getAllowedRegimeIcons(), true);
    }

    private function ensureRegimeOptionTable() {
        $db = $this->getDb();
        $sql = "CREATE TABLE IF NOT EXISTS regime_option (
                    id_option INT AUTO_INCREMENT PRIMARY KEY,
                    option_value VARCHAR(100) NOT NULL UNIQUE,
                    option_label VARCHAR(100) NOT NULL UNIQUE,
                    icon_class VARCHAR(80) NOT NULL DEFAULT 'fa-utensils',
                    icon_type VARCHAR(20) NOT NULL DEFAULT 'fa',
                    icon_image_path VARCHAR(255) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);

        $columns = $db->query("SHOW COLUMNS FROM regime_option")->fetchAll();
        $columnNames = array_map(static function ($col) {
            return $col['Field'];
        }, $columns);

        if (!in_array('icon_type', $columnNames, true)) {
            $db->exec("ALTER TABLE regime_option ADD COLUMN icon_type VARCHAR(20) NOT NULL DEFAULT 'fa' AFTER icon_class");
        }

        if (!in_array('icon_image_path', $columnNames, true)) {
            $db->exec("ALTER TABLE regime_option ADD COLUMN icon_image_path VARCHAR(255) DEFAULT NULL AFTER icon_type");
        }
    }

    private function seedDefaultRegimeOptions() {
        $db = $this->getDb();
        $count = (int)$db->query('SELECT COUNT(*) FROM regime_option')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['value' => 'halal', 'label' => 'Halal', 'icon' => 'fa-star-and-crescent'],
            ['value' => 'vegetarien', 'label' => 'Vegetarien', 'icon' => 'fa-leaf'],
            ['value' => 'vegan', 'label' => 'Vegan', 'icon' => 'fa-seedling'],
            ['value' => 'sans-gluten', 'label' => 'Sans gluten', 'icon' => 'fa-wheat-awn'],
            ['value' => 'bio', 'label' => 'Bio', 'icon' => 'fa-spa'],
            ['value' => 'sans-lactose', 'label' => 'Sans lactose', 'icon' => 'fa-glass-water'],
            ['value' => 'budget', 'label' => 'Petit budget', 'icon' => 'fa-coins'],
            ['value' => 'equilibre', 'label' => 'Equilibre', 'icon' => 'fa-scale-balanced'],
        ];

        $query = $db->prepare('INSERT INTO regime_option (option_value, option_label, icon_class, icon_type, icon_image_path) VALUES (:value, :label, :icon, :icon_type, :icon_image_path)');
        foreach ($defaults as $option) {
            $query->execute([
                'value' => $option['value'],
                'label' => $option['label'],
                'icon' => $option['icon'],
                'icon_type' => 'fa',
                'icon_image_path' => null,
            ]);
        }
    }

    private function buildOptionValueFromLabel($label) {
        $value = $label;

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z\s-]/', '', $value);
        $value = preg_replace('/\s+/', '-', trim((string)$value));
        $value = preg_replace('/-+/', '-', (string)$value);

        return $value ?? '';
    }

    private function getRegimeOptionById($idOption) {
        $db = $this->getDb();
        $query = $db->prepare('SELECT id_option, option_value, option_label, icon_class, icon_type, icon_image_path FROM regime_option WHERE id_option = :id_option LIMIT 1');
        $query->execute(['id_option' => (int)$idOption]);

        return $query->fetch() ?: null;
    }

    private function getRegimeIconUploadDirectory() {
        return __DIR__ . '/../assets/regime-icons';
    }

    private function uploadRegimeIconImage($fileInfo) {
        if (!is_array($fileInfo) || !isset($fileInfo['tmp_name']) || !isset($fileInfo['error'])) {
            return ['ok' => false, 'status' => 'error_option_icon_invalid'];
        }

        if ((int)$fileInfo['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'status' => 'error_option_icon_invalid'];
        }

        if (!is_uploaded_file($fileInfo['tmp_name'])) {
            return ['ok' => false, 'status' => 'error_option_icon_invalid'];
        }

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'];
        $extension = strtolower(pathinfo((string)$fileInfo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['ok' => false, 'status' => 'error_option_icon_invalid'];
        }

        $maxSize = 2 * 1024 * 1024;
        if (isset($fileInfo['size']) && (int)$fileInfo['size'] > $maxSize) {
            return ['ok' => false, 'status' => 'error_option_icon_invalid'];
        }

        $directory = $this->getRegimeIconUploadDirectory();
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        if (!is_dir($directory)) {
            return ['ok' => false, 'status' => 'error_db'];
        }

        try {
            $randomPart = bin2hex(random_bytes(4));
        } catch (Exception $e) {
            $randomPart = substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
        }

        $fileName = 'regime_' . date('Ymd_His') . '_' . $randomPart . '.' . $extension;
        $targetPath = $directory . '/' . $fileName;

        if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            return ['ok' => false, 'status' => 'error_db'];
        }

        return [
            'ok' => true,
            'path' => 'assets/regime-icons/' . $fileName,
        ];
    }

    private function deleteRegimeIconImageFile($webPath) {
        $webPath = trim((string)$webPath);
        if ($webPath === '') {
            return;
        }

        $prefix = 'assets/regime-icons/';
        if (strpos($webPath, $prefix) !== 0) {
            return;
        }

        $fileName = basename($webPath);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return;
        }

        $absolutePath = $this->getRegimeIconUploadDirectory() . '/' . $fileName;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function isRegimeOptionInUse($optionValue) {
        $db = $this->getDb();
        $needle = '%,' . strtolower(str_replace(' ', '', (string)$optionValue)) . ',%';
        $query = $db->prepare("SELECT COUNT(*) FROM preference WHERE CONCAT(',', REPLACE(LOWER(regime_alimentaire), ' ', ''), ',') LIKE :needle");
        $query->execute(['needle' => $needle]);

        return ((int)$query->fetchColumn()) > 0;
    }

    private function parseCommaList($value) {
        $parts = preg_split('/[,;]+/', (string)$value);
        if (!$parts) {
            return [];
        }

        $clean = array_values(array_filter(array_map(function ($item) {
            return $this->normalizePlainText($item);
        }, $parts), static function ($item) {
            return $item !== '';
        }));

        return $clean;
    }

    private function normalizeCommaListText($value) {
        $items = $this->parseCommaList($value);

        return implode(', ', $items);
    }

    private function normalizeRegime($source) {
        if (isset($source['regimes']) && is_array($source['regimes'])) {
            $clean = array_values(array_filter(array_map(function ($item) {
                return $this->normalizePlainText($item);
            }, $source['regimes']), static function ($item) {
                return $item !== '';
            }));

            $clean = array_values(array_unique(array_map('strtolower', $clean)));

            return implode(', ', $clean);
        }

        if (isset($source['regime_alimentaire'])) {
            return strtolower($this->normalizeCommaListText($source['regime_alimentaire']));
        }

        return '';
    }

    private function normalizePayload($source) {
        $preference = new Preference();
        $preference->setRegimeAlimentaire($this->normalizeRegime($source));
        $preference->setAllergies(strtolower($this->normalizeCommaListText($source['allergies'] ?? '')));
        $preference->setLocalisation($this->normalizePlainText($source['localisation'] ?? ''));
        $preference->setIdUser(isset($source['id_user']) ? (int)$source['id_user'] : 0);
        $preference->setDateDemande(date('Y-m-d H:i:s'));

        return $preference;
    }

    private function mapPreferenceToArray($preference) {
        return $preference instanceof Preference ? $preference->toArray() : null;
    }

    private function getByIdEntity($idPref) {
        $db = $this->getDb();
        $query = $db->prepare('SELECT * FROM preference WHERE id_pref = :id_pref LIMIT 1');
        $query->execute(['id_pref' => (int)$idPref]);

        return Preference::fromArray($query->fetch() ?: null);
    }

    private function getByUserIdEntity($idUser) {
        $db = $this->getDb();
        $query = $db->prepare('SELECT * FROM preference WHERE id_user = :id_user ORDER BY id_pref DESC LIMIT 1');
        $query->execute(['id_user' => (int)$idUser]);

        return Preference::fromArray($query->fetch() ?: null);
    }

    private function listByUserIdEntities($idUser) {
        $db = $this->getDb();
        $query = $db->prepare('SELECT * FROM preference WHERE id_user = :id_user ORDER BY id_pref DESC');
        $query->execute(['id_user' => (int)$idUser]);

        return array_map(static function ($row) {
            return Preference::fromArray($row);
        }, $query->fetchAll());
    }

    private function insertPreference($preference) {
        $db = $this->getDb();
        $sql = 'INSERT INTO preference (regime_alimentaire, allergies, localisation, date_demande, id_user)
                VALUES (:regime, :allergies, :localisation, :date_demande, :id_user)';

        $query = $db->prepare($sql);
        $query->execute([
            'regime' => $preference->getRegimeAlimentaire(),
            'allergies' => $preference->getAllergies(),
            'localisation' => $preference->getLocalisation(),
            'date_demande' => $preference->getDateDemande(),
            'id_user' => $preference->getIdUser(),
        ]);

        return (int)$db->lastInsertId();
    }

    private function updatePreferenceById($idPref, $preference) {
        $db = $this->getDb();
        $sql = 'UPDATE preference
                SET regime_alimentaire = :regime,
                    allergies = :allergies,
                    localisation = :localisation,
                    date_demande = :date_demande,
                    id_user = :id_user
                WHERE id_pref = :id_pref';

        $query = $db->prepare($sql);
        $query->execute([
            'id_pref' => (int)$idPref,
            'regime' => $preference->getRegimeAlimentaire(),
            'allergies' => $preference->getAllergies(),
            'localisation' => $preference->getLocalisation(),
            'date_demande' => $preference->getDateDemande(),
            'id_user' => $preference->getIdUser(),
        ]);

        return $query->rowCount() > 0;
    }

    private function deletePreferenceById($idPref) {
        $db = $this->getDb();
        $query = $db->prepare('DELETE FROM preference WHERE id_pref = :id_pref');
        $query->execute(['id_pref' => (int)$idPref]);

        return $query->rowCount() > 0;
    }

    private function existsDuplicate($preference, $excludeIdPref = null) {
        $db = $this->getDb();

        $sql = 'SELECT id_pref
                FROM preference
                WHERE id_user = :id_user
                  AND LOWER(TRIM(regime_alimentaire)) = LOWER(TRIM(:regime))
                  AND LOWER(TRIM(allergies)) = LOWER(TRIM(:allergies))
                  AND LOWER(TRIM(localisation)) = LOWER(TRIM(:localisation))';

        if ($excludeIdPref !== null) {
            $sql .= ' AND id_pref <> :exclude_id_pref';
        }

        $sql .= ' LIMIT 1';

        $query = $db->prepare($sql);
        $params = [
            'id_user' => $preference->getIdUser(),
            'regime' => $preference->getRegimeAlimentaire(),
            'allergies' => $preference->getAllergies(),
            'localisation' => $preference->getLocalisation(),
        ];

        if ($excludeIdPref !== null) {
            $params['exclude_id_pref'] = (int)$excludeIdPref;
        }

        $query->execute($params);

        return $query->fetch() ?: null;
    }

    public function getAllForAdmin() {
        $db = $this->getDb();
        $sql = 'SELECT p.*,
                       COALESCE(NULLIF(TRIM(CONCAT(COALESCE(up.prenom, \'\'), \' \', COALESCE(up.nom, \'\'))), \'\'), u.email, CONCAT(\'User #\', u.id)) AS user_nom,
                       u.email AS user_email,
                       u.role AS user_role
                FROM preference p
                LEFT JOIN users u ON u.id = p.id_user
                LEFT JOIN profiles up ON up.user_id = u.id
                ORDER BY p.id_pref DESC';

        return $db->query($sql)->fetchAll();
    }

    public function getRegimeOptions() {
        try {
            $this->ensureRegimeOptionTable();
            $this->seedDefaultRegimeOptions();

            $db = $this->getDb();
            $rows = $db->query('SELECT id_option, option_value, option_label, icon_class, icon_type, icon_image_path FROM regime_option ORDER BY option_label ASC')->fetchAll();

            return array_map(static function ($row) {
                return [
                    'id' => (int)$row['id_option'],
                    'value' => (string)$row['option_value'],
                    'label' => (string)$row['option_label'],
                    'icon' => (string)$row['icon_class'],
                    'icon_type' => (string)($row['icon_type'] ?? 'fa'),
                    'icon_image' => (string)($row['icon_image_path'] ?? ''),
                ];
            }, $rows);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAvailableRegimeIcons() {
        return $this->getAllowedRegimeIcons();
    }

    public function adminAddRegimeOption($source, $files = []) {
        $label = $this->normalizeRegimeOptionLabel($source['option_label'] ?? '');
        $iconMode = $this->normalizePlainText($source['icon_mode'] ?? 'fa');
        $iconClass = $this->normalizePlainText($source['icon_class'] ?? 'fa-utensils');
        $iconType = 'fa';
        $iconImagePath = null;

        if (!$this->isValidRegimeOptionLabel($label)) {
            return ['ok' => false, 'status' => 'error_option_invalid'];
        }

        if ($iconMode === 'upload') {
            $upload = $this->uploadRegimeIconImage($files['icon_file'] ?? null);
            if (!$upload['ok']) {
                return ['ok' => false, 'status' => $upload['status'] ?? 'error_option_icon_invalid'];
            }
            $iconType = 'image';
            $iconImagePath = (string)$upload['path'];
            $iconClass = 'fa-image';
        } else {
            if (!$this->isValidRegimeIcon($iconClass)) {
                return ['ok' => false, 'status' => 'error_option_icon_invalid'];
            }
        }

        $value = $this->buildOptionValueFromLabel($label);
        if ($value === '') {
            return ['ok' => false, 'status' => 'error_option_invalid'];
        }

        try {
            $this->ensureRegimeOptionTable();
            $this->seedDefaultRegimeOptions();
            $db = $this->getDb();

            $existingQuery = $db->prepare('SELECT id_option FROM regime_option WHERE LOWER(option_label) = LOWER(:label) OR option_value = :value LIMIT 1');
            $existingQuery->execute([
                'label' => $label,
                'value' => $value,
            ]);

            if ($existingQuery->fetch()) {
                return ['ok' => false, 'status' => 'error_option_exists'];
            }

            $insert = $db->prepare('INSERT INTO regime_option (option_value, option_label, icon_class, icon_type, icon_image_path) VALUES (:value, :label, :icon, :icon_type, :icon_image_path)');
            $insert->execute([
                'value' => $value,
                'label' => $label,
                'icon' => $iconClass,
                'icon_type' => $iconType,
                'icon_image_path' => $iconImagePath,
            ]);

            return ['ok' => true, 'status' => 'success_option_added'];
        } catch (Exception $e) {
            if ($iconType === 'image' && $iconImagePath !== null) {
                $this->deleteRegimeIconImageFile($iconImagePath);
            }
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    public function adminUpdateRegimeOption($source, $files = []) {
        $idOption = isset($source['option_id']) ? (int)$source['option_id'] : 0;
        $label = $this->normalizeRegimeOptionLabel($source['option_label'] ?? '');
        $iconMode = $this->normalizePlainText($source['icon_mode'] ?? 'fa');
        $iconClass = $this->normalizePlainText($source['icon_class'] ?? '');
        if ($idOption <= 0 || !$this->isValidRegimeOptionLabel($label)) {
            return ['ok' => false, 'status' => 'error_option_invalid'];
        }

        try {
            $this->ensureRegimeOptionTable();
            $this->seedDefaultRegimeOptions();
            $db = $this->getDb();

            $existing = $this->getRegimeOptionById($idOption);
            if (!$existing) {
                return ['ok' => false, 'status' => 'error_option_not_found'];
            }

            $newIconType = (string)($existing['icon_type'] ?? 'fa');
            $newIconClass = (string)($existing['icon_class'] ?? 'fa-utensils');
            $newIconImagePath = (string)($existing['icon_image_path'] ?? '');
            $uploadedIconPath = null;

            if ($iconMode === 'upload') {
                $hasFile = isset($files['icon_file']) && is_array($files['icon_file']) && isset($files['icon_file']['error']) && (int)$files['icon_file']['error'] !== UPLOAD_ERR_NO_FILE;

                if ($hasFile) {
                    $upload = $this->uploadRegimeIconImage($files['icon_file'] ?? null);
                    if (!$upload['ok']) {
                        return ['ok' => false, 'status' => $upload['status'] ?? 'error_option_icon_invalid'];
                    }
                    $uploadedIconPath = (string)$upload['path'];
                    $newIconType = 'image';
                    $newIconClass = 'fa-image';
                    $newIconImagePath = $uploadedIconPath;
                } elseif ((string)($existing['icon_type'] ?? 'fa') !== 'image' || empty($existing['icon_image_path'])) {
                    return ['ok' => false, 'status' => 'error_option_icon_invalid'];
                }
            } else {
                if (!$this->isValidRegimeIcon($iconClass)) {
                    return ['ok' => false, 'status' => 'error_option_icon_invalid'];
                }
                $newIconType = 'fa';
                $newIconClass = $iconClass;
                $newIconImagePath = '';
            }

            $duplicate = $db->prepare('SELECT id_option FROM regime_option WHERE LOWER(option_label) = LOWER(:label) AND id_option <> :id_option LIMIT 1');
            $duplicate->execute([
                'label' => $label,
                'id_option' => $idOption,
            ]);
            if ($duplicate->fetch()) {
                if ($uploadedIconPath !== null) {
                    $this->deleteRegimeIconImageFile($uploadedIconPath);
                }
                return ['ok' => false, 'status' => 'error_option_exists'];
            }

            $update = $db->prepare('UPDATE regime_option SET option_label = :label, icon_class = :icon, icon_type = :icon_type, icon_image_path = :icon_image_path WHERE id_option = :id_option');
            $update->execute([
                'label' => $label,
                'icon' => $newIconClass,
                'icon_type' => $newIconType,
                'icon_image_path' => $newIconImagePath === '' ? null : $newIconImagePath,
                'id_option' => $idOption,
            ]);

            $oldIconType = (string)($existing['icon_type'] ?? 'fa');
            $oldIconImage = (string)($existing['icon_image_path'] ?? '');
            if ($oldIconType === 'image' && $oldIconImage !== '' && $oldIconImage !== $newIconImagePath) {
                $this->deleteRegimeIconImageFile($oldIconImage);
            }

            return ['ok' => true, 'status' => 'success_option_updated'];
        } catch (Exception $e) {
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    public function adminDeleteRegimeOption($source) {
        $idOption = isset($source['option_id']) ? (int)$source['option_id'] : 0;
        if ($idOption <= 0) {
            return ['ok' => false, 'status' => 'error_option_invalid'];
        }

        try {
            $this->ensureRegimeOptionTable();
            $this->seedDefaultRegimeOptions();
            $db = $this->getDb();

            $existing = $this->getRegimeOptionById($idOption);
            if (!$existing) {
                return ['ok' => false, 'status' => 'error_option_not_found'];
            }

            if ($this->isRegimeOptionInUse($existing['option_value'])) {
                return ['ok' => false, 'status' => 'error_option_in_use'];
            }

            $delete = $db->prepare('DELETE FROM regime_option WHERE id_option = :id_option');
            $delete->execute(['id_option' => $idOption]);

            if ($delete->rowCount() > 0 && (string)($existing['icon_type'] ?? '') === 'image' && !empty($existing['icon_image_path'])) {
                $this->deleteRegimeIconImageFile((string)$existing['icon_image_path']);
            }

            return [
                'ok' => $delete->rowCount() > 0,
                'status' => $delete->rowCount() > 0 ? 'success_option_deleted' : 'error_option_not_found',
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'status' => 'error_db'];
        }
    }

    public function getById($idPref) {
        return $this->mapPreferenceToArray($this->getByIdEntity((int)$idPref));
    }

    public function getByUserId($idUser) {
        return $this->mapPreferenceToArray($this->getByUserIdEntity((int)$idUser));
    }

    public function getListByUserId($idUser) {
        return array_values(array_filter(array_map(function ($entity) {
            return $this->mapPreferenceToArray($entity);
        }, $this->listByUserIdEntities((int)$idUser))));
    }

    public function studentUpsert($source) {
        $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;
        $sessionUserId = $this->getSessionStudentUserId();
        if ($sessionUserId <= 0) {
            return [
                'ok' => false,
                'status' => 'error_forbidden',
                'id_user' => 0,
            ];
        }

        $payload = $this->normalizePayload($source);
        $payload->setIdUser($sessionUserId);

        try {
            if ($idPref > 0) {
                $existing = $this->getByIdEntity($idPref);
                if (!$existing) {
                    return [
                        'ok' => false,
                        'status' => 'error_not_found',
                        'id_user' => (int)$payload->getIdUser(),
                    ];
                }

                if ((int)$existing->getIdUser() !== (int)$payload->getIdUser()) {
                    return [
                        'ok' => false,
                        'status' => 'error_forbidden',
                        'id_user' => (int)$payload->getIdUser(),
                    ];
                }

                if ($this->existsDuplicate($payload, $idPref)) {
                    return [
                        'ok' => false,
                        'status' => 'error_duplicate_preference',
                        'id_user' => (int)$payload->getIdUser(),
                        'errors' => ['duplicate_preference'],
                    ];
                }

                $updated = $this->updatePreferenceById($idPref, $payload);

                if (!$updated && !$this->getByIdEntity($idPref)) {
                    return [
                        'ok' => false,
                        'status' => 'error_not_found',
                        'id_user' => (int)$payload->getIdUser(),
                    ];
                }

                $this->invalidateMatchingSnapshotsForPreference((int)$payload->getIdUser(), $idPref);

                return [
                    'ok' => true,
                    'status' => 'success_updated',
                    'id_user' => (int)$payload->getIdUser(),
                    'id_pref' => $idPref,
                ];
            }

            if ($this->existsDuplicate($payload)) {
                return [
                    'ok' => false,
                    'status' => 'error_duplicate_preference',
                    'id_user' => (int)$payload->getIdUser(),
                    'errors' => ['duplicate_preference'],
                ];
            }

            $newId = $this->insertPreference($payload);
            $this->invalidateMatchingSnapshotsForPreference((int)$payload->getIdUser(), $newId);

            return [
                'ok' => true,
                'status' => 'success_created',
                'id_user' => (int)$payload->getIdUser(),
                'id_pref' => $newId,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'id_user' => (int)$payload->getIdUser(),
                'message' => $e->getMessage(),
            ];
        }
    }

    public function studentDelete($source) {
        $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;
        $idUser = $this->getSessionStudentUserId();

        if ($idPref <= 0 || $idUser <= 0) {
            return [
                'ok' => false,
                'status' => 'error_missing_fields',
                'id_user' => $idUser,
            ];
        }

        $existing = $this->getByIdEntity($idPref);
        if (!$existing) {
            return [
                'ok' => false,
                'status' => 'error_not_found',
                'id_user' => $idUser,
            ];
        }

        if ((int)$existing->getIdUser() !== $idUser) {
            return [
                'ok' => false,
                'status' => 'error_forbidden',
                'id_user' => $idUser,
            ];
        }

        try {
            $this->deletePreferenceById($idPref);
            $this->invalidateMatchingSnapshotsForPreference($idUser, $idPref);

            return [
                'ok' => true,
                'status' => 'success_deleted',
                'id_user' => $idUser,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'id_user' => $idUser,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function adminCreate($source) {
        $payload = $this->normalizePayload($source);

        try {
            if ($this->existsDuplicate($payload)) {
                return [
                    'ok' => false,
                    'status' => 'error_duplicate_preference',
                    'errors' => ['duplicate_preference'],
                ];
            }

            $idPref = $this->insertPreference($payload);
            $this->invalidateMatchingSnapshotsForPreference((int)$payload->getIdUser(), $idPref);

            return [
                'ok' => true,
                'status' => 'success_created',
                'id_pref' => $idPref,
                'id_user' => (int)$payload->getIdUser(),
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'id_user' => (int)$payload->getIdUser(),
                'message' => $e->getMessage(),
            ];
        }
    }

    public function adminUpdate($source) {
        $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;
        $payload = $this->normalizePayload($source);
        if ($idPref <= 0) {
            return [
                'ok' => false,
                'status' => 'error_missing_fields',
                'errors' => ['id_pref'],
            ];
        }

        try {
            if ($this->existsDuplicate($payload, $idPref)) {
                return [
                    'ok' => false,
                    'status' => 'error_duplicate_preference',
                    'errors' => ['duplicate_preference'],
                ];
            }

            $updated = $this->updatePreferenceById($idPref, $payload);
            if ($updated) {
                $this->invalidateMatchingSnapshotsForPreference((int)$payload->getIdUser(), $idPref);
            }

            return [
                'ok' => $updated,
                'status' => $updated ? 'success_updated' : 'error_not_found',
                'id_pref' => $idPref,
                'id_user' => (int)$payload->getIdUser(),
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'id_user' => (int)$payload->getIdUser(),
                'message' => $e->getMessage(),
            ];
        }
    }

    public function adminDelete($source) {
        $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;

        if ($idPref <= 0) {
            return [
                'ok' => false,
                'status' => 'error_missing_fields',
            ];
        }

        $existing = $this->getByIdEntity($idPref);
        $idUser = $existing ? (int)$existing->getIdUser() : 0;

        try {
            $deleted = $this->deletePreferenceById($idPref);
            if ($deleted) {
                $this->invalidateMatchingSnapshotsForPreference($idUser, $idPref);
            }

            return [
                'ok' => $deleted,
                'status' => $deleted ? 'success_deleted' : 'error_not_found',
                'id_user' => $idUser,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'id_user' => $idUser,
                'message' => $e->getMessage(),
            ];
        }
    }
}
