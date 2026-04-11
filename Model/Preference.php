<?php
require_once __DIR__ . '/../config/database.php';

class Preference {
    public static function create($regime, $allergies, $localisation, $idUser, $dateDemande = null) {
        $db = config::getConnexion();
        $dateDemande = $dateDemande ?: date('Y-m-d H:i:s');

        $sql = 'INSERT INTO preference (regime_alimentaire, allergies, localisation, date_demande, id_user)
                VALUES (:regime, :allergies, :localisation, :date_demande, :id_user)';

        $query = $db->prepare($sql);
        $query->execute([
            'regime' => $regime,
            'allergies' => $allergies,
            'localisation' => $localisation,
            'date_demande' => $dateDemande,
            'id_user' => $idUser,
        ]);

        return (int)$db->lastInsertId();
    }

    public static function upsertByUserId($regime, $allergies, $localisation, $idUser, $dateDemande = null) {
        $db = config::getConnexion();
        $dateDemande = $dateDemande ?: date('Y-m-d H:i:s');
        $existing = self::getByUserId($idUser);

        if ($existing) {
            $sql = 'UPDATE preference
                    SET regime_alimentaire = :regime,
                        allergies = :allergies,
                        localisation = :localisation,
                        date_demande = :date_demande
                    WHERE id_user = :id_user';

            $query = $db->prepare($sql);
            $query->execute([
                'regime' => $regime,
                'allergies' => $allergies,
                'localisation' => $localisation,
                'date_demande' => $dateDemande,
                'id_user' => $idUser,
            ]);

            return (int)$existing['id_pref'];
        }

        return self::create($regime, $allergies, $localisation, $idUser, $dateDemande);
    }

    public static function updateById($idPref, $regime, $allergies, $localisation, $idUser, $dateDemande = null) {
        $db = config::getConnexion();
        $dateDemande = $dateDemande ?: date('Y-m-d H:i:s');

        $sql = 'UPDATE preference
                SET regime_alimentaire = :regime,
                    allergies = :allergies,
                    localisation = :localisation,
                    date_demande = :date_demande,
                    id_user = :id_user
                WHERE id_pref = :id_pref';

        $query = $db->prepare($sql);
        $query->execute([
            'id_pref' => $idPref,
            'regime' => $regime,
            'allergies' => $allergies,
            'localisation' => $localisation,
            'date_demande' => $dateDemande,
            'id_user' => $idUser,
        ]);

        return $query->rowCount() > 0;
    }

    public static function deleteById($idPref) {
        $db = config::getConnexion();
        $query = $db->prepare('DELETE FROM preference WHERE id_pref = :id_pref');
        $query->execute(['id_pref' => $idPref]);

        return $query->rowCount() > 0;
    }

    public static function getById($idPref) {
        $db = config::getConnexion();
        $query = $db->prepare('SELECT * FROM preference WHERE id_pref = :id_pref LIMIT 1');
        $query->execute(['id_pref' => $idPref]);

        return $query->fetch() ?: null;
    }

    public static function getByUserId($idUser) {
        $db = config::getConnexion();
        $query = $db->prepare('SELECT * FROM preference WHERE id_user = :id_user ORDER BY id_pref DESC LIMIT 1');
        $query->execute(['id_user' => $idUser]);

        return $query->fetch() ?: null;
    }

    public static function listByUserId($idUser) {
        $db = config::getConnexion();
        $query = $db->prepare('SELECT * FROM preference WHERE id_user = :id_user ORDER BY id_pref DESC');
        $query->execute(['id_user' => $idUser]);

        return $query->fetchAll();
    }

    public static function listAllWithUser() {
        $db = config::getConnexion();
        $sql = 'SELECT p.*, u.nom AS user_nom, u.email AS user_email, u.role AS user_role
                FROM preference p
                LEFT JOIN utilisateur u ON u.id = p.id_user
                ORDER BY p.id_pref DESC';

        return $db->query($sql)->fetchAll();
    }

    // Backward-compatible methods
    public static function listPreferences() {
        $db = config::getConnexion();

        return $db->query('SELECT * FROM preference ORDER BY id_pref DESC')->fetchAll();
    }

    public static function deletePreference($idPref) {
        return self::deleteById($idPref);
    }

    public static function getPreferenceByUser($idUser) {
        return self::getByUserId($idUser);
    }
}

