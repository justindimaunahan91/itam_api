<?php
require __DIR__ . 'UserTransactionsController.php';
header("Content-Type: application/json");

$controller = new UserTransactionsController();

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Read input parameters
$input = json_decode(file_get_contents("php://input"), true);

// Route handling based on HTTP method
switch ($method) {
    case 'GET':
        if (isset($_GET['user_id'])) {
            // Fetch transactions for a specific user
            $user_id = $_GET['user_id'];
            echo json_encode($controller->getTransactionsByUser($user_id));
        } else {
            // Fetch all transactions
            echo json_encode($controller->getUserTransactions());
        }
        break;

    case 'POST':
        // Add a new user transaction
        if (isset($input['user_id'], $input['asset_id'], $input['date_borrowed'], $input['due_date'], $input['asset_condition_id'])) {
            $controller->addUserTransaction(
                $input['user_id'],
                $input['asset_id'],
                $input['date_borrowed'],
                $input['due_date'],
                $input['return_date'] ?? null,
                $input['duration'] ?? null,
                $input['asset_condition_id'],
                $input['remarks'] ?? null
            );
            echo json_encode(["message" => "Transaction added successfully"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
        }
        break;

    case 'PUT':
        // Update an existing transaction
        if (isset($input['borrow_transaction_id'], $input['user_id'], $input['asset_id'], $input['date_borrowed'], $input['due_date'], $input['return_date'], $input['duration'], $input['asset_condition_id'], $input['remarks'])) {
            $controller->updateUserTransaction(
                $input['borrow_transaction_id'],
                $input['user_id'],
                $input['asset_id'],
                $input['date_borrowed'],
                $input['due_date'],
                $input['return_date'],
                $input['duration'],
                $input['asset_condition_id'],
                $input['remarks']
            );
            echo json_encode(["message" => "Transaction updated successfully"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
        }
        break;

    case 'DELETE':
        // Delete a transaction
        if (isset($input['borrow_transaction_id'])) {
            $controller->deleteUserTransaction($input['borrow_transaction_id']);
            echo json_encode(["message" => "Transaction deleted successfully"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Missing borrow_transaction_id"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
?>
