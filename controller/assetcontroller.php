<?php
require_once 'controller.php';
// require_once 'db.php';
class Asset extends Controller
{
    /**
     * Retrieve a setting value from the database or configuration.
     * For demonstration, this uses a simple query from a settings table.
     * Adjust the implementation as needed for your application.
     */ 
    protected function getSetting($key)
    {
        $this->setStatement("SELECT value FROM settings WHERE `key` = ?");
        $this->statement->execute([$key]);
        $value = $this->statement->fetchColumn();
        return $value !== false ? $value : null;
    }

    function retrieveAssets()
    {
        $this->setStatement("SELECT A.*, C.category_name, SC.sub_category_name, A.type_id, A.brand, T.type_name, 
       CO.asset_condition_name, A.status_id, S.status_name,A.file 
		FROM itam_asset A
		LEFT JOIN itam_asset_category C ON A.category_id = C.category_id
		LEFT JOIN itam_asset_sub_category SC ON A.sub_category_id = SC.sub_category_id
		LEFT JOIN itam_asset_type T ON A.type_id = T.type_id
		LEFT JOIN itam_asset_condition CO ON A.asset_condition_id = CO.asset_condition_id
		LEFT JOIN itam_asset_status S ON A.status_id = S.status_id;");

        $this->statement->execute() ;
        $this->sendJsonResponse($this->statement->fetchAll());
    } 

    function retrieveOneAsset($id)
    {
        $this->setStatement("SELECT * FROM itam_asset WHERE asset_id = ?");
        $this->statement->execute([$id]);
        $result = $this->statement->fetch();
        $this->sendJsonResponse($result ?: ["error" => "Asset not found"], $result ? 200 : 404);
    }
    // // Retrieve a single sub-category by ID
    // function retrieveOneSubCategory($id)
    // {
    //     $this->setStatement("SELECT * FROM itam_asset_sub_category WHERE sub_category_id = ?");
    //     $this->statement->execute([$id]);
    //     $result = $this->statement->fetch();
    //     return $result;
    // }


public function insertAsset($data)
{
    extract($data);

    // Validate required field
    if (!isset($category_id)) {
        $this->sendJsonResponse(["error" => "Missing category_id"], 400);
    }

    $sub_category_id = null;

    // If a subcategory name is provided, insert it into the subcategory table
    if (!empty($sub_category_name)) {
    $cleanName = trim($sub_category_name);
    $subCategoryCode = strtoupper(substr($cleanName, 0, 2));

    $this->setStatement("INSERT INTO itam_asset_sub_category (category_id, sub_category_name, code) VALUES (?, ?, ?)");
    $this->statement->execute([
        $category_id,
        $cleanName,
        $subCategoryCode
    ]);

    $sub_category_id = $this->connection->lastInsertId();
}


    // Count how many assets already exist with the same category and subcategory, and no type
    $this->setStatement("SELECT COUNT(*) as count FROM itam_asset WHERE sub_category_id = ? AND category_id = ? AND type_id IS NULL");
    $this->statement->execute([$sub_category_id, $category_id]);
    $count = $this->statement->fetchColumn(0);
    $count += 1; // Increment for the new asset

    // Generate asset name based on category
    // Category 1 = External, Category 2 = Internal
    if (in_array($category_id, [1, 2])) {
        // Get subcategory code if subcategory is selected
        if (!empty($sub_category_id)) {
            $this->setStatement("SELECT code FROM itam_asset_sub_category WHERE sub_category_id = ?");
            $this->statement->execute([$sub_category_id]);
            $subcategory_code = $this->statement->fetchColumn() ?: strtoupper(substr($asset_name, 0, 2)); // fallback to asset_name prefix
        } else {
            $subcategory_code = strtoupper(substr($asset_name, 0, 2)); // fallback if no subcategory
        }

        // Format: CODE-CATEGORYID000X
        // Example: DE-20012 means DE subcategory, category 2 (Internal), 12th asset
        $asset_name = $subcategory_code . "-" . $category_id . str_pad($count, 4, "0", STR_PAD_LEFT);

    } else {
        // If the category is neither External nor Internal
        if (!empty($sub_category_id)) {
            $this->setStatement("SELECT code FROM itam_asset_sub_category WHERE sub_category_id = ?");
            $this->statement->execute([$sub_category_id]);
            $subcategory_code = $this->statement->fetchColumn() ?: "SC"; // Default fallback code
            $asset_name = $subcategory_code . "-" . $category_id;
        } else {
            $asset_name = "ASSET-" . $category_id; // Fallback format if no subcategory
        }

        // Add count or type ID to the name
        if (empty($type_id)) {
            $asset_name .= str_pad($count, 4, "0", STR_PAD_LEFT);
        } else {
            $asset_name .= $type_id . str_pad($count, 3, "0", STR_PAD_LEFT);
        }
    }

    // Optional: Insert insurance record
    $insurance_id = null;
    if (!empty($insurance_coverage) && !empty($insurance_date_from) && !empty($insurance_date_to)) {
        $this->setStatement("INSERT INTO itam_asset_insurance (insurance_coverage, insurance_date_from, insurance_date_to) VALUES (?, ?, ?)");
        $insuranceSuccess = $this->statement->execute([
            $insurance_coverage,
            $insurance_date_from,
            $insurance_date_to
        ]);

        if ($insuranceSuccess) {
            $insurance_id = $this->connection->lastInsertId();
        }
    }

    // Load image settings
    $maxImages = (int) $this->getSetting('max_images_per_item');
    $allowedTypes = explode(',', $this->getSetting('allowed_file_types')); // e.g. 'jpg,jpeg,png,webp'

    // Validate uploaded images
    if (!isset($_FILES['images'])) {
        $this->sendJsonResponse(["error" => "No image files uploaded."], 400);
    }

    $uploadedImages = $_FILES['images'];
    $filenames = [];

    // check image count
    $imageCount = count(array_filter($uploadedImages['name']));
    if ($imageCount > $maxImages) {
        $this->sendJsonResponse(["error" => "Maximum of $maxImages images allowed."], 400);
    }

    // Validate and save each image
    for ($i = 0; $i < $imageCount; $i++) {     
        $tmpName = $uploadedImages['tmp_name'][$i];
        $originalName = $uploadedImages['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedTypes)) {
            $this->sendJsonResponse(["error" => "File type .$ext is not allowed."], 400);
        }

        $newName = uniqid() . '.' . $ext;
        $destination = "uploads/" . $newName;

        if (move_uploaded_file($tmpName, $destination)) {
            $filenames[] = $destination;
        } else {
            $this->sendJsonResponse(["error" => "Failed to upload image: $originalName"], 500);
        }
    }

    // You can now insert asset record here using $asset_name, $category_id, $sub_category_id, $type_id, $insurance_id, $filenames, etc.



        // Insert asset with file path
        $this->setStatement("INSERT INTO itam_asset (asset_name, serial_number, brand, category_id, sub_category_id, asset_condition_id, type_id, status_id, location, specifications, asset_amount, warranty_duration, warranty_due_date, purchase_date, notes, insurance_id, file) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $success = $this->statement->execute([
            $asset_name,
            $serial_number,
            $brand,
            $category_id,
            empty($sub_category_id) ? NULL : $sub_category_id,
            4,
            $type_id === "" ? null : $type_id,
            1,
            $location,
            $specifications,
            $asset_amount,
            $warranty_duration,
            $warranty_due_date,
            $purchase_date,
            $notes,
            $insurance_id === null ? null : $insurance_id, // Use the insurance_id if insurance exists
            implode(', ', $filenames)
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
