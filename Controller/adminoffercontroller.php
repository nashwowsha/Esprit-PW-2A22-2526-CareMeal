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
    }

    public function index()
    {
        $offers = $this->getAllOffers();
        $categories = $this->getAllCategories();
        $counts = $this->countByStatut();

        $flash = $_SESSION['flash'] ?? null;
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/BackOffice/admin/offers.php';
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
            $_SESSION['old'] = $_POST;
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
            $stmt = $this->pdo->prepare('DELETE FROM offre WHERE id_offre = ?');
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre supprimée.'];
        }

        $this->redirect();
    }

    private function getAllOffers(): array
    {
        $sql = 'SELECT o.*, c.nom_categorie, c.icone
                FROM offre o
                LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
                ORDER BY o.date_creation DESC';
        $stmt = $this->pdo->query($sql);
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

    private function countByStatut(): array
    {
        $stmt = $this->pdo->query('SELECT statut, COUNT(*) as total FROM offre GROUP BY statut');
        $rows = $stmt->fetchAll();
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
                titre = :titre,
                description = :description,
                prix = :prix,
                prix_original = :prix_original,
                photo_url = :photo_url,
                quantite = :quantite,
                heure_debut = :heure_debut,
                heure_fin = :heure_fin,
                statut = :statut,
                id_categorie = :id_categorie
            WHERE id_offre = :id
        ');

        $stmt->execute([
            ':titre' => $offer->getTitre(),
            ':description' => $offer->getDescription(),
            ':prix' => $offer->getPrix(),
            ':prix_original' => $offer->getPrixOriginal(),
            ':photo_url' => $offer->getPhotoUrl(),
            ':quantite' => $offer->getQuantite(),
            ':heure_debut' => $offer->getHeureDebut(),
            ':heure_fin' => $offer->getHeureFin(),
            ':statut' => $offer->getStatut(),
            ':id_categorie' => $offer->getIdCategorie(),
            ':id' => $offer->getIdOffre(),
        ]);
    }

    private function buildOfferFromInput(array $data): OfferModel
    {
        $photo = $this->normalizePhotoUrl(trim((string)($data['photo_url'] ?? '')));

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
            ->setIdCategorie(!empty($data['id_categorie']) ? (int)$data['id_categorie'] : null);
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

        if (empty($data['id_categorie']) || (int)$data['id_categorie'] <= 0) {
            $errors[] = 'La catégorie est obligatoire.';
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
            'publiee' => 'publiée',
            'publiée' => 'publiée',
            'publiã©e' => 'publiée',
            'publiãƒâ©e' => 'publiée',
            'active' => 'publiée',
            'actif' => 'publiée',
            'expiree' => 'expirée',
            'expirée' => 'expirée',
            'expirã©e' => 'expirée',
            'expirãƒâ©e' => 'expirée',
            'archivee' => 'archivée',
            'archivée' => 'archivée',
            'archivã©e' => 'archivée',
            'archivãƒâ©e' => 'archivée',
            'draft' => 'brouillon',
            'brouillon' => 'brouillon',
        ];

        return $map[$s] ?? $default;
    }
}
