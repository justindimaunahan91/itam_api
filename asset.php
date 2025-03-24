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

require __DIR__ . '/controller/assetcontroller.php';
require __DIR__ . '/controller/AssetCategory.php';
require __DIR__ . '/controller/AssetSubCategory.php';
require __DIR__ . '/controller/AssetType.php';

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

/**
 * Send JSON response with HTTP status code
 */
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    exit;
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
    case "repairUrgency":
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
                // Process form data and file upload
                $data = $_POST['data'];
                $data = (array) json_decode($data);
                $file = isset($_FILES['file']) ? $_FILES['file'] : null;

                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . "/uploads/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = time() . "_" . basename($file['name']);
                    $filePath = "uploads/" . $fileName;
                    move_uploaded_file($file['tmp_name'], $filePath);
                    $data["file"] = $filePath;
                }

                $success = $controller->{$actions['create']}($data);
                sendJsonResponse(["message" => $success ? "Created successfully" : "Creation failed"], $success ? 201 : 500);
                break;

            case 'PUT':
                $data = json_decode(file_get_contents('php://input'), true);
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
