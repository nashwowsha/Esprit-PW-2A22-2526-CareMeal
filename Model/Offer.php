<?php
require_once __DIR__ . '/../config/database.php';

class OfferModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Config::getConnexion();
        $this->migrate();
    }

    // ── Migrations automatiques ──────────────────────────────
    // (conservées pour compatibilité avec les anciennes BD)
    private function migrate()
    {
        try {
            $this->pdo->query("SELECT id_partenaire FROM offre LIMIT 1");
        } catch (Exception $e) {
            $this->pdo->exec("ALTER TABLE offre ADD COLUMN id_partenaire VARCHAR(100) NULL DEFAULT NULL");
        }

        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM offre LIKE 'photo_url'")->fetch();
            if ($col && stripos($col['Type'], 'longtext') === false) {
                $this->pdo->exec("ALTER TABLE offre MODIFY COLUMN photo_url LONGTEXT NULL");
            }
        } catch (Exception $e) { /* silencieux */ }
    }

    // ── Récupérer toutes les offres ──────────────────────────
    public function getAll(?string $partnerId = null): array
    {
        $where  = [];
        $params = [];

        if ($partnerId !== null) {
            $where[]              = "o.id_partenaire = :partner_id";
            $params[':partner_id'] = $partnerId;
        }

        $sql = "SELECT o.*, c.nom_categorie, c.icone
                FROM offre o
                LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY o.date_creation DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Récupérer une offre par ID ───────────────────────────
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, c.nom_categorie, c.icone
            FROM offre o
            LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
            WHERE o.id_offre = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ── Limiter la taille de la photo base64 (max 1MB) ──────
    private function compressPhoto(string $base64): string
    {
        if (strpos($base64, 'data:image') !== 0) return $base64;
        if (strlen($base64) > 1 * 1024 * 1024) return '';
        return $base64;
    }

    // ── Créer une offre ──────────────────────────────────────
    public function create(array $data): int
    {
        $photo = $this->compressPhoto(trim($data['photo_url'] ?? ''));

        $stmt = $this->pdo->prepare("
            INSERT INTO offre
                (titre, description, prix, prix_original, photo_url,
                 quantite, quantite_restante, heure_debut, heure_fin,
                 statut, id_categorie, id_partenaire)
            VALUES
                (:titre, :description, :prix, :prix_original, :photo_url,
                 :quantite, :quantite, :heure_debut, :heure_fin,
                 :statut, :id_categorie, :id_partenaire)
        ");
        $stmt->execute([
            ':titre'          => trim($data['titre']),
            ':description'    => trim($data['description']  ?? ''),
            ':prix'           => (float) $data['prix'],
            ':prix_original'  => (float) $data['prix_original'],
            ':photo_url'      => $photo,
            ':quantite'       => (int)   $data['quantite'],
            ':heure_debut'    => ($data['heure_debut']      ?: null),
            ':heure_fin'      => ($data['heure_fin']        ?: null),
            ':statut'         => $data['statut']            ?? 'publiée',
            ':id_categorie'   => !empty($data['id_categorie'])  ? (int)$data['id_categorie']  : null,
            ':id_partenaire'  => !empty($data['id_partenaire']) ? $data['id_partenaire']       : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // ── Modifier une offre ───────────────────────────────────
    public function update(int $id, array $data): void
    {
        $photo = $this->compressPhoto(trim($data['photo_url'] ?? ''));

        $stmt = $this->pdo->prepare("
            UPDATE offre SET
                titre           = :titre,
                description     = :description,
                prix            = :prix,
                prix_original   = :prix_original,
                photo_url       = :photo_url,
                quantite        = :quantite,
                heure_debut     = :heure_debut,
                heure_fin       = :heure_fin,
                statut          = :statut,
                id_categorie    = :id_categorie
            WHERE id_offre = :id
        ");
        $stmt->execute([
            ':titre'         => trim($data['titre']),
            ':description'   => trim($data['description']  ?? ''),
            ':prix'          => (float) $data['prix'],
            ':prix_original' => (float) $data['prix_original'],
            ':photo_url'     => $photo,
            ':quantite'      => (int)   $data['quantite'],
            ':heure_debut'   => ($data['heure_debut']      ?: null),
            ':heure_fin'     => ($data['heure_fin']        ?: null),
            ':statut'        => $data['statut']            ?? 'publiée',
            ':id_categorie'  => !empty($data['id_categorie']) ? (int)$data['id_categorie'] : null,
            ':id'            => $id,
        ]);
    }

    // ── Supprimer une offre ──────────────────────────────────
    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM offre WHERE id_offre = ?")->execute([$id]);
    }

    // ── Compter par statut ───────────────────────────────────
    public function countByStatut(): array
    {
        $stmt   = $this->pdo->query("SELECT statut, COUNT(*) as total FROM offre GROUP BY statut");
        $rows   = $stmt->fetchAll();
        $counts = ['publiée' => 0, 'expirée' => 0, 'brouillon' => 0, 'archivée' => 0];
        foreach ($rows as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }
        return $counts;
    }

    // ── Toutes les catégories ────────────────────────────────
    public function getAllCategories(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM categorie_offre ORDER BY nom_categorie");
        return $stmt->fetchAll();
    }
}