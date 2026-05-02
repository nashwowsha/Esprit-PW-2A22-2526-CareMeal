<?php
/**
 * Callback GitHub OAuth
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/oauth.php';

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (!$code || !$state) {
    die('ERREUR: code ou state manquant. code=' . $code . ' state=' . $state);
}

// Échanger le code contre un access_token
$ch = curl_init('https://github.com/login/oauth/access_token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code'          => $code,
        'redirect_uri'  => 'http://localhost/projet2a22/Controller/github_callback.php',
    ]),
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
]);
$tokenRaw  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    die('ERREUR CURL token: ' . $curlError);
}

$tokenData   = json_decode($tokenRaw, true);
$accessToken = $tokenData['access_token'] ?? '';

if (!$accessToken) {
    die('ERREUR: pas de token. Réponse GitHub: ' . $tokenRaw);
}

// Récupérer les infos utilisateur
$ch = curl_init('https://api.github.com/user');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        'Authorization: token ' . $accessToken,
        'User-Agent: CareMeal-App',
        'Accept: application/json',
    ],
]);
$githubUser = json_decode(curl_exec($ch), true);
curl_close($ch);

// Récupérer l'email
$email = $githubUser['email'] ?? '';
if (!$email) {
    $ch = curl_init('https://api.github.com/user/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $accessToken,
            'User-Agent: CareMeal-App',
            'Accept: application/json',
        ],
    ]);
    $emails = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (is_array($emails)) {
        foreach ($emails as $e) {
            if (!empty($e['primary']) && !empty($e['verified'])) {
                $email = $e['email']; break;
            }
        }
    }
}

if (!$email) {
    $email = ($githubUser['login'] ?? 'user') . '@github-user.caremeal.tn';
}

$name   = $githubUser['name'] ?? $githubUser['login'] ?? 'Utilisateur';
$avatar = $githubUser['avatar_url'] ?? '';

// Créer ou récupérer l'utilisateur
$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $referralCode = strtoupper(substr(md5(uniqid()), 0, 8));
    $conn->prepare("INSERT INTO users (email, password, role, status, referral_code) VALUES (?, '', 'student', 'active', ?)")
         ->execute([$email, $referralCode]);
    $userId = $conn->lastInsertId();
    $parts  = explode(' ', trim($name), 2);
    $conn->prepare("INSERT INTO profiles (user_id, nom, prenom, avatar) VALUES (?, ?, ?, ?)")
         ->execute([$userId, $parts[1] ?? '', $parts[0] ?? $name, $avatar]);
    $role = 'student';
} else {
    $userId = $user['id'];
    $role   = $user['role'];
}

// Sauvegarder la session
$_SESSION['user_id']   = $userId;
$_SESSION['user_role'] = $role;

$sessionId = session_id();
session_write_close();

// Rediriger avec le session_id dans l'URL pour forcer la reprise de session
switch ($role) {
    case 'student': $redirect = '/projet2a22/View/FrontOffice/student/dashboard.php'; break;
    case 'partner': $redirect = '/projet2a22/View/FrontOffice/partner/dashboard.php'; break;
    case 'admin':   $redirect = '/projet2a22/View/BackOffice/admin/dashboard.php'; break;
    default:        $redirect = '/projet2a22/View/FrontOffice/index.php'; break;
}

header('Location: ' . $redirect);
exit;
?>
