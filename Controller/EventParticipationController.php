<?php
require_once __DIR__ . '/../Model/EventParticipation.php';

class EventParticipationController {
    private $model;

    public function __construct() {
        $this->model = new EventParticipation();
    }

    public function participate($evenement_id, $etudiant_id) {
        return $this->model->participate($evenement_id, $etudiant_id);
    }

    public function unparticipate($evenement_id, $etudiant_id) {
        return $this->model->unparticipate($evenement_id, $etudiant_id);
    }

    public function getStudentEvents($etudiant_id) {
        $events = $this->model->getStudentEvents($etudiant_id);

        // Add displayImg based on ID and physical files just like Admin and Partner panels
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
                $displayImg = "/projet2a22/assets/images/events/" . basename($possibleFiles[0]) . "?v=" . filemtime($possibleFiles[0]);
            }
            $event['image'] = $displayImg; // mapped to e.image in JS
        }
        
        return $events;
    }

    // CRUD - Admin Methods
    public function getAllParticipations() {
        return $this->model->getAllParticipationsWithDetails();
    }

    public function changeStatus($evenement_id, $etudiant_id, $statut) {
        return $this->model->updateStatus($evenement_id, $etudiant_id, $statut);
    }
}

if (basename($_SERVER['PHP_SELF']) == 'EventParticipationController.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    session_start();
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { $data = $_POST; }

    // Identify user
    $user_id = null;
    $user_role = null;
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
    } elseif (isset($data['student_id'])) {
        $user_id = intval($data['student_id']);
        $user_role = 'student'; // Fallback for old requests
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé, veuillez vous reconnecter.']);
        exit;
    }

    $action = $data['action'] ?? '';
    $evenement_id = isset($data['evenement_id']) ? intval($data['evenement_id']) : 0;
    
    $controller = new EventParticipationController();

    if ($action === 'get_events' && $user_role === 'student') {
        $events = $controller->getStudentEvents($user_id);
        echo json_encode(['success' => true, 'events' => $events]);
    } elseif ($action === 'participate' && $user_role === 'student') {
        if ($evenement_id > 0) {
            echo json_encode($controller->participate($evenement_id, $user_id));
        } else {
            echo json_encode(['success' => false, 'message' => 'ID événement invalide.']);
        }
    } elseif ($action === 'unparticipate' && $user_role === 'student') {
         if ($evenement_id > 0) {
            echo json_encode($controller->unparticipate($evenement_id, $user_id));
        } else {
            echo json_encode(['success' => false, 'message' => 'ID événement invalide.']);
        }
    } 
    // Admin routes
    elseif ($action === 'get_all_participations' && $user_role === 'admin') {
        $participations = $controller->getAllParticipations();
        echo json_encode(['success' => true, 'participations' => $participations]);
    }
    elseif ($action === 'update_status' && $user_role === 'admin') {
        $etudiant_id = isset($data['etudiant_id']) ? intval($data['etudiant_id']) : 0;
        $statut = isset($data['statut']) ? $data['statut'] : '';
        if ($evenement_id > 0 && $etudiant_id > 0 && $statut !== '') {
            echo json_encode($controller->changeStatus($evenement_id, $etudiant_id, $statut));
        } else {
            echo json_encode(['success' => false, 'message' => 'Données invalides.']);
        }
    }
    elseif ($action === 'delete_participation' && $user_role === 'admin') {
        $etudiant_id = isset($data['etudiant_id']) ? intval($data['etudiant_id']) : 0;
        if ($evenement_id > 0 && $etudiant_id > 0) {
            echo json_encode($controller->unparticipate($evenement_id, $etudiant_id));
        } else {
            echo json_encode(['success' => false, 'message' => 'ID invalides.']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Action invalide ou droits insuffisants.']);
    }
}
?>