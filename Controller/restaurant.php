<?php
require_once __DIR__ . '/RestaurantController.php';

$controller = new RestaurantController();
$action = $_GET['action'] ?? '';

function redirectPartner($result)
{
    $status = urlencode($result['status'] ?? 'error_unknown');
    $idOwner = isset($result['id_owner']) ? (int)$result['id_owner'] : 1;
    $location = '../partner/restaurants.php?status=' . $status . '&id_owner=' . $idOwner;
    if (isset($result['selected_id']) && (int)$result['selected_id'] > 0) {
        $location .= '&selected_id=' . (int)$result['selected_id'];
    }
    $anchor = $result['anchor'] ?? '';
    if ($anchor === '') {
        $anchor = in_array(($result['status'] ?? ''), ['success_meal_added', 'success_meal_updated', 'success_meal_deleted'], true)
            ? 'restaurant-meals-section'
            : 'partner-restaurants-section';
    }
    $location .= '#' . $anchor;
    header('Location: ' . $location);
    exit;
}

function redirectAdmin($result)
{
    $status = urlencode($result['status'] ?? 'error_unknown');
    $location = '../admin/restaurants.php?status=' . $status;
    if (isset($result['id_owner']) && (int)$result['id_owner'] > 0) {
        $location .= '&owner_filter=' . (int)$result['id_owner'];
    }
    if (isset($result['selected_id']) && (int)$result['selected_id'] > 0) {
        $location .= '&selected_id=' . (int)$result['selected_id'];
    }
    $anchor = $result['anchor'] ?? '';
    if ($anchor === '') {
        $anchor = in_array(($result['status'] ?? ''), ['success_meal_added', 'success_meal_updated', 'success_meal_deleted'], true)
            ? 'admin-meals-section'
            : 'admin-restaurants-section';
    }
    $location .= '#' . $anchor;
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'partner_create_restaurant':
            redirectPartner($controller->partnerCreateRestaurant($_POST, $_FILES));
            break;
        case 'partner_update_restaurant':
            redirectPartner($controller->partnerUpdateRestaurant($_POST, $_FILES));
            break;
        case 'partner_delete_restaurant':
            redirectPartner($controller->partnerDeleteRestaurant($_POST));
            break;
        case 'partner_add_meal':
            redirectPartner($controller->partnerAddMeal($_POST));
            break;
        case 'partner_update_meal':
            redirectPartner($controller->partnerUpdateMeal($_POST));
            break;
        case 'partner_delete_meal':
            redirectPartner($controller->partnerDeleteMeal($_POST));
            break;

        case 'admin_create_restaurant':
            redirectAdmin($controller->adminCreateRestaurant($_POST, $_FILES));
            break;
        case 'admin_update_restaurant':
            redirectAdmin($controller->adminUpdateRestaurant($_POST, $_FILES));
            break;
        case 'admin_delete_restaurant':
            redirectAdmin($controller->adminDeleteRestaurant($_POST));
            break;
        case 'admin_add_meal':
            redirectAdmin($controller->adminAddMeal($_POST));
            break;
        case 'admin_update_meal':
            redirectAdmin($controller->adminUpdateMeal($_POST));
            break;
        case 'admin_delete_meal':
            redirectAdmin($controller->adminDeleteMeal($_POST));
            break;
        default:
            redirectAdmin(['status' => 'error_unknown_action']);
    }
}

header('Location: ../admin/restaurants.php?status=error_invalid_request');
exit;

