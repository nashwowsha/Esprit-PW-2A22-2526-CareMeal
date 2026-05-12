<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorise.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Config::getConnexion();

    $userId = (string)($_SESSION['user_id'] ?? '');
    $role   = strtolower((string)($_SESSION['user_role'] ?? 'student'));
    $isPartner = ($role === 'partner');

    $baseSql = "
        SELECT
            o.id_offre,
            o.titre,
            o.prix,
            o.prix_original,
            o.quantite,
            o.id_partenaire,
            c.nom_categorie,
            COALESCE(
                NULLIF(p.nom_entreprise, ''),
                NULLIF(CONCAT_WS(' ', p.prenom, p.nom), ''),
                'Partenaire'
            ) AS partner_name
        FROM offre o
        LEFT JOIN categorie_offre c ON c.id_categorie = o.id_categorie
        LEFT JOIN profiles p ON p.user_id = o.id_partenaire
        WHERE LOWER(o.statut) IN ('publiee', 'publiée', 'active')
    ";

    if ($isPartner) {
        $sql = $baseSql . " AND CAST(o.id_partenaire AS CHAR) = :uid ORDER BY o.date_creation DESC, o.id_offre DESC LIMIT 6";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_STR);
    } else {
        $sql = $baseSql . " AND COALESCE(o.quantite, 0) > 0 ORDER BY o.date_creation DESC, o.id_offre DESC LIMIT 6";
        $stmt = $pdo->prepare($sql);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $offers = array_map(static function (array $row): array {
        return [
            'id'            => (int)($row['id_offre'] ?? 0),
            'title'         => (string)($row['titre'] ?? ''),
            'price'         => (float)($row['prix'] ?? 0),
            'originalPrice' => (float)($row['prix_original'] ?? 0),
            'quantity'      => (int)($row['quantite'] ?? 0),
            'partnerName'   => (string)($row['partner_name'] ?? 'Partenaire'),
            'category'      => (string)($row['nom_categorie'] ?? ''),
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'scope'   => $isPartner ? 'partner' : 'student',
        'offers'  => $offers,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur.',
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

