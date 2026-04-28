<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
ensurePublicationSchema($pdo);

if ($action === 'list') {
    $sql = "SELECT * FROM publication ORDER BY date_publication DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['image_url'] = buildPublicationImageUrl($row['image_path'] ?? null);
    }
    unset($row);

    echo json_encode($rows);
    exit;
}

if ($action === 'create') {
    $contenu = trim($_POST['contenu_publication'] ?? '');
    $auteurType = normalizeAuthorType($_POST['auteur_type'] ?? '');
    $auteurId = trim((string)($_POST['auteur_id'] ?? ''));

    $uploadError = null;
    $imagePath = storePublicationImage($_FILES['image_publication'] ?? null, $uploadError);
    if ($uploadError !== null) {
        echo json_encode(['success' => false, 'message' => $uploadError]);
        exit;
    }

    if ($contenu === '' && empty($imagePath)) {
        echo json_encode(['success' => false, 'message' => "Le texte ou l'image est obligatoire."]);
        exit;
    }

    $sql = "INSERT INTO publication (contenu_publication, image_path, auteur_type, auteur_id) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$contenu, $imagePath, $auteurType, $auteurId]);

    echo json_encode([
        'success' => $success,
        'image_url' => $success ? buildPublicationImageUrl($imagePath) : null
    ]);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id_publication'] ?? 0);
    $contenu = trim($_POST['contenu_publication'] ?? '');
    $removeImage = (string)($_POST['remove_image'] ?? '0') === '1';

    $current = getPublicationById($pdo, $id);
    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Publication introuvable.']);
        exit;
    }

    $currentImagePath = $current['image_path'] ?? null;

    $uploadError = null;
    $newImagePath = storePublicationImage($_FILES['image_publication'] ?? null, $uploadError);
    if ($uploadError !== null) {
        echo json_encode(['success' => false, 'message' => $uploadError]);
        exit;
    }

    $finalImagePath = $currentImagePath;
    if ($removeImage) {
        $finalImagePath = null;
    }
    if (!empty($newImagePath)) {
        $finalImagePath = $newImagePath;
    }

    if ($contenu === '' && empty($finalImagePath)) {
        if (!empty($newImagePath)) {
            deletePublicationImage($newImagePath);
        }
        echo json_encode(['success' => false, 'message' => "Le texte ou l'image est obligatoire."]);
        exit;
    }

    $sql = "UPDATE publication SET contenu_publication = ?, image_path = ? WHERE id_publication = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$contenu, $finalImagePath, $id]);

    if ($success) {
        $mustDeleteOld = ($removeImage || !empty($newImagePath)) && !empty($currentImagePath) && $currentImagePath !== $finalImagePath;
        if ($mustDeleteOld) {
            deletePublicationImage($currentImagePath);
        }
    } elseif (!empty($newImagePath) && $newImagePath !== $currentImagePath) {
        deletePublicationImage($newImagePath);
    }

    echo json_encode([
        'success' => $success,
        'image_url' => $success ? buildPublicationImageUrl($finalImagePath) : null
    ]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id_publication'] ?? 0);
    $current = getPublicationById($pdo, $id);

    $sql = "DELETE FROM publication WHERE id_publication = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$id]);

    if ($success && $current && !empty($current['image_path'])) {
        deletePublicationImage($current['image_path']);
    }

    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'list_reactions') {
    try {
        $sql = "SELECT * FROM publication_reaction";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

if ($action === 'react') {
    $pubId = $_POST['publication_id'] ?? 0;
    $auteurType = $_POST['auteur_type'] ?? '';
    $auteurId = $_POST['auteur_id'] ?? '';
    $reaction = $_POST['reaction'] ?? '';

    try {
        $stmt = $pdo->prepare("DELETE FROM publication_reaction WHERE publication_id = ? AND auteur_type = ? AND auteur_id = ?");
        $stmt->execute([$pubId, $auteurType, $auteurId]);

        if (!empty($reaction)) {
            $stmt = $pdo->prepare("INSERT INTO publication_reaction (publication_id, auteur_type, auteur_id, reaction) VALUES (?, ?, ?, ?)");
            $stmt->execute([$pubId, $auteurType, $auteurId, $reaction]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'stats') {
    try {
        $totalPubs = (int)$pdo->query("SELECT COUNT(*) FROM publication")->fetchColumn();
        $totalComments = (int)$pdo->query("SELECT COUNT(*) FROM commentaire")->fetchColumn();
        $studentPubs = (int)$pdo->query("SELECT COUNT(*) FROM publication WHERE auteur_type = 'etudiant'")->fetchColumn();
        $partnerPubs = (int)$pdo->query("SELECT COUNT(*) FROM publication WHERE auteur_type = 'partenaire'")->fetchColumn();

        $studentComments = (int)$pdo->query("
            SELECT COUNT(c.id_commentaire) FROM commentaire c
            INNER JOIN publication p ON c.id_publication = p.id_publication
            WHERE p.auteur_type = 'etudiant'
        ")->fetchColumn();

        $partnerComments = (int)$pdo->query("
            SELECT COUNT(c.id_commentaire) FROM commentaire c
            INNER JOIN publication p ON c.id_publication = p.id_publication
            WHERE p.auteur_type = 'partenaire'
        ")->fetchColumn();

        $mostCommented = $pdo->query("
            SELECT p.id_publication, p.contenu_publication, COUNT(c.id_commentaire) AS nb_comments
            FROM publication p
            LEFT JOIN commentaire c ON p.id_publication = c.id_publication
            GROUP BY p.id_publication, p.contenu_publication
            ORDER BY nb_comments DESC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $mostReacted = null;
        try {
            $mostReacted = $pdo->query("
                SELECT p.id_publication, p.contenu_publication, COUNT(r.publication_id) AS nb_reactions
                FROM publication p
                LEFT JOIN publication_reaction r ON p.id_publication = r.publication_id
                GROUP BY p.id_publication, p.contenu_publication
                ORDER BY nb_reactions DESC
                LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ignored) {}

        $avgComments = $totalPubs > 0 ? round($totalComments / $totalPubs, 1) : 0;

        echo json_encode([
            'total_publications'       => $totalPubs,
            'total_commentaires'       => $totalComments,
            'publications_etudiants'   => $studentPubs,
            'publications_partenaires' => $partnerPubs,
            'commentaires_etudiants'   => $studentComments,
            'commentaires_partenaires' => $partnerComments,
            'most_commented'           => $mostCommented ?: null,
            'most_reacted'             => $mostReacted ?: null,
            'avg_comments'             => $avgComments
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);

function normalizeAuthorType(string $value): string {
    $raw = strtolower(trim($value));
    if ($raw === 'student' || $raw === 'etudiant') return 'etudiant';
    if ($raw === 'partner' || $raw === 'partenaire') return 'partenaire';
    if ($raw === 'admin' || $raw === 'administrateur') return 'admin';
    return 'etudiant';
}

function ensurePublicationSchema(PDO $pdo): void {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM publication LIKE 'image_path'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec("ALTER TABLE publication ADD COLUMN image_path VARCHAR(255) NULL AFTER contenu_publication");
        }
    } catch (PDOException $e) {
        // Keep legacy compatibility if schema migration fails.
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM publication LIKE 'auteur_type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && isset($column['Type'])) {
            $type = strtolower((string)$column['Type']);
            if (startsWith($type, 'enum(') && strpos($type, "'admin'") === false) {
                $pdo->exec("ALTER TABLE publication MODIFY auteur_type ENUM('etudiant','partenaire','admin') NOT NULL");
            }
        }
    } catch (PDOException $e) {
        // Keep app alive if DB user cannot alter enum.
    }
}

function getPublicationById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM publication WHERE id_publication = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function storePublicationImage(?array $file, ?string &$errorMessage): ?string {
    $errorMessage = null;

    if (!$file || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "L'envoi de l'image a echoue.";
        return null;
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpPath)) {
        $errorMessage = "Fichier image invalide.";
        return null;
    }

    $maxSize = 5 * 1024 * 1024;
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxSize) {
        $errorMessage = "L'image doit faire 5 Mo maximum.";
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpPath) : '';
    if ($finfo) finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    if (!isset($allowed[$mimeType])) {
        $errorMessage = "Format d'image non supporte (JPG, PNG, WEBP, GIF).";
        return null;
    }

    $uploadDir = dirname(__DIR__) . '/public/uploads/publications';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        $errorMessage = "Impossible de creer le dossier d'upload.";
        return null;
    }

    $extension = $allowed[$mimeType];
    $fileName = 'pub_' . bin2hex(random_bytes(10)) . '.' . $extension;
    $absolutePath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        $errorMessage = "Impossible d'enregistrer l'image.";
        return null;
    }

    return 'public/uploads/publications/' . $fileName;
}

function buildPublicationImageUrl(?string $storedPath): ?string {
    if (empty($storedPath)) return null;

    $clean = str_replace('\\', '/', trim($storedPath));
    if ($clean === '') return null;
    if (startsWith($clean, '/webEsprit/')) return $clean;

    $clean = ltrim($clean, '/');
    if (startsWith($clean, 'webEsprit/')) return '/' . $clean;

    return '/webEsprit/' . $clean;
}

function deletePublicationImage(?string $storedPath): void {
    if (empty($storedPath)) return;

    $clean = str_replace('\\', '/', trim($storedPath));
    $clean = ltrim($clean, '/');
    if (startsWith($clean, 'webEsprit/')) {
        $clean = substr($clean, strlen('webEsprit/'));
    }

    $prefix = 'public/uploads/publications/';
    if (!startsWith($clean, $prefix)) return;

    $absolutePath = dirname(__DIR__) . '/' . $clean;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function startsWith(string $value, string $prefix): bool {
    if ($prefix === '') return true;
    return strpos($value, $prefix) === 0;
}
?>
