<?php
require_once dirname(__DIR__) . '/config/database.php';

class User {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function getConn() {
        return $this->conn;
    }

    // ================================================================
    // CRUD - CREATE : Inscription d'un étudiant (INSERT INTO users)
    // ================================================================
    public function registerStudent($nom, $prenom, $email, $password, $ecole, $annee, $telephone, $quartier) {
        try {
            $this->conn->beginTransaction();

            // 1. Check if email exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ["success" => false, "message" => "Cet email est deja utilise."];
            }

            // 2. Insert into users
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'student', 'active')");
            $stmt->execute([$email, $hashedPassword]);
            $userId = $this->conn->lastInsertId();

            // 3. Insert into profiles
            $stmt = $this->conn->prepare("INSERT INTO profiles (user_id, nom, prenom, telephone, ecole, annee_etude, quartier) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nom, $prenom, $telephone, $ecole, $annee, $quartier]);

            $this->conn->commit();
            return ["success" => true, "message" => "Inscription reussie. Vous pouvez maintenant vous connecter!"];
        } catch(Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    // ================================================================
    // CRUD - READ : Connexion (SELECT FROM users WHERE email = ?)
    // ================================================================
    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.email, u.password, u.role, u.status, u.created_at, 
                       p.nom, p.prenom, p.nom_entreprise, p.telephone, p.ecole, p.quartier, p.annee_etude, p.description,
                       p.linkedin, p.instagram, p.github, p.facebook, p.twitter, p.secteur_activite, p.site_web
                FROM users u
                LEFT JOIN profiles p ON u.id = p.user_id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    if ($user['status'] === 'banned') {
                        return ["success" => false, "message" => "Votre compte a été suspendu."];
                    }
                    if ($user['status'] === 'inactive') {
                        return ["success" => false, "message" => "Votre compte n'est pas encore actif."];
                    }
                    
                    // Nettoyage du mot de passe avant d'envoyer l'utilisateur
                    unset($user['password']);
                    
                    return [
                        "success" => true, 
                        "message" => "Connexion reussie.",
                        "user" => $user
                    ];
                } else {
                    return ["success" => false, "message" => "Mot de passe incorrect."];
                }
            } else {
                return ["success" => false, "message" => "Aucun compte trouve avec cet email."];
            }
        } catch(Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    // ================================================================
    // CRUD - CREATE : Inscription d'un partenaire (INSERT INTO users)
    // ================================================================
    public function registerPartner($nomContact, $prenomContact, $email, $password, $nomEntreprise, $tel, $description) {
        try {
            $this->conn->beginTransaction();

            // 1. Check if email exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ["success" => false, "message" => "Cet email est deja utilise."];
            }

            // 2. Insert into users
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'partner', 'active')");
            $stmt->execute([$email, $hashedPassword]);
            $userId = $this->conn->lastInsertId();

            // 3. Insert into profiles
            $stmt = $this->conn->prepare("INSERT INTO profiles (user_id, nom, prenom, nom_entreprise, telephone, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nomContact, $prenomContact, $nomEntreprise, $tel, $description]);

            $this->conn->commit();
            return ["success" => true, "message" => "Inscription partenaire reussie. Connectez-vous!"];
        } catch(Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }
    public function updateProfile($userId, $nom, $prenom, $telephone, $ecole, $quartier, $linkedin, $github, $instagram, $facebook, $twitter) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE profiles 
                SET nom = ?, prenom = ?, telephone = ?, ecole = ?, quartier = ?, linkedin = ?, github = ?, instagram = ?, facebook = ?, twitter = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$nom, $prenom, $telephone, $ecole, $quartier, $linkedin, $github, $instagram, $facebook, $twitter, $userId]);
            return ["success" => true, "message" => "Profil mis a jour avec succes."];
        } catch(Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    // ================================================================
    // CRUD - UPDATE : Modifier le mot de passe (UPDATE users SET password)
    // ================================================================
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // First check old password
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ["success" => false, "message" => "Mot de passe actuel incorrect."];
            }

            // Update new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            return ["success" => true, "message" => "Mot de passe modifie avec succes."];
        } catch(Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }
}
?>
