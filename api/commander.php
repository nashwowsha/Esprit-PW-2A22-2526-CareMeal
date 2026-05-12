<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function readJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respondJson($payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function findCommanderOrder(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM commander WHERE id_commande = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Throwable $e) {
    respondJson(['success' => false, 'error' => 'Database connection failed'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id_offre']) && $_GET['id_offre'] !== '') {
            $idOffre = (int)$_GET['id_offre'];
            if ($idOffre <= 0) {
                respondJson(['success' => false, 'error' => 'id_offre invalide'], 400);
            }

            $stmt = $pdo->prepare('SELECT * FROM commander WHERE id_offre = :id_offre ORDER BY date_commande DESC');
            $stmt->execute([':id_offre' => $idOffre]);
            respondJson($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        $stmt = $pdo->query('SELECT * FROM commander ORDER BY date_commande DESC');
        respondJson($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($method === 'POST') {
        $data = array_merge($_POST, readJsonInput());

        $idOffre = isset($data['id_offre']) ? (int)$data['id_offre'] : 0;
        $nom = trim((string)($data['nom'] ?? ''));
        $prenom = trim((string)($data['prenom'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $telephone = trim((string)($data['telephone'] ?? ''));
        $quantite = isset($data['quantite']) ? (int)$data['quantite'] : 1;
        $statut = trim((string)($data['statut'] ?? 'en_attente'));

        if ($idOffre <= 0) {
            respondJson(['success' => false, 'error' => 'id_offre requis'], 422);
        }
        if ($nom === '' || $prenom === '' || $email === '' || $telephone === '') {
            respondJson(['success' => false, 'error' => 'nom, prenom, email et telephone sont requis'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respondJson(['success' => false, 'error' => 'email invalide'], 422);
        }
        if ($quantite <= 0) {
            respondJson(['success' => false, 'error' => 'quantite invalide'], 422);
        }

        $allowedStatuses = ['en_attente', 'confirmee', 'annulee', 'recuperee'];
        if (!in_array($statut, $allowedStatuses, true)) {
            respondJson(['success' => false, 'error' => 'statut invalide'], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO commander (id_offre, nom, prenom, email, telephone, quantite, statut)
             VALUES (:id_offre, :nom, :prenom, :email, :telephone, :quantite, :statut)'
        );
        $stmt->execute([
            ':id_offre' => $idOffre,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':telephone' => $telephone,
            ':quantite' => $quantite,
            ':statut' => $statut,
        ]);

        $id = (int)$pdo->lastInsertId();
        respondJson(['success' => true, 'order' => findCommanderOrder($pdo, $id)], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            respondJson(['success' => false, 'error' => 'id requis'], 400);
        }

        $data = readJsonInput();
        $statut = trim((string)($data['statut'] ?? ''));
        $allowedStatuses = ['en_attente', 'confirmee', 'annulee', 'recuperee'];
        if (!in_array($statut, $allowedStatuses, true)) {
            respondJson(['success' => false, 'error' => 'statut invalide'], 422);
        }

        if (!findCommanderOrder($pdo, $id)) {
            respondJson(['success' => false, 'error' => 'Commande introuvable'], 404);
        }

        $stmt = $pdo->prepare('UPDATE commander SET statut = :statut WHERE id_commande = :id');
        $stmt->execute([':statut' => $statut, ':id' => $id]);

        respondJson(['success' => true, 'order' => findCommanderOrder($pdo, $id)]);
    }

    respondJson(['success' => false, 'error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    respondJson(['success' => false, 'error' => $e->getMessage()], 400);
}
