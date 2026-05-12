<?php
require_once __DIR__ . '/../config/database.php';

class AdminCategoryController
{
    private PDO $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->pdo = Config::getConnexion();
        $this->migrateSchema();
    }

    public function index(): void
    {
        $categories         = $this->getCategoriesWithCounts();
        $selectedCategoryId = isset($_GET['id_categorie']) ? (int)$_GET['id_categorie'] : 0;

        if ($selectedCategoryId <= 0 && !empty($categories)) {
            $selectedCategoryId = (int)$categories[0]['id_categorie'];
        }

        $offers = $selectedCategoryId > 0 ? $this->getOffersByCategory($selectedCategoryId) : [];

        $flash  = $_SESSION['flash']  ?? null;
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/BackOffice/admin/categorie.php';
    }

    public function createCategory(): void
    {
        $payload = $this->sanitizeCategoryPayload($_POST);
        $errors  = $this->validateCategory($payload);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO categorie_offre (nom_categorie, description, icone) VALUES (:nom, :description, :icone)'
        );

        $stmt->execute([
            ':nom'         => $payload['nom_categorie'],
            ':description' => $payload['description'],
            ':icone'       => $payload['icone'],
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Categorie creee avec succes.'];
        $this->redirect();
    }

    public function updateCategory(): void
    {
        $id      = (int)($_POST['id_categorie'] ?? 0);
        $payload = $this->sanitizeCategoryPayload($_POST);
        $errors  = $this->validateCategory($payload);

        if ($id <= 0) {
            $errors[] = 'Categorie introuvable.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $stmt = $this->pdo->prepare(
            'UPDATE categorie_offre
             SET nom_categorie = :nom, description = :description, icone = :icone
             WHERE id_categorie = :id'
        );

        $stmt->execute([
            ':nom'         => $payload['nom_categorie'],
            ':description' => $payload['description'],
            ':icone'       => $payload['icone'],
            ':id'          => $id,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Categorie modifiee avec succes.'];
        $this->redirect('id_categorie=' . $id);
    }

    /**
     * Supprime une catégorie et toutes ses offres associées.
     */
    public function deleteCategory(): void
    {
        $id = (int)($_POST['id_categorie'] ?? 0);
        if ($id <= 0) {
            $this->redirect();
        }

        // Supprimer les offres liées à cette catégorie
        $this->pdo->prepare('DELETE FROM offre WHERE id_categorie = ?')
                  ->execute([$id]);

        // Supprimer la catégorie
        $this->pdo->prepare('DELETE FROM categorie_offre WHERE id_categorie = ?')->execute([$id]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Categorie et ses offres supprimees.'];
        $this->redirect();
    }

    // ─── Queries ─────────────────────────────────────────────────────────────

    private function getCategoriesWithCounts(): array
    {
        $sql = 'SELECT c.id_categorie, c.nom_categorie, c.description, c.icone,
                       COUNT(o.id_offre) AS total_offres
                FROM categorie_offre c
                LEFT JOIN offre o ON o.id_categorie = c.id_categorie
                GROUP BY c.id_categorie, c.nom_categorie, c.description, c.icone
                ORDER BY c.nom_categorie ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    private function getOffersByCategory(int $idCategorie): array
    {
        $sql = 'SELECT o.id_offre, o.titre, o.prix, o.prix_original, o.quantite, o.statut, o.date_creation
                FROM offre o
                WHERE o.id_categorie = :id
                ORDER BY o.date_creation DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idCategorie]);
        return $stmt->fetchAll();
    }

    // ─── Validation ──────────────────────────────────────────────────────────

    private function sanitizeCategoryPayload(array $data): array
    {
        return [
            'nom_categorie' => trim((string)($data['nom_categorie'] ?? '')),
            'description'   => trim((string)($data['description']   ?? '')) ?: null,
            'icone'         => trim((string)($data['icone']         ?? '')) ?: null,
        ];
    }

    private function validateCategory(array $payload): array
    {
        $errors = [];
        $name   = $payload['nom_categorie'];

        if ($name === '') {
            $errors[] = 'Le nom de categorie est obligatoire.';
        } elseif (mb_strlen($name) < 2) {
            $errors[] = 'Le nom de categorie doit contenir au moins 2 caracteres.';
        } elseif (mb_strlen($name) > 50) {
            $errors[] = 'Le nom de categorie doit contenir au plus 50 caracteres.';
        }

        return $errors;
    }

    // ─── Migration ───────────────────────────────────────────────────────────

    private function migrateSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS categorie_offre (
                id_categorie  INT AUTO_INCREMENT PRIMARY KEY,
                nom_categorie VARCHAR(50) NOT NULL,
                description   TEXT NULL,
                icone         VARCHAR(100) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function redirect(string $query = ''): void
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        if ($query !== '') {
            $url .= '?' . $query;
        }
        header('Location: ' . $url);
        exit;
    }
}