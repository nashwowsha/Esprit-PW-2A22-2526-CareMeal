<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Preference.php';

class PreferenceController {
    private function getDb() {
        return config::getConnexion();
    }

    private function normalizePlainText($value) {
        $text = trim((string)$value);
        $text = preg_replace('/\s+/', ' ', $text);

        return $text ?? '';
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

    private function validatePayload($preference) {
        $errors = [];

        if ($preference->getRegimeAlimentaire() === '') {
            $errors[] = 'regime_alimentaire';
        }

        if ($preference->getAllergies() === '') {
            $errors[] = 'allergies';
        }

        if ($preference->getLocalisation() === '') {
            $errors[] = 'localisation';
        }

        if ((int)$preference->getIdUser() <= 0) {
            $errors[] = 'id_user';
        }

        return $errors;
    }

    private function validateLogicalPayload($preference) {
        $errors = [];

        $regimes = $this->parseCommaList($preference->getRegimeAlimentaire());
        $allergies = $this->parseCommaList($preference->getAllergies());

        if (count($regimes) === 0) {
            $errors[] = 'regime_alimentaire';
        }

        if (count($regimes) > 4) {
            $errors[] = 'too_many_regimes';
        }

        if (count($allergies) === 0) {
            $errors[] = 'allergies';
        }

        $allergySeen = [];
        foreach ($allergies as $allergy) {
            if (preg_match('/\d/', $allergy)) {
                $errors[] = 'allergies_has_number';
                break;
            }

            if (!preg_match("/^[\\p{L}\\s'\\-]+$/u", $allergy)) {
                $errors[] = 'allergies_invalid_format';
                break;
            }

            $normalized = function_exists('mb_strtolower') ? mb_strtolower($allergy, 'UTF-8') : strtolower($allergy);
            if (isset($allergySeen[$normalized])) {
                $errors[] = 'allergies_duplicate';
                break;
            }
            $allergySeen[$normalized] = true;
        }

        if ($preference->getRegimeAlimentaire() !== '' && strlen($preference->getRegimeAlimentaire()) > 1000) {
            $errors[] = 'regime_too_long';
        }

        if ($preference->getAllergies() !== '' && strlen($preference->getAllergies()) > 1000) {
            $errors[] = 'allergies_too_long';
        }

        if ($preference->getLocalisation() !== '' && strlen($preference->getLocalisation()) > 1000) {
            $errors[] = 'localisation_too_long';
        }

        return array_values(array_unique($errors));
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
        $sql = 'SELECT p.*, u.nom AS user_nom, u.email AS user_email, u.role AS user_role
                FROM preference p
                LEFT JOIN utilisateur u ON u.id = p.id_user
                ORDER BY p.id_pref DESC';

        return $db->query($sql)->fetchAll();
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
        $payload = $this->normalizePayload($source);
        $errors = $this->validatePayload($payload);
        $logicalErrors = $this->validateLogicalPayload($payload);
        $errors = array_values(array_unique(array_merge($errors, $logicalErrors)));

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_validation',
                'id_user' => (int)$payload->getIdUser(),
                'errors' => $errors,
            ];
        }

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
        $idUser = isset($source['id_user']) ? (int)$source['id_user'] : 0;

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
        $errors = $this->validatePayload($payload);
        $logicalErrors = $this->validateLogicalPayload($payload);
        $errors = array_values(array_unique(array_merge($errors, $logicalErrors)));

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_validation',
                'errors' => $errors,
            ];
        }

        try {
            if ($this->existsDuplicate($payload)) {
                return [
                    'ok' => false,
                    'status' => 'error_duplicate_preference',
                    'errors' => ['duplicate_preference'],
                ];
            }

            $idPref = $this->insertPreference($payload);

            return [
                'ok' => true,
                'status' => 'success_created',
                'id_pref' => $idPref,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function adminUpdate($source) {
        $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;
        $payload = $this->normalizePayload($source);
        $errors = $this->validatePayload($payload);
        $logicalErrors = $this->validateLogicalPayload($payload);
        $errors = array_values(array_unique(array_merge($errors, $logicalErrors)));

        if ($idPref <= 0) {
            $errors[] = 'id_pref';
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_validation',
                'errors' => array_values(array_unique($errors)),
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

            return [
                'ok' => $updated,
                'status' => $updated ? 'success_updated' : 'error_not_found',
                'id_pref' => $idPref,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
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

        try {
            $deleted = $this->deletePreferenceById($idPref);

            return [
                'ok' => $deleted,
                'status' => $deleted ? 'success_deleted' : 'error_not_found',
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'message' => $e->getMessage(),
            ];
        }
    }
}
