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
     * Supprime une catégorie.
     * - Les offres liées à d'autres catégories restent intactes.
     * - Les offres qui n'appartiennent QU'à cette catégorie sont supprimées.
     */
    public function deleteCategory(): void
    {
        $id = (int)($_POST['id_categorie'] ?? 0);
        if ($id <= 0) {
            $this->redirect();
        }

        // 1. Trouver les offres qui n'ont que cette catégorie (elles vont être orphelines)
        $stmt = $this->pdo->prepare(
            "SELECT oc.id_offre
             FROM offre_categorie oc
             WHERE oc.id_categorie = ?
               AND (
                   SELECT COUNT(*)
                   FROM offre_categorie oc2
                   WHERE oc2.id_offre = oc.id_offre
               ) = 1"
        );
        $stmt->execute([$id]);
        $orphanIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // 2. Supprimer les offres orphelines (et leurs liaisons pivot via CASCADE)
        if (!empty($orphanIds)) {
            $placeholders = implode(',', array_fill(0, count($orphanIds), '?'));
            $this->pdo->prepare("DELETE FROM offre WHERE id_offre IN ($placeholders)")
                      ->execute($orphanIds);
        }

        // 3. Supprimer la catégorie
        //    Les FK ON DELETE CASCADE suppriment automatiquement les lignes restantes de offre_categorie.
        $this->pdo->prepare('DELETE FROM categorie_offre WHERE id_categorie = ?')->execute([$id]);

        // 4. Mettre à jour la colonne legacy id_categorie pour les offres encore liées
        //    (elles avaient cette catégorie comme primaire mais en ont d'autres)
        $this->pdo->prepare(
            "UPDATE offre o
             SET id_categorie = (
                 SELECT oc.id_categorie
                 FROM offre_categorie oc
                 WHERE oc.id_offre = o.id_offre
                 ORDER BY oc.id_categorie
                 LIMIT 1
             )
             WHERE id_categorie = ? OR id_categorie IS NULL"
        )->execute([$id]);

        $orphanCount = count($orphanIds);
        $msg = 'Categorie supprimee.';
        if ($orphanCount > 0) {
            $msg .= " $orphanCount offre(s) appartenant uniquement a cette categorie ont ete supprimees.";
        }

        $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
        $this->redirect();
    }

    private function getCategoriesWithCounts(): array
    {
        $sql = 'SELECT c.id_categorie, c.nom_categorie, c.description, c.icone, COUNT(oc.id_offre) AS total_offres
                FROM categorie_offre c
                LEFT JOIN offre_categorie oc ON oc.id_categorie = c.id_categorie
                GROUP BY c.id_categorie, c.nom_categorie, c.description, c.icone
                ORDER BY c.nom_categorie ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    private function getOffersByCategory(int $idCategorie): array
    {
        $sql = 'SELECT o.id_offre, o.titre, o.prix, o.prix_original, o.quantite, o.statut, o.date_creation
                FROM offre o
                JOIN offre_categorie oc ON oc.id_offre = o.id_offre
                WHERE oc.id_categorie = :id
                ORDER BY o.date_creation DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idCategorie]);
        return $stmt->fetchAll();
    }

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

        $this->pdo->exec('ALTER TABLE offre ADD COLUMN IF NOT EXISTS id_categorie INT NULL');

        // Table pivot
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS offre_categorie (
                id_offre     INT NOT NULL,
                id_categorie INT NOT NULL,
                PRIMARY KEY (id_offre, id_categorie),
                CONSTRAINT fk_oc_offre2 FOREIGN KEY (id_offre)     REFERENCES offre(id_offre)               ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_oc_cat2   FOREIGN KEY (id_categorie) REFERENCES categorie_offre(id_categorie) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // FK legacy sur offre.id_categorie
        $fkExists = $this->pdo->prepare(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'offre'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME = 'fk_offre_categorie'"
        );
        $fkExists->execute();

        if (!$fkExists->fetch()) {
            try {
                $this->pdo->exec(
                    'ALTER TABLE offre
                     ADD CONSTRAINT fk_offre_categorie
                     FOREIGN KEY (id_categorie) REFERENCES categorie_offre(id_categorie)
                     ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (\Throwable $e) {
                // already exists under another name
            }
        }
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