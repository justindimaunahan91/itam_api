<?php
require_once 'controller.php';

class AssetCategory extends Controller {
    function retrieveCategories() {
        $this->setStatement("SELECT * FROM itam_asset_category");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll());
    }

    function retrieveOneCategory($id) {
        $this->setStatement("SELECT * FROM itam_asset_category WHERE category_id = ?");
        $this->statement->execute([$id]);
        $this->sendJsonResponse($this->statement->fetch());
    }

    function insertCategory($category_name, $status) {
        $this->setStatement("INSERT INTO itam_asset_category (category_name, status) VALUES (?, ?)");
        $success = $this->statement->execute([$category_name, $status]);
        $this->sendJsonResponse(["message" => $success ? "Category added successfully" : "Failed to add category"], $success ? 201 : 500);
    }

    function updateCategory($id, $category_name, $status) {
        $this->setStatement("UPDATE itam_asset_category SET category_name = ?, status = ? WHERE category_id = ?");
        $success = $this->statement->execute([$category_name, $status, $id]);
        $this->sendJsonResponse(["message" => $success ? "Category updated successfully" : "Failed to update category"], $success ? 200 : 500);
    }

    function deleteCategory($id) {
        $this->setStatement("DELETE FROM itam_asset_category WHERE category_id = ?");
        $success = $this->statement->execute([$id]);
        $this->sendJsonResponse(["message" => $success ? "Category deleted successfully" : "Failed to delete category"], $success ? 200 : 500);
    }
}
?>
