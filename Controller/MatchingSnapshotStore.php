<?php
require_once __DIR__ . '/../config/database.php';

class MatchingSnapshotStore
{
    private static function db()
    {
        return config::getConnexion();
    }

    private static function normToken($value, $fallback)
    {
        $value = strtolower(trim((string)$value));
        return $value === '' ? $fallback : $value;
    }

    public static function normalizeFilterOptions($options = [])
    {
        $options = is_array($options) ? $options : [];
        $priceMode = self::normToken($options['price_mode'] ?? 'all', 'all');
        if (!in_array($priceMode, ['all', 'free', 'paid'], true)) {
            $priceMode = 'all';
        }
        $sortBy = self::normToken($options['sort_by'] ?? 'score', 'score');
        if (!in_array($sortBy, ['score', 'location', 'name'], true)) {
            $sortBy = 'score';
        }
        $safetyMode = self::normToken($options['safety_mode'] ?? 'strict', 'strict');
        if (!in_array($safetyMode, ['strict', 'souple'], true)) {
            $safetyMode = 'strict';
        }
        return [
            'price_mode' => $priceMode,
            'sort_by' => $sortBy,
            'safety_mode' => $safetyMode,
        ];
    }

    public static function ensureTables()
    {
        $db = self::db();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS matching_source_version (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                source_version BIGINT UNSIGNED NOT NULL DEFAULT 1,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS matching_snapshot (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_pref INT NOT NULL,
                price_mode VARCHAR(16) NOT NULL,
                sort_by VARCHAR(16) NOT NULL,
                safety_mode VARCHAR(16) NOT NULL,
                source_version BIGINT UNSIGNED NOT NULL,
                payload_json LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY ux_snapshot_key (id_user, id_pref, price_mode, sort_by, safety_mode),
                KEY idx_snapshot_source_version (source_version),
                KEY idx_snapshot_pref (id_pref),
                KEY idx_snapshot_user (id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $db->exec(
            "INSERT INTO matching_source_version (id, source_version)
             SELECT 1, 1
             WHERE NOT EXISTS (SELECT 1 FROM matching_source_version WHERE id = 1)"
        );
    }

    public static function currentSourceVersion()
    {
        self::ensureTables();
        $q = self::db()->query("SELECT source_version FROM matching_source_version WHERE id = 1 LIMIT 1");
        $row = $q ? $q->fetch() : null;
        return (int)($row['source_version'] ?? 1);
    }

    public static function bumpSourceVersion($reason = '')
    {
        self::ensureTables();
        $db = self::db();
        $db->exec("UPDATE matching_source_version SET source_version = source_version + 1 WHERE id = 1");
        return self::currentSourceVersion();
    }

    public static function purgeSnapshotsByPreference($idUser, $idPref)
    {
        self::ensureTables();
        $idUser = (int)$idUser;
        $idPref = (int)$idPref;
        if ($idUser <= 0 || $idPref <= 0) {
            return 0;
        }
        $q = self::db()->prepare(
            "DELETE FROM matching_snapshot
             WHERE id_user = :id_user
               AND id_pref = :id_pref"
        );
        $q->execute([
            'id_user' => $idUser,
            'id_pref' => $idPref,
        ]);
        return (int)$q->rowCount();
    }

    public static function purgeSnapshotsByUser($idUser)
    {
        self::ensureTables();
        $idUser = (int)$idUser;
        if ($idUser <= 0) {
            return 0;
        }
        $q = self::db()->prepare(
            "DELETE FROM matching_snapshot
             WHERE id_user = :id_user"
        );
        $q->execute([
            'id_user' => $idUser,
        ]);
        return (int)$q->rowCount();
    }

    public static function readSnapshot($idUser, $idPref, $options = [])
    {
        self::ensureTables();
        $normalized = self::normalizeFilterOptions($options);
        $idUser = (int)$idUser;
        $idPref = (int)$idPref;
        if ($idUser <= 0 || $idPref <= 0) {
            return [
                'hit' => false,
                'payload' => null,
                'source_version' => self::currentSourceVersion(),
                'stored_version' => 0,
            ];
        }

        $sourceVersion = self::currentSourceVersion();
        $q = self::db()->prepare(
            "SELECT source_version, payload_json
             FROM matching_snapshot
             WHERE id_user = :id_user
               AND id_pref = :id_pref
               AND price_mode = :price_mode
               AND sort_by = :sort_by
               AND safety_mode = :safety_mode
             LIMIT 1"
        );
        $q->execute([
            'id_user' => $idUser,
            'id_pref' => $idPref,
            'price_mode' => $normalized['price_mode'],
            'sort_by' => $normalized['sort_by'],
            'safety_mode' => $normalized['safety_mode'],
        ]);
        $row = $q->fetch();
        if (!is_array($row)) {
            return [
                'hit' => false,
                'payload' => null,
                'source_version' => $sourceVersion,
                'stored_version' => 0,
            ];
        }

        $storedVersion = (int)($row['source_version'] ?? 0);
        $payload = json_decode((string)($row['payload_json'] ?? ''), true);
        if (!is_array($payload) || $storedVersion !== $sourceVersion) {
            return [
                'hit' => false,
                'payload' => null,
                'source_version' => $sourceVersion,
                'stored_version' => $storedVersion,
            ];
        }

        return [
            'hit' => true,
            'payload' => $payload,
            'source_version' => $sourceVersion,
            'stored_version' => $storedVersion,
        ];
    }

    public static function writeSnapshot($idUser, $idPref, $options, $sourceVersion, $payload)
    {
        self::ensureTables();
        $normalized = self::normalizeFilterOptions($options);
        $idUser = (int)$idUser;
        $idPref = (int)$idPref;
        $sourceVersion = (int)$sourceVersion;
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($idUser <= 0 || $idPref <= 0 || $sourceVersion <= 0 || !is_string($payloadJson)) {
            return false;
        }

        $q = self::db()->prepare(
            "INSERT INTO matching_snapshot (id_user, id_pref, price_mode, sort_by, safety_mode, source_version, payload_json)
             VALUES (:id_user, :id_pref, :price_mode, :sort_by, :safety_mode, :source_version, :payload_json)
             ON DUPLICATE KEY UPDATE
                 source_version = VALUES(source_version),
                 payload_json = VALUES(payload_json),
                 updated_at = CURRENT_TIMESTAMP"
        );
        return $q->execute([
            'id_user' => $idUser,
            'id_pref' => $idPref,
            'price_mode' => $normalized['price_mode'],
            'sort_by' => $normalized['sort_by'],
            'safety_mode' => $normalized['safety_mode'],
            'source_version' => $sourceVersion,
            'payload_json' => $payloadJson,
        ]);
    }

    public static function findRestaurantInSnapshot($restaurants, $idRestaurant)
    {
        $idRestaurant = (int)$idRestaurant;
        foreach ((array)$restaurants as $restaurant) {
            if (!is_array($restaurant)) {
                continue;
            }
            if ((int)($restaurant['id_restaurant'] ?? 0) === $idRestaurant) {
                return $restaurant;
            }
        }
        return null;
    }

    public static function findMealInSnapshotRestaurant($restaurant, $mealId)
    {
        $mealId = trim((string)$mealId);
        foreach ((array)($restaurant['matched_meals'] ?? []) as $meal) {
            if (!is_array($meal)) {
                continue;
            }
            if (trim((string)($meal['meal_id'] ?? '')) === $mealId) {
                return $meal;
            }
        }
        return null;
    }
}
