<?php

class Event {
    private $id_evenement;
    private $titre;
    private $description;
    private $date_evenement;
    private $heure_debut;
    private $heure_fin;
    private $type_evenement;
    private $lieu;
    private $lien_online;
    private $capacite_max;
    private $statut;
    private $createur_type;
    private $createur_id;
    private $statut_validation;

    public function __construct($titre = null, $description = null, $date_evenement = null, $heure_debut = null, $heure_fin = null, $type_evenement = null, $lieu = null, $lien_online = null, $capacite_max = null, $statut = null, $createur_type = null, $createur_id = null, $statut_validation = null, $id_evenement = null, $image_url = null) {
        $this->titre = $titre;
        $this->description = $description;
        $this->date_evenement = $date_evenement;
        $this->heure_debut = $heure_debut;
        $this->heure_fin = $heure_fin;
        $this->type_evenement = $type_evenement;
        $this->lieu = $lieu;
        $this->lien_online = $lien_online;
        $this->capacite_max = $capacite_max;
        $this->statut = $statut;
        $this->createur_type = $createur_type;
        $this->createur_id = $createur_id;
        $this->statut_validation = $statut_validation;
        $this->id_evenement = $id_evenement;
    }

    public function getIdEvenement() { return $this->id_evenement; }
    public function setIdEvenement($id_evenement) { $this->id_evenement = $id_evenement; }

    public function getTitre() { return $this->titre; }
    public function setTitre($titre) { $this->titre = $titre; }

    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }

    public function getDateEvenement() { return $this->date_evenement; }
    public function setDateEvenement($date_evenement) { $this->date_evenement = $date_evenement; }

    public function getHeureDebut() { return $this->heure_debut; }
    public function setHeureDebut($heure_debut) { $this->heure_debut = $heure_debut; }

    public function getHeureFin() { return $this->heure_fin; }
    public function setHeureFin($heure_fin) { $this->heure_fin = $heure_fin; }

    public function getTypeEvenement() { return $this->type_evenement; }
    public function setTypeEvenement($type_evenement) { $this->type_evenement = $type_evenement; }

    public function getLieu() { return $this->lieu; }
    public function setLieu($lieu) { $this->lieu = $lieu; }

    public function getLienOnline() { return $this->lien_online; }
    public function setLienOnline($lien_online) { $this->lien_online = $lien_online; }

    public function getCapaciteMax() { return $this->capacite_max; }
    public function setCapaciteMax($capacite_max) { $this->capacite_max = $capacite_max; }

    public function getStatut() { return $this->statut; }
    public function setStatut($statut) { $this->statut = $statut; }

    public function getCreateurType() { return $this->createur_type; }
    public function setCreateurType($createur_type) { $this->createur_type = $createur_type; }

    public function getCreateurId() { return $this->createur_id; }
    public function setCreateurId($createur_id) { $this->createur_id = $createur_id; }

    public function getStatutValidation() { return $this->statut_validation; }
    public function setStatutValidation($statut_validation) { $this->statut_validation = $statut_validation; }
}
?>

