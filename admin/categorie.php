<?php
session_start();
require_once __DIR__ . '/../Controller/admincategoriecontroller.php';

$controller = new AdminCategoryController();
$action = $_POST['action'] ?? 'index';

switch ($action) {
    case 'create_category':
        $controller->createCategory();
        break;
    case 'update_category':
        $controller->updateCategory();
        break;
    case 'delete_category':
        $controller->deleteCategory();
        break;
    default:
        $controller->index();
        break;
}
