<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private $conn;
    private $table_name = "EVENEMENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
        (titre, description, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation) 
        VALUES 
        (:titre, :description, :date_evenement, :heure_debut, :heure_fin, :type_evenement, :lieu, :lien_online, :capacite_max, 'Planifié', :createur_type, :createur_id, 'En attente')";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":titre", $data['titre']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":date_evenement", $data['date_evenement']);
        $stmt->bindParam(":heure_debut", $data['heure_debut']);
        $stmt->bindParam(":heure_fin", $data['heure_fin']);
        $stmt->bindParam(":type_evenement", $data['type_evenement']);
        $stmt->bindParam(":lieu", $data['lieu']);
        $stmt->bindParam(":lien_online", $data['lien_online']);
        $stmt->bindParam(":capacite_max", $data['capacite_max'], PDO::PARAM_INT);
        $stmt->bindParam(":createur_type", $data['createur_type']);
        $stmt->bindParam(":createur_id", $data['createur_id'], PDO::PARAM_INT);

        return $stmt->execute();
    }
    
    public function getPartnerEvents($partner_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE createur_type = 'Partenaire' AND createur_id = :partner_id ORDER BY date_evenement DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteEvent($event_id, $partner_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_evenement = :event_id AND createur_type = 'Partenaire' AND createur_id = :partner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id, PDO::PARAM_INT);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getEventById($event_id, $partner_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_evenement = :event_id AND createur_type = 'Partenaire' AND createur_id = :partner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id, PDO::PARAM_INT);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateEvent($event_id, $partner_id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET titre = :titre, description = :description, date_evenement = :date_evenement, 
                      heure_debut = :heure_debut, heure_fin = :heure_fin, type_evenement = :type_evenement, 
                      lieu = :lieu, lien_online = :lien_online, capacite_max = :capacite_max, 
                      statut_validation = 'En attente'
                  WHERE id_evenement = :event_id AND createur_type = 'Partenaire' AND createur_id = :partner_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":titre", $data['titre']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":date_evenement", $data['date_evenement']);
        $stmt->bindParam(":heure_debut", $data['heure_debut']);
        $stmt->bindParam(":heure_fin", $data['heure_fin']);
        $stmt->bindParam(":type_evenement", $data['type_evenement']);
        $stmt->bindParam(":lieu", $data['lieu']);
        $stmt->bindParam(":lien_online", $data['lien_online']);
        $stmt->bindParam(":capacite_max", $data['capacite_max'], PDO::PARAM_INT);
        $stmt->bindParam(":event_id", $event_id, PDO::PARAM_INT);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // ADMIN: Récupérer tous les événements avec infos partenaire
    public function getAllEventsWithPartner() {
        $sql = "SELECT e.*, u.email as partner_email, p.nom_entreprise as partner_name
                FROM EVENEMENT e
                LEFT JOIN users u ON e.createur_id = u.id
                LEFT JOIN profiles p ON u.id = p.user_id
                ORDER BY e.date_evenement DESC, e.heure_debut DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ADMIN: Valider/Rejeter un événement
    public function setValidationStatus($id_evenement, $statut) {
        $sql = "UPDATE EVENEMENT SET statut_validation = :statut WHERE id_evenement = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":statut", $statut);
        $stmt->bindParam(":id", $id_evenement, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>