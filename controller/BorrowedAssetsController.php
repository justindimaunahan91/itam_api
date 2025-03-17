<?php
require_once __DIR__ . '/controller.php';

class BorrowedAssetsController extends Controller
{
    /**
     * Retrieve all borrowed assets (excluding returned ones).
     */
    public function getBorrowedAssets()
    {
        try {
            $sql = "SELECT
    t.borrow_transaction_id,
    t.user_id,
    u.employee_id,
    CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
    t.asset_id,
    a.asset_name,
    t.date_borrowed,
    t.due_date,
    t.return_date,
    t.duration,
    c.asset_condition_name, 
    t.remarks
FROM itam_asset_transactions AS t
JOIN un_users AS u ON t.user_id = u.user_id
JOIN itam_asset AS a ON t.asset_id = a.asset_id
JOIN itam_asset_condition AS c ON t.asset_condition_id = c.asset_condition_id  
WHERE t.return_date IS NULL
ORDER BY t.date_borrowed DESC;
";

            $this->setStatement($sql);
            $this->statement->execute();
            $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);

            return $result ?: ["error" => "No borrowed assets found"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Retrieve one borrowed asset by transaction ID.
     */
    public function getOneBorrowedAsset($borrowTransactionId)
    {
        try {
            $sql = "SELECT
                        t.borrow_transaction_id,
                        t.user_id,
                        u.employee_id,
                        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
                        t.asset_id,
                        a.asset_name,
                        t.date_borrowed,
                        t.due_date,
                        t.duration,
                        c.asset_condition_name, 
                        t.remarks
                    FROM itam_asset_transactions AS t
                    JOIN un_users AS u ON t.user_id = u.user_id
                    JOIN itam_asset AS a ON t.asset_id = a.asset_id
                    JOIN itam_asset_condition AS c ON t.asset_condition_id = c.asset_condition_id  
                    WHERE t.borrow_transaction_id = ?";

            $this->setStatement($sql);
            $this->statement->execute([$borrowTransactionId]);
            $result = $this->statement->fetch(PDO::FETCH_ASSOC);

            return $result ?: ["error" => "Transaction not found"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Insert a new borrowed asset transaction.
     */
    public function insertBorrowedAsset($company_id, $department_id, $unit_id, $userId, $assetId, $dateBorrowed, $dueDate, $duration, $assetConditionId, $remarks = null)
    {
        try {
            $sql = "INSERT INTO itam_asset_transactions 
                    (company_id, department_id, unit_id, user_id, asset_id, date_borrowed, due_date, duration, asset_condition_id, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->setStatement($sql);
            $success = $this->statement->execute([$company_id, $department_id, $unit_id,$userId, $assetId, $dateBorrowed, $dueDate, $duration, $assetConditionId, $remarks]);

            return ["message" => $success ? "Transaction added successfully" : "Insert failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Update an existing borrowed asset record.
     */
    public function updateBorrowedAsset($borrowTransactionId, $dueDate = null, $remarks = null)
    {
        try {
            $sql = "UPDATE itam_asset_transactions SET 
                    due_date = COALESCE(?, due_date), 
                    remarks = COALESCE(?, remarks) 
                    WHERE borrow_transaction_id = ?";

            $this->setStatement($sql);
            $success = $this->statement->execute([$dueDate, $remarks, $borrowTransactionId]);

            return ["message" => $success ? "Transaction updated successfully" : "Update failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Mark a borrowed asset as returned.
     */
    public function returnBorrowedAsset($borrowTransactionId, $returnDate)
    {
        try {
            $sql = "UPDATE itam_asset_transactions SET return_date = ? WHERE borrow_transaction_id = ?";

            $this->setStatement($sql);
            $success = $this->statement->execute([$returnDate, $borrowTransactionId]);

            return ["message" => $success ? "Asset marked as returned" : "Update failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Delete a borrowed asset transaction.
     */
    public function deleteBorrowedAsset($borrowTransactionId)
    {
        try {
            $sql = "DELETE FROM itam_asset_transactions WHERE borrow_transaction_id = ?";

            $this->setStatement($sql);
            $success = $this->statement->execute([$borrowTransactionId]);

            return ["message" => $success ? "Transaction deleted successfully" : "Delete failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
