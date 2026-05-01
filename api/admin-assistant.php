<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
$action = trim((string)($data['action'] ?? ''));

if ($action === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Action is required']);
    exit;
}

$pdo = Config::getConnexion();

try {
    switch ($action) {
        case 'get_counts':
            $offersCount = (int)$pdo->query('SELECT COUNT(*) FROM offre')->fetchColumn();
            $categoriesCount = (int)$pdo->query('SELECT COUNT(*) FROM categorie_offre')->fetchColumn();

            echo json_encode([
                'offers_count' => $offersCount,
                'categories_count' => $categoriesCount,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'create_category':
            $name = trim((string)($data['nom_categorie'] ?? ''));
            $description = trim((string)($data['description'] ?? ''));
            $icon = trim((string)($data['icone'] ?? ''));

            if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 50) {
                http_response_code(422);
                echo json_encode(['error' => 'Nom de categorie invalide (2 a 50 caracteres).']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO categorie_offre (nom_categorie, description, icone) VALUES (:nom, :description, :icone)'
            );
            $stmt->execute([
                ':nom' => $name,
                ':description' => $description !== '' ? $description : null,
                ':icone' => $icon !== '' ? $icon : null,
            ]);

            echo json_encode([
                'ok' => true,
                'id_categorie' => (int)$pdo->lastInsertId(),
                'nom_categorie' => $name,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'create_offer':
            $titre = trim((string)($data['titre'] ?? ''));
            $description = trim((string)($data['description'] ?? ''));
            $prix = (float)str_replace(',', '.', (string)($data['prix'] ?? '0'));
            $prixOriginal = (float)str_replace(',', '.', (string)($data['prix_original'] ?? '0'));
            $quantite = (int)($data['quantite'] ?? 0);
            $statut = trim((string)($data['statut'] ?? 'publiée'));
            $heureDebut = trim((string)($data['heure_debut'] ?? ''));
            $heureFin = trim((string)($data['heure_fin'] ?? ''));
            $photoUrl = trim((string)($data['photo_url'] ?? ''));

            $categoryId = isset($data['id_categorie']) ? (int)$data['id_categorie'] : 0;
            $categoryName = trim((string)($data['nom_categorie'] ?? ''));

            if ($categoryId <= 0 && $categoryName !== '') {
                $catStmt = $pdo->prepare('SELECT id_categorie FROM categorie_offre WHERE LOWER(nom_categorie) = LOWER(:nom) LIMIT 1');
                $catStmt->execute([':nom' => $categoryName]);
                $foundId = $catStmt->fetchColumn();
                if ($foundId !== false) {
                    $categoryId = (int)$foundId;
                }
            }

            $errors = [];
            if ($titre === '') {
                $errors[] = 'Titre obligatoire.';
            }
            if ($prix <= 0) {
                $errors[] = 'Prix invalide.';
            }
            if ($prixOriginal <= 0) {
                $errors[] = 'Prix original invalide.';
            }
            if ($prix >= $prixOriginal) {
                $errors[] = 'Le prix reduit doit etre inferieur au prix original.';
            }
            if ($quantite < 1) {
                $errors[] = 'Quantite invalide.';
            }
            if ($categoryId <= 0) {
                $errors[] = 'Categorie introuvable.';
            }

            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode(['error' => implode(' ', $errors)], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO offre
                 (titre, description, prix, prix_original, photo_url, quantite, heure_debut, heure_fin, statut, id_categorie, date_creation)
                 VALUES
                 (:titre, :description, :prix, :prix_original, :photo_url, :quantite, :heure_debut, :heure_fin, :statut, :id_categorie, NOW())'
            );

            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description !== '' ? $description : null,
                ':prix' => $prix,
                ':prix_original' => $prixOriginal,
                ':photo_url' => $photoUrl !== '' ? $photoUrl : null,
                ':quantite' => $quantite,
                ':heure_debut' => $heureDebut !== '' ? $heureDebut : null,
                ':heure_fin' => $heureFin !== '' ? $heureFin : null,
                ':statut' => $statut,
                ':id_categorie' => $categoryId,
            ]);

            echo json_encode([
                'ok' => true,
                'id_offre' => (int)$pdo->lastInsertId(),
                'titre' => $titre,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        default:
            http_response_code(422);
            echo json_encode(['error' => 'Unknown action']);
            exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
