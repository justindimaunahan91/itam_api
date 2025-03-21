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

require __DIR__ . '/controller/BorrowedAssetsController.php';

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
$borrowedAssets = new BorrowedAssetsController();

// Handle API Requests
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$resource = $_POST['resource'] ?? null;
$resource = $_PUT['resource'] ?? null;


try {
    switch ($method) {
        case 'GET':
            if ($resource === 'borrowed_assets') {
                $result = isset($_GET['id']) ? 
                    $borrowedAssets->getOneBorrowedAsset($_GET['id']) : 
                    $borrowedAssets->getBorrowedAssets();
                sendJsonResponse($result);
            }
            break;

        case 'POST':
            $data = (array) json_decode($_POST['data']);
            if (isset($data['company_id'], $data['user_id'], $data['asset_id'], $data['date_borrowed'], $data['due_date'],$data['duration'], $data['remarks'] )) {
                $result = $borrowedAssets->insertBorrowedAsset($data['company_id'], $data['department_id'] === "" ? NULL : $data['department_id'], $data['unit_id'] === "" ? NULL : $data['unit_id'], $data['user_id'], $data['asset_id'], $data['date_borrowed'], $data['due_date'], $data['duration'], $data['asset_condition_id'], $data['remarks']  ?? null);
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing required fields"], 400);
            }
            break;

        case 'PUT':
            $data = getJsonInput();
            if (isset($data['borrow_transaction_id'])) {
                $result = $borrowedAssets->updateBorrowedAsset($data['borrow_transaction_id'], $data['due_date'] ?? null, $data['remarks'] ?? null);
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing 'borrow_transaction_id'"], 400);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $result = $borrowedAssets->deleteBorrowedAsset($_GET['id']);
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing 'id' parameter"], 400);
            }
            break;

        default:
            sendJsonResponse(["error" => "Method not allowed"], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(["error" => $e->getMessage()], 500);
}

?>
