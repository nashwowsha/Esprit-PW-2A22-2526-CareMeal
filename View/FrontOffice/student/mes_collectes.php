<?php
require_once dirname(__DIR__, 2) . '/session_check.php';
require_once dirname(__DIR__, 3) . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = $_GET;
if (!isset($query['id_user']) && isset($_SESSION['user_id'])) {
    $query['id_user'] = (int)$_SESSION['user_id'];
}

$target = caremeal_path('student/mes_collectes.php');
if (!empty($query)) {
    $target .= '?' . http_build_query($query);
}

header('Location: ' . $target, true, 302);
exit;

