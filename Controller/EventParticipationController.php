<?php
require_once __DIR__ . '/../Model/EventParticipation.php';
require_once __DIR__ . '/../config/database.php';

class EventParticipationController {

    private $conn;
    private $table_name = "participation";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    //===============
    // Ajouter une participation
    //=====
    public function participate(EventParticipation $participation) {
        $evenement_id = $participation->getEvenementId();
        $etudiant_id  = $participation->getEtudiantId();

        // Vérifier capacité de l'événement
        $sqlCap = "SELECT capacite_max, 
                   (SELECT COUNT(*) FROM " . $this->table_name . "
                    WHERE evenement_id = e.id_evenement) as inscrits
                   FROM EVENEMENT e
                   WHERE id_evenement = :evenement_id";
        $stmtCap = $this->conn->prepare($sqlCap);
        $stmtCap->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);
        $stmtCap->execute();
        $eventInfo = $stmtCap->fetch(PDO::FETCH_ASSOC);

        if (!$eventInfo) {
            return ['success' => false, 'message' => 'Événement introuvable ou non disponible.'];
        }

        if ((int)$eventInfo['inscrits'] >= (int)$eventInfo['capacite_max']) {
            return ['success' => false, 'message' => 'L\'événement est complet.'];
        }

        // Vérifier doublon par etudiant + evenement (ignorer les annulés)
        $sqlCheck = "SELECT id_participation FROM " . $this->table_name . "
                     WHERE evenement_id = :evenement_id
                     AND (etudiant_id = :etudiant_id OR etudiant_id IS NULL)
                     AND statut != 'Annulé'";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);
        $stmtCheck->bindParam(":etudiant_id",  $etudiant_id, PDO::PARAM_INT);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            return ['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement.'];
        }

        // Vérifier s'il y avait une inscription annulée à réactiver
        $sqlCheckAnnule = "SELECT id_participation FROM " . $this->table_name . "
                           WHERE evenement_id = :evenement_id AND etudiant_id = :etudiant_id AND statut = 'Annulé'";
        $stmtCheckAnnule = $this->conn->prepare($sqlCheckAnnule);
        $stmtCheckAnnule->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);
        $stmtCheckAnnule->bindParam(":etudiant_id",  $etudiant_id, PDO::PARAM_INT);
        $stmtCheckAnnule->execute();
        
        $date   = $participation->getDateInscription();
        $statut = $participation->getStatut();

        if ($stmtCheckAnnule->rowCount() > 0) {
            // Réactiver l'inscription existante
            $rowAnnule = $stmtCheckAnnule->fetch(PDO::FETCH_ASSOC);
            $idPart = $rowAnnule['id_participation'];
            
            $sql = "UPDATE " . $this->table_name . " 
                    SET statut = :statut, date_inscription = :date_inscription 
                    WHERE id_participation = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":statut",           $statut);
            $stmt->bindParam(":date_inscription", $date);
            $stmt->bindParam(":id",               $idPart, PDO::PARAM_INT);
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Inscription réactivée avec succès.'];
            }
            return ['success' => false, 'message' => 'Erreur lors de la réinscription.'];
        }

        // Insertion
        $sql = "INSERT INTO " . $this->table_name . "
                (evenement_id, etudiant_id, date_inscription, statut)
                VALUES (:evenement_id, :etudiant_id, :date_inscription, :statut)";
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(":evenement_id",     $evenement_id, PDO::PARAM_INT);
        $stmt->bindParam(":etudiant_id",      $etudiant_id, PDO::PARAM_INT);
        $stmt->bindParam(":date_inscription", $date);
        $stmt->bindParam(":statut",           $statut);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Inscription réussie.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
    }

    //===============
    // Supprimer une participation
    //=====
    public function unparticipate($id) {
        $sql = "DELETE FROM " . $this->table_name . " WHERE id_participation = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Désinscription réussie.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la désinscription.'];
    }

    //===============
    // Désinscription par email + evenement (côté étudiant)
    //=====
    public function unparticipateByEmailAndEvent($etudiant_id, $evenement_id) {
        $sql = "UPDATE " . $this->table_name . " 
                SET statut = 'Annulé'
                WHERE etudiant_id = :etudiant_id AND evenement_id = :evenement_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":etudiant_id",  $etudiant_id, PDO::PARAM_INT);
        $stmt->bindParam(":evenement_id", $evenement_id, PDO::PARAM_INT);
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Désinscription réussie.'];
        }
        return ['success' => false, 'message' => 'Inscription introuvable.'];
    }

    //===============
    // Événements disponibles pour un étudiant (avec statut d'inscription)
    //=====
    public function getStudentEvents($etudiant_id) {
        $sql = "SELECT e.*,
                (SELECT COUNT(*) FROM " . $this->table_name . " p
                 WHERE p.evenement_id = e.id_evenement
                 AND p.statut != 'Annulé') as inscrits,
                (SELECT COUNT(*) FROM " . $this->table_name . " p2
                 WHERE p2.evenement_id = e.id_evenement
                 AND p2.etudiant_id = :etudiant_id
                 AND p2.statut != 'Annulé') as est_inscrit
                FROM EVENEMENT e
                ORDER BY e.date_evenement ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":etudiant_id", $etudiant_id, PDO::PARAM_INT);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as &$event) {
            $displayImg = "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800";
            $uploadDir = __DIR__ . '/../assets/images/events/';
            $possibleFiles = glob($uploadDir . 'event_' . $event['id_evenement'] . '.*');
            if (!empty($possibleFiles)) {
                $displayImg = "/assets/images/events/" . basename($possibleFiles[0]) . "?v=" . filemtime($possibleFiles[0]);
            }
            $event['image'] = $displayImg;
        }
        return $events;
    }

    //===============
    //===============
    // Participants d'un événement (jointure via evenement_id)
    //=====
    public function getParticipantsByEvent($evenement_id) {
        $sql = "SELECT p.id_participation as id, p.date_inscription, p.statut,
                       u.email, u.id as student_id,
                       pr.nom, pr.prenom, pr.telephone, pr.ecole as universite, pr.annee_etude,
                       e.titre AS event_title, e.date_evenement AS event_date,
                       e.type_evenement AS event_type, e.lieu AS event_lieu
                FROM " . $this->table_name . " p
                INNER JOIN EVENEMENT e ON p.evenement_id = e.id_evenement
                INNER JOIN users u ON p.etudiant_id = u.id
                LEFT JOIN profiles pr ON u.id = pr.user_id
                WHERE p.evenement_id = :evenement_id
                ORDER BY p.date_inscription ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //===============
    // Toutes les participations (jointure)
    //=====
    public function getAllParticipations() {
        $sql = "SELECT p.id_participation as id, p.date_inscription, p.statut,
                       u.email, u.id as student_id,
                       pr.nom, pr.prenom, pr.telephone, pr.ecole as universite, pr.annee_etude,
                       e.id_evenement, e.titre as event_title, e.type_evenement AS event_type,
                       e.lieu AS event_lieu, e.date_evenement AS event_date
                FROM " . $this->table_name . " p
                INNER JOIN EVENEMENT e ON p.evenement_id = e.id_evenement
                INNER JOIN users u ON p.etudiant_id = u.id
                LEFT JOIN profiles pr ON u.id = pr.user_id
                ORDER BY p.date_inscription DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //===============
    // Modifier le statut d'une participation
    //=====
    public function changeStatus($id, $statut) {
        $statuts_autorises = ['Inscrit', 'Présent', 'Absent', 'Annulé'];
        if (!in_array($statut, $statuts_autorises)) {
            return ['success' => false, 'message' => 'Statut invalide.'];
        }
        $sql = "UPDATE " . $this->table_name . " SET statut = :statut WHERE id_participation = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":statut", $statut);
        $stmt->bindParam(":id",     $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Statut mis à jour.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
    }
}

// ── Handler HTTP ─────────────────────────────────────────────────────────────
if (basename($_SERVER['PHP_SELF']) == 'EventParticipationController.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    session_start();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { $data = $_POST; }

    // Identifier l'utilisateur
    $user_id   = null;
    $user_role = null;
    $user_email = null;

    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        $user_id    = $_SESSION['user_id'];
        $user_role  = $_SESSION['user_role'];
        $user_email = $_SESSION['user_email'] ?? null;
    } elseif (isset($data['student_id'])) {
        $user_id    = intval($data['student_id']);
        $user_role  = 'student';
    }

    // Fallback SPA admin : session PHP absente (autre onglet / cookie) mais user_id + rôle fournis — vérification en base
    if (!$user_id && !empty($data['user_id']) && !empty($data['user_role'])) {
        $candidateId = intval($data['user_id']);
        $candidateRole = strtolower(trim((string) $data['user_role']));
        if ($candidateId > 0 && $candidateRole === 'admin') {
            $dbAuth = new Database();
            $connAuth = $dbAuth->getConnection();
            $stAuth = $connAuth->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
            $stAuth->execute([$candidateId]);
            $rowAuth = $stAuth->fetch(PDO::FETCH_ASSOC);
            if ($rowAuth && strtolower(trim((string) $rowAuth['role'])) === 'admin') {
                $user_id = (int) $rowAuth['id'];
                $user_role = 'admin';
            }
        }
    }

    if ($user_role !== null) {
        $user_role = strtolower(trim((string) $user_role));
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé, veuillez vous reconnecter.']);
        exit;
    }

    $action = $data['action'] ?? '';
    $controller = new EventParticipationController();

    // ── Routes étudiant ───────────────────────────────────────────
    if ($action === 'get_events' && $user_role === 'student') {
        $etudiant_id = $_SESSION['user_id'] ?? $user_id ?? 0;
        $events = $controller->getStudentEvents($etudiant_id);
        echo json_encode(['success' => true, 'events' => $events]);

    } elseif ($action === 'participate' && $user_role === 'student') {
        $evenement_id = isset($data['evenement_id']) ? intval($data['evenement_id']) : 0;
        if ($evenement_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID événement invalide.']); exit;
        }

        // ── Validation serveur ────────────────────────────────────
        $nom        = isset($data['nom'])        ? trim(strip_tags($data['nom']))        : '';
        $prenom     = isset($data['prenom'])      ? trim(strip_tags($data['prenom']))      : '';
        $email      = isset($data['email'])       ? trim(strip_tags($data['email']))       : '';
        $telephone  = isset($data['telephone'])   ? trim(strip_tags($data['telephone']))   : '';
        $universite = isset($data['universite'])  ? trim(strip_tags($data['universite']))  : '';
        $annee      = isset($data['annee'])       ? trim(strip_tags($data['annee']))       : '';

        if (empty($nom) || strlen($nom) < 2 || preg_match('/[0-9]/', $nom)) {
            echo json_encode(['success' => false, 'message' => 'Nom invalide.']); exit;
        }
        if (empty($prenom) || strlen($prenom) < 2 || preg_match('/[0-9]/', $prenom)) {
            echo json_encode(['success' => false, 'message' => 'Prénom invalide.']); exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email invalide.']); exit;
        }
        if (!empty($telephone)) {
            $digitsOnly = preg_replace('/[\s\-().+]/', '', $telephone);
            if (preg_match('/[a-zA-Z]/', $telephone) || !preg_match('/^\d{8}$/', $digitsOnly)) {
                echo json_encode(['success' => false, 'message' => 'Téléphone invalide (8 chiffres requis).']); exit;
            }
        }
        if (empty($universite)) { echo json_encode(['success' => false, 'message' => 'Université requise.']); exit; }
        if (empty($annee))      { echo json_encode(['success' => false, 'message' => 'Année d\'étude requise.']); exit; }

        $etudiant_id = $data['student_id'] ?? $_SESSION['user_id'] ?? 0;

        $participation = new EventParticipation();
        $participation->setEvenementId($evenement_id);
        $participation->setEtudiantId($etudiant_id);
        $participation->setStatut('Inscrit');
        
        // Optionnel : enregistrement des variables en log / cache car ces informations viennent de profil et de user
        //$participation->setNom($nom);
        //$participation->setPrenom($prenom);
        
        echo json_encode($controller->participate($participation));

    } elseif ($action === 'unparticipate' && $user_role === 'student') {
        $evenement_id = $data['evenement_id'] ?? 0;
        $etudiant_id  = $data['student_id'] ?? $_SESSION['user_id'] ?? 0;
        
        echo json_encode($controller->unparticipateByEmailAndEvent($etudiant_id, $evenement_id));

    } elseif ($action === 'cancel_participation' && $user_role === 'student') {
        $evenement_id = $data['evenement_id'] ?? 0;
        $etudiant_id  = $data['student_id'] ?? $_SESSION['user_id'] ?? 0;

        echo json_encode($controller->unparticipateByEmailAndEvent($etudiant_id, $evenement_id));

    // ── Routes admin ──────────────────────────────────────────────
    } elseif ($action === 'get_all_participations' && $user_role === 'admin') {
        echo json_encode(['success' => true, 'participations' => $controller->getAllParticipations()]);

    } elseif ($action === 'get_participants_by_event' && $user_role === 'admin') {
        $ev_id = isset($data['evenement_id']) ? intval($data['evenement_id']) : 0;
        if ($ev_id > 0) {
            echo json_encode(['success' => true, 'participants' => $controller->getParticipantsByEvent($ev_id)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID événement invalide.']);
        }

    } elseif ($action === 'update_status' && $user_role === 'admin') {
        $id     = isset($data['id']) ? intval($data['id']) : 0;
        $statut = isset($data['statut']) ? $data['statut'] : '';
        if ($id > 0 && $statut !== '') {
            echo json_encode($controller->changeStatus($id, $statut));
        } else {
            echo json_encode(['success' => false, 'message' => 'Données invalides.']);
        }

    } elseif ($action === 'delete_participation' && $user_role === 'admin') {
        $id = $data['id'] ?? 0;
        if ($id > 0) {
            echo json_encode($controller->unparticipate($id));
        } else {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Action invalide ou droits insuffisants.']);
    }
}
?>

