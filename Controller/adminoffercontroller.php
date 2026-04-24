<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Offer.php';

class AdminOfferController
{
    private PDO $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->pdo = Config::getConnexion();
        $this->migrateOfferSchema();
        $this->migrateCategorySchema();
        $this->migratePivotTable();
    }

    public function index()
    {
        $offers     = $this->getAllOffers();
        $categories = $this->getAllCategories();
        $counts     = $this->countByStatut();

        $flash  = $_SESSION['flash']  ?? null;
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/BackOffice/admin/offers.php';
    }

    public function create()
    {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $_SESSION['open_create_modal'] = true;
            $this->redirect();
        }

        $offer = $this->buildOfferFromInput($_POST);

        $stmt = $this->pdo->prepare('
            INSERT INTO offre (titre, description, prix, prix_original, photo_url, quantite, heure_debut, heure_fin, statut, id_categorie, date_creation)
            VALUES (:titre, :description, :prix, :prix_original, :photo_url, :quantite, :heure_debut, :heure_fin, :statut, :id_categorie, NOW())
        ');

        $stmt->execute([
            ':titre'         => $offer->getTitre(),
            ':description'   => $offer->getDescription(),
            ':prix'          => $offer->getPrix(),
            ':prix_original' => $offer->getPrixOriginal(),
            ':photo_url'     => $offer->getPhotoUrl(),
            ':quantite'      => $offer->getQuantite(),
            ':heure_debut'   => $offer->getHeureDebut(),
            ':heure_fin'     => $offer->getHeureFin(),
            ':statut'        => $offer->getStatut(),
            ':id_categorie'  => $offer->getIdCategorie(),
        ]);

        $newId        = (int)$this->pdo->lastInsertId();
        $categorieIds = $this->extractCategorieIds($_POST);
        $this->syncOfferCategories($newId, $categorieIds);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre créée avec succès !'];
        $this->redirect();
    }

    public function update()
    {
        $id = (int)($_POST['id_offre'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Offre introuvable.'];
            $this->redirect();
        }

        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $offer = $this->buildOfferFromInput($_POST)->setIdOffre($id);
        $this->updateOffer($offer);

        $categorieIds = $this->extractCategorieIds($_POST);
        $this->syncOfferCategories($id, $categorieIds);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre modifiée avec succès !'];
        $this->redirect();
    }

    public function delete()
    {
        $id = (int)($_POST['id_offre'] ?? 0);

        if ($id) {
            $this->pdo->prepare('DELETE FROM offre_categorie WHERE id_offre = ?')->execute([$id]);
            $stmt = $this->pdo->prepare('DELETE FROM offre WHERE id_offre = ?');
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre supprimée.'];
        }

        $this->redirect();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function extractCategorieIds(array $data): array
    {
        if (!empty($data['id_categories']) && is_array($data['id_categories'])) {
            return array_map('intval', $data['id_categories']);
        }
        if (!empty($data['id_categorie'])) {
            return [(int)$data['id_categorie']];
        }
        return [];
    }

    private function syncOfferCategories(int $idOffre, array $categorieIds): void
    {
        $this->pdo->prepare('DELETE FROM offre_categorie WHERE id_offre = ?')->execute([$idOffre]);

        if (!empty($categorieIds)) {
            $ins = $this->pdo->prepare(
                'INSERT IGNORE INTO offre_categorie (id_offre, id_categorie) VALUES (?, ?)'
            );
            foreach ($categorieIds as $idCat) {
                if ($idCat > 0) {
                    $ins->execute([$idOffre, $idCat]);
                }
            }
        }

        $primaryCat = !empty($categorieIds) ? $categorieIds[0] : null;
        $this->pdo->prepare('UPDATE offre SET id_categorie = ? WHERE id_offre = ?')
                  ->execute([$primaryCat, $idOffre]);
    }

    private function getAllOffers(): array
    {
        $sql = "
            SELECT
                o.*,
                GROUP_CONCAT(c.id_categorie ORDER BY c.nom_categorie SEPARATOR ',') AS cat_ids,
                GROUP_CONCAT(c.nom_categorie ORDER BY c.nom_categorie SEPARATOR ', ') AS cat_noms,
                (
                    SELECT c2.nom_categorie
                    FROM offre_categorie oc2
                    JOIN categorie_offre c2 ON oc2.id_categorie = c2.id_categorie
                    WHERE oc2.id_offre = o.id_offre
                    ORDER BY c2.nom_categorie
                    LIMIT 1
                ) AS nom_categorie,
                (
                    SELECT c2.icone
                    FROM offre_categorie oc2
                    JOIN categorie_offre c2 ON oc2.id_categorie = c2.id_categorie
                    WHERE oc2.id_offre = o.id_offre
                    ORDER BY c2.nom_categorie
                    LIMIT 1
                ) AS icone
            FROM offre o
            LEFT JOIN offre_categorie oc ON o.id_offre = oc.id_offre
            LEFT JOIN categorie_offre  c  ON oc.id_categorie = c.id_categorie
            GROUP BY o.id_offre
            ORDER BY o.date_creation DESC
        ";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['statut']        = $this->normalizeStatus($row['statut'] ?? null);
            $row['categorie_ids'] = $row['cat_ids']
                ? array_map('intval', explode(',', $row['cat_ids']))
                : [];
        }
        unset($row);

        return $rows;
    }

    private function getAllCategories(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categorie_offre ORDER BY nom_categorie');
        return $stmt->fetchAll();
    }

    private function countByStatut(): array
    {
        $stmt   = $this->pdo->query('SELECT statut, COUNT(*) as total FROM offre GROUP BY statut');
        $rows   = $stmt->fetchAll();
        $counts = ['publiée' => 0, 'expirée' => 0, 'brouillon' => 0, 'archivée' => 0];

        foreach ($rows as $row) {
            $normalized = $this->normalizeStatus($row['statut'] ?? null);
            if (isset($counts[$normalized])) {
                $counts[$normalized] += (int)$row['total'];
            }
        }

        return $counts;
    }

    private function updateOffer(OfferModel $offer): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE offre SET
                titre         = :titre,
                description   = :description,
                prix          = :prix,
                prix_original = :prix_original,
                photo_url     = :photo_url,
                quantite      = :quantite,
                heure_debut   = :heure_debut,
                heure_fin     = :heure_fin,
                statut        = :statut,
                id_categorie  = :id_categorie
            WHERE id_offre = :id
        ');

        $stmt->execute([
            ':titre'         => $offer->getTitre(),
            ':description'   => $offer->getDescription(),
            ':prix'          => $offer->getPrix(),
            ':prix_original' => $offer->getPrixOriginal(),
            ':photo_url'     => $offer->getPhotoUrl(),
            ':quantite'      => $offer->getQuantite(),
            ':heure_debut'   => $offer->getHeureDebut(),
            ':heure_fin'     => $offer->getHeureFin(),
            ':statut'        => $offer->getStatut(),
            ':id_categorie'  => $offer->getIdCategorie(),
            ':id'            => $offer->getIdOffre(),
        ]);
    }

    private function buildOfferFromInput(array $data): OfferModel
    {
        $photo        = $this->normalizePhotoUrl(trim((string)($data['photo_url'] ?? '')));
        $categorieIds = $this->extractCategorieIds($data);
        $primaryCat   = !empty($categorieIds) ? $categorieIds[0] : null;

        return (new OfferModel())
            ->setTitre((string)($data['titre'] ?? ''))
            ->setDescription((string)($data['description'] ?? ''))
            ->setPrix((float)($data['prix'] ?? 0))
            ->setPrixOriginal((float)($data['prix_original'] ?? 0))
            ->setPhotoUrl($photo)
            ->setQuantite((int)($data['quantite'] ?? 0))
            ->setHeureDebut(!empty($data['heure_debut']) ? (string)$data['heure_debut'] : null)
            ->setHeureFin(!empty($data['heure_fin'])   ? (string)$data['heure_fin']   : null)
            ->setStatut($this->normalizeStatus((string)($data['statut'] ?? 'publiée'), 'publiée'))
            ->setIdCategorie($primaryCat);
    }

    private function normalizePhotoUrl(string $base64): string
    {
        if (strpos($base64, 'data:image') !== 0) {
            return $base64;
        }
        if (strlen($base64) > 1 * 1024 * 1024) {
            return '';
        }
        return $base64;
    }

    // ─── Migrations ──────────────────────────────────────────────────────────

    private function migrateOfferSchema(): void
    {
        try {
            $this->pdo->query('SELECT id_partenaire FROM offre LIMIT 1');
        } catch (Exception $e) {
            $this->pdo->exec('ALTER TABLE offre ADD COLUMN id_partenaire VARCHAR(100) NULL DEFAULT NULL');
        }

        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM offre LIKE 'photo_url'")->fetch();
            if ($col && stripos((string)$col['Type'], 'longtext') === false) {
                $this->pdo->exec('ALTER TABLE offre MODIFY COLUMN photo_url LONGTEXT NULL');
            }
        } catch (Exception $e) {
            // no-op
        }
    }

    private function migrateCategorySchema(): void
    {
        try {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS categorie_offre (
                    id_categorie  INT AUTO_INCREMENT PRIMARY KEY,
                    nom_categorie VARCHAR(50) NOT NULL,
                    description   TEXT NULL,
                    icone         VARCHAR(100) NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Exception $e) {
            // no-op
        }
    }

    private function migratePivotTable(): void
    {
        try {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS offre_categorie (
                    id_offre     INT NOT NULL,
                    id_categorie INT NOT NULL,
                    PRIMARY KEY (id_offre, id_categorie),
                    CONSTRAINT fk_oc_offre FOREIGN KEY (id_offre)     REFERENCES offre(id_offre)               ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_oc_cat   FOREIGN KEY (id_categorie) REFERENCES categorie_offre(id_categorie) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            // Migrer les liaisons existantes (colonne legacy id_categorie -> table pivot)
            $this->pdo->exec(
                "INSERT IGNORE INTO offre_categorie (id_offre, id_categorie)
                 SELECT id_offre, id_categorie
                 FROM offre
                 WHERE id_categorie IS NOT NULL"
            );
        } catch (Exception $e) {
            // no-op
        }
    }

    private function redirect()
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $url);
        exit;
    }

    private function validate($data)
    {
        $errors = [];

        if (empty(trim($data['titre'] ?? ''))) {
            $errors[] = 'Le titre est obligatoire.';
        }

        if (!isset($data['prix']) || !is_numeric($data['prix']) || (float)$data['prix'] <= 0) {
            $errors[] = 'Le prix réduit doit être un nombre positif.';
        }

        if (!isset($data['prix_original']) || !is_numeric($data['prix_original']) || (float)$data['prix_original'] <= 0) {
            $errors[] = 'Le prix original doit être un nombre positif.';
        }

        if (isset($data['prix'], $data['prix_original']) && (float)$data['prix'] >= (float)$data['prix_original']) {
            $errors[] = 'Le prix réduit doit être inférieur au prix original.';
        }

        if (!isset($data['quantite']) || (int)$data['quantite'] < 1) {
            $errors[] = 'La quantité doit être au moins 1.';
        }

        $categorieIds = $this->extractCategorieIds($data);
        if (empty($categorieIds)) {
            $errors[] = 'Veuillez sélectionner au moins une catégorie.';
        }

        return $errors;
    }

    private function normalizeStatus(?string $status, string $default = 'brouillon'): string
    {
        $s = trim((string)$status);
        if ($s === '') {
            return $default;
        }

        $s = mb_strtolower($s, 'UTF-8');

        $map = [
            'publiee'   => 'publiée',
            'publiée'   => 'publiée',
            'active'    => 'publiée',
            'actif'     => 'publiée',
            'expiree'   => 'expirée',
            'expirée'   => 'expirée',
            'archivee'  => 'archivée',
            'archivée'  => 'archivée',
            'draft'     => 'brouillon',
            'brouillon' => 'brouillon',
        ];

        return $map[$s] ?? $default;
    }
}