<?php
require_once 'controller.php';

class AssetInsurance extends Controller {

    // Retrieve all insurance records
    function retrieveInsurance() {
        $this->setStatement("SELECT * FROM itam_asset_insurance");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll());
    }

    // Retrieve a single insurance record by ID
    function retrieveOneInsurance($insurance_id) {
        $this->setStatement("SELECT * FROM itam_asset_insurance WHERE insurance_id = ?");
        $this->statement->execute([$insurance_id]);
        $result = $this->statement->fetch();
        return $result;
    }

    // Insert a new insurance record
    function insertInsurance($insurance_coverage = null, $insurance_date_from = null, $insurance_date_to = null) {
        try {
            $this->setStatement("INSERT INTO itam_asset_insurance (insurance_coverage, insurance_date_from, insurance_date_to) VALUES (?, ?, ?)");
            $success = $this->statement->execute([
                $insurance_coverage,
                $insurance_date_from,
                $insurance_date_to
            ]);

            return ["message" => $success ? "Insurance record added successfully" : "Failed to add insurance record"];

        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Update an existing insurance record
    function updateInsurance($insurance_id, $insurance_coverage, $insurance_date_from, $insurance_date_to) {
        $this->setStatement("UPDATE itam_asset_insurance SET insurance_coverage = ?, insurance_date_from = ?, insurance_date_to = ? WHERE insurance_id = ?");
        $success = $this->statement->execute([
            $insurance_coverage,
            $insurance_date_from,
            $insurance_date_to,
            $insurance_id
        ]);

        $this->sendJsonResponse(["message" => $success ? "Insurance record updated successfully" : "Failed to update insurance record"], $success ? 200 : 500);
    }

    // Delete an insurance record
    function deleteInsurance($insurance_id) {
        $this->setStatement("DELETE FROM itam_asset_insurance WHERE insurance_id = ?");
        $success = $this->statement->execute([$insurance_id]);
        $this->sendJsonResponse(["message" => $success ? "Insurance record deleted successfully" : "Failed to delete insurance record"], $success ? 200 : 500);
    }
}
?>
