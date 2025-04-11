<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/controller/RepairRequestController.php';

function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    exit;
}

function getJsonInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
    }
    return $input;
}

$repairRequests = new RepairRequestController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $repairRequests->getRepairRequest($_GET['id']);
            } elseif (isset($_GET['status_id'])) {
                $result = $repairRequests->getRepairRequests(); // Get all repair requests
            } else {
                $result = $repairRequests->getRepairRequests(); // Get all
            }
            sendJsonResponse($result);
            break;

        case 'POST':
            $data = getJsonInput();
            $required = ['user_id', 'asset_id', 'issue', 'remarks', 'date_reported', 'urgency_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendJsonResponse(["error" => "Missing required field: $field"], 400);
                }
            }

            $result = $repairRequests->addRepairRequest(
                $data['user_id'],
                $data['asset_id'],
                $data['issue'],
                $data['remarks'],
                $data['date_reported'],
                $data['urgency_id']
            );
            sendJsonResponse($result);
            break;

        case 'PUT':
            $data = getJsonInput();
            $required = ['repair_request_id', 'user_id', 'repair_completion_date', 'status_id', 'repair_cost'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    sendJsonResponse(["error" => "Missing required field: $field"], 400);
                }
            }

            // Auto-complete date if completed
            if ($data['status_id'] == '5' && empty($data['repair_completion_date'])) {
                $data['repair_completion_date'] = date('Y-m-d H:i:s');
            }

            $result = $repairRequests->updateRepairRequest(
                $data['repair_request_id'],
                $data['user_id'],
                $data['remarks'] ?? null,
                $data['repair_completion_date'],
                $data['status_id']
            );
            sendJsonResponse($result);
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $result = $repairRequests->deleteRepairRequest($_GET['id']);
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing ID parameter"], 400);
            }
            break;

        default:
            sendJsonResponse(["error" => "Method not allowed"], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(["error" => $e->getMessage()], 500);
}
