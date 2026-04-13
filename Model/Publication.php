<?php
require_once __DIR__ . '/../config/database.php';

class Publication {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT * FROM publication ORDER BY date_publication DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($contenu, $auteur_type, $auteur_id) {
        $sql = "INSERT INTO publication (contenu_publication, auteur_type, auteur_id)
                VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$contenu, $auteur_type, $auteur_id]);
    }

    public function update($id, $contenu) {
        $sql = "UPDATE publication SET contenu_publication = ? WHERE id_publication = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$contenu, $id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM publication WHERE id_publication = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>