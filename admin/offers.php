<?php
session_start();
require_once __DIR__ . '/../Controller/AdminOfferController.php';

$controller = new AdminOfferController();
$action     = $_POST['action'] ?? 'index';

switch ($action) {
    case 'update': $controller->update(); break;
    case 'delete': $controller->delete(); break;
    default:       $controller->index();  break;
}