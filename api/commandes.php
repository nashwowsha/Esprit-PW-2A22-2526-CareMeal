<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Controller/OrderController.php';

try {
    $pdo = getPDO();
    $controller = new OrderController($pdo);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function readJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $row = $controller->show($id);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
                exit;
            }
            echo json_encode($row);
        } else {
            $userId = isset($_GET['user_id']) ? (string)$_GET['user_id'] : null;
            echo json_encode($controller->index($userId));
        }
        exit;
    }

    if ($method === 'POST') {
        $data = readJsonInput();
        $result = $controller->store($data);
        http_response_code(201);
        echo json_encode($result);
        exit;
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        $data = readJsonInput();

        // Handle cancel action
        if (isset($data['action']) && $data['action'] === 'cancel') {
            $result = $controller->cancel($id);
            echo json_encode($result);
            exit;
        }

        // Handle status update
        if (isset($data['statut'])) {
            $result = $controller->updateStatus($id, $data['statut']);
            echo json_encode($result);
            exit;
        }

        http_response_code(422);
        echo json_encode(['error' => 'No supported fields to update']);
        exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        $ok = $controller->delete($id);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
