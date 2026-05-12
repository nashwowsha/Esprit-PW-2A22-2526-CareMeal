<?php
class Publication {
    private $id_publication;
    private $contenu_publication;
    private $image_path;
    private $date_publication;
    private $auteur_type;
    private $auteur_id;
    private $moderation_status;
    private $moderation_risk;
    private $moderation_reason;
    private $moderation_provider;

    public function __construct($id_publication = null, $contenu_publication = null, $image_path = null, $date_publication = null, $auteur_type = null, $auteur_id = null, $moderation_status = 'approved', $moderation_risk = null, $moderation_reason = null, $moderation_provider = 'gemini') {
        $this->id_publication = $id_publication;
        $this->contenu_publication = $contenu_publication;
        $this->image_path = $image_path;
        $this->date_publication = $date_publication;
        $this->auteur_type = $auteur_type;
        $this->auteur_id = $auteur_id;
        $this->moderation_status = $moderation_status;
        $this->moderation_risk = $moderation_risk;
        $this->moderation_reason = $moderation_reason;
        $this->moderation_provider = $moderation_provider;
    }

    // Getters
    public function getIdPublication() { return $this->id_publication; }
    public function getContenuPublication() { return $this->contenu_publication; }
    public function getImagePath() { return $this->image_path; }
    public function getDatePublication() { return $this->date_publication; }
    public function getAuteurType() { return $this->auteur_type; }
    public function getAuteurId() { return $this->auteur_id; }
    public function getModerationStatus() { return $this->moderation_status; }
    public function getModerationRisk() { return $this->moderation_risk; }
    public function getModerationReason() { return $this->moderation_reason; }
    public function getModerationProvider() { return $this->moderation_provider; }

    // Setters
    public function setIdPublication($id_publication) { $this->id_publication = $id_publication; }
    public function setContenuPublication($contenu_publication) { $this->contenu_publication = $contenu_publication; }
    public function setImagePath($image_path) { $this->image_path = $image_path; }
    public function setDatePublication($date_publication) { $this->date_publication = $date_publication; }
    public function setAuteurType($auteur_type) { $this->auteur_type = $auteur_type; }
    public function setAuteurId($auteur_id) { $this->auteur_id = $auteur_id; }
    public function setModerationStatus($moderation_status) { $this->moderation_status = $moderation_status; }
    public function setModerationRisk($moderation_risk) { $this->moderation_risk = $moderation_risk; }
    public function setModerationReason($moderation_reason) { $this->moderation_reason = $moderation_reason; }
    public function setModerationProvider($moderation_provider) { $this->moderation_provider = $moderation_provider; }
}
?>
