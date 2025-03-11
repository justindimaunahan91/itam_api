<?php
require __DIR__ . "/controller.php";

class UserTransactionsController extends Controller {

    // Retrieve all user transactions with user and asset details
    function getUserTransactions() {
        $this->setStatement("SELECT u.user_id, u.employee_id, CONCAT(u.first_name, ' ', u.last_name) AS employee_name, 
                                     t.borrow_transaction_id, t.asset_id, a.asset_name, t.date_borrowed, 
                                     t.due_date, t.return_date, t.duration, t.asset_condition_id, t.remarks 
                              FROM un_users AS u 
                              JOIN itam_asset_transactions AS t ON u.user_id = t.user_id
                              JOIN itam_asset AS a ON t.asset_id = a.asset_id
                              ORDER BY u.user_id, t.borrow_transaction_id"); 
        $this->statement->execute();
        return $this->statement->fetchAll();
    }

    // Retrieve transactions for a specific user
    function getTransactionsByUser($user_id) {
        $this->setStatement("SELECT t.borrow_transaction_id, t.asset_id, a.asset_name, 
                                     t.date_borrowed, t.due_date, t.return_date, t.duration, 
                                     t.asset_condition_id, t.remarks 
                              FROM itam_asset_transactions AS t
                              JOIN itam_asset AS a ON t.asset_id = a.asset_id
                              WHERE t.user_id = ?
                              ORDER BY t.date_borrowed DESC");
        $this->statement->execute([$user_id]);
        return $this->statement->fetchAll();
    }

    // Add a new user transaction record
    function addUserTransaction($user_id, $asset_id, $date_borrowed, $due_date, $return_date = null, $duration = null, $asset_condition_id, $remarks = null) {
        $this->setStatement("INSERT INTO itam_asset_transactions (user_id, asset_id, date_borrowed, due_date, return_date, duration, asset_condition_id, remarks)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $this->statement->execute([$user_id, $asset_id, $date_borrowed, $due_date, $return_date, $duration, $asset_condition_id, $remarks]);
    }

    // Update an existing user transaction record
    function updateUserTransaction($borrow_transaction_id, $user_id, $asset_id, $date_borrowed, $due_date, $return_date, $duration, $asset_condition_id, $remarks) {
        $this->setStatement("UPDATE itam_asset_transactions 
                             SET user_id = ?, asset_id = ?, date_borrowed = ?, due_date = ?, return_date = ?, duration = ?, asset_condition_id = ?, remarks = ?
                             WHERE borrow_transaction_id = ?");
        $this->statement->execute([$user_id, $asset_id, $date_borrowed, $due_date, $return_date, $duration, $asset_condition_id, $remarks, $borrow_transaction_id]);
    }

    // Optionally, delete a user transaction record if allowed by your business logic
    function deleteUserTransaction($borrow_transaction_id) {
        $this->setStatement("DELETE FROM itam_asset_transactions WHERE borrow_transaction_id = ?");
        $this->statement->execute([$borrow_transaction_id]);
    }
}
?>
 