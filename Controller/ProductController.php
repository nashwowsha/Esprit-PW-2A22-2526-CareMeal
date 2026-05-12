<?php
require_once __DIR__ . '/../Model/Product.php';

class ProductController
{
    private Product $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new Product($pdo);
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function show(int $id): ?array
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }
        return $this->model->find($id);
    }

    public function store(array $data): array
    {
        $name = $data['name'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        $price = $data['price'] ?? null;
        $stock = $data['stock'] ?? null;
        $description = $data['description'] ?? null;
        $originalPrice = $data['original_price'] ?? $price;
        $active = !empty($data['active']) ? 1 : 0;

        if (empty($name) || strlen($name) < 2 || strlen($name) > 150) {
            throw new Exception('Nom invalide (2-150 caractères)');
        }
        if (empty($categoryId) || !is_numeric($categoryId)) {
            throw new Exception('Catégorie invalide');
        }
        if (empty($price) || !is_numeric($price) || (float)$price <= 0) {
            throw new Exception('Prix invalide (> 0)');
        }
        if (!is_numeric($stock) || (int)$stock < 0) {
            throw new Exception('Stock invalide (>= 0)');
        }

        return $this->model->create([
            'category_id' => (int)$categoryId,
            'name' => $name,
            'description' => $description,
            'original_price' => (float)$originalPrice,
            'price' => (float)$price,
            'stock' => (int)$stock,
            'active' => $active
        ]);
    }

    public function update(int $id, array $data): ?array
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        if (!$this->model->find($id)) {
            throw new Exception('Produit introuvable');
        }

        if (isset($data['name']) && (strlen($data['name']) < 2 || strlen($data['name']) > 150)) {
            throw new Exception('Nom invalide (2-150 caractères)');
        }
        if (isset($data['price']) && (!is_numeric($data['price']) || (float)$data['price'] <= 0)) {
            throw new Exception('Prix invalide (> 0)');
        }
        if (isset($data['stock']) && (!is_numeric($data['stock']) || (int)$data['stock'] < 0)) {
            throw new Exception('Stock invalide (>= 0)');
        }

        return $this->model->update($id, $data);
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        if (!$this->model->find($id)) {
            throw new Exception('Produit introuvable');
        }

        return $this->model->delete($id);
    }
}
