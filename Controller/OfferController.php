<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Offer.php';

class OfferController
{
    private PDO $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->pdo = Config::getConnexion();
        $this->migrateOfferSchema();
    }

    public function index()
    {
        $partnerId  = !empty($_GET['partner_id']) ? $_GET['partner_id'] : null;
        $offers     = $this->getAllOffers($partnerId);
        $categories = $this->getAllCategories();

        $active  = array_values(array_filter($offers, fn($o) => $o['statut'] === 'publiée'));
        $expired = array_values(array_filter($offers, fn($o) => in_array($o['statut'], ['expirée', 'archivée', 'brouillon'], true)));

        $counts = [
            'publiée'   => count($active),
            'expirée'   => count(array_filter($offers, fn($o) => $o['statut'] === 'expirée')),
            'archivée'  => count(array_filter($offers, fn($o) => $o['statut'] === 'archivée')),
            'brouillon' => count(array_filter($offers, fn($o) => $o['statut'] === 'brouillon')),
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/FrontOffice/partner/offers.php';
    }

    public function store()
    {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $offer = $this->buildOfferFromInput($_POST);
        $this->insertOffer($offer);

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

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre modifiée avec succès !'];
        $this->redirect();
    }

    public function delete()
    {
        $id = (int)($_POST['id_offre'] ?? 0);

        if ($id) {
            $this->pdo->prepare('DELETE FROM offre WHERE id_offre = ?')->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre supprimée.'];
        }

        $this->redirect();
    }

    // ─── Queries ─────────────────────────────────────────────────────────────

    /**
     * Jointure simple offre JOIN categorie_offre
     * (comme Album JOIN Genre dans le workshop)
     */
    private function getAllOffers(?string $partnerId = null): array
    {
        $where  = '';
        $params = [];

        if ($partnerId !== null) {
            $where  = ' WHERE o.id_partenaire = :partner_id';
            $params[':partner_id'] = $partnerId;
        }

        $sql = "
            SELECT
                o.*,
                c.id_categorie,
                c.nom_categorie,
                c.icone
            FROM offre o
            JOIN categorie_offre c ON o.id_categorie = c.id_categorie
            $where
            ORDER BY o.date_creation DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['statut'] = $this->normalizeStatus($row['statut'] ?? null);
        }
        unset($row);

        return $rows;
    }

    private function getAllCategories(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categorie_offre ORDER BY nom_categorie');
        return $stmt->fetchAll();
    }

    private function insertOffer(OfferModel $offer): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO offre
                (titre, description, prix, prix_original, photo_url,
                 quantite, heure_debut, heure_fin,
                 statut, id_categorie, id_partenaire, date_creation)
            VALUES
                (:titre, :description, :prix, :prix_original, :photo_url,
                 :quantite, :heure_debut, :heure_fin,
                 :statut, :id_categorie, :id_partenaire, NOW())
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
            ':id_partenaire' => $offer->getIdPartenaire(),
        ]);

        return (int)$this->pdo->lastInsertId();
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
        $photo  = $this->normalizePhotoUrl(trim((string)($data['photo_url'] ?? '')));
        // Le formulaire envoie id_categories[] (multi-select) → on prend le premier
        if (!empty($data['id_categories']) && is_array($data['id_categories'])) {
            $catId = (int)$data['id_categories'][0];
        } else {
            $catId = !empty($data['id_categorie']) ? (int)$data['id_categorie'] : null;
        }

        return (new OfferModel())
            ->setTitre((string)($data['titre'] ?? ''))
            ->setDescription((string)($data['description'] ?? ''))
            ->setPrix((float)($data['prix'] ?? 0))
            ->setPrixOriginal((float)($data['prix_original'] ?? 0))
            ->setPhotoUrl($photo)
            ->setQuantite((int)($data['quantite'] ?? 0))
            ->setHeureDebut(!empty($data['heure_debut']) ? (string)$data['heure_debut'] : null)
            ->setHeureFin(!empty($data['heure_fin']) ? (string)$data['heure_fin'] : null)
            ->setStatut($this->normalizeStatus((string)($data['statut'] ?? 'publiée'), 'publiée'))
            ->setIdCategorie($catId)
            ->setIdPartenaire(!empty($data['id_partenaire']) ? (string)$data['id_partenaire'] : null);
    }

    // ─── Migration ───────────────────────────────────────────────────────────

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

    // ─── Utilities ───────────────────────────────────────────────────────────

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

    private function redirect()
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?') . '?action=index';
        header('Location: ' . $url);
        exit;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $titre = trim($data['titre'] ?? '');

        if (empty($titre)) {
            $errors[] = 'Le titre est obligatoire.';
        } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $titre)) {
            $errors[] = 'Le titre doit contenir uniquement des lettres (pas de chiffres ni symboles).';
        } elseif (mb_strlen($titre) < 3) {
            $errors[] = 'Le titre doit contenir au moins 3 caractères.';
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

        $hasCat = (!empty($data['id_categories']) && is_array($data['id_categories']))
                  || !empty($data['id_categorie']);
        if (!$hasCat) {
            $errors[] = 'Veuillez sélectionner une catégorie.';
        }

        return $errors;
    }

    private function normalizeStatus(?string $status, string $default = 'brouillon'): string
    {
        $s = trim((string)$status);
        if ($s === '') return $default;

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