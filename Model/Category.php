<?php
require_once __DIR__ . '/../config/database.php';

class Category
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM categorie ORDER BY nom ASC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categorie WHERE id_categorie = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO categorie (nom, description, actif) VALUES (:nom, :description, :actif)");
        $stmt->execute([
            ':nom' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':actif' => $data['active'] ?? 0
        ]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->find($id);
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->find($id);
        if (!$existing) return null;

        $nom = $data['name'] ?? $existing['nom'];
        $description = array_key_exists('description', $data) ? $data['description'] : $existing['description'];
        $actif = array_key_exists('active', $data) ? ($data['active'] ?? 0) : $existing['actif'];

        $stmt = $this->pdo->prepare("UPDATE categorie SET nom = :nom, description = :description, actif = :actif, updated_at = CURRENT_TIMESTAMP WHERE id_categorie = :id");
        $stmt->execute([
            ':nom' => $nom,
            ':description' => $description,
            ':actif' => $actif,
            ':id' => $id
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM categorie WHERE id_categorie = :id");
        return $stmt->execute([':id' => $id]);
    }
}

