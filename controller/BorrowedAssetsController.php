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
                        comp.name AS company_name,
                        u.company_id,
                        u.department_id,
                        d.name AS department_name,
                        t.asset_id,
                        a.category_id,
                        a.sub_category_id,
                        cat.category_name,
                        s.sub_category_name,
                        a.type_id,
                        type.type_name,
                        a.asset_name,
                        t.date_borrowed,
                        t.due_date,
                        t.return_date,
                        t.duration,
                        t.asset_condition_id,
                        c.asset_condition_name, 
                        t.remarks
                    FROM itam_asset_transactions AS t
                    JOIN un_users AS u ON t.user_id = u.user_id
                    LEFT JOIN un_company_departments AS d ON u.department_id = d.department_id
                    LEFT JOIN un_companies AS comp ON u.company_id = comp.company_id
                    JOIN itam_asset AS a ON t.asset_id = a.asset_id
                    LEFT JOIN itam_asset_type AS type ON a.type_id = type.type_id
                    LEFT JOIN itam_asset_category AS cat ON a.category_id = cat.category_id
                    LEFT JOIN itam_asset_sub_category AS s ON a.sub_category_id = s.sub_category_id
                    LEFT JOIN itam_asset_condition AS c ON t.asset_condition_id = c.asset_condition_id
                    ORDER BY t.borrow_transaction_id ASC;
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
    comp.name AS company_name,
    u.company_id,
    u.department_id,
    d.name AS department_name,
    t.asset_id,
    a.category_id,
    a.sub_category_id,
    cat.category_name,
    s.sub_category_name,
    a.asset_name,
    t.date_borrowed,
    t.due_date,
    t.return_date,
    t.duration,
    t.asset_condition_id,
    c.asset_condition_name, 
    t.remarks
FROM itam_asset_transactions AS t
JOIN un_users AS u ON t.user_id = u.user_id
LEFT JOIN un_company_departments AS d ON u.department_id = d.department_id
LEFT JOIN un_companies AS comp ON u.company_id = comp.company_id
JOIN itam_asset AS a ON t.asset_id = a.asset_id
LEFT JOIN itam_asset_category AS cat ON a.category_id = cat.category_id
LEFT JOIN itam_asset_sub_category AS s ON a.sub_category_id = s.sub_category_id
LEFT JOIN itam_asset_condition AS c ON t.asset_condition_id = c.asset_condition_id  
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
            // Insert the borrow transaction
            $sql = "INSERT INTO itam_asset_transactions 
                    (company_id, department_id, unit_id, user_id, asset_id, date_borrowed, due_date, duration, asset_condition_id, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->setStatement($sql);
            $success = $this->statement->execute([
                $company_id, $department_id, $unit_id,
                $userId, $assetId, $dateBorrowed, $dueDate,
                $duration, $assetConditionId, $remarks
            ]);
    
            // If insert succeeded, update asset status
            if ($success) {
                $this->setStatement("UPDATE itam_asset SET status_id = 2 WHERE asset_id = ?");
                $this->statement->execute([$assetId]);
            }
    
            return ["message" => $success ? "Transaction added and asset status updated" : "Insert failed"];
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
    public function returnBorrowedAsset($borrowTransactionId, $returnDate, $assetConditionId)
{
    try {
        // Fetch the date_borrowed for the given transaction
        $this->setStatement("SELECT date_borrowed FROM itam_asset_transactions WHERE borrow_transaction_id = ?");
        $this->statement->execute([$borrowTransactionId]);
        $dateBorrowed = $this->statement->fetchColumn();

        if (!$dateBorrowed) {
            return ["error" => "Borrow transaction not found"];
        }

       
        $duration = (new DateTime($returnDate))->diff(new DateTime($dateBorrowed))->days;

        
        $sql = "UPDATE itam_asset_transactions 
                SET return_date = ?, asset_condition_id = ?, duration = ? 
                WHERE borrow_transaction_id = ?";

        $this->setStatement($sql);
        $success = $this->statement->execute([$returnDate, $assetConditionId, $duration, $borrowTransactionId]);

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
