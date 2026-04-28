<?php
class Publication {
    private $id_publication;
    private $contenu_publication;
    private $image_path;
    private $date_publication;
    private $auteur_type;
    private $auteur_id;

    public function __construct($id_publication = null, $contenu_publication = null, $image_path = null, $date_publication = null, $auteur_type = null, $auteur_id = null) {
        $this->id_publication = $id_publication;
        $this->contenu_publication = $contenu_publication;
        $this->image_path = $image_path;
        $this->date_publication = $date_publication;
        $this->auteur_type = $auteur_type;
        $this->auteur_id = $auteur_id;
    }

    // Getters
    public function getIdPublication() { return $this->id_publication; }
    public function getContenuPublication() { return $this->contenu_publication; }
    public function getImagePath() { return $this->image_path; }
    public function getDatePublication() { return $this->date_publication; }
    public function getAuteurType() { return $this->auteur_type; }
    public function getAuteurId() { return $this->auteur_id; }

    // Setters
    public function setIdPublication($id_publication) { $this->id_publication = $id_publication; }
    public function setContenuPublication($contenu_publication) { $this->contenu_publication = $contenu_publication; }
    public function setImagePath($image_path) { $this->image_path = $image_path; }
    public function setDatePublication($date_publication) { $this->date_publication = $date_publication; }
    public function setAuteurType($auteur_type) { $this->auteur_type = $auteur_type; }
    public function setAuteurId($auteur_id) { $this->auteur_id = $auteur_id; }
}
?>
