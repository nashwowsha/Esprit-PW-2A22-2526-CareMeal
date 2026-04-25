<?php
require_once dirname(__DIR__) . "/Model/User.php";

class UserController {
    public function handleRequest() {
        header("Content-Type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) { $data = $_POST; }
        $action = $_GET["action"] ?? ($data["action"] ?? "");

        // ============================================================
        // CRUD - READ : Récupérer la liste de tous les utilisateurs
        // ============================================================
        if ($action === "get_users") {
            $userModel = new User();
            $stmt = $userModel->getConn()->prepare("SELECT u.id, u.email, u.role, u.status, u.created_at, p.nom, p.prenom, p.nom_entreprise, p.ecole, p.quartier, p.secteur_activite, p.site_web, p.telephone FROM users u LEFT JOIN profiles p ON u.id = p.user_id");
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
            $userModel = new User();
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

            // Mettre à jour le statut
            try {
                $stmt = $userModel->getConn()->prepare("UPDATE users SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $status, ':id' => $userId]);

                echo json_encode(["success" => true, "message" => "Statut mis à jour"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Erreur base de données"]);
            }
            exit;
        }

        // ============================================================
        // CRUD - DELETE : Supprimer un utilisateur (par l'admin)
        // ============================================================
        if ($action === "delete_user") {
            $userModel = new User();
            $userId = $data["user_id"] ?? null;

            if (!$userId) {
                echo json_encode(["success" => false, "message" => "ID requis"]);
                exit;
            }

            try {
                // Delete linked profile first
                $stmtProfile = $userModel->getConn()->prepare("DELETE FROM profiles WHERE user_id = :id");
                $stmtProfile->execute([':id' => $userId]);

                // Delete user
                $stmtUser = $userModel->getConn()->prepare("DELETE FROM users WHERE id = :id");
                $stmtUser->execute([':id' => $userId]);

                echo json_encode(["success" => true, "message" => "Utilisateur supprimé"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Erreur suppression"]);
            }
            exit;
        }

        // ============================================================
        // CRUD - UPDATE : Modifier le profil partenaire + CONTROLE DE SAISIE côté serveur
        // ============================================================
        if ($action === "update_partner_profile") {
            $userModel = new User();
            $userId = $data["user_id"] ?? null;

            if (!$userId) {
                echo json_encode(["success" => false, "message" => "ID utilisateur requis"]);
                exit;
            }

            if (empty(trim($data['nom_entreprise'] ?? ''))) {
                echo json_encode(["success" => false, "message" => "Le nom de l'établissement est requis."]);
                exit;
            }

            if (empty(trim($data['nom'] ?? '')) || empty(trim($data['prenom'] ?? ''))) {
                echo json_encode(["success" => false, "message" => "Votre nom et prénom (contact) sont requis."]);
                exit;
            }

            $tel = trim($data['telephone'] ?? '');
            if (!empty($tel) && !preg_match('/^[0-9\+\s\-]{8,15}$/', $tel)) {
                echo json_encode(["success" => false, "message" => "Le format du numéro de téléphone est invalide."]);
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
                $stmt = $userModel->getConn()->prepare("
                    UPDATE profiles SET 
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
                    WHERE user_id = :user_id
                ");

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

                echo json_encode(["success" => true, "message" => "Profil mis à jour avec succès dans la base de données"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Erreur lors de la mise à jour: " . $e->getMessage()]);
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