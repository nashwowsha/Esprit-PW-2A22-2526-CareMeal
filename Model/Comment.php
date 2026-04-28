<?php
class Comment {
    private $id_commentaire;
    private $contenu_commentaire;
    private $id_publication;
    private $date_commentaire;
    private $auteur_type;
    private $auteur_id;

    public function __construct($id_commentaire = null, $contenu_commentaire = null, $id_publication = null, $date_commentaire = null, $auteur_type = null, $auteur_id = null) {
        $this->id_commentaire = $id_commentaire;
        $this->contenu_commentaire = $contenu_commentaire;
        $this->id_publication = $id_publication;
        $this->date_commentaire = $date_commentaire;
        $this->auteur_type = $auteur_type;
        $this->auteur_id = $auteur_id;
    }

    // Getters
    public function getIdCommentaire() { return $this->id_commentaire; }
    public function getContenuCommentaire() { return $this->contenu_commentaire; }
    public function getIdPublication() { return $this->id_publication; }
    public function getDateCommentaire() { return $this->date_commentaire; }
    public function getAuteurType() { return $this->auteur_type; }
    public function getAuteurId() { return $this->auteur_id; }

    // Setters
    public function setIdCommentaire($id_commentaire) { $this->id_commentaire = $id_commentaire; }
    public function setContenuCommentaire($contenu_commentaire) { $this->contenu_commentaire = $contenu_commentaire; }
    public function setIdPublication($id_publication) { $this->id_publication = $id_publication; }
    public function setDateCommentaire($date_commentaire) { $this->date_commentaire = $date_commentaire; }
    public function setAuteurType($auteur_type) { $this->auteur_type = $auteur_type; }
    public function setAuteurId($auteur_id) { $this->auteur_id = $auteur_id; }
}
?>