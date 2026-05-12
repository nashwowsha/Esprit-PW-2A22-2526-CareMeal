<?php
require_once __DIR__ . '/../Model/Event.php';
require_once __DIR__ . '/../config/database.php';

class EventController {

    private $conn;
    private $table_name = "EVENEMENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    //===============
    // CRUD ajout evenement
    //=====
    public function create(Event $event) {
        $query = "INSERT INTO " . $this->table_name . " 
        (titre, description, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation) 
        VALUES 
        (:titre, :description, :date_evenement, :heure_debut, :heure_fin, :type_evenement, :lieu, :lien_online, :capacite_max, 'Planifie', :createur_type, :createur_id, 'En attente')";

        $stmt = $this->conn->prepare($query);

        $titre = $event->getTitre();
        $description = $event->getDescription();
        $date_evenement = $event->getDateEvenement();
        $heure_debut = $event->getHeureDebut();
        $heure_fin = $event->getHeureFin();
        $type_evenement = $event->getTypeEvenement();
        $lieu = $event->getLieu();
        $lien_online = $event->getLienOnline();
        $capacite_max = $event->getCapaciteMax();
        $createur_type = $event->getCreateurType();
        $createur_id = $event->getCreateurId();

        $stmt->bindParam(":titre", $titre);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":date_evenement", $date_evenement);
        $stmt->bindParam(":heure_debut", $heure_debut);
        $stmt->bindParam(":heure_fin", $heure_fin);
        $stmt->bindParam(":type_evenement", $type_evenement);
        $stmt->bindParam(":lieu", $lieu);
        $stmt->bindParam(":lien_online", $lien_online);
        $stmt->bindParam(":capacite_max", $capacite_max, PDO::PARAM_INT);
        $stmt->bindParam(":createur_type", $createur_type);
        $stmt->bindParam(":createur_id", $createur_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    //===============
    // CRUD afficher les evenements du partenaire
    //=====
    public function getPartnerEvents($partner_id) {
        $query = "SELECT e.*,
                  (SELECT COUNT(*) 
                   FROM participation p 
                   WHERE p.evenement_id = e.id_evenement
                  ) as inscrits 
                  FROM " . $this->table_name . " e 
                  WHERE createur_type = 'Partenaire' AND createur_id = :partner_id 
                  ORDER BY date_evenement DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //===============
    // CRUD suppression evenement
    //=====
    public function deleteEvent($event_id, $partner_id) {
        // La table PARTICIPATION utilise normalement ON DELETE CASCADE avec evenement_id

        $query = "DELETE FROM " . $this->table_name . " WHERE id_evenement = :event_id AND createur_type = 'Partenaire' AND createur_id = :partner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id, PDO::PARAM_INT);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    //===============
    // CRUD afficher un evenement par id
    //=====
    public function getEventById($event_id, $partner_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_evenement = :event_id AND createur_type = 'Partenaire' AND createur_id = :partner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id, PDO::PARAM_INT);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //===============
    // CRUD modification evenement
    //=====
    public function updateEvent($event_id, $partner_id, Event $event) {
        $query = "UPDATE " . $this->table_name . " 
                  SET titre = :titre, description = :description, date_evenement = :date_evenement, 
                      heure_debut = :heure_debut, heure_fin = :heure_fin, type_evenement = :type_evenement, 
                      lieu = :lieu, lien_online = :lien_online, capacite_max = :capacite_max, 
                      statut_validation = 'En attente'
                  WHERE id_evenement = :event_id AND createur_type = 'Partenaire' AND createur_id = :partner_id";
        
        $stmt = $this->conn->prepare($query);
        
        $titre = $event->getTitre();
        $description = $event->getDescription();
        $date_evenement = $event->getDateEvenement();
        $heure_debut = $event->getHeureDebut();
        $heure_fin = $event->getHeureFin();
        $type_evenement = $event->getTypeEvenement();
        $lieu = $event->getLieu();
        $lien_online = $event->getLienOnline();
        $capacite_max = $event->getCapaciteMax();

        $stmt->bindParam(":titre", $titre);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":date_evenement", $date_evenement);
        $stmt->bindParam(":heure_debut", $heure_debut);
        $stmt->bindParam(":heure_fin", $heure_fin);
        $stmt->bindParam(":type_evenement", $type_evenement);
        $stmt->bindParam(":lieu", $lieu);
        $stmt->bindParam(":lien_online", $lien_online);
        $stmt->bindParam(":capacite_max", $capacite_max, PDO::PARAM_INT);
        $stmt->bindParam(":event_id", $event_id, PDO::PARAM_INT);
        $stmt->bindParam(":partner_id", $partner_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    //===============
    // CRUD afficher tous les evenements
    //=====
    public function getAllEventsWithPartner() {
        $sql = "SELECT e.*, u.email as partner_email, p.nom_entreprise as partner_name,
                (SELECT COUNT(*) FROM PARTICIPATION par WHERE par.evenement_id = e.id_evenement) as inscrits
                FROM " . $this->table_name . " e
                LEFT JOIN users u ON e.createur_id = u.id
                LEFT JOIN profiles p ON u.id = p.user_id
                ORDER BY e.date_evenement DESC, e.heure_debut DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //===============
    // CRUD valide evenement
    //=====
    public function setValidationStatus($id_evenement, $statut, $motif_refus = null) {
        // VÃ©rifier si la colonne motif_refus existe avant de l'utiliser
        try {
            $sql = "UPDATE " . $this->table_name . " SET statut_validation = :statut, motif_refus = :motif_refus WHERE id_evenement = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":statut", $statut);
            $stmt->bindParam(":motif_refus", $motif_refus);
            $stmt->bindParam(":id", $id_evenement, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Si motif_refus n'existe pas, on fait la mise Ã  jour sans cette colonne
            $sql = "UPDATE " . $this->table_name . " SET statut_validation = :statut WHERE id_evenement = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":statut", $statut);
            $stmt->bindParam(":id", $id_evenement, PDO::PARAM_INT);
            return $stmt->execute();
        }
    }
}

if (basename($_SERVER['PHP_SELF']) == 'EventController.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { $data = $_POST; }

    $action = $data['action'] ?? '';
    $controller = new EventController();

    if ($action === 'get_all') {
        $events = $controller->getAllEventsWithPartner();
        
        // Include displayImg logic exactly as partner does so JS admin can use it
        foreach ($events as &$event) {
            $displayImg = "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800";
            $seeds = ["vegetables", "cooking", "gardening", "food", "market", "farm"];
            $seed = $seeds[$event['id_evenement'] % count($seeds)];
            if($seed == "gardening") $displayImg = "https://images.unsplash.com/photo-1416879598555-220b8fa017ae?auto=format&fit=crop&q=80&w=800";
            if($seed == "cooking") $displayImg = "https://images.unsplash.com/photo-1556910103-1c02745a872e?auto=format&fit=crop&q=80&w=800";
            if($seed == "food") $displayImg = "https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&q=80&w=800";
            
            $uploadDir = __DIR__ . '/../assets/images/events/';
            $possibleFiles = glob($uploadDir . 'event_' . $event['id_evenement'] . '.*');
            if (!empty($possibleFiles)) {
                $displayImg = "/assets/images/events/" . basename($possibleFiles[0]) . "?v=" . filemtime($possibleFiles[0]);
            }
            $event['displayImg'] = $displayImg;
        }
        
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        echo json_encode(["success" => true, "events" => $events], $jsonFlags);
        exit;
    }

    if ($action === 'validate' && !empty($data['id_evenement'])) {
        $ok = $controller->setValidationStatus($data['id_evenement'], 'Valide');
        echo json_encode(["success" => $ok]);
        exit;
    }

    if ($action === 'reject' && !empty($data['id_evenement'])) {
        $motif = $data['raison'] ?? null;
        $ok = $controller->setValidationStatus($data['id_evenement'], 'Rejete', $motif);
        echo json_encode(["success" => $ok]);
        exit;
    }

    if ($action === 'add' || $action === 'update') {
        $event = new Event(
            $data['titre'] ?? '',
            $data['description'] ?? '',
            $data['date_evenement'] ?? '',
            $data['heure_debut'] ?? '',
            $data['heure_fin'] ?? '',
            $data['type_evenement'] ?? '',
            $data['lieu'] ?? null,
            $data['lien_online'] ?? null,
            $data['capacite_max'] ?? null,
            null,
            $data['createur_type'] ?? 'Partenaire',
            $data['createur_id'] ?? null
        );
        
        // Backend Validation sans HTML5
        $errors = [];
        if (trim($event->getTitre()) === '') $errors[] = "Le titre est obligatoire.";
        if (strlen($event->getTitre()) < 3) $errors[] = "Le titre doit faire au moins 3 caractÃ¨res.";
        if (trim($event->getDateEvenement()) === '') $errors[] = "La date est obligatoire.";
        if (trim($event->getHeureDebut()) === '') $errors[] = "L'heure de dÃ©but est obligatoire.";
        if (trim($event->getHeureFin()) === '') $errors[] = "L'heure de fin est obligatoire.";
        if (!is_numeric($event->getCapaciteMax()) || $event->getCapaciteMax() <= 0) $errors[] = "La capacitÃ© doit Ãªtre un nombre positif.";
        
        $type = $event->getTypeEvenement();
        if ($type === 'PrÃ©sentiel' && trim($event->getLieu()) === '') {
            $errors[] = "Le lieu est exigÃ© pour un Ã©vÃ©nement prÃ©sentiel.";
        }
        if ($type === 'En ligne' && trim($event->getLienOnline()) === '') {
            $errors[] = "Le lien est exigÃ© pour un Ã©vÃ©nement en ligne.";
        }

        if (!empty($errors)) {
            echo json_encode(["success" => false, "message" => implode(" ", $errors)]);
            exit;
        }

        if ($action === 'add') {
            $ok = $controller->create($event);
            $msg = "Ã‰vÃ©nement ajoutÃ© avec succÃ¨s";
        } else {
            $id_event = intval($data['id_evenement']);
            $partner_id = intval($data['createur_id']);
            $ok = $controller->updateEvent($id_event, $partner_id, $event);
            $msg = "Ã‰vÃ©nement modifiÃ© avec succÃ¨s";
        }

        if ($ok) {
            echo json_encode(["success" => true, "message" => $msg]);
        } else {
            echo json_encode(["success" => false, "message" => "Erreur lors de l'opÃ©ration en base de donnÃ©es"]);
        }
        exit;
    }

    if ($action === 'delete' && !empty($data['id_evenement']) && !empty($data['partner_id'])) {
        $ok = $controller->deleteEvent($data['id_evenement'], $data['partner_id']);
        echo json_encode(["success" => $ok, "message" => $ok ? "SupprimÃ© avec succÃ¨s" : "Erreur de suppression"]);
        exit;
    }

    if ($action === 'get_partner_events' && !empty($data['partner_id'])) {
        $events = $controller->getPartnerEvents($data['partner_id']);
        foreach ($events as &$event) {
            $uploadDir = __DIR__ . '/../assets/images/events/';
            $possibleFiles = glob($uploadDir . 'event_' . $event['id_evenement'] . '.*');
            if (!empty($possibleFiles)) {
                 $event['displayImg'] = '/assets/images/events/' . basename($possibleFiles[0]) . '?v=' . filemtime($possibleFiles[0]);
            }
        }
        echo json_encode(['success' => true, 'events' => $events]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Action inconnue"]);
    exit;
}

