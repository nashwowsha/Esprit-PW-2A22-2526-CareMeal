<?php
require_once __DIR__ . '/../config/database.php';

class Order
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(?string $userId = null): array
    {
        if ($userId !== null && $userId !== '') {
            $sql = "SELECT o.*, p.nom AS product_name, p.prix_commande AS product_price FROM commande o LEFT JOIN produit p ON o.id_produit = p.id_produit WHERE o.id_user = :uid ORDER BY o.date_commande DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
        } else {
            $sql = "SELECT o.*, p.nom AS product_name, p.prix_commande AS product_price FROM commande o LEFT JOIN produit p ON o.id_produit = p.id_produit ORDER BY o.date_commande DESC";
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT o.*, p.nom AS product_name, p.prix_commande AS product_price FROM commande o LEFT JOIN produit p ON o.id_produit = p.id_produit WHERE o.id_commande = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $productId = (int)$data['product_id'];
        $qty = (int)$data['quantity'];

        if ($qty <= 0) {
            throw new Exception('Quantité invalide');
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('SELECT * FROM produit WHERE id_produit = :id FOR UPDATE');
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $this->pdo->rollBack();
                throw new Exception('Produit introuvable');
            }

            if (!(int)$product['actif']) {
                $this->pdo->rollBack();
                throw new Exception('Produit inactif');
            }

            if ((int)$product['stock'] < $qty) {
                $this->pdo->rollBack();
                throw new Exception('Stock insuffisant');
            }

            $unitPrice = (float)$product['prix_commande'];
            $normalPrice = (float)$product['prix_normal'];
            $total = round($unitPrice * $qty, 2);

            $insert = $this->pdo->prepare('INSERT INTO commande (id_produit, id_user, quantite, prix_unitaire_snapshot, prix_normal_snapshot, total, statut) VALUES (:id_produit, :id_user, :quantite, :prix_unitaire_snapshot, :prix_normal_snapshot, :total, :statut)');
            $insert->execute([
                ':id_produit' => $productId,
                ':id_user' => $data['user_id'],
                ':quantite' => $qty,
                ':prix_unitaire_snapshot' => $unitPrice,
                ':prix_normal_snapshot' => $normalPrice,
                ':total' => $total,
                ':statut' => $data['statut'] ?? 'en_attente'
            ]);

            $orderId = (int)$this->pdo->lastInsertId();

            $newStock = (int)$product['stock'] - $qty;
            $upd = $this->pdo->prepare('UPDATE produit SET stock = :stock, updated_at = CURRENT_TIMESTAMP WHERE id_produit = :id');
            $upd->execute([':stock' => $newStock, ':id' => $productId]);

            $this->pdo->commit();
            return $this->find($orderId);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): ?array
    {
        $allowed = ['en_attente', 'validee', 'retiree', 'annulee'];
        if (!in_array($status, $allowed, true)) {
            throw new Exception('Statut invalide');
        }
        $stmt = $this->pdo->prepare('UPDATE commande SET statut = :statut WHERE id_commande = :id');
        $stmt->execute([':statut' => $status, ':id' => $id]);
        return $this->find($id);
    }

    public function cancel(int $id): ?array
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('SELECT * FROM commande WHERE id_commande = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $order = $stmt->fetch();

            if (!$order) {
                $this->pdo->rollBack();
                throw new Exception('Commande introuvable');
            }

            if ($order['statut'] !== 'en_attente') {
                $this->pdo->rollBack();
                throw new Exception('Seules les commandes en attente peuvent être annulées');
            }

            $productStmt = $this->pdo->prepare('SELECT stock FROM produit WHERE id_produit = :id FOR UPDATE');
            $productStmt->execute([':id' => $order['id_produit']]);
            $product = $productStmt->fetch();

            if ($product) {
                $newStock = (int)$product['stock'] + (int)$order['quantite'];
                $upd = $this->pdo->prepare('UPDATE produit SET stock = :stock, updated_at = CURRENT_TIMESTAMP WHERE id_produit = :id');
                $upd->execute([':stock' => $newStock, ':id' => $order['id_produit']]);
            }

            $statusStmt = $this->pdo->prepare('UPDATE commande SET statut = :statut WHERE id_commande = :id');
            $statusStmt->execute([':statut' => 'annulee', ':id' => $id]);

            $this->pdo->commit();
            return $this->find($id);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM commande WHERE id_commande = :id');
        return $stmt->execute([':id' => $id]);
    }
}
