<?php
require_once __DIR__ . '/../Model/Offer.php';
require_once __DIR__ . '/../config/database.php';

class AdminOfferController
{
    private $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->pdo = Config::getConnexion();
    }

    // ── Liste toutes les offres ──────────────────────────────
    public function index(): void
    {
        // Requête SQL : récupérer toutes les offres avec leur catégorie
        $stmt = $this->pdo->query("
            SELECT o.*, c.nom_categorie, c.icone
            FROM offre o
            LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
            ORDER BY o.date_creation DESC
        ");
        $offersData = $stmt->fetchAll();

        // Requête SQL : récupérer toutes les catégories
        $stmtCat    = $this->pdo->query("SELECT * FROM categorie_offre ORDER BY nom_categorie");
        $categories = $stmtCat->fetchAll();

        // Requête SQL : compter les offres par statut
        $stmtCount = $this->pdo->query("SELECT statut, COUNT(*) as total FROM offre GROUP BY statut");
        $rows      = $stmtCount->fetchAll();
        $counts    = ['publiée' => 0, 'expirée' => 0, 'brouillon' => 0, 'archivée' => 0];
        foreach ($rows as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }

        // Hydratation en objets Offer
        $offers = array_map(fn($row) => new Offer($row), $offersData);

        $flash  = $_SESSION['flash']  ?? null;
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/BackOffice/admin/offers.php';
    }

    // ── Modifier une offre (admin peut modifier toutes) ──────
    public function update(): void
    {
        $id = (int) ($_POST['id_offre'] ?? 0);

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

        $photo = $this->compressPhoto(trim($_POST['photo_url'] ?? ''));

        // Requête SQL : mettre à jour une offre
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
            ':titre'         => trim($_POST['titre']),
            ':description'   => trim($_POST['description']  ?? ''),
            ':prix'          => (float) $_POST['prix'],
            ':prix_original' => (float) $_POST['prix_original'],
            ':photo_url'     => $photo,
            ':quantite'      => (int)   $_POST['quantite'],
            ':heure_debut'   => ($_POST['heure_debut']      ?: null),
            ':heure_fin'     => ($_POST['heure_fin']        ?: null),
            ':statut'        => $_POST['statut']            ?? 'publiée',
            ':id_categorie'  => !empty($_POST['id_categorie']) ? (int)$_POST['id_categorie'] : null,
            ':id'            => $id,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre modifiée avec succès !'];
        $this->redirect();
    }

    // ── Supprimer une offre ──────────────────────────────────
    public function delete(): void
    {
        $id = (int) ($_POST['id_offre'] ?? 0);

        if ($id) {
            // Requête SQL : supprimer une offre
            $this->pdo->prepare("DELETE FROM offre WHERE id_offre = ?")->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre supprimée.'];
        }

        $this->redirect();
    }

    // ── Redirection vers la liste ────────────────────────────
    private function redirect(): void
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $url);
        exit;
    }

    // ── Limiter la taille de la photo base64 (max 1MB) ──────
    private function compressPhoto(string $base64): string
    {
        if (strpos($base64, 'data:image') !== 0) return $base64;
        if (strlen($base64) > 1 * 1024 * 1024) return '';
        return $base64;
    }

    // ── Validation ───────────────────────────────────────────
    private function validate(array $data): array
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

        if (
            isset($data['prix'], $data['prix_original']) &&
            (float)$data['prix'] >= (float)$data['prix_original']
        ) {
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
}