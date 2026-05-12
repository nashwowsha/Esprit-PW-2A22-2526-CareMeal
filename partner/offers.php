<?php
session_start();
require_once __DIR__ . '/../Controller/OfferController.php';

// Si partner_id n'est pas dans l'URL, l'injecter depuis la session
if (!isset($_GET['partner_id']) && isset($_SESSION['user_id'])) {
    $_GET['partner_id'] = $_SESSION['user_id'];
}

$controller = new OfferController();
$action     = $_POST['action'] ?? 'index';

switch ($action) {
    case 'create': $controller->store();  break;
    case 'update': $controller->update(); break;
    case 'delete': $controller->delete(); break;
    default:       $controller->index();  break;
}