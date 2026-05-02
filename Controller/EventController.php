<?php
require_once __DIR__ . '/../Model/Event.php';
require_once __DIR__ . '/../Model/User.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { $data = $_POST; }

$action = $data['action'] ?? '';
$eventModel = new Event();
$userModel = new User();

if ($action === 'get_all') {
    // Récupérer tous les événements avec infos partenaire
    $events = $eventModel->getAllEventsWithPartner();
    echo json_encode(["success" => true, "events" => $events]);
    exit;
}

if ($action === 'validate' && !empty($data['id_evenement'])) {
    $ok = $eventModel->setValidationStatus($data['id_evenement'], 'Validé');
    echo json_encode(["success" => $ok]);
    exit;
}

if ($action === 'reject' && !empty($data['id_evenement'])) {
    $ok = $eventModel->setValidationStatus($data['id_evenement'], 'Rejeté');
    echo json_encode(["success" => $ok]);
    exit;
}

echo json_encode(["success" => false, "message" => "Action inconnue"]);
