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
                a.sub_category_id,
                sub.sub_category_name,
                i.user_id,
                u.company_id,
                comp.company_id, 
                u.department_id,
                d.name,
                CONCAT(u.first_name, ' ', u.last_name) AS employee_name, 
                i.issuance_date,
                s.status_id,
                s.status_name  
            FROM itam_asset_issuance AS i
            JOIN itam_asset AS a ON i.asset_id = a.asset_id
            JOIN itam_asset_sub_category AS sub ON a.sub_category_id = sub.sub_category_id
            JOIN un_users AS u ON i.user_id = u.user_id
            JOIN un_company_departments AS d ON u.department_id = d.department_id
            JOIN un_company AS comp ON u.company_id = comp.company_id
            JOIN itam_asset_status AS s ON i.status_id = s.status_id  
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
                                         i.issuance_date, s.status_name 
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
    function addAssetIssuance($asset_id, $user_id, $issuance_date, $status_id)
    {
        try {
            $this->setStatement("INSERT INTO itam_asset_issuance (asset_id, user_id, issuance_date, status_id)
                                 VALUES (?, ?, ?, ?)");
            $success = $this->statement->execute([$asset_id, $user_id, $issuance_date, $status_id]);

            return ["message" => $success ? "Issuance record added successfully" : "Failed to add record"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Update an existing asset issuance record
     */
    function updateAssetIssuance($issuance_id, $asset_id, $user_id, $issuance_date, $status_id)
    {
        try {
            $this->setStatement("UPDATE itam_asset_issuance 
                                 SET asset_id = ?, user_id = ?, issuance_date = ?, status_id = ?
                                 WHERE issuance_id = ?");
            $success = $this->statement->execute([$asset_id, $user_id, $issuance_date, $status_id, $issuance_id]);

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
