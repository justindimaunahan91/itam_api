<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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
        'getAll'      => 'retrieveAssets',
        'getOne'      => 'retrieveOneAsset',
        'create'      => 'insertAsset',
        'update'      => 'updateAsset',
        'delete'      => 'deleteAsset',
        'batchInsert' => 'batchInsertAssets'
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
    ],
    'mappedtype' => [
        'getAll'  => 'retrieveAllMappedTypes',
        'create'  => 'insertMappedAssetType'
    ]
];

function sendJsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    exit;
}

$assetController       = new Asset();
$subCategoryController = new AssetSubCategory();

$controller = [
    'asset'       => $assetController,
    'category'    => new AssetCategory(),
    'subcategory' => $subCategoryController,
    'type'        => new AssetType(),
    'mappedtype'  => new AssetType()
];

$resource = $_GET['resource'] ?? null;

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

function handleRequest($controller, $actions)
{
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
                if (!isset($_POST['data'])) {
                    sendJsonResponse(["error" => "Invalid request, missing data"], 400);
                }

                $data = json_decode($_POST['data'], true);
                if (!$data) {
                    sendJsonResponse(["error" => "Invalid JSON input"], 400);
                }

                $files = isset($_FILES['file']) ? $_FILES['file'] : null;
                $filenames = [];

                if ($files && is_array($files['name'])) {
                    $uploadDir = __DIR__ . "/uploads/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    foreach ($files['name'] as $index => $name) {
                        if ($files['error'][$index] === UPLOAD_ERR_OK) {
                            $fileName = time() . "_" . basename($name);
                            $filePath = "uploads/" . $fileName;
                            move_uploaded_file($files['tmp_name'][$index], $filePath);
                            $filenames[] = $filePath;
                        }
                    }
                }

                $data['filenames'] = $filenames;

                // ✅ Handle batch insert for assets
                if ($actions['create'] === 'batchInsertAssets') {
                    $success = call_user_func([$controller, $actions['create']], $data);

                    if (isset($success['error'])) {
                        sendJsonResponse($success, 500);
                    } else {
                        sendJsonResponse(["message" => "Batch insert completed."], 201);
                    }
                    return;
                }

                // ✅ Handle insertMappedAssetType
                if ($actions['create'] === 'insertMappedAssetType') {
                    if (!isset($data['sub_category_id']) || !isset($data['type_name'])) {
                        sendJsonResponse(["error" => "Missing sub_category_id or type_name"], 400);
                    }

                    $controller->insertMappedAssetType($data['sub_category_id'], $data['type_name']);
                    return;
                }

                // ✅ Handle insertSubCategory
                if ($actions['create'] === 'insertSubCategory') {
                    if (!isset($data['sub_category_name'])) {
                        sendJsonResponse(["error" => "Missing sub_category_name"], 400);
                    }

                    $category_id = $data['category_id'] ?? null;
                    $sub_category_name = $data['sub_category_name'] ?? null;

                    if (!$category_id) {
                        sendJsonResponse(["error" => "Missing category_id"], 400);
                    }

                    $result = $controller->insertSubCategory($category_id, $sub_category_name);

                    if (isset($result['error'])) {
                        sendJsonResponse($result, 500);
                    } elseif (isset($result['sub_category_id'])) {
                        sendJsonResponse($result, 200); // Already exists
                    } elseif (isset($result['message'])) {
                        sendJsonResponse($result, 201); // Newly created
                    } else {
                        sendJsonResponse(["message" => "Unknown response"], 500);
                    }
                    return;
                }

                // ✅ Default insert action
                $success = call_user_func([$controller, $actions['create']], $data);
                sendJsonResponse(["message" => $success ? "Created successfully" : "Creation failed"], $success ? 201 : 500);
                break;

            case 'PUT':
            case 'DELETE':
                parse_str(file_get_contents("php://input"), $input);
                $data = json_decode($input['data'] ?? '{}', true);
                $id = $_GET['id'] ?? null;

                if (!$id) {
                    sendJsonResponse(["error" => "Missing ID"], 400);
                }

                $action = $method === 'PUT' ? $actions['update'] : $actions['delete'];
                $success = $controller->{$action}($id, $data);

                sendJsonResponse(["message" => $success ? "Success" : "Failed"], $success ? 200 : 500);
                break;

            default:
                sendJsonResponse(["error" => "Unsupported method"], 405);
                break;
        }
    } catch (Exception $e) {
        sendJsonResponse(["error" => $e->getMessage()], 500);
    }
}
