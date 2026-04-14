<?php
class Preference {
    private $idPref;
    private $regimeAlimentaire;
    private $allergies;
    private $localisation;
    private $dateDemande;
    private $idUser;

    public function __construct($idPref = null, $regimeAlimentaire = '', $allergies = '', $localisation = '', $dateDemande = null, $idUser = null) {
        $this->idPref = $idPref !== null ? (int)$idPref : null;
        $this->regimeAlimentaire = (string)$regimeAlimentaire;
        $this->allergies = (string)$allergies;
        $this->localisation = (string)$localisation;
        $this->dateDemande = $dateDemande !== null ? (string)$dateDemande : null;
        $this->idUser = $idUser !== null ? (int)$idUser : null;
    }

    public static function fromArray($row) {
        if (!is_array($row)) {
            return null;
        }

        return new self(
            isset($row['id_pref']) ? (int)$row['id_pref'] : null,
            $row['regime_alimentaire'] ?? '',
            $row['allergies'] ?? '',
            $row['localisation'] ?? '',
            $row['date_demande'] ?? null,
            isset($row['id_user']) ? (int)$row['id_user'] : null
        );
    }

    public function toArray() {
        return [
            'id_pref' => $this->idPref,
            'regime_alimentaire' => $this->regimeAlimentaire,
            'allergies' => $this->allergies,
            'localisation' => $this->localisation,
            'date_demande' => $this->dateDemande,
            'id_user' => $this->idUser,
        ];
    }

    public function getIdPref() {
        return $this->idPref;
    }

    public function setIdPref($idPref) {
        $this->idPref = $idPref !== null ? (int)$idPref : null;
    }

    public function getRegimeAlimentaire() {
        return $this->regimeAlimentaire;
    }

    public function setRegimeAlimentaire($regimeAlimentaire) {
        $this->regimeAlimentaire = (string)$regimeAlimentaire;
    }

    public function getAllergies() {
        return $this->allergies;
    }

    public function setAllergies($allergies) {
        $this->allergies = (string)$allergies;
    }

    public function getLocalisation() {
        return $this->localisation;
    }

    public function setLocalisation($localisation) {
        $this->localisation = (string)$localisation;
    }

    public function getDateDemande() {
        return $this->dateDemande;
    }

    public function setDateDemande($dateDemande) {
        $this->dateDemande = $dateDemande !== null ? (string)$dateDemande : null;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function setIdUser($idUser) {
        $this->idUser = $idUser !== null ? (int)$idUser : null;
    }
}
