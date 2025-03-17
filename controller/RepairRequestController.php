<?php
require_once __DIR__ . "/controller.php";

class RepairRequestController extends Controller {

    /**
     * Retrieve all repair requests
     */
    function getRepairRequests(): array {
        try {
            $this->setStatement("SELECT 
    r.repair_request_id,
    r.user_id,
    u.employee_id,
    CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
    r.asset_id,
    a.asset_name,
    r.issue,
    r.remarks,
    r.date_reported,
    urg.urgency_level,
    r.repair_start_date,
    r.repair_completion_date,
    s.status_name,  
    r.repair_cost
FROM itam_asset_repair_request AS r
JOIN itam_asset AS a ON r.asset_id = a.asset_id
JOIN un_users AS u ON r.user_id = u.user_id
JOIN itam_repair_urgency AS urg ON r.urgency_id = urg.urgency_id  
JOIN itam_asset_status AS s ON r.status_id = s.status_id 
ORDER BY r.repair_request_id;
");
            $this->statement->execute();
            return $this->statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Retrieve one repair request by ID
     */
    function getRepairRequest($repair_request_id) {
        try {
            $this->setStatement("SELECT 
                        r.repair_request_id,
                        r.user_id,
                        u.employee_id,
                        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
                        r.asset_id,
                        a.asset_name,
                        r.issue,
                        r.remarks,
                        r.date_reported,
                       urg.urgency_level,
                        r.repair_start_date,
                        r.repair_completion_date,
                        s.status_name,  
                        r.repair_cost
                    FROM itam_asset_repair_request AS r
                    JOIN itam_asset AS a ON r.asset_id = a.asset_id
                    JOIN un_users AS u ON r.user_id = u.user_id
                    JOIN itam_repair_urgency AS urg ON r.urgency_id = urg.urgency_id  
                    JOIN itam_asset_status AS s ON r.status_id = s.status_id 
                    WHERE r.repair_request_id = ?");
            $this->statement->execute([$repair_request_id]);
            $result = $this->statement->fetch(PDO::FETCH_ASSOC);

            return $result ?: ["error" => "Repair request not found"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Add a new repair request
     */
    function addRepairRequest($user_id, $asset_id, $issue, $remarks, $date_reported, $urgency_id, $repair_start_date, $repair_completion_date, $status_id, $repair_cost) {
        try {
            $sql = "INSERT INTO itam_asset_repair_request
                    (user_id, asset_id, issue, remarks, date_reported, urgency_id, repair_start_date, repair_completion_date, status_id, repair_cost)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->setStatement($sql);
            $success = $this->statement->execute([$user_id, $asset_id, $issue, $remarks, $date_reported, $urgency_id, $repair_start_date, $repair_completion_date, $status_id, $repair_cost]);

            return ["message" => $success ? "Repair request added successfully" : "Insert failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Update an existing repair request
     */
    function updateRepairRequest($repair_request_id, $user_id, $remarks, $repair_start_date, $repair_completion_date, $status_id, $repair_cost) {
        try {
            $sql = "UPDATE itam_asset_repair_request
                    SET user_id = ?, remarks = ?, repair_start_date = ?, repair_completion_date = ?, status_id = ?, repair_cost = ?
                    WHERE repair_request_id = ?";
            $this->setStatement($sql);
            $success = $this->statement->execute([$user_id, $remarks, $repair_start_date, $repair_completion_date, $status_id, $repair_cost, $repair_request_id]);

            return ["message" => $success ? "Repair request updated successfully" : "Update failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Delete a repair request
     */
    function deleteRepairRequest($repair_request_id) {
        try {
            $this->setStatement("DELETE FROM itam_asset_repair_request WHERE repair_request_id = ?");
            $success = $this->statement->execute([$repair_request_id]);

            return ["message" => $success ? "Repair request deleted successfully" : "Delete failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
