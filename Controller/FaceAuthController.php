<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/Model/User.php';
require_once dirname(__DIR__) . '/Model/Profile.php';

class FaceAuthController {
    private $conn;
    // Seuil strict — 0.35 bloque les sosies et visages étrangers
    // tout en tolérant lunettes, lumière faible, coupe de cheveux différente
    private $THRESHOLD = 0.35;
    // Nombre minimum de descripteurs de login qui doivent matcher le même user
    private $MIN_CONSENSUS = 3;
    // Écart minimum entre le meilleur et le 2ème meilleur match (anti-sosie)
    private $MIN_GAP = 0.08;
    // Anti brute-force : max tentatives avant blocage
    private $MAX_ATTEMPTS = 5;
    private $BLOCK_DURATION = 300; // 5 minutes en secondes

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function handleRequest() {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Methode non autorisee."]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) { $data = $_POST; }
        $action = $data['action'] ?? '';

        if ($action === 'save-face') { $this->saveFace($data); }
        elseif ($action === 'remove-face') { $this->removeFace($data); }
        elseif ($action === 'face-login') { $this->faceLogin($data); }
        elseif ($action === 'check-face') { $this->checkFace($data); }
        else { echo json_encode(["success" => false, "message" => "Action non reconnue."]); }
    }

    // ================================================================
    // SAVE — Accepte un tableau de descripteurs (multi-angle)
    // ================================================================
    private function saveFace($data) {
        session_start();
        $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);

        if (!$userId) { echo json_encode(["success" => false, "message" => "Non connecte."]); return; }

        // Accepter 'descriptors' (multi-angle array) ou 'descriptor' (single legacy)
        $descriptors = $data['descriptors'] ?? null;
        if (!$descriptors && isset($data['descriptor'])) {
            $single = $data['descriptor'];
            if (is_array($single) && count($single) === 128) {
                $descriptors = [$single];
            }
        }

        if (!$descriptors || !is_array($descriptors) || count($descriptors) === 0) {
            echo json_encode(["success" => false, "message" => "Descripteurs invalides."]); return;
        }

        // Valider chaque descripteur (128 floats)
        foreach ($descriptors as $d) {
            if (!is_array($d) || count($d) !== 128) {
                echo json_encode(["success" => false, "message" => "Descripteur invalide (attendu 128 floats)."]); return;
            }
        }

        try {
            $stmt = $this->conn->prepare("UPDATE profiles SET face_descriptor = ? WHERE user_id = ?");
            $stmt->execute([json_encode($descriptors), $userId]);
            echo json_encode(["success" => true, "message" => "Visage enregistre (" . count($descriptors) . " angles)."]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Erreur: " . $e->getMessage()]);
        }
    }

    private function removeFace($data) {
        session_start();
        $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
        if (!$userId) { echo json_encode(["success" => false, "message" => "Non connecte."]); return; }

        try {
            $stmt = $this->conn->prepare("UPDATE profiles SET face_descriptor = NULL WHERE user_id = ?");
            $stmt->execute([$userId]);
            echo json_encode(["success" => true, "message" => "Visage supprime."]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Erreur."]);
        }
    }

    // ================================================================
    // LOGIN — Consensus multi-descripteur (sécurité renforcée)
    // Accepte 'descriptors' (tableau de 3 scans) ou 'descriptor' (single, legacy)
    // Avec consensus : au moins MIN_CONSENSUS descripteurs doivent matcher le même user
    // ================================================================
    private function faceLogin($data) {
        // Rate limiting — anti brute-force
        session_start();
        $now = time();
        if (isset($_SESSION['face_block_until']) && $now < $_SESSION['face_block_until']) {
            $remaining = ceil(($_SESSION['face_block_until'] - $now) / 60);
            echo json_encode(["success" => false, "message" => "Trop de tentatives. Reessayez dans {$remaining} min."]);
            return;
        }
        if (!isset($_SESSION['face_attempts'])) { $_SESSION['face_attempts'] = 0; }

        // Support both multi-scan (new) and single-scan (legacy)
        $loginDescriptors = [];
        if (isset($data['descriptors']) && is_array($data['descriptors'])) {
            foreach ($data['descriptors'] as $d) {
                if (is_array($d) && count($d) === 128) {
                    $loginDescriptors[] = $d;
                }
            }
        }
        if (count($loginDescriptors) === 0 && isset($data['descriptor'])) {
            $single = $data['descriptor'];
            if (is_array($single) && count($single) === 128) {
                $loginDescriptors = [$single];
            }
        }

        if (count($loginDescriptors) === 0) {
            echo json_encode(["success" => false, "message" => "Descripteur invalide."]); return;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.email, u.role, u.status, u.created_at,
                       p.nom, p.prenom, p.nom_entreprise, p.telephone, p.ecole,
                       p.quartier, p.annee_etude, p.description,
                       p.linkedin, p.instagram, p.github, p.facebook, p.twitter,
                       p.secteur_activite, p.site_web, p.points_accumules, p.face_descriptor
                FROM users u
                INNER JOIN profiles p ON u.id = p.user_id
                WHERE p.face_descriptor IS NOT NULL AND u.status = 'active'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For each login descriptor, find the best matching user
            $matchResults = []; // array of ['user_id' => id, 'distance' => dist, 'user' => data]

            foreach ($loginDescriptors as $loginDesc) {
                $bestMatch = null;
                $bestDistance = PHP_FLOAT_MAX;

                foreach ($users as $user) {
                    $stored = json_decode($user['face_descriptor'], true);
                    if (!$stored) continue;

                    // Detect format: multi-angle [[128], [128], ...] vs legacy [128]
                    $storedDescriptors = [];
                    if (isset($stored[0]) && is_array($stored[0])) {
                        $storedDescriptors = $stored;
                    } else {
                        if (count($stored) === 128) {
                            $storedDescriptors = [$stored];
                        }
                    }

                    // Find best distance against all stored descriptors for this user
                    foreach ($storedDescriptors as $singleDesc) {
                        if (!is_array($singleDesc) || count($singleDesc) !== 128) continue;
                        $dist = $this->euclideanDistance($loginDesc, $singleDesc);
                        if ($dist < $bestDistance) {
                            $bestDistance = $dist;
                            $bestMatch = $user;
                        }
                    }
                }

                if ($bestMatch && $bestDistance <= $this->THRESHOLD) {
                    $matchResults[] = [
                        'user_id' => $bestMatch['id'],
                        'distance' => $bestDistance,
                        'user' => $bestMatch
                    ];
                }
            }

            // Consensus check: at least MIN_CONSENSUS descriptors must match the SAME user
            $requiredConsensus = min($this->MIN_CONSENSUS, count($loginDescriptors));

            if (count($matchResults) >= $requiredConsensus) {
                // Count matches per user
                $userCounts = [];
                $userDistances = [];
                $userData = [];
                foreach ($matchResults as $m) {
                    $uid = $m['user_id'];
                    $userCounts[$uid] = ($userCounts[$uid] ?? 0) + 1;
                    $userDistances[$uid] = ($userDistances[$uid] ?? []);
                    $userDistances[$uid][] = $m['distance'];
                    $userData[$uid] = $m['user'];
                }

                // Find the user with the most consensus matches
                arsort($userCounts);
                $bestUserId = array_key_first($userCounts);
                $consensusCount = $userCounts[$bestUserId];

                if ($consensusCount >= $requiredConsensus) {
                    $avgDistance = array_sum($userDistances[$bestUserId]) / count($userDistances[$bestUserId]);
                    $confidence = round((1 - $avgDistance) * 100, 1);

                    // Minimum confidence check (72% élimine les matchs faibles de sosies)
                    if ($confidence < 72) {
                        echo json_encode(["success" => false, "message" => "Confiance trop faible ($confidence%). Visage non reconnu."]);
                        return;
                    }

                    // Anti-sosie: vérifier l'écart avec le 2ème meilleur utilisateur
                    $closestCompetitorAvg = PHP_FLOAT_MAX;
                    foreach ($userDistances as $uid => $dists) {
                        if ($uid == $bestUserId) continue;
                        $competitorAvg = array_sum($dists) / count($dists);
                        if ($competitorAvg < $closestCompetitorAvg) {
                            $closestCompetitorAvg = $competitorAvg;
                        }
                    }
                    if ($closestCompetitorAvg < PHP_FLOAT_MAX) {
                        $gap = $closestCompetitorAvg - $avgDistance;
                        if ($gap < $this->MIN_GAP) {
                            echo json_encode(["success" => false, "message" => "Identification ambigue. Utilisez email/mot de passe."]);
                            return;
                        }
                    }

                    $bestMatch = $userData[$bestUserId];

                    if ($bestMatch['status'] === 'banned') {
                        echo json_encode(["success" => false, "message" => "Compte suspendu."]); return;
                    }

                    // Success — reset rate limiter
                    $_SESSION['face_attempts'] = 0;
                    unset($_SESSION['face_block_until']);
                    $_SESSION['user_id'] = $bestMatch['id'];
                    $_SESSION['user_role'] = $bestMatch['role'];

                    $Profile = new Profile($bestMatch);
                    $User = new User($bestMatch['id'], $bestMatch['email'], $bestMatch['role'], $bestMatch['status'], $bestMatch['created_at'], $Profile);

                    echo json_encode([
                        "success" => true,
                        "message" => "Reconnaissance faciale reussie.",
                        "confidence" => $confidence,
                        "consensus" => "$consensusCount/" . count($loginDescriptors),
                        "user" => $User->toArray()
                    ]);
                } else {
                    $_SESSION['face_attempts']++;
                    if ($_SESSION['face_attempts'] >= $this->MAX_ATTEMPTS) {
                        $_SESSION['face_block_until'] = time() + $this->BLOCK_DURATION;
                    }
                    echo json_encode(["success" => false, "message" => "Visage non reconnu."]);
                }
            } else {
                $_SESSION['face_attempts']++;
                if ($_SESSION['face_attempts'] >= $this->MAX_ATTEMPTS) {
                    $_SESSION['face_block_until'] = time() + $this->BLOCK_DURATION;
                }
                echo json_encode(["success" => false, "message" => "Visage non reconnu."]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Erreur serveur."]);
        }
    }

    private function checkFace($data) {
        session_start();
        $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
        if (!$userId) { echo json_encode(["success" => false, "message" => "Non connecte."]); return; }

        try {
            $stmt = $this->conn->prepare("SELECT face_descriptor FROM profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "has_face" => !empty($r['face_descriptor'])]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Erreur."]);
        }
    }

    private function euclideanDistance($a, $b) {
        $sum = 0;
        for ($i = 0; $i < 128; $i++) { $diff = $a[$i] - $b[$i]; $sum += $diff * $diff; }
        return sqrt($sum);
    }
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new FaceAuthController();
    $controller->handleRequest();
}
?>
