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
                    c.company_id,
                    c.alias,
                    a.status_id,
                    COUNT(*) AS count
                FROM itam_asset_transactions t
                JOIN itam_asset a ON t.asset_id = a.asset_id
                JOIN un_companies c ON t.company_id = c.company_id
                GROUP BY c.company_id, c.alias, a.status_id
                ORDER BY c.company_id;
                    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
    function getIssuedAssetsByCompany()
    {
        $sql = "SELECT 
    c.alias AS alias,
    COUNT(i.issuance_id) AS issued_count
FROM itam_asset_issuance i
JOIN itam_asset a ON i.asset_id = a.asset_id
JOIN un_users u ON i.user_id = u.user_id
JOIN un_companies c ON u.company_id = c.company_id
WHERE a.status_id = 3
GROUP BY c.company_id, c.alias
ORDER BY issued_count DESC

    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }

    function getOverdueBorrowedAssets()
    {
        $sql = "
        SELECT 
            t.asset_id,
            a.asset_name,
            t.user_id,
            CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) AS full_name,
            t.due_date,
            DATEDIFF(CURDATE(), t.due_date) AS days_overdue
        FROM itam_asset_transactions t
        JOIN itam_asset a ON t.asset_id = a.asset_id
        JOIN un_users u ON t.user_id = u.user_id
        WHERE t.due_date < CURDATE()
          AND (t.return_date IS NULL OR t.return_date = '')
    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
    function getUrgentRepairRequests()
    {
        $sql = "
        SELECT 
            r.repair_request_id,
            r.asset_id,
            a.asset_name,
            r.issue,
            r.date_reported,
            CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) AS reported_by,
            r.urgency_id,
            u.user_id
        FROM itam_asset_repair_request r
        JOIN itam_asset a ON r.asset_id = a.asset_id
        JOIN un_users u ON r.user_id = u.user_id
        WHERE r.urgency_id = 1
    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
    function getMonthlyRepairsThisYear()
    {
        $sql = "
        SELECT 
            MONTHNAME(repair_start_date) AS month,
            COUNT(*) AS repair_count
        FROM itam_asset_repair_request
        WHERE YEAR(repair_start_date) = YEAR(CURDATE())
        GROUP BY MONTH(repair_start_date), MONTHNAME(repair_start_date)
        ORDER BY MONTH(repair_start_date)
    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
    function getAssetsByCondition()
    {
        $sql = "
        SELECT 
            ac.asset_condition_name,
            COUNT(a.asset_id) AS count
        FROM itam_asset a
        JOIN itam_asset_condition ac ON a.asset_condition_id = ac.asset_condition_id
        GROUP BY a.asset_condition_id, ac.asset_condition_name
        ORDER BY count DESC
    ";

        $this->setStatement($sql);
        $this->statement->execute();
        $this->sendJsonResponse($this->statement->fetchAll(PDO::FETCH_ASSOC));
    }
}
