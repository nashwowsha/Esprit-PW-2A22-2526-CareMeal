<?php

class EventParticipation {
    private $id;
    private $nom_evenement;
    private $nom;
    private $prenom;
    private $email;
    private $telephone;
    private $universite;
    private $annee_etude;
    private $date_inscription;
    private $statut;

    // Garder ces deux pour la logique métier (vérif doublons, capacité)
    private $evenement_id;
    private $etudiant_id;

    public function __construct($evenement_id = null, $etudiant_id = null, $statut = 'Inscrit') {
        $this->evenement_id    = $evenement_id;
        $this->etudiant_id     = $etudiant_id;
        $this->statut          = $statut;
        $this->date_inscription = date('Y-m-d');
    }

    // Getters
    public function getId()              { return $this->id; }
    public function getNomEvenement()    { return $this->nom_evenement; }
    public function getNom()             { return $this->nom; }
    public function getPrenom()          { return $this->prenom; }
    public function getEmail()           { return $this->email; }
    public function getTelephone()       { return $this->telephone; }
    public function getUniversite()      { return $this->universite; }
    public function getAnneeEtude()      { return $this->annee_etude; }
    public function getDateInscription() { return $this->date_inscription; }
    public function getStatut()          { return $this->statut; }
    public function getEvenementId()     { return $this->evenement_id; }
    public function getEtudiantId()      { return $this->etudiant_id; }

    // Setters
    public function setId($v)              { $this->id = $v; }
    public function setNomEvenement($v)    { $this->nom_evenement = $v; }
    public function setNom($v)             { $this->nom = $v; }
    public function setPrenom($v)          { $this->prenom = $v; }
    public function setEmail($v)           { $this->email = $v; }
    public function setTelephone($v)       { $this->telephone = $v; }
    public function setUniversite($v)      { $this->universite = $v; }
    public function setAnneeEtude($v)      { $this->annee_etude = $v; }
    public function setDateInscription($v) { $this->date_inscription = $v; }
    public function setStatut($v)          { $this->statut = $v; }
    public function setEvenementId($v)     { $this->evenement_id = $v; }
    public function setEtudiantId($v)      { $this->etudiant_id = $v; }
}
?>
