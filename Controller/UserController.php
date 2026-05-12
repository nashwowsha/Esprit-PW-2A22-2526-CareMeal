<?php
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/Model/User.php";
require_once dirname(__DIR__) . "/Model/Profile.php";

class UserController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // -----------------------------
    // RequÃªtes SQL : login / register
    // -----------------------------
    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT u.id, u.email, u.password, u.role, u.status, u.created_at, u.failed_login_attempts, u.lockout_until,
                       p.nom, p.prenom, p.nom_entreprise, p.telephone, p.ecole, p.quartier, p.annee_etude, p.description,
                       p.linkedin, p.instagram, p.github, p.facebook, p.twitter, p.secteur_activite, p.site_web, p.points_accumules
                FROM users u
                LEFT JOIN profiles p ON u.id = p.user_id
                WHERE u.email = ?"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ["success" => false, "message" => "Aucun compte trouve avec cet email."];
            }

            $storedPassword = (string)($user['password'] ?? '');
            $passwordInfo = password_get_info($storedPassword);
            $algoName = (string)($passwordInfo['algoName'] ?? 'unknown');
            $isModernHash = !empty($storedPassword) && strtolower($algoName) !== 'unknown';
            $isLegacyPlain = !empty($storedPassword) && !$isModernHash;

            $passwordOk = false;
            if ($isModernHash) {
                $passwordOk = password_verify($password, $storedPassword);
            } elseif ($isLegacyPlain) {
                // Compat legacy: anciennes lignes en texte clair.
                $passwordOk = hash_equals($storedPassword, (string)$password);
            }

            // UX guard: user pasted a DB hash instead of the real password.
            // Do not count this as a brute-force attempt.
            if (!$passwordOk && preg_match('/^\$2[aby]\$\d{2}\$.{53}$/', (string)$password)) {
                return [
                    "success" => false,
                    "message" => "Le mot de passe colle semble etre un hash de base de donnees. Utilisez le vrai mot de passe en clair."
                ];
            }

            if (!$passwordOk && $user['lockout_until'] !== null && strtotime((string)$user['lockout_until']) > time()) {
                $minutesLeft = ceil((strtotime((string)$user['lockout_until']) - time()) / 60);
                return ["success" => false, "message" => "Compte temporairement bloque suite a trop d echecs. Reessayez dans $minutesLeft minute(s)."];
            }

            if ($passwordOk) {
                if ($user['status'] === 'banned') {
                    return ["success" => false, "message" => "Votre compte a ete suspendu."];
                }
                if ($user['status'] === 'inactive') {
                    return ["success" => false, "message" => "Votre compte n est pas encore actif ou a ete desactive."];
                }

                if (($user['failed_login_attempts'] ?? 0) > 0 || $user['lockout_until'] !== null) {
                    $resetStmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?");
                    $resetStmt->execute([$user['id']]);
                }

                if ($isLegacyPlain) {
                    $rehash = password_hash($password, PASSWORD_DEFAULT);
                    $rehashStmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $rehashStmt->execute([$rehash, $user['id']]);
                } elseif (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $rehash = password_hash($password, PASSWORD_DEFAULT);
                    $rehashStmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $rehashStmt->execute([$rehash, $user['id']]);
                }

                $Profile = new Profile($user);
                $User = new User(
                    $user['id'],
                    $user['email'],
                    $user['role'],
                    $user['status'],
                    $user['created_at'],
                    $Profile
                );
                return [
                    "success" => true,
                    "message" => "Connexion reussie.",
                    "user" => $User->toArray()
                ];
            }

            $attempts = ((int)($user['failed_login_attempts'] ?? 0)) + 1;
            $lockoutTime = null;
            $message = "Mot de passe incorrect.";

            if ($attempts >= 3) {
                $lockoutTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $message = "Trop de tentatives echouees. Votre compte est bloque pour 15 minutes.";
            }

            $updateStmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = ?, lockout_until = ? WHERE id = ?");
            $updateStmt->execute([$attempts, $lockoutTime, $user['id']]);

            return ["success" => false, "message" => $message];
        } catch(Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }
    public function registerStudent($nom, $prenom, $email, $password, $ecole, $annee, $telephone, $quartier, $sponsorCode = '') {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ["success" => false, "message" => "Cet email est deja utilise."];
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'student', 'active')");
            $stmt->execute([$email, $hashedPassword]);
            $userId = $this->conn->lastInsertId();

            // GÃ©nÃ©rer le code de parrainage pour ce nouvel utilisateur
            $stmtCode = $this->conn->prepare("UPDATE users SET referral_code = CONCAT('CARE-', id, '-', UPPER(LEFT(MD5(email), 4))) WHERE id = ?");
            $stmtCode->execute([$userId]);

            $pointsInitial = 0;

            // SystÃ¨me de Parrainage (MÃ©tier AvancÃ©)
            if (!empty($sponsorCode)) {
                $stmtSponsor = $this->conn->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmtSponsor->execute([$sponsorCode]);
                $sponsor = $stmtSponsor->fetch(PDO::FETCH_ASSOC);

                if ($sponsor) {
                    $sponsorId = $sponsor['id'];
                    // Le parrain gagne 100 points
                    $stmtUpdateSponsor = $this->conn->prepare("UPDATE profiles SET points_accumules = COALESCE(points_accumules, 0) + 100 WHERE user_id = ?");
                    $stmtUpdateSponsor->execute([$sponsorId]);

                    // Le filleul (nouvel inscrit) gagne 50 points bonus
                    $pointsInitial = 50;
                }
            }

            $stmt = $this->conn->prepare("INSERT INTO profiles (user_id, nom, prenom, telephone, ecole, annee_etude, quartier, points_accumules) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nom, $prenom, $telephone, $ecole, $annee, $quartier, $pointsInitial]);

            $this->conn->commit();

            $Profile = new Profile([
                'user_id' => $userId,
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone,
                'ecole' => $ecole,
                'annee_etude' => $annee,
                'quartier' => $quartier
            ]);
            $User = new User($userId, $email, 'student', 'active', date('Y-m-d H:i:s'), $Profile);
            return [
                "success" => true,
                "message" => "Inscription reussie. Vous pouvez maintenant vous connecter!",
                "user" => $User->toArray()
            ];
        } catch(Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function registerPartner($nomContact, $prenomContact, $email, $password, $nomEntreprise, $tel, $description) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ["success" => false, "message" => "Cet email est deja utilise."];
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'partner', 'active')");
            $stmt->execute([$email, $hashedPassword]);
            $userId = $this->conn->lastInsertId();

            $stmt = $this->conn->prepare("INSERT INTO profiles (user_id, nom, prenom, nom_entreprise, telephone, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nomContact, $prenomContact, $nomEntreprise, $tel, $description]);

            $this->conn->commit();

            $Profile = new Profile([
                'user_id' => $userId,
                'nom' => $nomContact,
                'prenom' => $prenomContact,
                'nom_entreprise' => $nomEntreprise,
                'telephone' => $tel,
                'description' => $description
            ]);
            $User = new User($userId, $email, 'partner', 'active', date('Y-m-d H:i:s'), $Profile);
            return [
                "success" => true,
                "message" => "Inscription partenaire reussie. Connectez-vous!",
                "user" => $User->toArray()
            ];
        } catch(Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ["success" => false, "message" => "Mot de passe actuel incorrect."];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            return ["success" => true, "message" => "Mot de passe modifie avec succes."];
        } catch(Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getConn() { return $this->conn; }

    // ============================================================
    // MOT DE PASSE OUBLIÃ‰ 
    // ============================================================
    
    public function forgotPasswordRequest($email) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.email, p.telephone 
                FROM users u 
                LEFT JOIN profiles p ON u.id = p.user_id 
                WHERE u.email = ? OR p.telephone = ?
            ");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate 6 digit code
                $code = sprintf("%06d", mt_rand(1, 999999));
                
                // Save in session with the real email (since it's needed for reset)
                $_SESSION['reset_code'] = $code;
                $_SESSION['reset_identifier'] = $email; // This is what the user typed (email or phone)
                $_SESSION['reset_real_email'] = $user['email']; // True email for DB update
                $_SESSION['reset_time'] = time();

                $isEmailInput = filter_var($email, FILTER_VALIDATE_EMAIL);

                if ($isEmailInput) {
                    $emailResult = $this->sendBrevoEmail($user['email'], $code);
                    $emailData = json_decode($emailResult, true);
                    if (isset($emailData['code'])) {
                        return ["success" => false, "message" => "Erreur d'envoi email : " . ($emailData['message'] ?? $emailData['code'])];
                    }
                    $methodMsg = "un email a été envoyé.";
                } else {
                    if (!empty($user['telephone'])) {
                        $phone = trim($user['telephone']);
                        if (preg_match('/^[0-9]{8}$/', $phone)) {
                            $phone = '+216' . $phone;
                        } elseif (!str_starts_with($phone, '+')) {
                            $phone = '+216' . $phone;
                        }

                        $smsResponse = $this->sendBrevoSMS($phone, $code);
                        $smsResult = json_decode($smsResponse, true);

                        if (isset($smsResult['code'])) {
                            return ["success" => false, "message" => "Erreur Brevo SMS : " . $smsResult['message'] . " (" . $smsResult['code'] . ")"];
                        }

                        $methodMsg = "un SMS a été envoyé.";
                    } else {
                        $emailResult = $this->sendBrevoEmail($user['email'], $code);
                        $emailData = json_decode($emailResult, true);
                        if (isset($emailData['code'])) {
                            return ["success" => false, "message" => "Erreur d'envoi email : " . ($emailData['message'] ?? $emailData['code'])];
                        }
                        $methodMsg = "un email a été envoyé.";
                    }
                }
            }

            return ["success" => true, "message" => "Si un compte existe, " . ($methodMsg ?? "un code a été envoyé.")];
        } catch(Exception $e) {
            error_log("[forgotPasswordRequest] Exception: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur système."];
        }
    }

    public function verifyResetCode($identifier, $code) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (isset($_SESSION['reset_code']) && isset($_SESSION['reset_identifier'])) {
            if ($_SESSION['reset_identifier'] === $identifier && $_SESSION['reset_code'] === $code) {
                // Check expiration (15 minutes = 900 seconds)
                if (time() - $_SESSION['reset_time'] <= 900) {
                    return ["success" => true, "message" => "Code valide."];
                } else {
                    return ["success" => false, "message" => "Ce code a expiré."];
                }
            }
        }
        return ["success" => false, "message" => "Code invalide."];
    }

    public function resetPasswordFinal($identifier, $code, $newPassword) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Verify code again
        $verify = $this->verifyResetCode($identifier, $code);
        if (!$verify['success']) {
            return $verify;
        }

        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $realEmail = $_SESSION['reset_real_email']; // use the true email we found earlier
            
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $realEmail]);

            // Clear session variables
            unset($_SESSION['reset_code']);
            unset($_SESSION['reset_identifier']);
            unset($_SESSION['reset_real_email']);
            unset($_SESSION['reset_time']);

            return ["success" => true, "message" => "Mot de passe modifié avec succès."];
        } catch(Exception $e) {
            return ["success" => false, "message" => "Erreur système."];
        }
    }

    private function getBrevoApiKey($type) {
        $secretsFile = dirname(__DIR__) . '/config/secrets.php';
        if (file_exists($secretsFile)) {
            require_once $secretsFile;
        }
        if ($type === 'email' && defined('BREVO_EMAIL_API_KEY')) {
            return BREVO_EMAIL_API_KEY;
        } elseif ($type === 'sms' && defined('BREVO_SMS_API_KEY')) {
            return BREVO_SMS_API_KEY;
        }
        return 'votre_cle_api_brevo_ici';
    }

    private function sendBrevoEmail($toEmail, $code) {
        $apiKey = $this->getBrevoApiKey('email');

        $data = [
            "sender" => ["name" => "CareMeal", "email" => "sloficloud@gmail.com"],
            "to" => [["email" => $toEmail]],
            "subject" => "Code de réinitialisation de mot de passe",
            "htmlContent" => "<html><body><h2>Réinitialisation de mot de passe</h2><p>Votre code de vérification est : <strong>$code</strong></p><p>Ce code expire dans 15 minutes.</p></body></html>"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log("[Brevo Email] cURL error: $curlError");
        } elseif ($httpCode >= 400) {
            error_log("[Brevo Email] HTTP $httpCode response: $result");
        }

        return $result;
    }

    private function sendBrevoSMS($phone, $code) {
        $apiKey = $this->getBrevoApiKey('sms');

        $data = [
            "type" => "transactional",
            "unicodeEnabled" => false,
            "sender" => "CareMeal",
            "recipient" => $phone,
            "content" => "Votre code de réinitialisation CareMeal est : $code. Il expire dans 15 minutes."
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/transactionalSMS/sms');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log("[Brevo SMS] cURL error: $curlError");
        } elseif ($httpCode >= 400) {
            error_log("[Brevo SMS] HTTP $httpCode response: $result");
        }

        return $result;
    }

    public function handleRequest() {
        header("Content-Type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) { $data = $_POST; }
        $action = $_GET["action"] ?? ($data["action"] ?? "");

        // ============================================================
        // CRUD - READ : RÃ©cupÃ©rer la liste de tous les utilisateurs
        // ============================================================
        if ($action === "get_users") {
            $stmt = $this->conn->prepare("SELECT u.id, u.email, u.role, u.status, u.created_at, p.nom, p.prenom, p.nom_entreprise, p.ecole, p.quartier, p.secteur_activite, p.site_web, p.telephone FROM users u LEFT JOIN profiles p ON u.id = p.user_id");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formattedUsers = array_map(function($u) {
                $name = "Sans nom";
                if (!empty($u["nom"]) && !empty($u["prenom"])) {
                    $name = $u["prenom"] . " " . $u["nom"];
                } elseif (!empty($u["nom_entreprise"])) {
                    $name = $u["nom_entreprise"];
                } else {
                    $name = $u["email"];
                }

                return [
                    "id" => (int)$u["id"],
                    "email" => $u["email"],
                    "role" => $u["role"],
                    "status" => $u["status"],
                    "createdAt" => $u["created_at"],
                    "name" => $name,
                    "firstName" => $u["prenom"] ?? "",
                    "lastName" => $u["nom"] ?? "",
                    "establishmentName" => $u["nom_entreprise"] ?? "",
                    "university" => $u["ecole"] ?? "",
                    "quartier" => $u["quartier"] ?? "",
                    "type" => $u["secteur_activite"] ?? "",
                    "address" => $u["site_web"] ?? "",
                    "phone" => $u["telephone"] ?? "",
                    "points" => 0,
                    "co2Saved" => 0,
                    "mealsSaved" => 0,
                    "ordersCount" => 0
                ];
            }, $users);

            echo json_encode(["success" => true, "users" => $formattedUsers]);
            exit;
        }

        // ============================================================
        // CRUD - UPDATE : Modifier le statut d'un utilisateur (ban/activer)
        // ============================================================
        if ($action === "update_user_status") {
            $userId = $data["user_id"] ?? null;
            $status = $data["status"] ?? null;

            if (!$userId || !$status) {
                echo json_encode(["success" => false, "message" => "ID et statut requis"]);
                exit;
            }

            if (!in_array($status, ['active', 'banned'])) {
                echo json_encode(["success" => false, "message" => "Statut invalide. Utilisez 'active' ou 'banned'."]);
                exit;
            }

            // Exemple POO : crÃ©ation User, setStatus, puis usage des getters
            $User = new User($userId, '', '', $status, null);
            $User->setStatus($status);
            try {
                $stmt = $this->conn->prepare("UPDATE users SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $User->getStatus(), ':id' => $User->getId()]);

                echo json_encode(["success" => true, "message" => "Statut mis Ã  jour"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Erreur base de donnÃ©es"]);
            }
            exit;
        }

        // ============================================================
        // CRUD - DELETE : Supprimer un utilisateur (par l'admin)
        // ============================================================
        if ($action === "delete_user") {
            $userId = $data["user_id"] ?? null;

            if (!$userId) {
                echo json_encode(["success" => false, "message" => "ID requis"]);
                exit;
            }

            try {
                // Delete linked profile first
                $stmtProfile = $this->conn->prepare("DELETE FROM profiles WHERE user_id = :id");
                $stmtProfile->execute([':id' => $userId]);

                // Delete user
                $stmtUser = $this->conn->prepare("DELETE FROM users WHERE id = :id");
                $stmtUser->execute([':id' => $userId]);

                echo json_encode(["success" => true, "message" => "Utilisateur supprimÃ©"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Erreur suppression"]);
            }
            exit;
        }

        // ============================================================
        // CRUD - UPDATE : Modifier le profil partenaire + CONTROLE DE SAISIE cÃ´tÃ© serveur
        // ============================================================
        if ($action === "update_partner_profile") {
            $userId = $data["user_id"] ?? null;

            if (!$userId) {
                echo json_encode(["success" => false, "message" => "ID utilisateur requis"]);
                exit;
            }

            if (empty(trim($data['nom_entreprise'] ?? ''))) {
                echo json_encode(["success" => false, "message" => "Le nom de l'Ã©tablissement est requis."]);
                exit;
            }

            if (empty(trim($data['nom'] ?? '')) || empty(trim($data['prenom'] ?? ''))) {
                echo json_encode(["success" => false, "message" => "Votre nom et prÃ©nom (contact) sont requis."]);
                exit;
            }

            $tel = trim($data['telephone'] ?? '');
            if (!empty($tel) && !preg_match('/^[0-9\+\s\-]{8,15}$/', $tel)) {
                echo json_encode(["success" => false, "message" => "Le format du numÃ©ro de tÃ©lÃ©phone est invalide."]);
                exit;
            }

            $urls = [
                "Site Web" => trim($data['site_web'] ?? ''),
                "LinkedIn" => trim($data['linkedin'] ?? ''),
                "Facebook" => trim($data['facebook'] ?? ''),
                "Instagram" => trim($data['instagram'] ?? ''),
                "Twitter" => trim($data['twitter'] ?? '')
            ];
            foreach ($urls as $name => $url) {
                if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                    echo json_encode(["success" => false, "message" => "Le format de l'URL pour {$name} est invalide (n'oubliez pas le http:// ou https://)."]);
                    exit;
                }
            }

            try {
                $stmt = $this->conn->prepare(""
                    . "UPDATE profiles SET 
                        nom_entreprise = :nom_entreprise,
                        secteur_activite = :secteur_activite,
                        site_web = :site_web,
                        telephone = :telephone,
                        description = :description,
                        linkedin = :linkedin,
                        facebook = :facebook,
                        instagram = :instagram,
                        twitter = :twitter,
                        nom = :nom,
                        prenom = :prenom
                    WHERE user_id = :user_id"
                );

                $stmt->execute([
                    ':nom_entreprise' => $data['nom_entreprise'] ?? null,
                    ':secteur_activite' => $data['secteur_activite'] ?? null,
                    ':site_web' => $data['site_web'] ?? null,
                    ':telephone' => $data['telephone'] ?? null,
                    ':description' => $data['description'] ?? null,
                    ':linkedin' => $data['linkedin'] ?? null,
                    ':facebook' => $data['facebook'] ?? null,
                    ':instagram' => $data['instagram'] ?? null,
                    ':twitter' => $data['twitter'] ?? null,
                    ':nom' => $data['nom'] ?? null,
                    ':prenom' => $data['prenom'] ?? null,
                    ':user_id' => $userId
                ]);

                echo json_encode(["success" => true, "message" => "Profil mis Ã  jour avec succÃ¨s dans la base de donnÃ©es"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Erreur lors de la mise Ã  jour: " . $e->getMessage()]);
            }
            exit;
        }
        
    }
}


if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new UserController();
    $controller->handleRequest();
}
?>
