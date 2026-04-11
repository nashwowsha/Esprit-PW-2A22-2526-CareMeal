<?php
require_once __DIR__ . '/../Model/Preference.php';

class PreferenceController {
    private function normalizeRegime($source) {
        if (isset($source['regimes']) && is_array($source['regimes'])) {
            $clean = array_values(array_filter(array_map('trim', $source['regimes']), static function ($item) {
                return $item !== '';
            }));

            return implode(', ', $clean);
        }

        if (isset($source['regime_alimentaire'])) {
            return trim((string)$source['regime_alimentaire']);
        }

        return '';
    }

    private function normalizePayload($source) {
        $idUser = isset($source['id_user']) ? (int)$source['id_user'] : 0;

        return [
            'regime_alimentaire' => $this->normalizeRegime($source),
            'allergies' => trim((string)($source['allergies'] ?? '')),
            'localisation' => trim((string)($source['localisation'] ?? '')),
            'id_user' => $idUser,
            'date_demande' => date('Y-m-d H:i:s'),
        ];
    }

    private function validatePayload($payload) {
        $errors = [];

        if ($payload['regime_alimentaire'] === '') {
            $errors[] = 'regime_alimentaire';
        }

        if ($payload['allergies'] === '') {
            $errors[] = 'allergies';
        }

        if ($payload['localisation'] === '') {
            $errors[] = 'localisation';
        }

        if ($payload['id_user'] <= 0) {
            $errors[] = 'id_user';
        }

        return $errors;
    }

    public function getAllForAdmin() {
        return Preference::listAllWithUser();
    }

    public function getByUserId($idUser) {
        return Preference::getByUserId((int)$idUser);
    }

    public function getListByUserId($idUser) {
        return Preference::listByUserId((int)$idUser);
    }

    public function studentUpsert($source) {
        $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;
        $payload = $this->normalizePayload($source);
        $errors = $this->validatePayload($payload);

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_missing_fields',
                'id_user' => $payload['id_user'],
                'errors' => $errors,
            ];
        }

        try {
            if ($idPref > 0) {
                $existing = Preference::getById($idPref);
                if (!$existing) {
                    return [
                        'ok' => false,
                        'status' => 'error_not_found',
                        'id_user' => $payload['id_user'],
                    ];
                }

                if ((int)$existing['id_user'] !== $payload['id_user']) {
                    return [
                        'ok' => false,
                        'status' => 'error_forbidden',
                        'id_user' => $payload['id_user'],
                    ];
                }

                $updated = Preference::updateById(
                    $idPref,
                    $payload['regime_alimentaire'],
                    $payload['allergies'],
                    $payload['localisation'],
                    $payload['id_user'],
                    $payload['date_demande']
                );

                if (!$updated && !Preference::getById($idPref)) {
                    return [
                        'ok' => false,
                        'status' => 'error_not_found',
                        'id_user' => $payload['id_user'],
                    ];
                }

                return [
                    'ok' => true,
                    'status' => 'success_updated',
                    'id_user' => $payload['id_user'],
                    'id_pref' => $idPref,
                ];
            }

            $newId = Preference::create(
                $payload['regime_alimentaire'],
                $payload['allergies'],
                $payload['localisation'],
                $payload['id_user'],
                $payload['date_demande']
            );

            return [
                'ok' => true,
                'status' => 'success_created',
                'id_user' => $payload['id_user'],
                'id_pref' => $newId,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'status' => 'error_db',
                'id_user' => $payload['id_user'],
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

        $existing = Preference::getById($idPref);
        if (!$existing) {
            return [
                'ok' => false,
                'status' => 'error_not_found',
                'id_user' => $idUser,
            ];
        }

        if ((int)$existing['id_user'] !== $idUser) {
            return [
                'ok' => false,
                'status' => 'error_forbidden',
                'id_user' => $idUser,
            ];
        }

        try {
            Preference::deleteById($idPref);

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

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_missing_fields',
                'errors' => $errors,
            ];
        }

        try {
            $idPref = Preference::create(
                $payload['regime_alimentaire'],
                $payload['allergies'],
                $payload['localisation'],
                $payload['id_user'],
                $payload['date_demande']
            );

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

        if ($idPref <= 0) {
            $errors[] = 'id_pref';
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'status' => 'error_missing_fields',
                'errors' => $errors,
            ];
        }

        try {
            $updated = Preference::updateById(
                $idPref,
                $payload['regime_alimentaire'],
                $payload['allergies'],
                $payload['localisation'],
                $payload['id_user'],
                $payload['date_demande']
            );

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
            $deleted = Preference::deleteById($idPref);

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

