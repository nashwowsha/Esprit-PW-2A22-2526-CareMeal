<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = $_GET;
if (!isset($query['id_user']) && isset($_SESSION['user_id'])) {
    $query['id_user'] = (int)$_SESSION['user_id'];
}

$target = caremeal_path('View/FrontOffice/student/dashboard.php');
if (!empty($query)) {
    $target .= '?' . http_build_query($query);
}

header('Location: ' . $target, true, 302);
exit;
