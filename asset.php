<?php

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require __DIR__ . '/controller/assetcontroller.php';
require __DIR__ . '/controller/AssetCategory.php';
require __DIR__ . '/controller/AssetSubCategory.php';
require __DIR__ . '/controller/AssetType.php';

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

// Instantiate controllers
$assetController = new Asset();
$controller = [  
    'asset'       => $assetController,
    'category'    => new AssetCategory(),
    'subcategory' => new AssetSubCategory(),
    'type'        => new AssetType()
];

$resource = $_GET['resource'] ?? null;

/**
 * Handle Special Requests
 */
switch ($resource) {
    case "repair_urgency_levels":
        sendJsonResponse($assetController->getRepairUrgencyLevels());
        break;

    case "repair_urgency_assets":
        sendJsonResponse($assetController->getRepairUrgencyAssets());
        break;

    case "condition":
        sendJsonResponse($assetController->getAssetCondition());
        break;

    case "status":
        sendJsonResponse($assetController->getAssetStatus());
        break;

    default:
        if ($resource && isset($controller[$resource])) {
            handleRequest($controller[$resource], $routes[$resource]);
        } else {
            sendJsonResponse(["error" => "Unknown or missing 'resource' parameter"], 400);
        }
        break;
}

/**
 * Handle CRUD operations dynamically
 */
function handleRequest($controller, $actions) {
    $method = $_SERVER['REQUEST_METHOD'];

    try {
        switch ($method) {
            case 'GET':
                $result = !empty($_GET['id']) 
                    ? $controller->{$actions['getOne']}($_GET['id']) 
                    : $controller->{$actions['getAll']}();
                sendJsonResponse($result ?: ["error" => "No records found"], $result ? 200 : 404);
                break;
            
            case 'POST':
                $data = $_POST ?: getJsonInput();
                $success = call_user_func_array([$controller, $actions['create']], array_values($data));
                sendJsonResponse(["message" => $success ? "Created successfully" : "Creation failed"], $success ? 201 : 500);
                break;

            case 'PUT':
                $data = getJsonInput();
                if (!isset($data['id']) || empty($data['id'])) 
                    sendJsonResponse(["error" => "Missing 'id' field for update"], 400);
                
                $success = call_user_func_array([$controller, $actions['update']], array_values($data));
                sendJsonResponse(["message" => $success ? "Updated successfully" : "Update failed"], $success ? 200 : 500);
                break;

            case 'DELETE':
                if (!isset($_GET['id']) || empty($_GET['id'])) 
                    sendJsonResponse(["error" => "Missing 'id' parameter for deletion"], 400);

                $success = $controller->{$actions['delete']}($_GET['id']);
                sendJsonResponse(["message" => $success ? "Deleted successfully" : "Delete failed"], $success ? 200 : 500);
                break;

            default:
                sendJsonResponse(["error" => "Method not allowed"], 405);
        }
    } catch (Exception $e) {
        sendJsonResponse(["error" => $e->getMessage()], 500);
    }
}

// Define API routes
$routes = [
    'asset' => [
        'getAll'  => 'retrieveAssets',
        'getOne'  => 'retrieveOneAsset',
        'create'  => 'insertAsset',
        'update'  => 'updateAsset',
        'delete'  => 'deleteAsset'
    ],
    'category' => [
        'getAll'  => 'retrieveCategories',
        'getOne'  => 'retrieveOneCategory',
        'create'  => 'insertCategory',
        'update'  => 'updateCategory',
        'delete'  => 'deleteCategory'
    ],
    'subcategory' => [
        'getAll'  => 'retrieveSubCategories',
        'getOne'  => 'retrieveOneSubCategory',
        'create'  => 'insertSubCategory',
        'update'  => 'updateSubCategory',
        'delete'  => 'deleteSubCategory'
    ],
    'type' => [
        'getAll'  => 'retrieveAssetTypes',
        'getOne'  => 'retrieveOneAssetType',
        'create'  => 'insertAssetType',
        'update'  => 'updateAssetType',
        'delete'  => 'deleteAssetType'
    ]
];
