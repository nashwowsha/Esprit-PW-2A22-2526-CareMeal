<?php
session_start();
require_once __DIR__ . '/../Controller/OfferController.php';

$controller = new OfferController();
$action     = $_POST['action'] ?? 'index';

switch ($action) {
    case 'create': $controller->store();  break;
    case 'update': $controller->update(); break;
    case 'delete': $controller->delete(); break;
    default:       $controller->index();  break;
}