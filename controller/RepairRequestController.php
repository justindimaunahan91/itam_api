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
                                    u.company_id,
                                    u.department_id,
                                    c.name AS company_name,
                                    d.name AS department_name,
                                    r.asset_id,
                                    a.asset_name,
                                    a.category_id,
                                    a.sub_category_id,
                                    a.type_id,
                                    ty.type_name,
                                    cat.category_name,
                                    sub.sub_category_name,
                                    r.issue,
                                    r.remarks,
                                    r.date_reported,
                                    r.urgency_id,
                                    urg.urgency_level,
                                    r.repair_start_date,
                                    r.repair_completion_date,
                                    r.status_id, 
                                    s.status_name,  
                                    r.repair_cost
                                FROM itam_asset_repair_request AS r
                                JOIN itam_asset AS a ON r.asset_id = a.asset_id
                                LEFT JOIN itam_asset_category AS cat ON a.category_id = cat.category_id
                                LEFT JOIN itam_asset_sub_category AS sub ON a.sub_category_id = sub.sub_category_id
                                JOIN un_users AS u ON r.user_id = u.user_id
                                LEFT JOIN un_companies AS c ON u.company_id = c.company_id
                                LEFT JOIN un_company_departments AS d ON u.department_id = d.department_id
                                LEFT JOIN itam_asset_type AS ty ON a.type_id = ty.type_id
                                JOIN itam_repair_urgency AS urg ON r.urgency_id = urg.urgency_id  
                                JOIN itam_asset_status AS s ON r.status_id = s.status_id 
                                ORDER BY r.urgency_id ASC;
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
                                    r.urgency_id,
                                    urg.urgency_level,
                                    r.repair_start_date,
                                    r.repair_completion_date,
                                    r.status_id,  
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
    function updateRepairRequest($repair_request_id, $user_id, $remarks, $repair_completion_date, $status_id, $repair_cost) {
        try {
            $sql = "UPDATE itam_asset_repair_request
                    SET user_id = ?, remarks = ?, repair_completion_date = ?, status_id = ?, repair_cost = ?
                    WHERE repair_request_id = ?";
            $this->setStatement($sql);
            $success = $this->statement->execute([$user_id, $remarks, $repair_completion_date, $status_id, $repair_cost, $repair_request_id]);

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
