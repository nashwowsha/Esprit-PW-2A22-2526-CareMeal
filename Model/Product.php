<?php
require_once __DIR__ . '/../config/database.php';

class Product
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $sql = "SELECT p.*, c.nom AS category_name FROM produit p LEFT JOIN categorie c ON p.id_categorie = c.id_categorie ORDER BY p.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT p.*, c.nom AS category_name FROM produit p LEFT JOIN categorie c ON p.id_categorie = c.id_categorie WHERE p.id_produit = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $sql = "INSERT INTO produit (id_categorie, nom, description, prix_normal, prix_commande, stock, actif) VALUES (:id_categorie, :nom, :description, :prix_normal, :prix_commande, :stock, :actif)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_categorie' => $data['category_id'],
            ':nom' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':prix_normal' => $data['original_price'] ?? 0,
            ':prix_commande' => $data['price'] ?? 0,
            ':stock' => $data['stock'] ?? 0,
            ':actif' => $data['active'] ?? 0
        ]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->find($id);
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->find($id);
        if (!$existing) return null;

        $categoryId = $data['category_id'] ?? $existing['id_categorie'];
        $nom = $data['name'] ?? $existing['nom'];
        $description = array_key_exists('description', $data) ? $data['description'] : $existing['description'];
        $prixNormal = $data['original_price'] ?? $existing['prix_normal'];
        $prixCommande = $data['price'] ?? $existing['prix_commande'];
        $stock = $data['stock'] ?? $existing['stock'];
        $actif = array_key_exists('active', $data) ? ($data['active'] ?? 0) : $existing['actif'];

        $sql = "UPDATE produit SET id_categorie = :id_categorie, nom = :nom, description = :description, prix_normal = :prix_normal, prix_commande = :prix_commande, stock = :stock, actif = :actif, updated_at = CURRENT_TIMESTAMP WHERE id_produit = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_categorie' => $categoryId,
            ':nom' => $nom,
            ':description' => $description,
            ':prix_normal' => $prixNormal,
            ':prix_commande' => $prixCommande,
            ':stock' => $stock,
            ':actif' => $actif,
            ':id' => $id
        ]);
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM produit WHERE id_produit = :id");
        return $stmt->execute([':id' => $id]);
    }
}

