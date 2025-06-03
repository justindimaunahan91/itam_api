<?php
require_once __DIR__ . '/controller/AssetInsurance.php';

header('Content-Type: application/json');

// Initialize the controller
$insuranceController = new AssetInsurance();

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get parameters (support JSON body and form data)
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Simple routing based on method and "action" in URL
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'getAll':
            $insuranceController->retrieveInsurance();
            break;

        case 'getOne':
            if (isset($_GET['id'])) {
                $result = $insuranceController->retrieveOneInsurance($_GET['id']);
                echo json_encode($result);
            } else {
                echo json_encode(["error" => "Missing insurance ID"]);
            }
            break;

        case 'insert':
            $insurance_name = $input['insurance_name'] ?? null;
            $insurance_coverage = $input['insurance_coverage'] ?? null;
            $insurance_date_from = $input['insurance_date_from'] ?? null;
            $insurance_date_to = $input['insurance_date_to'] ?? null;

            $result = $insuranceController->insertInsurance(
                $insurance_name,
                $insurance_coverage,
                $insurance_date_from,
                $insurance_date_to
            );
            echo json_encode($result);
            break;

        case 'update':
            if (isset($input['insurance_id'])) {
                $insurance_id = $input['insurance_id'];
                $insurance_name = $input['insurance_name'] ?? null;
                $insurance_coverage = $input['insurance_coverage'] ?? null;
                $insurance_date_from = $input['insurance_date_from'] ?? null;
                $insurance_date_to = $input['insurance_date_to'] ?? null;

                $insuranceController->updateInsurance(
                    $insurance_id,
                    $insurance_name,
                    $insurance_coverage,
                    $insurance_date_from,
                    $insurance_date_to
                );
            } else {
                echo json_encode(["error" => "Missing insurance ID for update"]);
            }
            break;

        case 'delete':
            if (isset($_GET['id'])) {
                $insuranceController->deleteInsurance($_GET['id']);
            } else {
                echo json_encode(["error" => "Missing insurance ID for deletion"]);
            }
            break;

        default:
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
} else {
    echo json_encode(["error" => "No action specified"]);
}
?>
