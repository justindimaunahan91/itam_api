<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Origin, Content-Type");

require __DIR__ . '/controller/AssetIssuanceController.php';

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
$assetIssuance = new AssetIssuanceController();
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($resource === 'asset_issuance') {
                $result = isset($_GET['id']) ? 
                    $assetIssuance->getOneAssetIssuance($_GET['id']) : 
                    $assetIssuance->getAssetIssuances();
                sendJsonResponse($result);
            }
            break;

        case 'POST':
            $data = getJsonInput();
            if (isset($data['asset_id'], $data['user_id'], $data['issuance_date'], $data['pullout_date'], $data['status_id'])) {
                $remarks = $data['remarks'] ?? null; // Allow remarks to be null
                $result = $assetIssuance->addAssetIssuance(
                    $data['asset_id'], $data['user_id'], $data['issuance_date'], $data['pullout_date'], $data['status_id']
                );
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing required fields"], 400);
            }
            break;

        case 'PUT':
            $data = getJsonInput();
            if (isset($data['issuance_id'], $data['asset_id'], $data['user_id'], $data['issuance_date'], $data['pullout_date'], $data['status_id'])) {
                $remarks = $data['remarks'] ?? null; // Allow remarks to be null
                $result = $assetIssuance->updateAssetIssuance(
                    $data['issuance_id'], $data['asset_id'], $data['user_id'], $data['issuance_date'], $data['pullout_date'], $data['status_id']
                );
                sendJsonResponse($result);
            } else {
                sendJsonResponse(["error" => "Missing required fields"], 400);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $result = $assetIssuance->deleteAssetIssuance($_GET['id']);
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
