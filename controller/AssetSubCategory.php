<?php
require_once 'controller.php';

class AssetSubCategory extends Controller
{

    // Retrieve all sub-categories
    function retrieveSubCategories()
    {
        $this->setStatement("
  SELECT 
    itam_asset_sub_category.*, 
    itam_asset_category.category_name 
  FROM 
    itam_asset_sub_category
  JOIN 
    itam_asset_category 
  ON 
    itam_asset_sub_category.category_id = itam_asset_category.category_id
");

        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll());
    }

    // Retrieve a single sub-category by ID
    function retrieveOneSubCategory($id)
    {
        $this->setStatement("SELECT * FROM itam_asset_sub_category WHERE sub_category_id = ?");
        $this->statement->execute([$id]);
        $result = $this->statement->fetch();
        return $result;
    }
    function insertSubCategory($category_id, $sub_category_name = null)
{
    try {
        if ($sub_category_name) {
            // Check if subcategory already exists
            $this->setStatement("SELECT sub_category_id FROM itam_asset_sub_category WHERE category_id = ? AND sub_category_name = ?");
            $this->statement->execute([$category_id, $sub_category_name]);
            $existingSubCategory = $this->statement->fetchColumn();

            if ($existingSubCategory) {
                return [
                    "message" => "Sub-category already exists",
                    "sub_category_id" => $existingSubCategory
                ];
            }

            // ✅ Generate code from initials (e.g., "System Unit" → "SU")
            $words = preg_split('/\s+/', trim($sub_category_name));
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper($word[0]);
                }
            }
            $code = substr($initials, 0, 2); // Limit to 2 letters

            // ✅ Insert with code
            $this->setStatement("INSERT INTO itam_asset_sub_category (category_id, sub_category_name, code) VALUES (?, ?, ?)");
            $success = $this->statement->execute([$category_id, $sub_category_name, $code]);

        } else {
            // No subcategory name provided
            $this->setStatement("INSERT INTO itam_asset_sub_category (category_id) VALUES (?)");
            $success = $this->statement->execute([$category_id]);
        }

        return ["message" => $success ? "Sub-category added successfully" : "Failed to add sub-category"];
    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}}
