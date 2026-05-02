<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/Model/User.php';
require_once dirname(__DIR__) . '/Controller/UserController.php';

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

        $db = new Database();
        $conn = $db->getConnection();
        $userController = new UserController();

        // ============================================================
        // CRUD - READ : Connexion d'un utilisateur (SELECT FROM users)
        // ============================================================
        if ($action === 'login') {
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            $res = $userController->login($email, $password);
            
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

            $stmt = $conn->prepare("
                SELECT u.id as user_id, u.id, u.email, u.role, u.status, u.created_at, u.referral_code, 
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
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
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
            $sponsorCode = trim($data['sponsorCode'] ?? '');
            $ecole = trim($data['ecole'] ?? '');
            $annee = trim($data['annee'] ?? '');
            $tel = trim($data['telephone'] ?? '');
            $quartier = trim($data['quartier'] ?? '');

            $res = $userController->registerStudent($nom, $prenom, $email, $password, $ecole, $annee, $tel, $quartier, $sponsorCode);
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

            $res = $userController->registerPartner($nomContact, $prenomContact, $email, $password, $nomEntreprise, $tel, $desc);
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

            // Utilisation du controleur profil
            require_once dirname(__DIR__) . '/Controller/ProfileController.php';
            $profileController = new ProfileController();
            $res = $profileController->updateProfile($userId, $nom, $prenom, $tel, $ecole, $quartier, $linkedin, $github, $instagram, $facebook, $twitter);
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

            require_once dirname(__DIR__) . '/Controller/ProfileController.php';
            $profileController = new ProfileController();
            $res = $profileController->updatePartnerProfile($userId, $nomEntreprise, $nom, $prenom, $tel, $desc, $linkedin, $facebook, $instagram, $twitter, $github);
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

            $res = $userController->updatePassword($userId, $currentPassword, $newPassword);
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
                // RGPD Soft Delete : Anonymisation au lieu d'une suppression physique (Métier Avancé)
                $anonymEmail = "anonyme_" . time() . "_" . $userId . "@caremeal.tn";
                
                // 1. Anonymiser la table users et désactiver le compte
                $stmt = $conn->prepare("UPDATE users SET email = ?, password = '', status = 'inactive' WHERE id = ?");
                $stmt->execute([$anonymEmail, $userId]);
                
                // 2. Anonymiser la table profiles
                $stmt2 = $conn->prepare("UPDATE profiles SET nom = 'Anonyme', prenom = 'Utilisateur', telephone = NULL, avatar = NULL, linkedin = NULL, instagram = NULL, facebook = NULL, twitter = NULL, github = NULL, ecole = NULL, annee_etude = NULL, quartier = NULL, nom_entreprise = NULL, description = NULL, site_web = NULL, secteur_activite = NULL WHERE user_id = ?");
                $stmt2->execute([$userId]);

                session_destroy();
                echo json_encode(["success" => true, "message" => "Compte supprimé (anonymisé) avec succès selon les normes RGPD."]);
            } catch(Exception $e) {
                echo json_encode(["success" => false, "message" => "Erreur lors de la suppression."]);
            }

        // ============================================================
        // Deconnexion de l'utilisateur (Destruction session)
        // ============================================================
        } elseif ($action === 'logout') {
            session_start();
            session_unset();
            session_destroy();
            echo json_encode(["success" => true, "message" => "Déconnecté"]);
        // ============================================================
        // CRUD - CREATE : Demande de réinitialisation de mot de passe
        // ============================================================
        } elseif ($action === 'forgot-password-request') {
            $email = trim($data['email'] ?? '');
            if (!$email) {
                echo json_encode(["success" => false, "message" => "L'email est requis."]);
                return;
            }
            $res = $userController->forgotPasswordRequest($email);
            echo json_encode($res);

        // ============================================================
        // CRUD - READ : Vérification du code de réinitialisation
        // ============================================================
        } elseif ($action === 'verify-reset-code') {
            $email = trim($data['email'] ?? '');
            $code = trim($data['code'] ?? '');

            if (!$email || !$code) {
                echo json_encode(["success" => false, "message" => "Email et code sont requis."]);
                return;
            }
            $res = $userController->verifyResetCode($email, $code);
            echo json_encode($res);

        // ============================================================
        // CRUD - UPDATE : Mise à jour finale du mot de passe
        // ============================================================
        } elseif ($action === 'reset-password-final') {
            $email = trim($data['email'] ?? '');
            $code = trim($data['code'] ?? '');
            $newPassword = $data['new_password'] ?? '';

            if (!$email || !$code || !$newPassword) {
                echo json_encode(["success" => false, "message" => "Tous les champs sont requis."]);
                return;
            }
            $res = $userController->resetPasswordFinal($email, $code, $newPassword);
            echo json_encode($res);

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
