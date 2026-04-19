<?php
session_start();
require_once __DIR__ . '/../Controller/admincategoriecontroller.php';

$controller = new AdminCategoryController();
$action     = $_POST['action'] ?? 'index';

switch ($action) {
    // Catégories
    case 'create_category': $controller->createCategory(); break;
    case 'update_category': $controller->updateCategory(); break;
    case 'delete_category': $controller->deleteCategory(); break;
    // Offres
    case 'create_offer':    $controller->createOffer();    break;
    case 'update_offer':    $controller->updateOffer();    break;
    case 'delete_offer':    $controller->deleteOffer();    break;
    // Défaut
    default:                $controller->index();          break;
}