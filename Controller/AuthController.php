<?php
require_once dirname(__DIR__) . '/Model/User.php';

class AuthController {
    public function handleRequest() {
        header('Content-Type: application/json; charset=utf-8');

        // Allow CORS if needed (for localhost development)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Methode non autorisee."]);
            return;
        }

        // Get JSON payload vs Form Data
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) { $data = $_POST; }

        $action = $data['action'] ?? '';

        $userModel = new User();

        // ============================================================
        // CRUD - READ : Connexion d'un utilisateur (SELECT FROM users)
        // ============================================================
        if ($action === 'login') {
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            $res = $userModel->login($email, $password);
            
            // Gestion de session si le login a reussi
            if ($res['success']) {
                session_start();
                $_SESSION['user_id'] = $res['user']['id'];
                $_SESSION['user_role'] = $res['user']['role'];
            }
            
            echo json_encode($res);
            return;
        }

        
        // ============================================================
        // CRUD - READ : Récupérer les infos de l'utilisateur connecté
        // ============================================================
        if ($action === 'get-me') {
            session_start();
            $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                echo json_encode(["success" => false, "message" => "Non connecte."]);
                return;
            }

            $stmt = $userModel->getConn()->prepare("
                SELECT u.id as user_id, u.id, u.email, u.role, u.status, u.created_at, 
                       p.nom, p.prenom, p.nom_entreprise, p.telephone, p.ecole, p.quartier, p.annee_etude, p.description,
                       p.linkedin, p.instagram, p.github, p.facebook, p.twitter, p.secteur_activite, p.site_web, p.points_accumules
                FROM users u
                LEFT JOIN profiles p ON u.id = p.user_id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Formatting for consistency with JS 
                $name = "Utilisateur";
                if (!empty($user["nom"]) && !empty($user["prenom"])) {
                    $name = $user["prenom"] . " " . $user["nom"];
                } elseif (!empty($user["nom_entreprise"])) {
                    $name = $user["nom_entreprise"];
                } else {
                    $name = $user["email"];
                }
                $user["name"] = $name;
                // fallback phone
                if (empty($user['phone']) && !empty($user['telephone'])) {
                    $user['phone'] = $user['telephone'];
                }

                echo json_encode(["success" => true, "user" => $user]);
            } else {
                echo json_encode(["success" => false, "message" => "Introuvable."]);
            }
            return;
        }
        // ============================================================
        // CONTROLE DE SAISIE côté serveur : Vérifier si l'email existe
        // ============================================================
        if ($action === 'check-email') {
            $email = trim($data['email'] ?? '');
            
            $stmt = $userModel->getConn()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(["success" => false, "message" => "Cet email est deja utilise."]);
            } else {
                echo json_encode(["success" => true, "message" => "Email disponible."]);
            }
            return;
        }

        // ============================================================
        // CRUD - CREATE : Inscription d'un étudiant
        // ============================================================
        if ($action === 'register-student') {
            $nom = trim($data['nom'] ?? '');
            $prenom = trim($data['prenom'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $ecole = trim($data['ecole'] ?? '');
            $annee = trim($data['annee'] ?? '');
            $tel = trim($data['telephone'] ?? '');
            $quartier = trim($data['quartier'] ?? '');

            $res = $userModel->registerStudent($nom, $prenom, $email, $password, $ecole, $annee, $tel, $quartier);
            echo json_encode($res);

        // ============================================================
        // CRUD - CREATE : Inscription d'un partenaire
        // ============================================================
        } elseif ($action === 'register-partner') {
            $nomEntreprise = trim($data['nom_entreprise'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $nomContact = trim($data['nom_contact'] ?? '');
            $prenomContact = trim($data['prenom_contact'] ?? '');
            $tel = trim($data['telephone'] ?? '');
            $desc = trim($data['description'] ?? '');

            $res = $userModel->registerPartner($nomContact, $prenomContact, $email, $password, $nomEntreprise, $tel, $desc);
            echo json_encode($res);

        // ============================================================
        // CRUD - UPDATE : Modifier le profil étudiant
        // ============================================================
        } elseif ($action === 'update-profile') {
            session_start();
            $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                echo json_encode(["success" => false, "message" => "Utilisateur non connecte."]);
                return;
            }

            $nom = trim($data['nom'] ?? '');
            $prenom = trim($data['prenom'] ?? '');
            $tel = trim($data['telephone'] ?? '');
            $ecole = trim($data['ecole'] ?? '');
            $quartier = trim($data['quartier'] ?? '');
            $linkedin = trim($data['linkedin'] ?? '');
            $github = trim($data['github'] ?? '');
            $instagram = trim($data['instagram'] ?? '');
            $facebook = trim($data['facebook'] ?? '');
            $twitter = trim($data['twitter'] ?? '');

            // Utilisation de la 2eme Entite: Profile
            require_once dirname(__DIR__) . '/Model/Profile.php';
            $profileModel = new Profile();
            $res = $profileModel->updateProfile($userId, $nom, $prenom, $tel, $ecole, $quartier, $linkedin, $github, $instagram, $facebook, $twitter);
            echo json_encode($res);

                // ============================================================
        // CRUD - UPDATE : Modifier le profil partenaire
        // ============================================================
        } elseif ($action === 'update-partner-profile') {
            session_start();
            $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                echo json_encode(["success" => false, "message" => "Utilisateur non connecte."]);
                return;
            }

            $nomEntreprise = trim($data['nom_entreprise'] ?? '');
            $nom = trim($data['nom'] ?? '');
            $prenom = trim($data['prenom'] ?? '');
            $tel = trim($data['telephone'] ?? '');
            $desc = trim($data['description'] ?? '');
            $linkedin = trim($data['linkedin'] ?? '');
            $facebook = trim($data['facebook'] ?? '');
            $instagram = trim($data['instagram'] ?? '');
            $twitter = trim($data['twitter'] ?? '');
            $github = trim($data['github'] ?? '');

            require_once dirname(__DIR__) . '/Model/Profile.php';
            $profileModel = new Profile();
            $res = $profileModel->updatePartnerProfile($userId, $nomEntreprise, $nom, $prenom, $tel, $desc, $linkedin, $facebook, $instagram, $twitter, $github);
            echo json_encode($res);

        // ============================================================
        // CRUD - UPDATE : Modifier le mot de passe
        // ============================================================
        } elseif ($action === 'update-password') {
            session_start();
            $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                echo json_encode(["success" => false, "message" => "Utilisateur non connecte."]);
                return;
            }

            $currentPassword = $data['current_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';

            $res = $userModel->updatePassword($userId, $currentPassword, $newPassword);
            echo json_encode($res);

        // ============================================================
        // CRUD - DELETE : Supprimer son propre compte
        // ============================================================
        } elseif ($action === 'delete-account') {
            session_start();
            $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                echo json_encode(["success" => false, "message" => "Utilisateur non connecte."]);
                return;
            }

            try {
                // Delete user from db. Cascade will delete profile and other data
                $stmt = $userModel->getConn()->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                session_destroy();
                echo json_encode(["success" => true, "message" => "Compte supprime avec succes."]);
            } catch(Exception $e) {
                echo json_encode(["success" => false, "message" => "Erreur lors de la suppression."]);
            }

        } else {
            echo json_encode(["success" => false, "message" => "Action non reconnue."]);
        }
    }
}


if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new AuthController();
    $controller->handleRequest();
}
?>