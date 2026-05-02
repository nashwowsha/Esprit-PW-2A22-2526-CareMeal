<?php
/* ============================================================
   CAREMEAL — NOTIFICATION CONTROLLER
   Retourne les notifications en temps réel selon le rôle
   Appelé toutes les 30s par setInterval() côté JS
   ============================================================ */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// ── Vérification session ──────────────────────────────────────
$user_id   = $_SESSION['user_id']   ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || !$user_role) {
    echo json_encode(['success' => false, 'notifications' => [], 'count' => 0]);
    exit;
}

$database = new Database();
$conn     = $database->getConnection();
$notifications = [];

try {

    // ── ADMIN ─────────────────────────────────────────────────
    if ($user_role === 'admin') {

        // Événements en attente de validation
        $stmt = $conn->query("SELECT COUNT(*) as nb FROM EVENEMENT WHERE statut_validation = 'En attente'");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $nb   = (int)$row['nb'];
        if ($nb > 0) {
            $notifications[] = [
                'id'      => 'admin_pending',
                'type'    => 'warning',
                'icon'    => 'fa-hourglass-half',
                'message' => "{$nb} événement" . ($nb > 1 ? 's' : '') . " en attente de validation",
                'time'    => 'Maintenant',
                'link'    => '/projet2a22/View/BackOffice/admin/events.php'
            ];
        }

        // Nouvelles participations (dernières 24h)
        $stmt2 = $conn->query("SELECT COUNT(*) as nb FROM participation WHERE date_inscription >= CURDATE()");
        $row2  = $stmt2->fetch(PDO::FETCH_ASSOC);
        $nb2   = (int)$row2['nb'];
        if ($nb2 > 0) {
            $notifications[] = [
                'id'      => 'admin_new_parts',
                'type'    => 'success',
                'icon'    => 'fa-user-plus',
                'message' => "{$nb2} nouvelle" . ($nb2 > 1 ? 's' : '') . " inscription" . ($nb2 > 1 ? 's' : '') . " aujourd'hui",
                'time'    => "Aujourd'hui",
                'link'    => '/projet2a22/View/BackOffice/admin/participations.php'
            ];
        }

        // Participations annulées non traitées
        $stmt3 = $conn->query("SELECT COUNT(*) as nb FROM participation WHERE statut = 'Annulé'");
        $row3  = $stmt3->fetch(PDO::FETCH_ASSOC);
        $nb3   = (int)$row3['nb'];
        if ($nb3 > 0) {
            $notifications[] = [
                'id'      => 'admin_cancelled',
                'type'    => 'danger',
                'icon'    => 'fa-user-xmark',
                'message' => "{$nb3} inscription" . ($nb3 > 1 ? 's' : '') . " annulée" . ($nb3 > 1 ? 's' : ''),
                'time'    => 'À traiter',
                'link'    => '/projet2a22/View/BackOffice/admin/participations.php'
            ];
        }
    }

    // ── PARTNER ───────────────────────────────────────────────
    elseif ($user_role === 'partner') {

        // Événements validés récemment (7 derniers jours)
        $stmt = $conn->prepare("
            SELECT titre FROM EVENEMENT
            WHERE createur_type = 'Partenaire'
            AND createur_id = :id
            AND statut_validation = 'Validé'
            ORDER BY id_evenement DESC LIMIT 3
        ");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $validated = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($validated as $ev) {
            $notifications[] = [
                'id'      => 'partner_validated_' . md5($ev['titre']),
                'type'    => 'success',
                'icon'    => 'fa-circle-check',
                'message' => "✅ \"" . mb_substr($ev['titre'], 0, 35) . "\" a été validé",
                'time'    => 'Récent',
                'link'    => '/projet2a22/View/FrontOffice/partner/events.php'
            ];
        }

        // Événements rejetés
        $stmt2 = $conn->prepare("
            SELECT titre, motif_refus FROM EVENEMENT
            WHERE createur_type = 'Partenaire'
            AND createur_id = :id
            AND statut_validation = 'Rejeté'
            ORDER BY id_evenement DESC LIMIT 2
        ");
        $stmt2->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt2->execute();
        $rejected = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rejected as $ev) {
            $notifications[] = [
                'id'      => 'partner_rejected_' . md5($ev['titre']),
                'type'    => 'danger',
                'icon'    => 'fa-circle-xmark',
                'message' => "❌ \"" . mb_substr($ev['titre'], 0, 30) . "\" a été rejeté",
                'time'    => 'Récent',
                'link'    => '/projet2a22/View/FrontOffice/partner/events.php'
            ];
        }

        // Nouvelles inscriptions sur ses événements
        $stmt3 = $conn->prepare("
            SELECT COUNT(*) as nb FROM participation p
            INNER JOIN EVENEMENT e ON p.nom_evenement = e.titre
            WHERE e.createur_type = 'Partenaire'
            AND e.createur_id = :id
            AND p.date_inscription >= CURDATE()
            AND p.statut != 'Annulé'
        ");
        $stmt3->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt3->execute();
        $nb3 = (int)$stmt3->fetchColumn();
        if ($nb3 > 0) {
            $notifications[] = [
                'id'      => 'partner_new_inscrits',
                'type'    => 'info',
                'icon'    => 'fa-users',
                'message' => "{$nb3} nouvelle" . ($nb3 > 1 ? 's' : '') . " inscription" . ($nb3 > 1 ? 's' : '') . " aujourd'hui",
                'time'    => "Aujourd'hui",
                'link'    => '/projet2a22/View/FrontOffice/partner/events.php'
            ];
        }
    }

    // ── STUDENT ───────────────────────────────────────────────
    elseif ($user_role === 'student') {

        // Récupérer l'email de l'étudiant
        $stmtEmail = $conn->prepare("SELECT email FROM users WHERE id = :id");
        $stmtEmail->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmtEmail->execute();
        $email = $stmtEmail->fetchColumn() ?? '';

        if (!empty($email)) {
            // Inscriptions actives
            $stmt = $conn->prepare("
                SELECT nom_evenement, statut FROM participation
                WHERE email = :email AND statut = 'Inscrit'
                ORDER BY date_inscription DESC LIMIT 3
            ");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($inscriptions as $p) {
                $notifications[] = [
                    'id'      => 'student_inscrit_' . md5($p['nom_evenement']),
                    'type'    => 'success',
                    'icon'    => 'fa-calendar-check',
                    'message' => "✅ Inscrit à \"" . mb_substr($p['nom_evenement'], 0, 35) . "\"",
                    'time'    => 'Actif',
                    'link'    => '/projet2a22/View/FrontOffice/student/events.php'
                ];
            }

            // Nouveaux événements disponibles (créés dans les 7 derniers jours)
            $stmt2 = $conn->query("
                SELECT COUNT(*) as nb FROM EVENEMENT
                WHERE statut_validation = 'Validé'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            // Fallback si pas de colonne created_at
            $nb2 = 0;
            try {
                $nb2 = (int)$stmt2->fetchColumn();
            } catch (Exception $e) {
                // Compter les événements futurs disponibles
                $stmt2b = $conn->query("SELECT COUNT(*) FROM EVENEMENT WHERE statut_validation = 'Validé' AND date_evenement >= CURDATE()");
                $nb2 = (int)$stmt2b->fetchColumn();
            }
            if ($nb2 > 0) {
                $notifications[] = [
                    'id'      => 'student_new_events',
                    'type'    => 'info',
                    'icon'    => 'fa-calendar-plus',
                    'message' => "{$nb2} événement" . ($nb2 > 1 ? 's' : '') . " disponible" . ($nb2 > 1 ? 's' : '') . " à venir",
                    'time'    => 'Disponible',
                    'link'    => '/projet2a22/View/FrontOffice/student/events.php'
                ];
            }
        }
    }

} catch (Exception $e) {
    // Silencieux — ne pas exposer les erreurs
}

echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'count'         => count($notifications)
]);
?>
