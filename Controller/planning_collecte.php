<?php
require_once __DIR__ . '/PlanningCollecteController.php';

$controller = new PlanningCollecteController();
$action = $_GET['action'] ?? '';

function wantsCollecteDetailRedirect($source = [])
{
    return isset($source['return_to_detail']) && (int)$source['return_to_detail'] === 1;
}

function redirectAdminCollecte($result, $source = [])
{
    $status = urlencode($result['status'] ?? 'error_unknown');
    $idCollecte = isset($result['id_collecte']) ? (int)$result['id_collecte'] : 0;

    if (wantsCollecteDetailRedirect($source) && $idCollecte > 0) {
        $location = '../admin/collecte_detail.php?id_collecte=' . $idCollecte . '&status=' . $status;
        $location .= '#collecte-detail-card';
    } else {
        $location = '../admin/planning_collecte.php?status=' . $status;
        if ($idCollecte > 0) {
            $location .= '&selected_id=' . $idCollecte;
        }
        $location .= '#collecte-list-section';
    }

    header('Location: ' . $location);
    exit;
}

function redirectPartnerCollecte($result, $source = [])
{
    $status = urlencode($result['status'] ?? 'error_unknown');
    $idOwner = isset($result['id_owner']) ? (int)$result['id_owner'] : 0;
    $idRestaurant = isset($result['id_restaurant']) ? (int)$result['id_restaurant'] : 0;
    $idCollecte = isset($result['id_collecte']) ? (int)$result['id_collecte'] : 0;

    if (wantsCollecteDetailRedirect($source) && $idCollecte > 0) {
        $location = '../partner/collecte_detail.php?id_collecte=' . $idCollecte . '&status=' . $status;
        if ($idOwner > 0) {
            $location .= '&id_owner=' . $idOwner;
        }
        $location .= '#collecte-detail-card';
    } else {
        $location = '../partner/restaurants.php?status=' . $status;
        if ($idOwner > 0) {
            $location .= '&id_owner=' . $idOwner;
        }
        if ($idRestaurant > 0) {
            $location .= '&selected_id=' . $idRestaurant;
        }
        $location .= '#restaurant-collectes-section';
    }

    header('Location: ' . $location);
    exit;
}

function redirectStudentCollecte($result, $source = [])
{
    $status = urlencode($result['status'] ?? 'error_unknown');
    $idUser = isset($source['id_user']) ? (int)$source['id_user'] : 0;
    $idPref = isset($source['id_pref']) ? (int)$source['id_pref'] : 0;
    $idRestaurant = isset($source['id_restaurant']) ? (int)$source['id_restaurant'] : 0;
    $itemsJson = isset($source['items_json']) ? (string)$source['items_json'] : '[]';
    $montantTotal = isset($source['montant_total']) ? (string)$source['montant_total'] : '0.00';
    $modeCollecte = isset($source['mode_collecte']) ? (string)$source['mode_collecte'] : '';
    $adresseLivraison = isset($source['adresse_livraison']) ? (string)$source['adresse_livraison'] : '';
    $heureSouhaitee = isset($source['heure_souhaitee']) ? (string)$source['heure_souhaitee'] : '';
    $adresseLat = isset($source['adresse_lat']) ? (string)$source['adresse_lat'] : '';
    $adresseLng = isset($source['adresse_lng']) ? (string)$source['adresse_lng'] : '';

    $isSuccessCreate = ($result['status'] ?? '') === 'success_collecte_created';
    $location = $isSuccessCreate
        ? ('../student/mes_collectes.php?status=' . $status)
        : ('../student/create_collecte.php?status=' . $status);
    if ($idUser > 0) {
        $location .= '&id_user=' . $idUser;
    }
    if ($idPref > 0) {
        $location .= '&id_pref=' . $idPref;
    }
    if ($idRestaurant > 0) {
        $location .= '&id_restaurant=' . $idRestaurant;
    }

    $encodedItems = base64_encode($itemsJson);
    if ($encodedItems !== '') {
        $location .= '&items_b64=' . urlencode($encodedItems);
    }
    if ($montantTotal !== '') {
        $location .= '&montant_total=' . urlencode($montantTotal);
    }
    if ($modeCollecte !== '') {
        $location .= '&mode_collecte=' . urlencode($modeCollecte);
    }
    if ($adresseLivraison !== '') {
        $location .= '&adresse_livraison=' . urlencode($adresseLivraison);
    }
    if ($heureSouhaitee !== '') {
        $location .= '&heure_souhaitee=' . urlencode($heureSouhaitee);
    }
    if ($adresseLat !== '') {
        $location .= '&adresse_lat=' . urlencode($adresseLat);
    }
    if ($adresseLng !== '') {
        $location .= '&adresse_lng=' . urlencode($adresseLng);
    }
    if (isset($result['id_collecte']) && (int)$result['id_collecte'] > 0) {
        $location .= '&id_collecte=' . (int)$result['id_collecte'];
    }
    if ($isSuccessCreate) {
        $location .= '#mes-collectes-section';
    } else {
        $location .= '#collecte-form-card';
    }

    header('Location: ' . $location);
    exit;
}

function redirectStudentCollecteList($result, $source = [])
{
    $status = urlencode($result['status'] ?? 'error_unknown');
    $idUser = isset($source['id_user']) ? (int)$source['id_user'] : (int)($result['id_user'] ?? 0);
    $idCollecte = isset($result['id_collecte']) ? (int)$result['id_collecte'] : 0;

    if (wantsCollecteDetailRedirect($source) && $idCollecte > 0) {
        $location = '../student/collecte_detail.php?status=' . $status . '&id_collecte=' . $idCollecte;
        if ($idUser > 0) {
            $location .= '&id_user=' . $idUser;
        }
        $location .= '#collecte-detail-card';
    } else {
        $location = '../student/mes_collectes.php?status=' . $status;
        if ($idUser > 0) {
            $location .= '&id_user=' . $idUser;
        }
        if ($idCollecte > 0) {
            $location .= '&selected_id=' . $idCollecte;
        }
        $location .= '#mes-collectes-section';
    }

    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'admin_create_collecte':
            redirectAdminCollecte($controller->createCollecte($_POST), $_POST);
            break;
        case 'admin_update_collecte_status':
            redirectAdminCollecte(
                $controller->updateStatus(
                    $_POST['id_collecte'] ?? 0,
                    $_POST['statut'] ?? ''
                ),
                $_POST
            );
            break;
        case 'admin_delete_collecte':
            redirectAdminCollecte(
                $controller->deleteCollecteWithOptions(
                    $_POST['id_collecte'] ?? 0,
                    (int)($_POST['restock_on_delete'] ?? 1) === 1
                ),
                $_POST
            );
            break;
        case 'partner_update_collecte_status':
            redirectPartnerCollecte(
                $controller->partnerUpdateStatus(
                    $_POST['id_collecte'] ?? 0,
                    $_POST['statut'] ?? '',
                    $_POST['id_owner'] ?? 0
                ),
                $_POST
            );
            break;
        case 'partner_delete_collecte':
            redirectPartnerCollecte(
                $controller->partnerDeleteCollecte(
                    $_POST['id_collecte'] ?? 0,
                    $_POST['id_owner'] ?? 0,
                    (int)($_POST['restock_on_delete'] ?? 1) === 1
                ),
                $_POST
            );
            break;
        case 'student_create_collecte':
            redirectStudentCollecte(
                $controller->createCollecte($_POST),
                $_POST
            );
            break;
        case 'student_cancel_collecte':
            redirectStudentCollecteList(
                $controller->studentCancelCollecte(
                    $_POST['id_collecte'] ?? 0,
                    $_POST['id_user'] ?? 0
                ),
                $_POST
            );
            break;
        case 'student_clear_collectes':
            redirectStudentCollecteList(
                $controller->studentClearCollectes(
                    $_POST['id_user'] ?? 0,
                    $_POST['scope'] ?? ''
                ),
                $_POST
            );
            break;
        default:
            if (strpos((string)$action, 'partner_') === 0) {
                redirectPartnerCollecte([
                    'status' => 'error_unknown_action',
                    'id_owner' => (int)($_POST['id_owner'] ?? 0),
                    'id_restaurant' => (int)($_POST['id_restaurant'] ?? 0),
                ]);
            }
            if (strpos((string)$action, 'student_') === 0) {
                redirectStudentCollecte(['status' => 'error_unknown_action'], $_POST);
            }
            redirectAdminCollecte(['status' => 'error_unknown_action']);
    }
}

header('Location: ../admin/planning_collecte.php?status=error_invalid_request');
exit;
