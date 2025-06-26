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
        'getAll'  => 'retrieveAssets',
        'getOne'  => 'retrieveOneAsset',
        'create'  => 'insertAsset',
        'update'  => 'updateAsset',
        'delete'  => 'deleteAsset',
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

$assetController = new Asset();
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
                $data['status'] = $data['status'] ?? $data['status_id'] ?? null;

                if ($actions['create'] === 'insertCategory') {
                    $data = json_decode($_POST['data'] ?? '', true);

                    if (!isset($data['category_name']) || !isset($data['status'])) {
                        sendJsonResponse(["error" => "Missing category_name or status"], 400);
                    }

                    $success = call_user_func_array([$controller, $actions['create']], [$data['category_name'], $data['status']]);
                    sendJsonResponse(["message" => $success ? "Category added successfully" : "Creation failed"], $success ? 201 : 500);
                }

                if (isset($_GET['action']) && $_GET['action'] === 'update') {
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        sendJsonResponse(["error" => "Missing asset_id in query string"], 400);
                    }

                    $jsonData = $_POST['data'] ?? '{}';
                    $data = json_decode($jsonData, true);
                    if (!is_array($data)) {
                        sendJsonResponse(["error" => "Invalid JSON in 'data' field"], 400);
                    }

                    $files = $_FILES['file'] ?? null;
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
                    $success = call_user_func([$controller, $actions['update']], $id, $data);
                    sendJsonResponse(["message" => $success ? "Updated successfully" : "Update failed"], $success ? 200 : 500);
                    return;
                }

                if (isset($_GET['action']) && $_GET['action'] === 'batchInsert') {
                    if (!isset($_POST['data'])) {
                        sendJsonResponse(["error" => "Missing batch insert data"], 400);
                    }

                    $data = json_decode($_POST['data'], true);
                    if (!is_array($data)) {
                        sendJsonResponse(["error" => "Decoded batch data is not an array"], 400);
                    }

                    // handle files if needed...

                    $success = call_user_func([$controller, $actions['batchInsert']], $data);
                    sendJsonResponse(["message" => "Batch insert completed."], 201);
                }

                // 🧠 If NOT batchInsert, treat it as single insert (FormData-style)
                $data = $_POST;
                $files = $_FILES['file'] ?? null;
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



                $data = json_decode($_POST['data'], true);
                $data['filenames'] = $filenames;

                if (!is_array($data)) {
                    sendJsonResponse(["error" => "Decoded data is not an array"], 400);
                }

                if ($actions['create'] === 'insertAsset' && !isset($data['category'])) {
                    sendJsonResponse(["error" => "Missing category_id"], 400);
                }

                $success = call_user_func([$controller, $actions['create']], $data);
                sendJsonResponse(["message" => $success ? "Created successfully" : "Creation failed"], $success ? 201 : 500);


                // $files = $_FILES['file'] ?? null;
                // $filenames = [];

                // if ($files && is_array($files['name'])) {
                //     $uploadDir = __DIR__ . "/uploads/";
                //     if (!is_dir($uploadDir)) {
                //         mkdir($uploadDir, 0777, true);
                //     }

                //     foreach ($files['name'] as $index => $name) {
                //         if ($files['error'][$index] === UPLOAD_ERR_OK) {
                //             $fileName = time() . "_" . basename($name);
                //             $filePath = "uploads/" . $fileName;
                //             move_uploaded_file($files['tmp_name'][$index], $filePath);
                //             $filenames[] = $filePath;
                //         }
                //     }
                // }

                // $data['filenames'] = $filenames;

                // var_dump($data);
                // echo $actions['create'];
                if ($actions['batchInsert'] === 'batchInsertAssets') {
                    if ($data === null) {
                        sendJsonResponse(["error" => "Invalid JSON input"], 400);
                    }

                    $success = call_user_func([$controller, $actions['batchInsert']], $data);

                    if (isset($success['error'])) {
                        sendJsonResponse($success, 500);
                    } else {
                        sendJsonResponse(["message" => "Batch insert completed."], 201);
                    }
                } elseif ($actions['create'] === 'insertAsset') {
                    // if (!isset($data['data'])) {
                    //     sendJsonResponse(["error" => "Missing category_id"], 400);
                    // }

                    $success = call_user_func([$controller, $actions['create']], $data);
                    sendJsonResponse(["message" => $success ? "Created successfully" : "Creation failed"], $success ? 201 : 500);
                } elseif ($actions['create'] === 'insertMappedAssetType') {
                    if (!isset($data['sub_category_id']) || !isset($data['type_name'])) {
                        sendJsonResponse(["error" => "Missing sub_category_id or type_name"], 400);
                    }

                    $controller->insertMappedAssetType($data['sub_category_id'], $data['type_name']);
                    return;
                } elseif ($actions['create'] === 'insertSubCategory') {
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
                        sendJsonResponse($result, 200);
                    } elseif (isset($result['message'])) {
                        sendJsonResponse($result, 201);
                    } else {
                        sendJsonResponse(["message" => "Unknown response"], 500);
                    }
                }

                break;


            case 'PUT':
                $data = json_decode(file_get_contents('php://input'), true);

                var_dump($data);
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
