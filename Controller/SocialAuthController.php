<?php
/**
 * SocialAuthController.php
 * GÃ¨re les callbacks OAuth pour GitHub
 * Google et Facebook sont gÃ©rÃ©s cÃ´tÃ© JS via leurs SDK
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/oauth.php';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/Model/User.php';

class SocialAuthController {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // ================================================================
    // CRUD - CREATE/READ : Connexion ou crÃ©ation via rÃ©seau social
    // Trouve l'utilisateur par email social, ou le crÃ©e s'il n'existe pas
    // ================================================================
    public function loginOrCreate($email, $name, $provider, $providerId, $avatar = null) {
        // Chercher si l'utilisateur existe dÃ©jÃ 
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // CrÃ©er le compte automatiquement
            $referralCode = strtoupper(substr(md5(uniqid()), 0, 8));
            $stmt = $this->conn->prepare(
                "INSERT INTO users (email, password, role, status, referral_code) VALUES (?, '', 'student', 'active', ?)"
            );
            $stmt->execute([$email, $referralCode]);
            $userId = $this->conn->lastInsertId();

            // Extraire nom/prÃ©nom
            $parts = explode(' ', trim($name), 2);
            $prenom = $parts[0] ?? $name;
            $nom    = $parts[1] ?? '';

            $stmt2 = $this->conn->prepare(
                "INSERT INTO profiles (user_id, nom, prenom, avatar) VALUES (?, ?, ?, ?)"
            );
            $stmt2->execute([$userId, $nom, $prenom, $avatar]);
        } else {
            $userId = $user['id'];
            // Mettre Ã  jour l'avatar si fourni
            if ($avatar) {
                $stmt = $this->conn->prepare("UPDATE profiles SET avatar = ? WHERE user_id = ? AND (avatar IS NULL OR avatar = '')");
                $stmt->execute([$avatar, $userId]);
            }
        }

        // RÃ©cupÃ©rer les infos complÃ¨tes
        $stmt = $this->conn->prepare("
            SELECT u.id, u.email, u.role, u.status,
                   p.nom, p.prenom, p.telephone, p.avatar, p.ecole, p.annee_etude,
                   p.quartier, p.nom_entreprise, p.description, p.linkedin, p.instagram,
                   p.facebook, p.twitter, p.github, p.points_accumules
            FROM users u
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ================================================================
    // CRUD - READ : Callback GitHub OAuth
    // ================================================================
    public function handleGithubCallback() {
        // DÃ©marrer la session avant tout
        if (session_status() === PHP_SESSION_NONE) session_start();

        $clientId     = defined('GITHUB_CLIENT_ID')     ? GITHUB_CLIENT_ID     : ($_ENV['GITHUB_CLIENT_ID'] ?? '');
        $clientSecret = defined('GITHUB_CLIENT_SECRET') ? GITHUB_CLIENT_SECRET : ($_ENV['GITHUB_CLIENT_SECRET'] ?? '');

        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        // VÃ©rifier le state CSRF
        if (!$code || !isset($_SESSION['github_oauth_state']) || $_SESSION['github_oauth_state'] !== $state) {
            $this->redirectWithError('ParamÃ¨tres OAuth invalides. RÃ©essayez.');
            return;
        }
        unset($_SESSION['github_oauth_state']);

        // Ã‰changer le code contre un access_token
        $tokenResponse = $this->httpPost('https://github.com/login/oauth/access_token', [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
        ], ['Accept: application/json']);

        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['access_token'] ?? '';

        if (!$accessToken) {
            $this->redirectWithError('Impossible d\'obtenir le token GitHub.');
            return;
        }

        // RÃ©cupÃ©rer les infos utilisateur
        $userJson  = $this->httpGet('https://api.github.com/user', $accessToken);
        $githubUser = json_decode($userJson, true);

        // GitHub peut ne pas exposer l'email public â†’ appel endpoint emails
        $email = $githubUser['email'] ?? '';
        if (!$email) {
            $emailsJson = $this->httpGet('https://api.github.com/user/emails', $accessToken);
            $emails = json_decode($emailsJson, true);
            foreach ($emails as $e) {
                if ($e['primary'] && $e['verified']) {
                    $email = $e['email'];
                    break;
                }
            }
        }

        if (!$email) {
            $this->redirectWithError('Aucun email public associÃ© Ã  ce compte GitHub.');
            return;
        }

        $name   = $githubUser['name'] ?? $githubUser['login'] ?? 'Utilisateur';
        $avatar = $githubUser['avatar_url'] ?? null;

        $user = $this->loginOrCreate($email, $name, 'github', $githubUser['id'], $avatar);

        if (!$user) {
            $this->redirectWithError('Erreur lors de la crÃ©ation du compte.');
            return;
        }

        // CrÃ©er la session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        session_write_close();

        // Page intermÃ©diaire pour s'assurer que la session est bien persistÃ©e
        $redirect = $this->getDashboardUrl($user['role']);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        echo '<script>window.location.href = "' . $redirect . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
        echo '</body></html>';
        exit;
    }

    // ================================================================
    // CRUD - READ : Initier le flow GitHub (gÃ©nÃ©rer state + redirect)
    // ================================================================
    public function initiateGithub() {
        $clientId = defined('GITHUB_CLIENT_ID') ? GITHUB_CLIENT_ID : ($_ENV['GITHUB_CLIENT_ID'] ?? '');

        if (session_status() === PHP_SESSION_NONE) session_start();
        $state = bin2hex(random_bytes(16));
        $_SESSION['github_oauth_state'] = $state;

        $params = http_build_query([
            'client_id'    => $clientId,
            'scope'        => 'user:email',
            'state'        => $state,
            'redirect_uri' => caremeal_url('Controller/SocialAuthController.php?action=github-callback'),
        ]);

        header('Location: https://github.com/login/oauth/authorize?' . $params);
        exit;
    }

    // ================================================================
    // CRUD - CREATE/READ : Connexion via Google/Facebook (token cÃ´tÃ© JS)
    // ReÃ§oit un token JWT (Google) ou access_token (Facebook) et vÃ©rifie
    // ================================================================
    public function handleSocialToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);

        $provider = $data['provider'] ?? '';
        $token    = $data['token']    ?? '';

        if (!$provider || !$token) {
            echo json_encode(['success' => false, 'message' => 'DonnÃ©es manquantes.']);
            return;
        }

        $email  = '';
        $name   = '';
        $avatar = '';

        if ($provider === 'google') {
            // VÃ©rifier le token Google via leur API
            $response = $this->httpGetRaw('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token));
            $payload  = json_decode($response, true);

            if (empty($payload['email']) || empty($payload['email_verified'])) {
                echo json_encode(['success' => false, 'message' => 'Token Google invalide.']);
                return;
            }
            $email  = $payload['email'];
            $name   = ($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? '');
            $avatar = $payload['picture'] ?? '';

        } elseif ($provider === 'facebook') {
            // VÃ©rifier le token Facebook
            $response = $this->httpGetRaw('https://graph.facebook.com/me?fields=id,name,email,picture.type(large)&access_token=' . urlencode($token));
            $fbUser   = json_decode($response, true);

            // Debug log
            error_log('Facebook user response: ' . $response);

            $email  = $fbUser['email'] ?? '';
            $name   = $fbUser['name'] ?? '';
            $avatar = $fbUser['picture']['data']['url'] ?? '';

            // Si pas d'email (compte Facebook sans email vÃ©rifiÃ©), utiliser un email gÃ©nÃ©rÃ©
            if (empty($email)) {
                $fbId  = $fbUser['id'] ?? uniqid();
                $email = 'fb_' . $fbId . '@facebook-user.caremeal.tn';
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Provider non supportÃ©.']);
            return;
        }

        $user = $this->loginOrCreate($email, trim($name), $provider, '', $avatar);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la crÃ©ation du compte.']);
            return;
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        session_write_close();

        // Formater pour le JS
        $formatted = [
            'id'           => $user['id'],
            'email'        => $user['email'],
            'role'         => $user['role'],
            'status'       => $user['status'],
            'name'         => trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')),
            'prenom'       => $user['prenom'] ?? '',
            'nom'          => $user['nom'] ?? '',
            'phone'        => $user['telephone'] ?? '',
            'avatar'       => $user['avatar'] ?? '',
            'ecole'        => $user['ecole'] ?? '',
            'annee'        => $user['annee_etude'] ?? '',
            'quartier'     => $user['quartier'] ?? '',
            'nomEntreprise'=> $user['nom_entreprise'] ?? '',
            'description'  => $user['description'] ?? '',
        ];

        echo json_encode(['success' => true, 'user' => $formatted]);
    }

    // --- Helpers HTTP ---
    private function httpPost($url, $data, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function httpGet($url, $accessToken) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $accessToken,
            'User-Agent: CareMeal-App',
            'Accept: application/json',
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function httpGetRaw($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CareMeal-App');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function getDashboardUrl($role) {
        switch ($role) {
            case 'student': return caremeal_url('View/FrontOffice/feed.php');
            case 'partner': return caremeal_url('View/FrontOffice/feed.php');
            case 'admin':   return caremeal_url('View/BackOffice/admin/dashboard.php');
            default:        return caremeal_url('View/FrontOffice/index.php');
        }
    }

    private function redirectWithError($msg) {
        $encoded = urlencode($msg);
        header('Location: /View/FrontOffice/login.php?social_error=' . $encoded);
        exit;
    }
}

// Point d'entrÃ©e direct
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $ctrl = new SocialAuthController();

    if ($action === 'github-init') {
        $ctrl->initiateGithub();
    } elseif ($action === 'github-callback') {
        $ctrl->handleGithubCallback();
    } elseif ($action === 'social-token') {
        $ctrl->handleSocialToken();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
    }
}
?>


