<?php
require_once __DIR__ . '/../Model/Offer.php';
require_once __DIR__ . '/../config/database.php';

class OfferController
{
    private $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->pdo = Config::getConnexion();
        $this->migrate();
    }

    // ── Migrations automatiques ──────────────────────────────
    private function migrate(): void
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

    // ── Afficher la liste des offres ─────────────────────────
    public function index(): void
    {
        $partnerId = !empty($_GET['partner_id']) ? $_GET['partner_id'] : null;

        // Requête SQL : récupérer toutes les offres (filtrées par partenaire si besoin)
        $where  = [];
        $params = [];

        if ($partnerId !== null) {
            $where[]               = "o.id_partenaire = :partner_id";
            $params[':partner_id'] = $partnerId;
        }

        $sql = "SELECT o.*, c.nom_categorie, c.icone
                FROM offre o
                LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY o.date_creation DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $offersData = $stmt->fetchAll();

        // Requête SQL : récupérer toutes les catégories
        $stmtCat    = $this->pdo->query("SELECT * FROM categorie_offre ORDER BY nom_categorie");
        $categories = $stmtCat->fetchAll();

        // Hydratation en objets Offer
        $offers = array_map(fn($row) => new Offer($row), $offersData);

        $active  = array_values(array_filter($offers, fn($o) => $o->statut === 'publiée'));
        $expired = array_values(array_filter($offers, fn($o) => in_array($o->statut, ['expirée', 'archivée', 'brouillon'])));

        $counts = [
            'publiée'   => count($active),
            'expirée'   => count(array_filter($offers, fn($o) => $o->statut === 'expirée')),
            'archivée'  => count(array_filter($offers, fn($o) => $o->statut === 'archivée')),
            'brouillon' => count(array_filter($offers, fn($o) => $o->statut === 'brouillon')),
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/FrontOffice/partner/offers.php';
    }

    // ── Créer une offre ──────────────────────────────────────
    public function store(): void
    {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $photo = $this->compressPhoto(trim($_POST['photo_url'] ?? ''));

        // Requête SQL : insérer une nouvelle offre
        $stmt = $this->pdo->prepare("
            INSERT INTO offre
                (titre, description, prix, prix_original, photo_url,
                 quantite, heure_debut, heure_fin,
                 statut, id_categorie, id_partenaire)
            VALUES
                (:titre, :description, :prix, :prix_original, :photo_url,
                 :quantite, :heure_debut, :heure_fin,
                 :statut, :id_categorie, :id_partenaire)
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
            ':id_categorie'  => !empty($_POST['id_categorie'])  ? (int)$_POST['id_categorie']  : null,
            ':id_partenaire' => !empty($_POST['id_partenaire']) ? $_POST['id_partenaire']       : null,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre créée avec succès !'];
        $this->redirect();
    }

    // ── Modifier une offre ───────────────────────────────────
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

        // Requête SQL : mettre à jour une offre existante
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

    // ── Redirection interne ──────────────────────────────────
    private function redirect(): void
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?') . '?action=index';
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

        $titre = trim($data['titre'] ?? '');

        if (empty($titre)) {
            $errors[] = 'Le titre est obligatoire.';
        } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s\-\']+$/u', $titre)) {
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