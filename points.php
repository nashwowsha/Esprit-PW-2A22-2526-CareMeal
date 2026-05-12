<?php
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = $_GET;
if (!isset($query['id_user']) && isset($_SESSION['user_id'])) {
    $query['id_user'] = (int)$_SESSION['user_id'];
}

$location = caremeal_path('View/FrontOffice/student/points.php');
if (!empty($query)) {
    $location .= '?' . http_build_query($query);
}

header('Location: ' . $location, true, 302);
exit;
