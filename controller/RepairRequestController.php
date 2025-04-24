<?php
require_once __DIR__ . "/controller.php";

class RepairRequestController extends Controller
{

    /**
     * Retrieve all repair requests (with optional status filter for tabs)
     */


    function getRepairRequestById($id): array
    {
        try {
            $sql = "SELECT 
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
                    WHERE r.repair_request_id = ?";

            $this->setStatement($sql);
            $this->statement->execute([$id]);

            $result = $this->statement->fetch(PDO::FETCH_ASSOC);

            return $result ?: ["error" => "Repair request not found"];
        } catch (Exception $e) {
            return ["error" => "Error fetching repair request: " . $e->getMessage()];
        }
    }



    function getRepairRequests(): array
    {
        try {
            $statusFilter = $_GET['status_id'] ?? null;
            $sql = "SELECT 
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
                    JOIN itam_asset_status AS s ON r.status_id = s.status_id ";

            if ($statusFilter !== null) {
                $sql .= "WHERE r.status_id = ? ";
            }

            $sql .= "ORDER BY r.urgency_id ASC";
            $this->setStatement($sql);

            if ($statusFilter !== null) {
                $this->statement->execute([$statusFilter]);
            } else {
                $this->statement->execute();
            }

            return $this->statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ["error" => "Error fetching repair requests: " . $e->getMessage()];
        }
    }

    /**
     * Add a new repair request with validation
     */
    function addRepairRequest($user_id, $asset_id, $issue, $remarks, $date_reported, $urgency_id, $repair_start_date)
    {
        try {
            // Default status to "Under Repair"
            $status_id = 4;

            // Check if user is assigned to the asset
            $this->setStatement("SELECT * FROM itam_asset_issuance WHERE asset_id = ? AND user_id = ?");
            $this->statement->execute([$asset_id, $user_id]);
            if (!$this->statement->fetch(PDO::FETCH_ASSOC)) {
                return ["error" => "You can only request repair for assets issued to you."];
            }

            // Check if asset is already under repair, on hold, or rejected
            $this->setStatement("SELECT status_id FROM itam_asset_repair_request WHERE asset_id = ? ORDER BY repair_request_id DESC LIMIT 1");
            $this->statement->execute([$asset_id]);
            $latestStatus = $this->statement->fetch(PDO::FETCH_ASSOC);

            if ($latestStatus && in_array($latestStatus['status_id'], [4, 7, 9])) {
                return ["error" => "Cannot create repair request: asset is currently under repair, on hold, or was rejected."];
            }

            $sql = "INSERT INTO itam_asset_repair_request
                    (user_id, asset_id, issue, remarks, date_reported, urgency_id, repair_start_date, status_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->setStatement($sql);
            $success = $this->statement->execute([$user_id, $asset_id, $issue, $remarks, $date_reported, $urgency_id, $repair_start_date, $status_id]);
            if ($success) {
                // 🔧 Update asset status to 'Under Repair'
                $this->setStatement("UPDATE itam_asset SET status_id = ? WHERE asset_id = ?");
                $this->statement->execute([$status_id, $asset_id]);

                return ["message" => "Repair request added and asset status updated to Under Repair"];
            } else {
                return ["error" => "Insert failed"];
            }
        } catch (Exception $e) {
            return ["error" => "Error adding repair request: " . $e->getMessage()];
        }
    }

    /**
     * Update an existing repair request with status restriction
     */
    function updateRepairRequest($repair_request_id, $user_id, $repair_completion_date, $status_id, $repair_cost, $remarks)
    {
        try {
            // Only allow status update to Completed, Rejected, or On Hold
            $allowedStatuses = [5, 9, 7];
            if (!in_array($status_id, $allowedStatuses)) {
                return ["error" => "Invalid status update. Status must be completed, rejected, or on hold."];
            }

            // // Ensure completion date is not in the future
            if (!empty($repair_completion_date) && strtotime($repair_completion_date) > strtotime(date('Y-m-d'))) {
                return ["error" => "Repair completion date cannot be in the future."];
            }

            $sql = "UPDATE itam_asset_repair_request
                    SET remarks = ?, repair_completion_date = ?, status_id = ?, repair_cost = ?
                    WHERE repair_request_id = ?";
            $this->setStatement($sql);
            $success = $this->statement->execute([
                $remarks,                  // 1st placeholder
                $repair_completion_date,   // 2nd
                $status_id,                // 3rd
                $repair_cost,              // 4th
                $repair_request_id         // 5th
            ]);


            return ["message" => $success ? "Repair request updated successfully" : "Update failed"];
        } catch (Exception $e) {
            return ["error" => "Error updating repair request: " . $e->getMessage()];
        }
    }

    /**
     * Delete a repair request
     */
    function deleteRepairRequest($repair_request_id)
    {
        try {
            $this->setStatement("DELETE FROM itam_asset_repair_request WHERE repair_request_id = ?");
            $success = $this->statement->execute([$repair_request_id]);

            return ["message" => $success ? "Repair request deleted successfully" : "Delete failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
