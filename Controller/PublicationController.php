<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Publication.php';

$publicationModel = new Publication($pdo);

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode($publicationModel->getAll());
    exit;
}

if ($action === 'create') {
    $contenu = $_POST['contenu_publication'] ?? '';
    $auteur_type = $_POST['auteur_type'] ?? '';
    $auteur_id = $_POST['auteur_id'] ?? '';

    $success = $publicationModel->create($contenu, $auteur_type, $auteur_id);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update') {
    $id = $_POST['id_publication'] ?? 0;
    $contenu = $_POST['contenu_publication'] ?? '';

    $success = $publicationModel->update($id, $contenu);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id_publication'] ?? 0;

    $success = $publicationModel->delete($id);
    echo json_encode(['success' => $success]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);
?>