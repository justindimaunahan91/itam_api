<?php
require_once __DIR__ . "/controller.php";

class AssetIssuanceController extends Controller
{
    /**
     * Retrieve all asset issuance records
     */
    function getAssetIssuances()
    {
        try {
            $this->setStatement("SELECT 
                i.issuance_id, 
                i.asset_id, 
                a.asset_name,
                a.category_id,
                a.sub_category_id,
                cat.category_name,
                sub.sub_category_name,
                ty.type_name,
                a.type_id,
                i.user_id,
                u.company_id,
                u.department_id,
                d.name AS department_name,
                comp.name AS company_name,
                CONCAT(u.first_name, ' ', u.last_name) AS employee_name, 
                i.issuance_date,
                i.pullout_date,
                s.status_id,
                s.status_name,
                i.remarks
            FROM itam_asset_issuance AS i
            JOIN itam_asset AS a ON i.asset_id = a.asset_id
            LEFT JOIN itam_asset_sub_category AS sub ON a.sub_category_id = sub.sub_category_id
            JOIN un_users AS u ON i.user_id = u.user_id
            LEFT JOIN un_companies AS comp ON u.company_id = comp.company_id
            LEFT JOIN un_company_departments AS d ON u.department_id = d.department_id
            LEFT JOIN itam_asset_category AS cat ON a.category_id = cat.category_id
            LEFT JOIN itam_asset_type AS ty ON a.type_id = ty.type_id
            LEFT JOIN itam_asset_status AS s ON i.status_id = s.status_id  
            ORDER BY i.issuance_id;
            ");
            $this->statement->execute();
            return $this->statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Retrieve one asset issuance record by ID
     */
    function getOneAssetIssuance($issuance_id)
    {
        try {
            $this->setStatement("SELECT i.issuance_id, i.asset_id, a.asset_name, i.user_id, 
                                         CONCAT(u.first_name, ' ', u.last_name) AS employee_name, 
                                         i.issuance_date, i.pullout_date, s.status_name 
                                  FROM itam_asset_issuance AS i
                                  JOIN itam_asset AS a ON i.asset_id = a.asset_id
                                  JOIN un_users AS u ON i.user_id = u.user_id
                                  JOIN itam_asset_status AS s ON i.status_id = s.status_id  
                                  WHERE i.issuance_id = ?");
            $this->statement->execute([$issuance_id]);
            $result = $this->statement->fetch(PDO::FETCH_ASSOC);

            return $result ?: ["error" => "Issuance record not found"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Add a new asset issuance record
     */
    function addAssetIssuance($asset_id, $user_id, $issuance_date)
    {
        try {
            // Insert issuance record
            $this->setStatement("INSERT INTO itam_asset_issuance (asset_id, user_id, issuance_date, status_id)
                                 VALUES (?, ?, ?, 3)");
            $success = $this->statement->execute([$asset_id, $user_id, $issuance_date]);
    
            if ($success) {
                // Update asset status to 4
                $this->setStatement("UPDATE itam_asset SET status_id = 3 WHERE asset_id = ?");
                $this->statement->execute([$asset_id]);
            }
    
            return ["message" => $success ? "Issuance added and asset status updated" : "Failed to add record"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
    

    /**
     * Update an existing asset issuance record
     */
    function updateAssetIssuance($issuance_id, $asset_id, $user_id, $pullout_date, $status_id)
    {
        try {
            $this->setStatement("UPDATE itam_asset_issuance 
                                 SET asset_id = ?, user_id = ?, pullout_date = ?, status_id = ?
                                 WHERE issuance_id = ?");
            $success = $this->statement->execute([$asset_id, $user_id, $pullout_date, $status_id, $issuance_id]);

            return ["message" => $success ? "Updated successfully" : "Update failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Delete an asset issuance record
     */
    function deleteAssetIssuance($issuance_id)
    {
        try {
            $this->setStatement("DELETE FROM itam_asset_issuance WHERE issuance_id = ?");
            $success = $this->statement->execute([$issuance_id]);

            return ["message" => $success ? "Deleted successfully" : "Delete failed"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
