<?php
require_once 'controller.php';

class Dashboard extends Controller
{

    function getTotalAssets()
    {
        $this->setStatement("SELECT COUNT(*) AS total_assets FROM itam_asset");
        $this->statement->execute();
        $result = $this->statement->fetch();
        $this->sendJsonResponse($result);
    }

    function getIssuedAssets()
    {
        $this->setStatement("SELECT COUNT(*) AS issued_assets FROM itam_asset_issuance WHERE status_id = 3");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetch());
    }

    function getBorrowedAssets()
    {
        $this->setStatement("SELECT COUNT(*) AS borrowed_assets FROM itam_asset_transactions WHERE status_id = 2");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetch());
    }

    function getUnderRepairAssets()
    {
        $this->setStatement("SELECT COUNT(*) AS under_repair_assets FROM itam_asset_repair_request WHERE status_id = 4");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetch());
    }

    function getAvailableAssets()
    {
        $this->setStatement("SELECT COUNT(*) AS available_assets FROM itam_asset WHERE status_id = 1");
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetch());
    }

    // Optionally: one endpoint for all counts
    function getAllDashboardStats()
    {
        $sql = "
    SELECT
        (SELECT COUNT(*) FROM itam_asset) AS total_assets,
        (SELECT COUNT(*) FROM itam_asset WHERE status_id = 1) AS available_assets,
        (SELECT COUNT(*)
         FROM itam_asset_issuance
         WHERE status_id = 3) AS issued_assets,
        (SELECT COUNT(DISTINCT a.asset_id)
         FROM itam_asset_transactions AS t
         JOIN itam_asset AS a ON t.asset_id = a.asset_id
         WHERE a.status_id = 2) AS borrowed_assets,
        (SELECT COUNT(DISTINCT a.asset_id)
         FROM itam_asset_repair_request AS r
         JOIN itam_asset AS a ON r.asset_id = a.asset_id
         WHERE a.status_id = 4) AS under_repair_assets
";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetch());
    }
    function getBorrowedAssetsByCompany()
    {
        $sql = "
        SELECT 
            c.alias AS company_alias,
            COUNT(t.transaction_id) AS borrowed_count
        FROM itam_asset_transactions t
        JOIN itam_asset a ON t.asset_id = a.asset_id
        JOIN un_companies c ON t.company_id = c.company_id
        WHERE a.status_id = 2
        GROUP BY c.company_id, c.alias
        ORDER BY borrowed_count DESC
    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
    function getIssuedAssetsByCompany()
    {
        $sql = "
        SELECT 
            c.alias AS company_alias,
            COUNT(i.issuance_id) AS issued_count
        FROM itam_asset_issuance i
        JOIN itam_asset a ON i.asset_id = a.asset_id
        JOIN un_companies c ON i.company_id = c.company_id
        WHERE a.status_id = 3
        GROUP BY c.company_id, c.alias
        ORDER BY issued_count DESC
    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
}
