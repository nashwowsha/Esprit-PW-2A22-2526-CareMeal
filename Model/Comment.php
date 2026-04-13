<?php
require_once __DIR__ . '/../config/database.php';

class Comment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getByPublication($id_publication) {
        $sql = "SELECT * FROM commentaire WHERE id_publication = ? ORDER BY date_commentaire ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_publication]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($contenu, $id_publication, $auteur_type, $auteur_id) {
        $sql = "INSERT INTO commentaire (contenu_commentaire, id_publication, auteur_type, auteur_id)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$contenu, $id_publication, $auteur_type, $auteur_id]);
    }

    public function update($id, $contenu) {
        $sql = "UPDATE commentaire SET contenu_commentaire = ? WHERE id_commentaire = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$contenu, $id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM commentaire WHERE id_commentaire = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>