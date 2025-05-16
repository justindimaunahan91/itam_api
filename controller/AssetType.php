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
    // 🔁 NEW: Insert and map to subcategory
    function insertMappedAssetType($sub_category_id, $type_name) {
        try {
            $this->connection->beginTransaction();

            $this->setStatement("INSERT INTO itam_asset_type (type_name) VALUES (?)");
            $this->statement->execute([$type_name]);
            $type_id = $this->connection->lastInsertId();

            $this->setStatement("INSERT INTO itam_subtype_map (subcategory_id, type_id) VALUES (?, ?)");
            $this->statement->execute([$sub_category_id, $type_id]);

            $this->connection->commit();
            $this->sendJsonResponse([
                "message" => "Mapped Asset Type inserted successfully",
                "type_id" => $type_id
            ], 201);
        } catch (PDOException $e) {
            $this->connection->rollBack();
            $this->sendJsonResponse(["error" => $e->getMessage()], 500);
        }
    }

  
    function retrieveAllMappedTypes() {
        $this->setStatement("
            SELECT
                T.type_id AS id,
                T.type_name AS name,
                'Type' AS classification,
                S.sub_category_name AS parent,
                S.sub_category_id AS parent_id
            FROM itam_asset_type T
            JOIN itam_subtype_map STM ON STM.type_id = T.type_id
            JOIN itam_asset_sub_category S ON STM.subcategory_id = S.sub_category_id
        ");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll());
    }

}
?>
