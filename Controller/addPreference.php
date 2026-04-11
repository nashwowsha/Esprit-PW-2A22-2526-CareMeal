<?php
require_once __DIR__ . '/PreferenceController.php';

$controller = new PreferenceController();
$result = $controller->studentUpsert($_POST);
$idUser = isset($result['id_user']) ? (int)$result['id_user'] : 1;
$status = urlencode($result['status'] ?? 'error_unknown');

header('Location: ../student/preferences.php?status=' . $status . '&id_user=' . $idUser);
exit;

