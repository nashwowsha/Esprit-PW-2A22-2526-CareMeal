<?php
require_once dirname(__DIR__) . '/config/database.php';

class EventParticipation {
    private $conn;
    private $table_name = "PARTICIPATION";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // ================================================================
    // CRUD - CREATE : Inscrire un étudiant à un événement
    // ================================================================
    public function participate($evenement_id, $etudiant_id) {
        $sqlCap = "SELECT capacite_max, (SELECT COUNT(*) FROM PARTICIPATION WHERE evenement_id = :evenement_id) as inscrits FROM EVENEMENT WHERE id_evenement = :evenement_id";
        $stmtCap = $this->conn->prepare($sqlCap);
        $stmtCap->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);    
        $stmtCap->execute();
        $eventInfo = $stmtCap->fetch(PDO::FETCH_ASSOC);

        if(!$eventInfo) {
            return ['success' => false, 'message' => 'Événement introuvable.'];
        }

        if($eventInfo['inscrits'] >= $eventInfo['capacite_max']) {
            return ['success' => false, 'message' => 'L\'événement est complet.'];
        }

        $sqlCheck = "SELECT id_participation FROM PARTICIPATION WHERE evenement_id = :evenement_id AND etudiant_id = :etudiant_id";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);  
        $stmtCheck->bindParam(":etudiant_id", $etudiant_id, PDO::PARAM_INT);    
        $stmtCheck->execute();

        if($stmtCheck->rowCount() > 0) {
            return ['success' => false, 'message' => 'Vous êtes déjà inscrit.'];
        }

        $sql = "INSERT INTO " . $this->table_name . " (evenement_id, etudiant_id, date_inscription, statut) VALUES (:evenement_id, :etudiant_id, CURDATE(), 'Inscrit')";    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);       
        $stmt->bindParam(":etudiant_id", $etudiant_id, PDO::PARAM_INT);

        if($stmt->execute()) {
            return ['success' => true, 'message' => 'Inscription réussie.'];   
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
    }

    // ================================================================
    // CRUD - DELETE : Désinscrire un étudiant
    // ================================================================
    public function unparticipate($evenement_id, $etudiant_id) {
        $sql = "DELETE FROM " . $this->table_name . " WHERE evenement_id = :evenement_id AND etudiant_id = :etudiant_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);       
        $stmt->bindParam(":etudiant_id", $etudiant_id, PDO::PARAM_INT);
        if($stmt->execute()) {
            return ['success' => true, 'message' => 'Désinscription réussie.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la désinscription.'];
    }

    public function getStudentEvents($etudiant_id) {
        $sql = "SELECT e.*,
                (SELECT COUNT(*) FROM PARTICIPATION p WHERE p.evenement_id = e.id_evenement) as inscrits,
                (SELECT COUNT(*) FROM PARTICIPATION p2 WHERE p2.evenement_id = e.id_evenement AND p2.etudiant_id = :etudiant_id) as est_inscrit
                FROM EVENEMENT e
                WHERE e.statut_validation = 'Validé'
                ORDER BY e.date_evenement ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":etudiant_id", $etudiant_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================================================================
    // CRUD - READ avec JOINTURE (Pour le BackOffice)
    // EXIGENCE 1 : 2ème entité avec JOINTURE
    // ================================================================
    public function getAllParticipationsWithDetails() {
        $sql = "SELECT p.id_participation, p.date_inscription, p.statut, 
                       e.id_evenement, e.titre as event_title, e.date_evenement as event_date,
                       pr.nom as etudiant_nom, pr.prenom as etudiant_prenom, u.id as etudiant_id, u.email as etudiant_email
                FROM " . $this->table_name . " p
                JOIN EVENEMENT e ON p.evenement_id = e.id_evenement
                JOIN users u ON p.etudiant_id = u.id
                LEFT JOIN profiles pr ON u.id = pr.user_id
                ORDER BY p.date_inscription DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================================================================
    // CRUD - UPDATE : Changer le statut de la participation (Pour le BackOffice)
    // ================================================================
    public function updateStatus($evenement_id, $etudiant_id, $nouveau_statut) {
        $sql = "UPDATE " . $this->table_name . " 
                SET statut = :statut 
                WHERE evenement_id = :evenement_id AND etudiant_id = :etudiant_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":statut", $nouveau_statut);
        $stmt->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);
        $stmt->bindParam(":etudiant_id", $etudiant_id, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            return ['success' => true, 'message' => 'Statut mis à jour avec succès.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
    }
}
?>
