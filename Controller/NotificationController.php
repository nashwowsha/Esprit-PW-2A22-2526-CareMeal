<?php
/* ============================================================
   CAREMEAL â€” NOTIFICATION CONTROLLER
   Retourne les notifications en temps rÃ©el selon le rÃ´le
   AppelÃ© toutes les 30s par setInterval() cÃ´tÃ© JS
   ============================================================ */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// â”€â”€ VÃ©rification session â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ ADMIN â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if ($user_role === 'admin') {

        // Ã‰vÃ©nements en attente de validation
        $stmt = $conn->query("SELECT COUNT(*) as nb FROM EVENEMENT WHERE statut_validation = 'En attente'");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $nb   = (int)$row['nb'];
        if ($nb > 0) {
            $notifications[] = [
                'id'      => 'admin_pending',
                'type'    => 'warning',
                'icon'    => 'fa-hourglass-half',
                'message' => "{$nb} Ã©vÃ©nement" . ($nb > 1 ? 's' : '') . " en attente de validation",
                'time'    => 'Maintenant',
                'link'    => caremeal_path('View/BackOffice/admin/events.php')
            ];
        }

        // Nouvelles participations (derniÃ¨res 24h)
        $stmt2 = $conn->query("SELECT COUNT(*) as nb FROM PARTICIPATION WHERE date_inscription >= CURDATE()");
        $row2  = $stmt2->fetch(PDO::FETCH_ASSOC);
        $nb2   = (int)$row2['nb'];
        if ($nb2 > 0) {
            $notifications[] = [
                'id'      => 'admin_new_parts',
                'type'    => 'success',
                'icon'    => 'fa-user-plus',
                'message' => "{$nb2} nouvelle" . ($nb2 > 1 ? 's' : '') . " inscription" . ($nb2 > 1 ? 's' : '') . " aujourd'hui",
                'time'    => "Aujourd'hui",
                'link'    => caremeal_path('View/BackOffice/admin/participations.php')
            ];
        }

        // Participations annulÃ©es non traitÃ©es
        $stmt3 = $conn->query("SELECT COUNT(*) as nb FROM PARTICIPATION WHERE statut = 'AnnulÃ©'");
        $row3  = $stmt3->fetch(PDO::FETCH_ASSOC);
        $nb3   = (int)$row3['nb'];
        if ($nb3 > 0) {
            $notifications[] = [
                'id'      => 'admin_cancelled',
                'type'    => 'danger',
                'icon'    => 'fa-user-xmark',
                'message' => "{$nb3} inscription" . ($nb3 > 1 ? 's' : '') . " annulÃ©e" . ($nb3 > 1 ? 's' : ''),
                'time'    => 'Ã€ traiter',
                'link'    => caremeal_path('View/BackOffice/admin/participations.php')
            ];
        }
    }

    // â”€â”€ PARTNER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    elseif ($user_role === 'partner') {

        // Ã‰vÃ©nements validÃ©s rÃ©cemment (7 derniers jours)
        $stmt = $conn->prepare("
            SELECT titre FROM EVENEMENT
            WHERE createur_type = 'Partenaire'
            AND createur_id = :id
            AND statut_validation = 'ValidÃ©'
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
                'message' => "âœ… \"" . mb_substr($ev['titre'], 0, 35) . "\" a Ã©tÃ© validÃ©",
                'time'    => 'RÃ©cent',
                'link'    => caremeal_path('View/FrontOffice/partner/events.php')
            ];
        }

        // Ã‰vÃ©nements rejetÃ©s
        $stmt2 = $conn->prepare("
            SELECT titre, motif_refus FROM EVENEMENT
            WHERE createur_type = 'Partenaire'
            AND createur_id = :id
            AND statut_validation = 'RejetÃ©'
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
                'message' => "âŒ \"" . mb_substr($ev['titre'], 0, 30) . "\" a Ã©tÃ© rejetÃ©",
                'time'    => 'RÃ©cent',
                'link'    => caremeal_path('View/FrontOffice/partner/events.php')
            ];
        }

        // Nouvelles inscriptions sur ses Ã©vÃ©nements
        $stmt3 = $conn->prepare("
            SELECT COUNT(*) as nb FROM PARTICIPATION p
            INNER JOIN EVENEMENT e ON p.evenement_id = e.id_evenement
            WHERE e.createur_type = 'Partenaire'
            AND e.createur_id = :id
            AND p.date_inscription >= CURDATE()
            AND p.statut != 'AnnulÃ©'
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
                'link'    => caremeal_path('View/FrontOffice/partner/events.php')
            ];
        }
    }

    // â”€â”€ STUDENT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    elseif ($user_role === 'student') {

        // Inscriptions actives
        $stmt = $conn->prepare("
            SELECT e.titre, p.statut FROM PARTICIPATION p
            INNER JOIN EVENEMENT e ON p.evenement_id = e.id_evenement
            WHERE p.etudiant_id = :id AND p.statut = 'Inscrit'
            ORDER BY p.date_inscription DESC LIMIT 2
        ");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($inscriptions as $p) {
            $notifications[] = [
                'id'      => 'student_inscrit_' . md5($p['titre']),
                'type'    => 'success',
                'icon'    => 'fa-calendar-check',
                'message' => "âœ… Inscrit Ã  \"" . mb_substr($p['titre'], 0, 35) . "\"",
                'time'    => 'Actif',
                'link'    => caremeal_path('View/FrontOffice/student/events.php')
            ];
        }

        // Nouveau Ã©vÃ©nement rÃ©cemment validÃ© (pour notifier l'Ã©tudiant)
        $stmtNew = $conn->query("
            SELECT id_evenement, titre FROM EVENEMENT 
            WHERE statut_validation = 'ValidÃ©' 
            ORDER BY id_evenement DESC LIMIT 1
        ");
        $newEv = $stmtNew->fetch(PDO::FETCH_ASSOC);
        if ($newEv) {
            $notifications[] = [
                'id'      => 'student_new_event_' . $newEv['id_evenement'],
                'type'    => 'info',
                'icon'    => 'fa-calendar-plus',
                'message' => "ðŸ“¢ Nouvel Ã©vÃ©nement disponible : \"" . mb_substr($newEv['titre'], 0, 30) . "\"",
                'time'    => 'Nouveau',
                'link'    => caremeal_path('View/FrontOffice/student/events.php')
            ];
        }

        // Compter les Ã©vÃ©nements futurs disponibles
        $stmt2b = $conn->query("SELECT COUNT(*) FROM EVENEMENT WHERE statut_validation = 'ValidÃ©' AND date_evenement >= CURDATE()");
        $nb2 = (int)$stmt2b->fetchColumn();
        if ($nb2 > 0) {
            $notifications[] = [
                'id'      => 'student_all_events',
                'type'    => 'primary',
                'icon'    => 'fa-calendar-days',
                'message' => "{$nb2} Ã©vÃ©nement" . ($nb2 > 1 ? 's' : '') . " disponible" . ($nb2 > 1 ? 's' : '') . " Ã  venir",
                'time'    => 'Disponible',
                'link'    => caremeal_path('View/FrontOffice/student/events.php')
            ];
        }
    }

} catch (Exception $e) {
    // Silencieux â€” ne pas exposer les erreurs
}

echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'count'         => count($notifications)
]);
?>

