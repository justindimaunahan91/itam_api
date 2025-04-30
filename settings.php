<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/controller/SettingsController.php';

$controller = new SettingsController();

// Get action from query
$action = $_GET['action'] ?? null;

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($action === 'getSettings') {
            sendJson($controller->getSettings());
        } elseif ($action === 'recycleBin') {
            sendJson($controller->getRecycleBinItems());
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        if ($action === 'updateSettings') {
            $result = $controller->updateSettings($input);
            sendJson(["success" => $result === true, "message" => $result === true ? "Settings updated" : $result['error'] ?? "Unknown error"]);
        } elseif ($action === 'restore') {
            $result = $controller->restoreFromRecycleBin($input['id'] ?? 0);
            sendJson(["success" => $result === true, "message" => $result === true ? "Asset restored" : $result['error'] ?? "Restore failed"]);
        } elseif ($action === 'factoryReset') {
            $result = $controller->factoryReset();
            sendJson(["success" => $result === true, "message" => $result === true ? "System reset successful" : $result['error'] ?? "Reset failed"]);
        }
        break;

    default:
        sendJson(["error" => "Invalid request method"], 405);
}
