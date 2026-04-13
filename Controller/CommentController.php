<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Comment.php';

$commentModel = new Comment($pdo);

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $id_publication = $_GET['id_publication'] ?? 0;
    echo json_encode($commentModel->getByPublication($id_publication));
    exit;
}

if ($action === 'create') {
    $contenu = $_POST['contenu_commentaire'] ?? '';
    $id_publication = $_POST['id_publication'] ?? 0;
    $auteur_type = $_POST['auteur_type'] ?? '';
    $auteur_id = $_POST['auteur_id'] ?? '';

    $success = $commentModel->create($contenu, $id_publication, $auteur_type, $auteur_id);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update') {
    $id = $_POST['id_commentaire'] ?? 0;
    $contenu = $_POST['contenu_commentaire'] ?? '';

    $success = $commentModel->update($id, $contenu);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id_commentaire'] ?? 0;

    $success = $commentModel->delete($id);
    echo json_encode(['success' => $success]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);
?>