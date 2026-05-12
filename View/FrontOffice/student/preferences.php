<?php
require_once dirname(__DIR__, 2) . '/session_check.php';
require_once dirname(__DIR__, 3) . '/config/app.php';

$query = $_GET;
if (!isset($query['id_user']) && isset($_SESSION['user_id'])) {
    $query['id_user'] = (int)$_SESSION['user_id'];
}

$target = caremeal_path('student/preferences.php');
if (!empty($query)) {
    $target .= '?' . http_build_query($query);
}

header('Location: ' . $target, true, 302);
exit;