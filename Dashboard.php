<?php
require_once __DIR__ . '/controller/Dashboard.php';

header('Content-Type: application/json');

$dashboard = new Dashboard();
$action = $_GET['action'] ?? null;

switch ($action) {
    case 'getTotalAssets':
        $dashboard->getTotalAssets();
        break;
    case 'getIssuedAssets':
        $dashboard->getIssuedAssets();
        break;
    case 'getBorrowedAssets':
        $dashboard->getBorrowedAssets();
        break;
    case 'getUnderRepairAssets':
        $dashboard->getUnderRepairAssets();
        break;
    case 'getAvailableAssets':
        $dashboard->getAvailableAssets();
        break;
    case 'getAllDashboardStats':
        $dashboard->getAllDashboardStats();
        break;
    case 'getBorrowedAssetsByCompany':
        $dashboard->getBorrowedAssetsByCompany();
        break;
    case 'getIssuedAssetsByCompany':
        $dashboard->getIssuedAssetsByCompany();
        break;


    default:
        echo json_encode(["error" => "Invalid or missing action"]);
        break;
}
