<?php
require_once dirname(__DIR__) . '/config/database.php';

class Profile {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Lit les données du profil d'un utilisateur avec une JOINTURE (INNER JOIN) vers la table users
     * Cela répond directement à l'exigence : "travailler sur la deuxième entité ainsi que sur la jointure associée"
     */
    // ================================================================
    // CRUD - READ : Lire le profil avec JOINTURE vers la table users
    // ================================================================
    public function getProfileWithUser($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.email, u.role, u.status, u.created_at as user_created_at
                FROM profiles p
                INNER JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return ["success" => true, "data" => $result];
            } else {
                return ["success" => false, "message" => "Profil introuvable."];
            }
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur PDO: " . $e->getMessage()];
        }
    }

    /**
     * Met à jour les informations du profil existant (CRUD - Update)
     */
    // ================================================================
    // CRUD - UPDATE : Modifier le profil étudiant (UPDATE profiles)
    // ================================================================
    public function updateProfile($userId, $nom, $prenom, $telephone, $ecole, $quartier, $linkedin, $github, $instagram, $facebook, $twitter) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE profiles 
                SET nom = :nom, prenom = :prenom, telephone = :tel, ecole = :ecole, 
                    quartier = :quartier, linkedin = :linkedin, github = :github, 
                    instagram = :instagram, facebook = :facebook, twitter = :twitter 
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                ':nom' => $nom, ':prenom' => $prenom, ':tel' => $telephone,
                ':ecole' => $ecole, ':quartier' => $quartier,
                ':linkedin' => $linkedin, ':github' => $github,
                ':instagram' => $instagram, ':facebook' => $facebook,
                ':twitter' => $twitter, ':user_id' => $userId
            ]);
            
            return ["success" => true, "message" => "Profil mis à jour avec succès !"];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur PDO: " . $e->getMessage()];
        }
    }

    /**
     * Met à jour les informations du profil partenaire
     */
    // ================================================================
    // CRUD - UPDATE : Modifier le profil partenaire (UPDATE profiles)
    // ================================================================
    public function updatePartnerProfile($userId, $nomEntreprise, $nom, $prenom, $telephone, $description, $linkedin, $facebook, $instagram, $twitter, $github) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE profiles 
                SET nom_entreprise = :nom_entreprise, 
                    nom = :nom, prenom = :prenom, 
                    telephone = :tel, 
                    description = :description, 
                    linkedin = :linkedin, facebook = :facebook, 
                    instagram = :instagram, twitter = :twitter, 
                    github = :github 
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                ':nom_entreprise' => $nomEntreprise, 
                ':nom' => $nom, 
                ':prenom' => $prenom, 
                ':tel' => $telephone,
                ':description' => $description,
                ':linkedin' => $linkedin, 
                ':facebook' => $facebook,
                ':instagram' => $instagram, 
                ':twitter' => $twitter, 
                ':github' => $github,
                ':user_id' => $userId
            ]);
            
            return ["success" => true, "message" => "Profil partenaire mis à jour avec succès !"];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur PDO: " . $e->getMessage()];
        }
    }
}
?>