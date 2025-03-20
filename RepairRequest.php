<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Origin, Content-Type");

require __DIR__ . '/controller/RepairRequestController.php';

/**
 * Send JSON response with HTTP status code
 */
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    exit;
}

/**
 * Retrieve JSON input
 */
function getJsonInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
    }
    return $input;
}

// Instantiate controller
$repairRequests = new RepairRequestController();
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;

try {
    switch ($method) {
        case 'GET':
            $result = isset($_GET['id']) ? 
                $repairRequests->getRepairRequest($_GET['id']) : 
                $repairRequests->getRepairRequests();
            sendJsonResponse($result);
            break;

        case 'POST':
            $data = getJsonInput();
            if (isset($data['user_id'], $data['asset_id'], $data['issue'], $data['remarks'], $data['date_reported'], $data['urgency_id'], $data['repair_start_date'], $data['repair_completion_date'], $data['status_id'], $data['repair_cost'])) {
                $result = $repairRequests->addRepairRequest(
                    $data['user_id'], $data['asset_id'], $data['issue'], $data['remarks'], 
                    $data['date_reported'], $data['urgency_id'], $data['repair_start_date'], 
                    $data['repair_completion_date'], $data['status_id'], $data['repair_cost']
                );
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing required fields"], 400);
            }
            break;

        case 'PUT':
            $data = getJsonInput();
            if (isset($data['repair_request_id'], $data['user_id'],  $data['repair_start_date'], $data['repair_completion_date'], $data['status_id'], $data['repair_cost'])) {
                // Automatically set completion date if status is 'Completed'
                if ($data['status_id'] == '5' && empty($data['repair_completion_date'])) {
                    $data['repair_completion_date'] = date('Y-m-d H:i:s');
                }
                $result = $repairRequests->updateRepairRequest(
                    $data['repair_request_id'], $data['user_id'], isset($data['remarks']) ? $data['remarks'] : null, 
                    $data['repair_start_date'], $data['repair_completion_date'], 
                    $data['status_id'], $data['repair_cost']
                );
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing required fields"], 400);
            }
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
?>
