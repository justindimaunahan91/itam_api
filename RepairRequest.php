<?php
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Allow specific methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow necessary headers
header("Content-Type: application/json");


// Handle OPTIONS request (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
/**
 * Send JSON response with HTTP status code
 */

require __DIR__ . '/controller/RepairRequestController.php';
function sendJsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    exit;
}

/**
 * Retrieve JSON input
 */
function getJsonInput()
{
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
// $resource = $_POST['resource'] ?? null;
// $resource = $_PUT['resource'] ?? null;


try {
    switch ($method) {
        case 'GET':
            $result = isset($_GET['id']) ?
                $repairRequests->getRepairRequestById($_GET['id']) :
                $repairRequests->getRepairRequests();
            sendJsonResponse($result);
            break;

        case 'POST':
            $data = (array) json_decode($_POST['data']);
            if (isset($data['user_id'], $data['asset_id'], $data['issue'], $data['remarks'], $data['date_reported'], $data['urgency_id'], $data['repair_start_date'], $data['repair_cost'])) {
                $result = $repairRequests->addRepairRequest(
                    $data['user_id'],
                    $data['asset_id'],
                    $data['issue'],
                    $data['remarks'],
                    $data['date_reported'],
                    $data['urgency_id'],
                    $data['repair_start_date']
                );
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing required fields"], 400);
            }
            break;

        case 'PUT':
            $data = getJsonInput();
            if (isset($data['repair_request_id'], $data['user_id'], $data['repair_completion_date'], $data['status_id'], $data['repair_cost'],  $data['remarks'] )) {
                // Automatically set completion date if status is 'Completed'
                if ($data['status_id'] == '5' && empty($data['repair_completion_date'])) {
                    $data['repair_completion_date'] = date('Y-m-d H:i:s');
                }
                $result = $repairRequests->updateRepairRequest(
                    repair_request_id: $data['repair_request_id'],
                    user_id: $data['user_id'],
                    repair_completion_date: $data['repair_completion_date'],
                    status_id: $data['status_id'],
                    repair_cost: $data['repair_cost'] ?? null,
                    remarks: $data['remarks'] ?? null,
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