<?php


require __DIR__ . '/config.php';
require __DIR__ . '/controller/asset_transaction_controller.php';

$controller = new AssetTransactionController();
$resource = $_GET['resource'] ?? null;

/**
 * Handle CRUD operations for transactions
 */
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $result = !empty($_GET['user_id']) 
            ? $controller->getTransactionsByUser($_GET['user_id']) 
            : $controller->getAssetTransactions();
        sendJsonResponse($result);
        break;
    
    case 'POST':
        $data = (array) json_decode($_POST['data']);
        $success = call_user_func_array([$controller, 'addAssetTransaction'], array_values($data));
        sendJsonResponse(["message" => $success ? "Created successfully" : "Creation failed"]);
        break;

    case 'PUT':
        $data = getJsonInput();
        if (!isset($data['borrow_transaction_id'])) sendJsonResponse(["error" => "Missing 'borrow_transaction_id' field"], 400);
        $success = call_user_func_array([$controller, 'updateAssetTransaction'], array_values($data));
        sendJsonResponse(["message" => $success ? "Updated successfully" : "Update failed"]);
        break;

    case 'DELETE':
        if (!isset($_GET['borrow_transaction_id'])) sendJsonResponse(["error" => "Missing 'borrow_transaction_id' parameter"], 400);
        $success = $controller->deleteAssetTransaction($_GET['borrow_transaction_id']);
        sendJsonResponse(["message" => $success ? "Deleted successfully" : "Delete failed"]);
        break;

    default:
        sendJsonResponse(["error" => "Method not allowed"], 405);
}
