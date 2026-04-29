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

        // Vérifier capacité et récupérer le titre de l'événement
        $sqlCap = "SELECT capacite_max, titre,
                   (SELECT COUNT(*) FROM " . $this->table_name . "
                    WHERE nom_evenement = e.titre) as inscrits
                   FROM EVENEMENT e
                   WHERE id_evenement = :evenement_id
                   AND statut_validation = 'Validé'";
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

        // Vérifier doublon par email + nom_evenement (ignorer les annulés)
        $sqlCheck = "SELECT id FROM " . $this->table_name . "
                     WHERE nom_evenement = :nom_evenement
                     AND email = :email
                     AND statut != 'Annulé'";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $nom_ev = $eventInfo['titre'];
        $email  = $participation->getEmail();
        $stmtCheck->bindParam(":nom_evenement", $nom_ev);
        $stmtCheck->bindParam(":email",         $email);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            return ['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement.'];
        }

        // Insertion
        $sql = "INSERT INTO " . $this->table_name . "
                (nom_evenement, nom, prenom, email, telephone, universite, annee_etude, date_inscription, statut)
                VALUES (:nom_evenement, :nom, :prenom, :email, :telephone, :universite, :annee_etude, :date_inscription, :statut)";
        $stmt = $this->conn->prepare($sql);

        $nom_evenement = $eventInfo['titre'];
        $nom           = $participation->getNom();
        $prenom        = $participation->getPrenom();
        $telephone     = $participation->getTelephone();
        $universite    = $participation->getUniversite();
        $annee         = $participation->getAnneeEtude();
        $date          = $participation->getDateInscription();
        $statut        = $participation->getStatut();

        $stmt->bindParam(":nom_evenement",   $nom_evenement);
        $stmt->bindParam(":nom",             $nom);
        $stmt->bindParam(":prenom",          $prenom);
        $stmt->bindParam(":email",           $email);
        $stmt->bindParam(":telephone",       $telephone);
        $stmt->bindParam(":universite",      $universite);
        $stmt->bindParam(":annee_etude",     $annee);
        $stmt->bindParam(":date_inscription",$date);
        $stmt->bindParam(":statut",          $statut);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Inscription réussie.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
    }

    //===============
    // Supprimer une participation
    //=====
    public function unparticipate($id) {
        $sql = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Désinscription réussie.'];
        }
        return ['success' => false, 'message' => 'Participation introuvable.'];
    }

    //===============
    // Désinscription par email + evenement (côté étudiant)
    //=====
    public function unparticipateByEmailAndEvent($email, $nom_evenement) {
        $sql = "DELETE FROM " . $this->table_name . "
                WHERE email = :email AND nom_evenement = :nom_evenement";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email",         $email);
        $stmt->bindParam(":nom_evenement", $nom_evenement);
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Désinscription réussie.'];
        }
        return ['success' => false, 'message' => 'Inscription introuvable.'];
    }

    //===============
    // Événements disponibles pour un étudiant (avec statut d'inscription)
    //=====
    public function getStudentEvents($email) {
        $sql = "SELECT e.*,
                (SELECT COUNT(*) FROM " . $this->table_name . " p
                 WHERE p.nom_evenement = e.titre
                 AND p.statut != 'Annulé') as inscrits,
                (SELECT COUNT(*) FROM " . $this->table_name . " p2
                 WHERE p2.nom_evenement = e.titre
                 AND p2.email = :email
                 AND p2.statut != 'Annulé') as est_inscrit
                FROM EVENEMENT e
                WHERE e.statut_validation = 'Validé'
                ORDER BY e.date_evenement ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as &$event) {
            $displayImg = "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800";
            $uploadDir = __DIR__ . '/../assets/images/events/';
            $possibleFiles = glob($uploadDir . 'event_' . $event['id_evenement'] . '.*');
            if (!empty($possibleFiles)) {
                $displayImg = "/projet2a22/assets/images/events/" . basename($possibleFiles[0]) . "?v=" . filemtime($possibleFiles[0]);
            }
            $event['image'] = $displayImg;
        }
        return $events;
    }

    //===============
    // Participants d'un événement (jointure via nom_evenement)
    //=====
    public function getParticipantsByEvent($evenement_id) {
        $sql = "SELECT p.id, p.nom_evenement, p.nom, p.prenom, p.email,
                       p.telephone, p.universite, p.annee_etude,
                       p.date_inscription, p.statut,
                       e.titre AS event_title, e.date_evenement AS event_date,
                       e.type_evenement AS event_type, e.lieu AS event_lieu
                FROM " . $this->table_name . " p
                INNER JOIN EVENEMENT e ON p.nom_evenement = e.titre
                WHERE e.id_evenement = :evenement_id
                ORDER BY p.date_inscription ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //===============
    // Toutes les participations (jointure PARTICIPATION ⟶ EVENEMENT)
    //=====
    public function getAllParticipations() {
        $sql = "SELECT p.id, p.nom_evenement, p.nom, p.prenom, p.email,
                       p.telephone, p.universite, p.annee_etude,
                       p.date_inscription, p.statut,
                       e.id_evenement, e.type_evenement AS event_type,
                       e.lieu AS event_lieu, e.date_evenement AS event_date
                FROM " . $this->table_name . " p
                INNER JOIN EVENEMENT e ON p.nom_evenement = e.titre
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
        $sql = "UPDATE " . $this->table_name . " SET statut = :statut WHERE id = :id";
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

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé, veuillez vous reconnecter.']);
        exit;
    }

    $action = $data['action'] ?? '';
    $controller = new EventParticipationController();

    // ── Routes étudiant ───────────────────────────────────────────
    if ($action === 'get_events' && $user_role === 'student') {
        // Récupérer l'email de l'utilisateur connecté
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $row['email'] ?? ($data['email'] ?? '');
        $events = $controller->getStudentEvents($email);
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

        $participation = new EventParticipation($evenement_id, $user_id);
        $participation->setNom($nom);
        $participation->setPrenom($prenom);
        $participation->setEmail($email);
        $participation->setTelephone($telephone);
        $participation->setUniversite($universite);
        $participation->setAnneeEtude($annee);
        echo json_encode($controller->participate($participation));

    } elseif ($action === 'unparticipate' && $user_role === 'student') {
        $evenement_id = isset($data['evenement_id']) ? intval($data['evenement_id']) : 0;
        if ($evenement_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID événement invalide.']); exit;
        }
        // Récupérer email + titre pour la désinscription
        $database = new Database();
        $conn = $database->getConnection();
        $stmtU = $conn->prepare("SELECT email FROM users WHERE id = :id");
        $stmtU->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmtU->execute();
        $rowU = $stmtU->fetch(PDO::FETCH_ASSOC);
        $email = $rowU['email'] ?? '';

        $stmtE = $conn->prepare("SELECT titre FROM EVENEMENT WHERE id_evenement = :id");
        $stmtE->bindParam(':id', $evenement_id, PDO::PARAM_INT);
        $stmtE->execute();
        $rowE = $stmtE->fetch(PDO::FETCH_ASSOC);
        $titre = $rowE['titre'] ?? '';

        echo json_encode($controller->unparticipateByEmailAndEvent($email, $titre));

    } elseif ($action === 'cancel_participation' && $user_role === 'student') {
        $evenement_id = isset($data['evenement_id']) ? intval($data['evenement_id']) : 0;
        if ($evenement_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID événement invalide.']); exit;
        }

        $database = new Database();
        $conn = $database->getConnection();

        // Utiliser l'email envoyé par le JS, sinon le récupérer depuis la DB
        $email = isset($data['email']) && !empty($data['email'])
            ? trim($data['email'])
            : '';

        if (empty($email)) {
            $stmtU = $conn->prepare("SELECT email FROM users WHERE id = :id");
            $stmtU->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmtU->execute();
            $rowU = $stmtU->fetch(PDO::FETCH_ASSOC);
            $email = $rowU['email'] ?? '';
        }

        // Récupérer le titre de l'événement
        $stmtE = $conn->prepare("SELECT titre FROM EVENEMENT WHERE id_evenement = :id");
        $stmtE->bindParam(':id', $evenement_id, PDO::PARAM_INT);
        $stmtE->execute();
        $rowE = $stmtE->fetch(PDO::FETCH_ASSOC);
        $titre = $rowE['titre'] ?? '';

        if (empty($email) || empty($titre)) {
            echo json_encode(['success' => false, 'message' => "Données introuvables. email=$email titre=$titre"]); exit;
        }

        // Mettre le statut à "Annulé" au lieu de supprimer
        $sql = "UPDATE participation SET statut = 'Annulé'
                WHERE email = :email AND nom_evenement = :titre AND statut != 'Annulé'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':titre', $titre);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Désinscription effectuée.']);
        } else {
            // Vérifier si la ligne existe
            $chk = $conn->prepare("SELECT id, statut FROM participation WHERE email = :email AND nom_evenement = :titre");
            $chk->bindParam(':email', $email);
            $chk->bindParam(':titre', $titre);
            $chk->execute();
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo json_encode(['success' => false, 'message' => 'Inscription déjà annulée (statut: ' . $row['statut'] . ').']);
            } else {
                echo json_encode(['success' => false, 'message' => "Inscription introuvable pour email=$email et événement=$titre"]);
            }
        }

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
        $id = isset($data['id']) ? intval($data['id']) : 0;
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
