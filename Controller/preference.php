<?php
require_once __DIR__ . '/PreferenceController.php';

$controller = new PreferenceController();
$action = $_GET['action'] ?? '';

function redirectStudent($result) {
    $idUser = isset($result['id_user']) ? (int)$result['id_user'] : 1;
    $status = urlencode($result['status'] ?? 'error_unknown');
    $location = '../student/preferences.php?status=' . $status . '&id_user=' . $idUser;
    if (($result['status'] ?? '') === 'success_updated' && isset($result['id_pref'])) {
        $location .= '&edit_id=' . (int)$result['id_pref'];
    }
    header('Location: ' . $location);
    exit;
}

function redirectAdmin($result) {
    $status = urlencode($result['status'] ?? 'error_unknown');
    $location = '../admin/preferences.php?status=' . $status;
    if (($result['status'] ?? '') === 'success_updated' && isset($result['id_pref'])) {
        $location .= '&edit_id=' . (int)$result['id_pref'];
    }
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'student_upsert':
            $result = $controller->studentUpsert($_POST);
            redirectStudent($result);
            break;

        case 'student_delete':
            $result = $controller->studentDelete($_POST);
            redirectStudent($result);
            break;

        case 'admin_create':
            $result = $controller->adminCreate($_POST);
            redirectAdmin($result);
            break;

        case 'admin_update':
            $result = $controller->adminUpdate($_POST);
            redirectAdmin($result);
            break;

        case 'admin_delete':
            $result = $controller->adminDelete($_POST);
            redirectAdmin($result);
            break;

        default:
            redirectAdmin(['status' => 'error_unknown_action']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'admin_list_json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($controller->getAllForAdmin());
    exit;
}

header('Location: ../admin/preferences.php?status=error_invalid_request');
exit;

