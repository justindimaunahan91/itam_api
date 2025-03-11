<?php
require_once "/controller.php";

class AssetTransactionController extends Controller {

    /**
     * Send JSON response with HTTP status code
     */
    protected function sendJsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        exit;
    }

    /**
     * Retrieve JSON input
     */
    private function getJsonInput() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendJsonResponse(['error' => 'Invalid JSON input'], 400);
        }
        return $input;
    }

    /**
     * Retrieve all asset transactions
     */
    function getAssetTransactions() {
        try {
            $this->setStatement("SELECT t.borrow_transaction_id, t.user_id, t.asset_id, a.asset_name, 
                                         t.date_borrowed, t.due_date, t.return_date, t.duration, 
                                         t.asset_condition_id, t.remarks
                                  FROM itam_asset_transactions AS t
                                  JOIN itam_asset AS a ON t.asset_id = a.asset_id
                                  ORDER BY t.borrow_transaction_id");
            $this->statement->execute();
            $result = $this->statement->fetchAll();

            $this->sendJsonResponse($result ?: ["error" => "No records found"], $result ? 200 : 404);
        } catch (Exception $e) {
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve asset transactions for a specific user
     */
    function getTransactionsByUser($user_id) {
        try {
            $this->setStatement("SELECT t.borrow_transaction_id, t.asset_id, a.asset_name, 
                                         t.date_borrowed, t.due_date, t.return_date, t.duration, 
                                         t.asset_condition_id, t.remarks
                                  FROM itam_asset_transactions AS t
                                  JOIN itam_asset AS a ON t.asset_id = a.asset_id
                                  WHERE t.user_id = ?
                                  ORDER BY t.date_borrowed DESC");
            $this->statement->execute([$user_id]);
            $result = $this->statement->fetchAll();

            $this->sendJsonResponse($result ?: ["error" => "No transactions found for user"], $result ? 200 : 404);
        } catch (Exception $e) {
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Add a new asset transaction
     */
    function addAssetTransaction($user_id, $asset_id, $date_borrowed, $due_date, $return_date = null, $duration = null, $asset_condition_id, $remarks = null) {
        try {
            $this->setStatement("INSERT INTO itam_asset_transactions (user_id, asset_id, date_borrowed, due_date, return_date, duration, asset_condition_id, remarks)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $success = $this->statement->execute([$user_id, $asset_id, $date_borrowed, $due_date, $return_date, $duration, $asset_condition_id, $remarks]);

            $this->sendJsonResponse(["message" => $success ? "Transaction added successfully" : "Failed to add transaction"], $success ? 201 : 500);
        } catch (Exception $e) {
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing asset transaction
     */
    function updateAssetTransaction($borrow_transaction_id, $user_id, $asset_id, $date_borrowed, $due_date, $return_date, $duration, $asset_condition_id, $remarks) {
        try {
            $this->setStatement("UPDATE itam_asset_transactions 
                                 SET user_id = ?, asset_id = ?, date_borrowed = ?, due_date = ?, return_date = ?, duration = ?, asset_condition_id = ?, remarks = ?
                                 WHERE borrow_transaction_id = ?");
            $success = $this->statement->execute([$user_id, $asset_id, $date_borrowed, $due_date, $return_date, $duration, $asset_condition_id, $remarks, $borrow_transaction_id]);

            $this->sendJsonResponse(["message" => $success ? "Transaction updated successfully" : "Update failed"], $success ? 200 : 500);
        } catch (Exception $e) {
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an asset transaction
     */
    function deleteAssetTransaction($borrow_transaction_id) {
        try {
            $this->setStatement("DELETE FROM itam_asset_transactions WHERE borrow_transaction_id = ?");
            $success = $this->statement->execute([$borrow_transaction_id]);

            $this->sendJsonResponse(["message" => $success ? "Transaction deleted successfully" : "Delete failed"], $success ? 200 : 500);
        } catch (Exception $e) {
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }
}
