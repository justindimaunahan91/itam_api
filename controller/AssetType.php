<?php
require_once 'controller.php';

class AssetType extends Controller {
    function retrieveAssetTypes() {
        $this->setStatement("SELECT * FROM itam_asset_type");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll());
    }

    function retrieveOneAssetType($id) {
        $this->setStatement("SELECT * FROM itam_asset_type WHERE type_id = ?");
        $this->statement->execute([$id]);
        $this->sendJsonResponse($this->statement->fetch());
    }

    function insertAssetType($type_name) {
        $this->setStatement("INSERT INTO itam_asset_type (type_name) VALUES (?)");
        $success = $this->statement->execute([$type_name]);
        $this->sendJsonResponse(["message" => $success ? "Asset Type added successfully" : "Failed to add Asset Type"], $success ? 201 : 500);
    }

    function updateAssetType($id, $type_name) {
        $this->setStatement("UPDATE itam_asset_type SET type_name = ? WHERE type_id = ?");
        $success = $this->statement->execute([$type_name, $id]);
        $this->sendJsonResponse(["message" => $success ? "Asset Type updated successfully" : "Failed to update Asset Type"], $success ? 200 : 500);
    }

    function deleteAssetType($id) {
        $this->setStatement("DELETE FROM itam_asset_type WHERE type_id = ?");
        $success = $this->statement->execute([$id]);
        $this->sendJsonResponse(["message" => $success ? "Asset Type deleted successfully" : "Failed to delete Asset Type"], $success ? 200 : 500);
    }
}
?>
