<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Model/Offer.php';

class AdminCategoryController
{
    private PDO $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->pdo = Config::getConnexion();
    }

    /* ═══════════════════════════════════════════════════════════
       INDEX — page principale : catégories + offres (jointure)
    ═══════════════════════════════════════════════════════════ */
    public function index()
    {
        // Jointure : toutes les catégories avec leurs offres (comme le workshop)
        $categories  = $this->getAllCategoriesWithOffers();
        $allOffers   = $this->getAllOffersFlat();

        $flash  = $_SESSION['flash']  ?? null;
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/BackOffice/admin/categories.php';
    }

    /* ═══════════════════════════════════════════════════════════
       CRUD CATÉGORIE
    ═══════════════════════════════════════════════════════════ */
    public function createCategory()
    {
        $nom  = trim($_POST['nom_categorie'] ?? '');
        $desc = trim($_POST['description']   ?? '');
        $icon = trim($_POST['icone']         ?? '');

        $errors = [];
        if ($nom === '')         $errors[] = 'Le nom de la catégorie est obligatoire.';
        if (strlen($nom) < 2)   $errors[] = 'Le nom doit contenir au moins 2 caractères.';

        if (!empty($errors)) {
            $_SESSION['flash']  = ['type' => 'error', 'msg' => implode(' ', $errors)];
            $this->redirect();
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO categorie_offre (nom_categorie, description, icone) VALUES (:nom, :desc, :icon)'
        );
        $stmt->execute([':nom' => $nom, ':desc' => $desc ?: null, ':icon' => $icon ?: null]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Catégorie « {$nom} » créée avec succès !"];
        $this->redirect();
    }

    public function updateCategory()
    {
        $id   = (int)($_POST['id_categorie']  ?? 0);
        $nom  = trim($_POST['nom_categorie']  ?? '');
        $desc = trim($_POST['description']    ?? '');
        $icon = trim($_POST['icone']          ?? '');

        if (!$id || $nom === '') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Données invalides.'];
            $this->redirect();
        }

        $stmt = $this->pdo->prepare(
            'UPDATE categorie_offre SET nom_categorie=:nom, description=:desc, icone=:icon WHERE id_categorie=:id'
        );
        $stmt->execute([':nom' => $nom, ':desc' => $desc ?: null, ':icon' => $icon ?: null, ':id' => $id]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Catégorie modifiée avec succès !"];
        $this->redirect();
    }

    public function deleteCategory()
    {
        $id = (int)($_POST['id_categorie'] ?? 0);
        if (!$id) { $this->redirect(); }

        // Masquer les offres liées (id_categorie = NULL) — elles restent en base
        $this->pdo->prepare('UPDATE offre SET id_categorie = NULL WHERE id_categorie = ?')->execute([$id]);

        // Supprimer la catégorie
        $this->pdo->prepare('DELETE FROM categorie_offre WHERE id_categorie = ?')->execute([$id]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Catégorie supprimée. Les offres associées ont été masquées.'];
        $this->redirect();
    }

    /* ═══════════════════════════════════════════════════════════
       CRUD OFFRE (depuis page catégories)
    ═══════════════════════════════════════════════════════════ */
    public function createOffer()
    {
        $errors = $this->validateOffer($_POST);

        if (!empty($errors)) {
            $_SESSION['flash']  = ['type' => 'error', 'msg' => implode(' ', $errors)];
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO offre (titre, description, prix, prix_original, photo_url, quantite,
                               heure_debut, heure_fin, statut, id_categorie, date_creation)
            VALUES (:titre, :description, :prix, :prix_original, :photo_url, :quantite,
                    :heure_debut, :heure_fin, :statut, :id_categorie, NOW())
        ');
        $stmt->execute($this->buildOfferParams($_POST));

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre créée avec succès !'];
        $this->redirect();
    }

    public function updateOffer()
    {
        $id = (int)($_POST['id_offre'] ?? 0);
        if (!$id) { $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Offre introuvable.']; $this->redirect(); }

        $errors = $this->validateOffer($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => implode(' ', $errors)];
            $_SESSION['old']   = $_POST;
            $this->redirect();
        }

        $params = $this->buildOfferParams($_POST);
        $params[':id'] = $id;

        $stmt = $this->pdo->prepare('
            UPDATE offre SET titre=:titre, description=:description, prix=:prix,
                prix_original=:prix_original, photo_url=:photo_url, quantite=:quantite,
                heure_debut=:heure_debut, heure_fin=:heure_fin, statut=:statut,
                id_categorie=:id_categorie
            WHERE id_offre=:id
        ');
        $stmt->execute($params);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre modifiée avec succès !'];
        $this->redirect();
    }

    public function deleteOffer()
    {
        $id = (int)($_POST['id_offre'] ?? 0);
        if ($id) {
            $this->pdo->prepare('DELETE FROM offre WHERE id_offre = ?')->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre supprimée.'];
        }
        $this->redirect();
    }

    /* ═══════════════════════════════════════════════════════════
       REQUÊTES SQL avec JOINTURE (comme le workshop)
    ═══════════════════════════════════════════════════════════ */

    /**
     * Jointure : SELECT * FROM categorie_offre + leurs offres
     * Principe identique au workshop (genre → album)
     */
    private function getAllCategoriesWithOffers(): array
    {
        // 1. Toutes les catégories
        $cats = $this->pdo->query(
            'SELECT * FROM categorie_offre ORDER BY nom_categorie'
        )->fetchAll();

        // 2. Pour chaque catégorie, récupérer ses offres (jointure)
        $stmt = $this->pdo->prepare(
            'SELECT o.*, c.nom_categorie, c.icone
             FROM offre o
             LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
             WHERE o.id_categorie = :id
             ORDER BY o.date_creation DESC'
        );

        foreach ($cats as &$cat) {
            $stmt->execute([':id' => $cat['id_categorie']]);
            $cat['offres'] = $stmt->fetchAll();
        }
        unset($cat);

        return $cats;
    }

    /** Toutes les offres à plat (pour le JS) */
    private function getAllOffersFlat(): array
    {
        return $this->pdo->query(
            'SELECT o.*, c.nom_categorie
             FROM offre o
             LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
             ORDER BY o.date_creation DESC'
        )->fetchAll();
    }

    public function getAllCategories(): array
    {
        return $this->pdo->query(
            'SELECT * FROM categorie_offre ORDER BY nom_categorie'
        )->fetchAll();
    }

    /* ═══════════════════════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════════════════════ */
    private function validateOffer(array $data): array
    {
        $errors = [];
        if (empty(trim($data['titre'] ?? '')))    $errors[] = 'Le titre est obligatoire.';
        if (!is_numeric($data['prix'] ?? '') || (float)$data['prix'] <= 0)
            $errors[] = 'Le prix réduit doit être un nombre positif.';
        if (!is_numeric($data['prix_original'] ?? '') || (float)$data['prix_original'] <= 0)
            $errors[] = 'Le prix original doit être un nombre positif.';
        if ((float)($data['prix'] ?? 0) >= (float)($data['prix_original'] ?? 0))
            $errors[] = 'Le prix réduit doit être inférieur au prix original.';
        if ((int)($data['quantite'] ?? 0) < 1)
            $errors[] = 'La quantité doit être au moins 1.';
        if (empty($data['id_categorie']))
            $errors[] = 'La catégorie est obligatoire.';
        return $errors;
    }

    private function buildOfferParams(array $data): array
    {
        $photo = trim((string)($data['photo_url'] ?? ''));
        if (strpos($photo, 'data:image') === 0 && strlen($photo) > 1 * 1024 * 1024) $photo = '';

        $statuts = ['publiée', 'brouillon', 'expirée', 'archivée'];
        $statut  = in_array($data['statut'] ?? '', $statuts) ? $data['statut'] : 'publiée';

        return [
            ':titre'        => trim($data['titre']),
            ':description'  => trim($data['description'] ?? ''),
            ':prix'         => (float)$data['prix'],
            ':prix_original'=> (float)$data['prix_original'],
            ':photo_url'    => $photo ?: null,
            ':quantite'     => (int)$data['quantite'],
            ':heure_debut'  => !empty($data['heure_debut']) ? $data['heure_debut'] : null,
            ':heure_fin'    => !empty($data['heure_fin'])   ? $data['heure_fin']   : null,
            ':statut'       => $statut,
            ':id_categorie' => !empty($data['id_categorie']) ? (int)$data['id_categorie'] : null,
        ];
    }

    private function redirect()
    {
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}