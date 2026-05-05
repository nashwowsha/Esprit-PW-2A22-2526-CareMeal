<?php
require_once __DIR__ . '/../Model/Order.php';

class OrderController
{
    private Order $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new Order($pdo);
    }

    public function index(?string $userId = null): array
    {
        return $this->model->all($userId);
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
        $productId = $data['product_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $quantity = $data['quantity'] ?? null;
        $status = $data['statut'] ?? 'en_attente';

        if (empty($productId) || !is_numeric($productId)) {
            throw new Exception('Produit invalide');
        }
        if (empty($userId)) {
            throw new Exception('Utilisateur invalide');
        }
        if (empty($quantity) || !is_numeric($quantity) || (int)$quantity <= 0) {
            throw new Exception('Quantité invalide (> 0)');
        }

        return $this->model->create([
            'product_id' => (int)$productId,
            'user_id' => (string)$userId,
            'quantity' => (int)$quantity,
            'statut' => $status
        ]);
    }

    public function updateStatus(int $id, string $status): ?array
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        $allowed = ['en_attente', 'validee', 'retiree', 'annulee'];
        if (!in_array($status, $allowed, true)) {
            throw new Exception('Statut invalide: ' . $status);
        }

        if (!$this->model->find($id)) {
            throw new Exception('Commande introuvable');
        }

        return $this->model->updateStatus($id, $status);
    }

    public function cancel(int $id): ?array
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        if (!$this->model->find($id)) {
            throw new Exception('Commande introuvable');
        }

        return $this->model->cancel($id);
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception('ID invalide');
        }

        if (!$this->model->find($id)) {
            throw new Exception('Commande introuvable');
        }

        return $this->model->delete($id);
    }
}
