<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $id_publication = $_GET['id_publication'] ?? 0;
    
    $sql = "SELECT * FROM commentaire WHERE id_publication = ? ORDER BY date_commentaire ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_publication]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'create') {
    $contenu = $_POST['contenu_commentaire'] ?? '';
    $id_publication = $_POST['id_publication'] ?? 0;
    $auteur_type = $_POST['auteur_type'] ?? '';
    $auteur_id = $_POST['auteur_id'] ?? '';

    $sql = "INSERT INTO commentaire (contenu_commentaire, id_publication, auteur_type, auteur_id) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$contenu, $id_publication, $auteur_type, $auteur_id]);
    
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update') {
    $id = $_POST['id_commentaire'] ?? 0;
    $contenu = $_POST['contenu_commentaire'] ?? '';

    $sql = "UPDATE commentaire SET contenu_commentaire = ? WHERE id_commentaire = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$contenu, $id]);
    
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id_commentaire'] ?? 0;

    $sql = "DELETE FROM commentaire WHERE id_commentaire = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$id]);
    
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'list_reactions') {
    try {
        $sql = "SELECT * FROM commentaire_reaction";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

if ($action === 'react') {
    $commentId = $_POST['commentaire_id'] ?? 0;
    $auteurType = $_POST['auteur_type'] ?? '';
    $auteurId = $_POST['auteur_id'] ?? '';
    $reaction = $_POST['reaction'] ?? '';

    try {
        // Delete existing reaction
        $stmt = $pdo->prepare("DELETE FROM commentaire_reaction WHERE commentaire_id = ? AND auteur_type = ? AND auteur_id = ?");
        $stmt->execute([$commentId, $auteurType, $auteurId]);
        
        // Insert new reaction if not removing
        if (!empty($reaction)) {
            $stmt = $pdo->prepare("INSERT INTO commentaire_reaction (commentaire_id, auteur_type, auteur_id, reaction) VALUES (?, ?, ?, ?)");
            $stmt->execute([$commentId, $auteurType, $auteurId, $reaction]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);
?>