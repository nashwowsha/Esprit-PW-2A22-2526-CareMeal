<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        echo '{"error":"json_encode_failed"}';
        exit;
    }
    echo $json;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(405, ['error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
$action = trim((string)($data['action'] ?? ''));

if ($action === '') {
    send_json(422, ['error' => 'Action is required']);
}

$pdo = Config::getConnexion();

try {
    switch ($action) {
        case 'get_counts': {
            $offersCount = (int)$pdo->query('SELECT COUNT(*) FROM offre')->fetchColumn();
            $categoriesCount = (int)$pdo->query('SELECT COUNT(*) FROM categorie_offre')->fetchColumn();
            send_json(200, [
                'offers_count' => $offersCount,
                'categories_count' => $categoriesCount,
            ]);
        }

        case 'create_category': {
            $name = trim((string)($data['nom_categorie'] ?? ''));
            $description = trim((string)($data['description'] ?? ''));
            $icon = trim((string)($data['icone'] ?? ''));

            if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 50) {
                send_json(422, ['error' => 'Nom de categorie invalide (2 a 50 caracteres).']);
            }

            $stmt = $pdo->prepare('INSERT INTO categorie_offre (nom_categorie, description, icone) VALUES (:nom, :description, :icone)');
            $stmt->execute([
                ':nom' => $name,
                ':description' => $description !== '' ? $description : null,
                ':icone' => $icon !== '' ? $icon : null,
            ]);

            send_json(200, [
                'ok' => true,
                'id_categorie' => (int)$pdo->lastInsertId(),
                'nom_categorie' => $name,
            ]);
        }

        case 'update_category': {
            $id = isset($data['id_categorie']) ? (int)$data['id_categorie'] : 0;
            $sourceName = trim((string)($data['nom_categorie_source'] ?? ''));
            $newName = trim((string)($data['nom_categorie'] ?? ''));
            $description = trim((string)($data['description'] ?? ''));
            $icon = trim((string)($data['icone'] ?? ''));
            $hasName = array_key_exists('nom_categorie', $data);
            $hasDescription = array_key_exists('description', $data);
            $hasIcon = array_key_exists('icone', $data);

            if ($id <= 0 && $sourceName !== '') {
                $s = $pdo->prepare('SELECT id_categorie FROM categorie_offre WHERE LOWER(nom_categorie)=LOWER(:nom) LIMIT 1');
                $s->execute([':nom' => $sourceName]);
                $found = $s->fetchColumn();
                if ($found !== false) {
                    $id = (int)$found;
                }
            }

            if ($id <= 0) {
                send_json(422, ['error' => 'Categorie introuvable.']);
            }

            $currentStmt = $pdo->prepare('SELECT nom_categorie, description, icone FROM categorie_offre WHERE id_categorie=:id LIMIT 1');
            $currentStmt->execute([':id' => $id]);
            $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                send_json(422, ['error' => 'Categorie introuvable.']);
            }

            $finalName = $hasName ? $newName : (string)($current['nom_categorie'] ?? '');
            $finalDescription = $hasDescription
                ? ($description !== '' ? $description : null)
                : ($current['description'] ?? null);
            $finalIcon = $hasIcon
                ? ($icon !== '' ? $icon : null)
                : ($current['icone'] ?? null);

            if ($finalName === '' || mb_strlen($finalName) < 2 || mb_strlen($finalName) > 50) {
                send_json(422, ['error' => 'Nouveau nom de categorie invalide.']);
            }

            $stmt = $pdo->prepare('UPDATE categorie_offre SET nom_categorie=:nom, description=:description, icone=:icone WHERE id_categorie=:id');
            $stmt->execute([
                ':nom' => $finalName,
                ':description' => $finalDescription,
                ':icone' => $finalIcon,
                ':id' => $id,
            ]);

            send_json(200, [
                'ok' => true,
                'id_categorie' => $id,
                'nom_categorie' => $finalName,
            ]);
        }

        case 'delete_category': {
            $id = isset($data['id_categorie']) ? (int)$data['id_categorie'] : 0;
            $sourceName = trim((string)($data['nom_categorie'] ?? ''));

            if ($id <= 0 && $sourceName !== '') {
                $s = $pdo->prepare('SELECT id_categorie FROM categorie_offre WHERE LOWER(nom_categorie)=LOWER(:nom) LIMIT 1');
                $s->execute([':nom' => $sourceName]);
                $found = $s->fetchColumn();
                if ($found !== false) {
                    $id = (int)$found;
                }
            }

            if ($id <= 0) {
                send_json(422, ['error' => 'Categorie introuvable.']);
            }

            $pdo->prepare('DELETE FROM offre WHERE id_categorie = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM categorie_offre WHERE id_categorie = ?')->execute([$id]);

            send_json(200, [
                'ok' => true,
                'deleted_id_categorie' => $id,
            ]);
        }

        case 'create_offer': {
            $titre = trim((string)($data['titre'] ?? ''));
            $description = trim((string)($data['description'] ?? ''));
            $hasPrix = isset($data['prix']) && trim((string)$data['prix']) !== '';
            $hasPrixOriginal = isset($data['prix_original']) && trim((string)$data['prix_original']) !== '';
            $hasQuantite = isset($data['quantite']) && trim((string)$data['quantite']) !== '';
            $prix = $hasPrix ? (float)str_replace(',', '.', (string)$data['prix']) : 1.0;
            $prixOriginal = $hasPrixOriginal ? (float)str_replace(',', '.', (string)$data['prix_original']) : 0.0;
            $quantite = $hasQuantite ? (int)$data['quantite'] : 1;
            $statut = trim((string)($data['statut'] ?? 'publiee'));
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

            if ($categoryId <= 0) {
                $firstCategoryId = $pdo->query('SELECT id_categorie FROM categorie_offre ORDER BY id_categorie ASC LIMIT 1')->fetchColumn();
                if ($firstCategoryId !== false) {
                    $categoryId = (int)$firstCategoryId;
                }
            }

            if ($categoryId <= 0) {
                $defaultCategoryName = 'General';
                $pdo->prepare('INSERT INTO categorie_offre (nom_categorie, description, icone) VALUES (?, NULL, NULL)')
                    ->execute([$defaultCategoryName]);
                $categoryId = (int)$pdo->lastInsertId();
            }

            if (!$hasPrixOriginal || $prixOriginal <= 0) {
                $prixOriginal = round(max($prix + 1, $prix * 1.2), 2);
            }
            if ($prixOriginal <= $prix) {
                $prixOriginal = round($prix + 1, 2);
            }

            $errors = [];
            if ($titre === '') $errors[] = 'Titre obligatoire.';
            if ($prix <= 0) $errors[] = 'Prix invalide.';
            if ($prixOriginal <= 0) $errors[] = 'Prix original invalide.';
            if ($prix >= $prixOriginal) $errors[] = 'Le prix reduit doit etre inferieur au prix original.';
            if ($quantite < 1) $errors[] = 'Quantite invalide.';
            if (!empty($errors)) send_json(422, ['error' => implode(' ', $errors)]);

            $stmt = $pdo->prepare(
                'INSERT INTO offre (titre, description, prix, prix_original, photo_url, quantite, heure_debut, heure_fin, statut, id_categorie, date_creation)
                 VALUES (:titre, :description, :prix, :prix_original, :photo_url, :quantite, :heure_debut, :heure_fin, :statut, :id_categorie, NOW())'
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

            send_json(200, [
                'ok' => true,
                'id_offre' => (int)$pdo->lastInsertId(),
                'titre' => $titre,
            ]);
        }

        case 'update_offer': {
            $id = isset($data['id_offre']) ? (int)$data['id_offre'] : 0;
            $sourceTitle = trim((string)($data['titre_source'] ?? ''));

            if ($id <= 0 && $sourceTitle !== '') {
                $s = $pdo->prepare('SELECT id_offre FROM offre WHERE LOWER(titre)=LOWER(:titre) ORDER BY id_offre DESC LIMIT 1');
                $s->execute([':titre' => $sourceTitle]);
                $found = $s->fetchColumn();
                if ($found !== false) {
                    $id = (int)$found;
                }
            }

            if ($id <= 0) {
                send_json(422, ['error' => 'Offre introuvable.']);
            }

            $currentStmt = $pdo->prepare('SELECT * FROM offre WHERE id_offre=:id LIMIT 1');
            $currentStmt->execute([':id' => $id]);
            $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                send_json(422, ['error' => 'Offre introuvable.']);
            }

            $titre = trim((string)($data['titre'] ?? $current['titre']));
            $description = trim((string)($data['description'] ?? (string)($current['description'] ?? '')));
            $prix = isset($data['prix']) ? (float)str_replace(',', '.', (string)$data['prix']) : (float)$current['prix'];
            $prixOriginal = isset($data['prix_original']) ? (float)str_replace(',', '.', (string)$data['prix_original']) : (float)$current['prix_original'];
            $quantite = isset($data['quantite']) ? (int)$data['quantite'] : (int)$current['quantite'];
            $statut = trim((string)($data['statut'] ?? $current['statut']));
            $heureDebut = array_key_exists('heure_debut', $data) ? trim((string)$data['heure_debut']) : (string)($current['heure_debut'] ?? '');
            $heureFin = array_key_exists('heure_fin', $data) ? trim((string)$data['heure_fin']) : (string)($current['heure_fin'] ?? '');
            $photoUrl = array_key_exists('photo_url', $data) ? trim((string)$data['photo_url']) : (string)($current['photo_url'] ?? '');
            $categoryId = isset($data['id_categorie']) ? (int)$data['id_categorie'] : (int)$current['id_categorie'];
            $categoryName = trim((string)($data['nom_categorie'] ?? ''));

            if ($categoryName !== '' && (!isset($data['id_categorie']) || (int)$data['id_categorie'] <= 0)) {
                $catStmt = $pdo->prepare('SELECT id_categorie FROM categorie_offre WHERE LOWER(nom_categorie)=LOWER(:nom) LIMIT 1');
                $catStmt->execute([':nom' => $categoryName]);
                $foundCat = $catStmt->fetchColumn();
                if ($foundCat !== false) {
                    $categoryId = (int)$foundCat;
                }
            }

            $errors = [];
            if ($titre === '') $errors[] = 'Titre obligatoire.';
            if ($prix <= 0) $errors[] = 'Prix invalide.';
            if ($prixOriginal <= 0) $errors[] = 'Prix original invalide.';
            if ($prix >= $prixOriginal) $errors[] = 'Le prix reduit doit etre inferieur au prix original.';
            if ($quantite < 1) $errors[] = 'Quantite invalide.';
            if ($categoryId <= 0) $errors[] = 'Categorie introuvable.';
            if (!empty($errors)) send_json(422, ['error' => implode(' ', $errors)]);

            $stmt = $pdo->prepare(
                'UPDATE offre
                 SET titre=:titre, description=:description, prix=:prix, prix_original=:prix_original, photo_url=:photo_url,
                     quantite=:quantite, heure_debut=:heure_debut, heure_fin=:heure_fin, statut=:statut, id_categorie=:id_categorie
                 WHERE id_offre=:id'
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
                ':id' => $id,
            ]);

            send_json(200, [
                'ok' => true,
                'id_offre' => $id,
                'titre' => $titre,
            ]);
        }

        case 'delete_offer': {
            $id = isset($data['id_offre']) ? (int)$data['id_offre'] : 0;
            $sourceTitle = trim((string)($data['titre'] ?? ''));

            if ($id <= 0 && $sourceTitle !== '') {
                $s = $pdo->prepare('SELECT id_offre FROM offre WHERE LOWER(titre)=LOWER(:titre) ORDER BY id_offre DESC LIMIT 1');
                $s->execute([':titre' => $sourceTitle]);
                $found = $s->fetchColumn();
                if ($found !== false) {
                    $id = (int)$found;
                }
            }

            if ($id <= 0) {
                send_json(422, ['error' => 'Offre introuvable.']);
            }

            $pdo->prepare('DELETE FROM offre WHERE id_offre = ?')->execute([$id]);
            send_json(200, [
                'ok' => true,
                'deleted_id_offre' => $id,
            ]);
        }

        default:
            send_json(422, ['error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    send_json(500, ['error' => 'Server error: ' . $e->getMessage()]);
}
