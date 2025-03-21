<?php
require_once 'controller.php';
class Asset extends Controller
{
    function retrieveAssets()
    {
        $this->setStatement("SELECT A.*, C.category_name, SC.sub_category_name, A.type_id, A.brand, T.type_name, 
       CO.asset_condition_name, A.status_id, S.status_name 
		FROM itam_asset A
		LEFT JOIN itam_asset_category C ON A.category_id = C.category_id
		LEFT JOIN itam_asset_sub_category SC ON A.sub_category_id = SC.sub_category_id
		LEFT JOIN itam_asset_type T ON A.type_id = T.type_id
		LEFT JOIN itam_asset_condition CO ON A.asset_condition_id = CO.asset_condition_id
		LEFT JOIN itam_asset_status S ON A.status_id = S.status_id;");

        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll());
    }

    function retrieveOneAsset($id)
    {
        $this->setStatement("SELECT * FROM itam_asset WHERE asset_id = ?");
        $this->statement->execute([$id]);
        $result = $this->statement->fetch();
        $this->sendJsonResponse($result ?: ["error" => "Asset not found"], $result ? 200 : 404);
    }
        // Retrieve a single sub-category by ID
        function retrieveOneSubCategory($id) {
            $this->setStatement("SELECT * FROM itam_asset_sub_category WHERE sub_category_id = ?");
            $this->statement->execute([$id]);
            $result = $this->statement->fetch();
            return $result;
        }
    

        function insertAsset($data)
        {
            extract($data);
        
            // Handle file upload
            // $filePath = null;
            // if ($file && $file['error'] === UPLOAD_ERR_OK) {
            //     $uploadDir = __DIR__ . "/uploads/";
            //     if (!is_dir($uploadDir)) {
            //         mkdir($uploadDir, 0777, true);
            //     }
        
            //     $fileName = time() . "_" . basename($file['name']);
            //     $filePath = "uploads/" . $fileName;
            //     move_uploaded_file($file['tmp_name'], $uploadDir . $fileName);
            // }
        
            // Generate asset name
            $this->setStatement("SELECT COUNT(*) as count FROM itam_asset WHERE sub_category_id = ? and category_id = ? and type_id = ?");
            $this->statement->execute([$sub_category_id, $category_id, $type_id]);
            $count = $this->statement->fetchColumn(0);
            $count += 1;
            $subcategory_item = $this->retrieveOneSubCategory($sub_category_id);
            $subcategory_code = $subcategory_item->code;
            $asset_name = $subcategory_code . "-" . $category_id;
            if ($type_id === "") {
                $asset_name .= str_pad($count, 4, "0", STR_PAD_LEFT);
            } else {
                $asset_name .= $type_id . str_pad($count, 3, "0", STR_PAD_LEFT);
            }
        
            // Insert asset with file path
            $this->setStatement("INSERT INTO itam_asset (asset_name, serial_number, brand, category_id, sub_category_id, asset_condition_id, type_id, status_id, location, specifications, asset_amount, warranty_duration, aging, warranty_due_date, purchase_date, notes, insurance) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
            $success = $this->statement->execute([
                $asset_name, $serial_number, $brand, $category_id, 
                $sub_category_id === "" ? null : $sub_category_id, 4, 
                $type_id === "" ? null : $type_id, $availability_status_id, 
                $location, $specifications, $asset_amount, 
                $warranty_duration, 0, $warranty_due_date, 
                $purchase_date, $notes, $insurance === "" ? null : $insurance
            ]);
        
            $this->sendJsonResponse(["message" => $success ? "Asset added successfully" : "Failed to add asset"], $success ? 201 : 500);
        }
        
  
    function updateAsset($id, $data)
    {
        extract($data);
        $this->setStatement("UPDATE itam_asset 
            SET asset_name = ?, serial_number = ?, category_id = ?, sub_category_id = ?, type_id = ?, asset_condition_id = ?, status_id = ?, location = ?, specifications = ?, warranty_duration = ?, aging = ?, warranty_due_date = ?, purchase_date = ?, notes = ? 
            WHERE asset_id = ?");

        $success = $this->statement->execute([$asset_name, $serial_number, $category_id, $sub_category_id, $type_id, $asset_condition_id, $status_id, $location, $specifications, $warranty_duration, $aging, $warranty_due_date, $purchase_date, $notes, $id]);

        $this->sendJsonResponse(["message" => $success ? "Asset updated successfully" : "Failed to update asset"], $success ? 200 : 500);
    }

    function deleteAsset($id)
    {
        $this->setStatement("DELETE FROM itam_asset WHERE asset_id = ?");
        $success = $this->statement->execute([$id]);
        $this->sendJsonResponse(["message" => $success ? "Asset deleted successfully" : "Failed to delete asset"], $success ? 200 : 500);
    }

    /**
     * Get predefined repair urgency levels
     */
    function getRepairUrgencyLevels()
    {
        $this->setStatement("SELECT * FROM `itam_repair_urgency");
        $this->statement->execute([]);
        $result = $this->statement->fetchAll();
        $this->sendJsonResponse($result ?: ["error" => "Repair Urgency not found"], $result ? 200 : 404);
    }

    /**
     * Get assets with any repair urgency level (Critical, High, Medium, Low)
     */
    function getRepairUrgencyAssets()
    {
        $this->setStatement("SELECT A.asset_id, A.asset_name, C.category_name, SC.sub_category_name, 
                                    R.issue, R.remarks, R.urgency_id, U.urgency_level 
                             FROM itam_asset A
                             JOIN itam_asset_category C ON A.category_id = C.category_id
                             JOIN itam_asset_sub_category SC ON A.sub_category_id = SC.sub_category_id
                             JOIN itam_asset_repair_request R ON A.asset_id = R.asset_id
                             JOIN itam_repair_urgency U ON R.urgency_id = U.urgency_id
                             WHERE R.urgency_id IN (1, 2, 3, 4)  -- 1 = Critical, 2 = High, 3 = Medium, 4 = Low
                             ORDER BY R.urgency_id ASC");

        $this->statement->execute();
        $result = $this->statement->fetchAll();

        $this->sendJsonResponse($result ?: ["error" => "No assets with repair urgency found"], $result ? 200 : 404);
    }
    /**
     * Retrieve all asset conditions.
     */
    function getAssetCondition()
    {
        $this->setStatement("SELECT * FROM itam_asset_condition ORDER BY asset_condition_id ASC");
        $this->statement->execute();
        $result = $this->statement->fetchAll();
        $this->sendJsonResponse($result ?: ["error" => "No asset conditions found"], $result ? 200 : 404);
    }

    /**
     * Retrieve all asset statuses.
     */
    function getAssetStatus()
    {
        $this->setStatement("SELECT * FROM itam_asset_status ORDER BY status_id ASC");
        $this->statement->execute();
        $result = $this->statement->fetchAll();
        $this->sendJsonResponse($result ?: ["error" => "No asset statuses found"], $result ? 200 : 404);
    }
}
