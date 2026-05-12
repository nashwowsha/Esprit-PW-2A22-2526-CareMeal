<?php
require_once __DIR__ . '/../Model/Category.php';

class CategoryController
{
    private Category $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new Category($pdo);
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
        $description = $data['description'] ?? null;
        $active = !empty($data['active']) ? 1 : 0;

        if (empty($name) || strlen($name) < 2 || strlen($name) > 120) {
            throw new Exception('Nom invalide (2-120 caractères)');
        }
        if (!empty($description) && strlen($description) > 255) {
            throw new Exception('Description trop longue (max 255 caractères)');
        }

        return $this->model->create([
            'name' => $name,
            'description' => $description,
            'active' => $active
        ]);
    }

    public function update(int $id, array $data): ?array
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        if (!$this->model->find($id)) {
            throw new Exception('Catégorie introuvable');
        }

        if (isset($data['name']) && (strlen($data['name']) < 2 || strlen($data['name']) > 120)) {
            throw new Exception('Nom invalide (2-120 caractères)');
        }
        if (isset($data['description']) && strlen($data['description']) > 255) {
            throw new Exception('Description trop longue (max 255 caractères)');
        }

        return $this->model->update($id, $data);
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        if (!$this->model->find($id)) {
            throw new Exception('Catégorie introuvable');
        }

        return $this->model->delete($id);
    }
}
