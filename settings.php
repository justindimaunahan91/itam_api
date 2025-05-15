<?php
require_once 'controllers/settingscontroller.php';

$controller = new SettingsController();

// Set JSON response header
header('Content-Type: application/json');

// Parse input (for PUT, DELETE, JSON body, etc.)
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Utility to send a response
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Global helper function to get a setting by key
function getSetting($key) {
    static $cache = [];

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $settingsController = new SettingsController();
    $value = $settingsController->getSetting($key);
    $cache[$key] = $value;

    return $value;
}

// Routing based on request
switch ($_GET['action'] ?? '') {

    // === GENERAL SETTINGS ===
    case 'get_settings':
        respond($controller->getSettings());

    case 'update_settings':
        if ($method !== 'POST' || empty($input)) respond(["error" => "Invalid request"], 400);
        respond($controller->updateSettings($input));

    case 'get_setting_by_key':
        if (!isset($_GET['key'])) respond(["error" => "Key parameter required"], 400);
        $value = getSetting($_GET['key']);
        respond(["key" => $_GET['key'], "value" => $value]);

    // === RECYCLE BIN ===
    case 'get_recycle_bin':
        respond($controller->getRecycleBinItems());

    case 'restore_asset':
        if ($method !== 'POST' || !isset($input['id'])) respond(["error" => "Asset ID required"], 400);
        respond($controller->restoreFromRecycleBin($input['id']));

    // === FACTORY RESET ===
    case 'factory_reset':
        if ($method !== 'POST') respond(["error" => "Invalid method"], 405);
        respond($controller->factoryReset());

    // === CATEGORY-SUBCATEGORY MAPPING ===
    case 'get_catsub_mappings':
        respond($controller->getCategorySubcategoryMappings());

    case 'add_catsub_mapping':
        if ($method !== 'POST' || !isset($input['category_id'], $input['subcategory_id'])) {
            respond(["error" => "category_id and subcategory_id required"], 400);
        }
        respond($controller->addCategorySubcategoryMapping($input['category_id'], $input['subcategory_id']));

    case 'update_catsub_mapping':
        if ($method !== 'POST' || !isset($input['id'], $input['category_id'], $input['subcategory_id'])) {
            respond(["error" => "id, category_id, and subcategory_id required"], 400);
        }
        respond($controller->updateCategorySubcategoryMapping($input['id'], $input['category_id'], $input['subcategory_id']));

    case 'delete_catsub_mapping':
        if ($method !== 'POST' || !isset($input['id'])) {
            respond(["error" => "id required"], 400);
        }
        respond($controller->deleteCategorySubcategoryMapping($input['id']));

    // === SUBCATEGORY-TYPE MAPPING ===
    case 'get_subtype_mappings':
        respond($controller->getSubTypeMappings());

    case 'add_subtype_mapping':
        if ($method !== 'POST' || !isset($input['subcategory_id'], $input['type_id'])) {
            respond(["error" => "subcategory_id and type_id required"], 400);
        }
        respond($controller->addSubTypeMapping($input['subcategory_id'], $input['type_id']));

    case 'update_subtype_mapping':
        if ($method !== 'POST' || !isset($input['id'], $input['subcategory_id'], $input['type_id'])) {
            respond(["error" => "id, subcategory_id, and type_id required"], 400);
        }
        respond($controller->updateSubTypeMapping($input['id'], $input['subcategory_id'], $input['type_id']));

    case 'delete_subtype_mapping':
        if ($method !== 'POST' || !isset($input['id'])) {
            respond(["error" => "id required"], 400);
        }
        respond($controller->deleteSubTypeMapping($input['id']));

    // === UNKNOWN ACTION ===
    default:
        respond(["error" => "Unknown or missing action."], 404);
}
