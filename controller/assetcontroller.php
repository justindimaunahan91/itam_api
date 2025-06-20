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
    // // Retrieve a single sub-category by ID
    // function retrieveOneSubCategory($id)
    // {
    //     $this->setStatement("SELECT * FROM itam_asset_sub_category WHERE sub_category_id = ?");
    //     $this->statement->execute([$id]);
    //     $result = $this->statement->fetch();
    //     return $result;
    // }


    public function insertAsset()
    {
        $data = $_POST;

        if (empty($data['serial_number'])) {
            $this->sendJsonResponse(["error" => "Serial number is required."], 400);
        }
        $insurance_id = $data['insurance_id'] ?? null;
        $serial_number = trim($data['serial_number'] ?? '');
        $brand = trim($data['brand'] ?? '');
        $category_id = $data['category_id'] ?? null;
        $sub_category_id = $data['sub_category_id'] ?? null;
        $type_id = $data['type_id'] ?? null;
        $location = trim($data['location'] ?? '');
        $specifications = trim($data['specifications'] ?? '');
        $asset_amount = $data['asset_amount'] ?? 0;
        $asset_name = $data['asset_name'] ?? '';
        $warranty_duration = $data['warranty_duration'] ?? null;
        $warranty_due_date = $data['warranty_due_date'] ?? null;
        $purchase_date = $data['purchase_date'] ?? null;
        $notes = trim($data['notes'] ?? '');
        $insurance_name = trim($data['insurance_name'] ?? '');
        $insurance_coverage = trim($data['insurance_coverage'] ?? '');
        $insurance_date_from = $data['insurance_date_from'] ?? null;
        $insurance_date_to = $data['insurance_date_to'] ?? null;


        // Validate required field
        if (!isset($category_id)) {
            $this->sendJsonResponse(["error" => "Missing category_id"], 400);
        }

        $sub_category_name = trim($data['sub_category_name'] ?? '');

        if (!empty($sub_category_name)) {
            // Insert new subcategory and get ID
            $this->setStatement("INSERT INTO itam_asset_sub_category (category_id, sub_category_name, code) VALUES (?, ?, ?)");
            $this->statement->execute([
                $category_id,
                $sub_category_name,
                strtoupper(substr($sub_category_name, 0, 2))
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


        if (
            empty($insurance_id) &&
            !empty($insurance_name) &&
            !empty($insurance_coverage) &&
            !empty($insurance_date_from) &&
            !empty($insurance_date_to)
        ) {
            $this->setStatement("INSERT INTO itam_asset_insurance (insurance_name, insurance_coverage, insurance_date_from, insurance_date_to) VALUES (?, ?, ?, ?)");
            $insuranceSuccess = $this->statement->execute([
                $insurance_name,
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
        if (!isset($_FILES['file'])) {
            $this->sendJsonResponse(["error" => "No image files uploaded."], 400);
        }
        $uploadedImages = $_FILES['file'];
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
                $this->sendJsonResponse([
                    "error" => "❌ Failed to move file: $originalName",
                    "debug" => [
                        "tmp_name" => $tmpName,
                        "destination" => $destination,
                        "is_uploaded_file" => is_uploaded_file($tmpName),
                        "file_exists_tmp" => file_exists($tmpName)
                    ]
                ], 500);
            }
        }

        // You can now insert asset record here using $asset_name, $category_id, $sub_category_id, $type_id, $insurance_id, $filenames, etc.


        if (empty($serial_number)) {
            $this->sendJsonResponse(["error" => "Missing serial number."], 400);
        }

        if (empty($asset_name)) {
            $this->sendJsonResponse(["error" => "Generated asset name is empty."], 400);
        }

        // Collect missing required fields
        $requiredFields = [
            'serial_number',
            'asset_amount',
            'warranty_duration',
            'brand',
            'category_id',
            'sub_category_id',
            'type_id',
            'location',
            'specifications',
            'asset_amount',
            'warranty_due_date',
            'purchase_date',
            'notes',
            'file',
            'insurance_id'
        ];

        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $this->sendJsonResponse([
                "error" => "Missing required fields",
                "missing" => $missingFields
            ], 400);
        }

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

        $this->sendJsonResponse(
            ["message" => $success ? "Asset added successfully" : "Failed to add asset"],
            $success ? 201 : 500
        );
    }



    public function batchInsertAssets($assets)
    {
        try {
            $this->connection->beginTransaction();

            // Prepare asset insert once
            $this->setStatement("INSERT INTO itam_asset (
            serial_number,
            category_id,
            sub_category_id,
            type_id,
            specifications,
            asset_amount,
            purchase_date,
            warranty_due_date,
            notes,
            insurance_id,
            status_id,
            asset_condition_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 4)");

            $assetStmt = $this->statement;

            foreach ($assets as $asset) {
                // Use insurance_id from payload if exists
                $insurance_id = $asset['insurance_id'] ?? null;

                if (!empty($asset['insurance_coverage']) && !empty($asset['insurance_date_from']) && !empty($asset['insurance_date_to'])) {
                    // Insert new insurance record
                    $this->setStatement("INSERT INTO itam_asset_insurance (
                    insurance_name,
                    insurance_coverage,
                    insurance_date_from,
                    insurance_date_to
                ) VALUES (?, ?, ?, ?)");
                    $insuranceStmt = $this->statement;

                    $insuranceStmt->execute([
                        $asset['insurance_name'],
                        $asset['insurance_coverage'],
                        $asset['insurance_date_from'],
                        $asset['insurance_date_to']
                    ]);

                    // Override with newly inserted insurance_id
                    $insurance_id = $this->connection->lastInsertId();
                } elseif (!empty($asset['insurance_name']) && empty($insurance_id)) {
                    // Fetch existing insurance ID by name only if insurance_id is not set
                    $this->setStatement("SELECT insurance_id FROM itam_asset_insurance WHERE insurance_name = ?");
                    $insuranceStmt = $this->statement;

                    $insuranceStmt->execute([$asset['insurance_name']]);
                    $existingInsurance = $insuranceStmt->fetch(PDO::FETCH_ASSOC);

                    $insurance_id = $existingInsurance['insurance_id'] ?? null;
                }

                // Insert asset with insurance_id (either from payload, newly inserted, or fetched)
                $assetStmt->execute([
                    $asset['serial_number'] ?? null,
                    $asset['category_id'] ?? null,
                    $asset['sub_category_id'] ?? null,
                    $asset['type_id'] ?? null,
                    $asset['specifications'] ?? null,
                    $asset['asset_amount'] ?? null,
                    $asset['purchase_date'] ?? null,
                    $asset['warranty_due_date'] ?? null,
                    $asset['notes'] ?? null,
                    $insurance_id
                ]);
            }

            $this->connection->commit();
            $this->sendJsonResponse(["message" => "Batch insert successful."], 201);
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }

    function updateAsset($id, $data)
    {
        extract($data);

        // Optional: handle filenames (if file upload included in update)
        $fileColumnValue = null;
        if (isset($data['filenames']) && is_array($data['filenames']) && count($data['filenames']) > 0) {
            $fileColumnValue = implode(', ', $data['filenames']);
        }

        $this->setStatement("UPDATE itam_asset 
        SET asset_name = ?, 
            serial_number = ?, 
            brand = ?, 
            category_id = ?, 
            sub_category_id = ?, 
            type_id = ?, 
            asset_condition_id = ?, 
            status_id = ?, 
            location = ?, 
            specifications = ?, 
            asset_amount = ?, 
            warranty_duration = ?, 
            warranty_due_date = ?, 
            purchase_date = ?, 
            notes = ?, 
            file = ?  -- ← this line added
        WHERE asset_id = ?");

        $success = $this->statement->execute([
            $asset_name,
            $serial_number,
            $brand,
            $category_id,
            $sub_category_id,
            $type_id,
            $asset_condition_id,
            $status_id,
            $location,
            $specifications,
            $asset_amount,
            $warranty_duration,
            $warranty_due_date,
            $purchase_date,
            $notes,
            $fileColumnValue, // ← this value
            $id
        ]);

        return $success;
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
