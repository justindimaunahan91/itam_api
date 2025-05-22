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
                $this->setStatement("SELECT sub_category_id FROM itam_asset_sub_category WHERE category_id = ? AND sub_category_name = ?");
                $this->statement->execute([$category_id, $sub_category_name]);
                $existingSubCategory = $this->statement->fetchColumn();

                if ($existingSubCategory) {
                    return [
                        "message" => "Sub-category already exists",
                        "sub_category_id" => $existingSubCategory
                    ];
                }

                $this->setStatement("INSERT INTO itam_asset_sub_category (category_id, sub_category_name) VALUES (?, ?)");
                $success = $this->statement->execute([$category_id, $sub_category_name]);
            } else {
                $this->setStatement("INSERT INTO itam_asset_sub_category (category_id) VALUES (?)");
                $success = $this->statement->execute([$category_id]);
            }

            return ["message" => $success ? "Sub-category added successfully" : "Failed to add sub-category"];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Update an existing sub-category
    function updateSubCategory($sub_category_id, $category_id, $sub_category_name)
    {
        $this->setStatement("UPDATE itam_asset_sub_category SET category_id = ?, sub_category_name = ? WHERE sub_category_id = ?");
        $success = $this->statement->execute([$category_id, $sub_category_name, $sub_category_id]);
        $this->sendJsonResponse(["message" => $success ? "Sub-category updated successfully" : "Failed to update sub-category"], $success ? 200 : 500);
    }

    // Delete a sub-category
    function deleteSubCategory($sub_category_id)
    {
        $this->setStatement("DELETE FROM itam_asset_sub_category WHERE sub_category_id = ?");
        $success = $this->statement->execute([$sub_category_id]);
        $this->sendJsonResponse(["message" => $success ? "Sub-category deleted successfully" : "Failed to delete sub-category"], $success ? 200 : 500);
    }
}
