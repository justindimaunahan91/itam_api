<?php

class SettingsController {
    private $conn;

    public function __construct() {
        require_once __DIR__ . "/controller.php";
        $this->conn = new Controller()->connection;
    }

    // Get all settings
    public function getSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT `key`, `value` FROM settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return $settings;
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Update multiple settings
    public function updateSettings($data) {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("REPLACE INTO settings (`key`, `value`) VALUES (:key, :value)");
            foreach ($data as $key => $value) {
                $stmt->execute([':key' => $key, ':value' => $value]);
            }
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ["error" => $e->getMessage()];
        }
    }

    // Get recycle bin assets
    public function getRecycleBinItems() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM itam_asset WHERE status = 'deleted'");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Restore asset from recycle bin
    public function restoreFromRecycleBin($assetId) {
        try {
            $stmt = $this->conn->prepare("UPDATE itam_asset SET status = 'active' WHERE id = :id AND status = 'deleted'");
            return $stmt->execute([':id' => $assetId]);
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Factory reset system (delete all relevant data)
    public function factoryReset() {
        try {
            $this->conn->beginTransaction();

            $tables = [
                'itam_asset',
                'itam_asset_category',
                'itam_asset_condition',
                'itam_asset_insurance',
                'itam_asset_issuance',
                'itam_asset_repair_request',
                'itam_asset_status',
                'itam_asset_transactions',
                'itam_asset_type',
                'itam_functions',
                'settings'
            ];

            foreach ($tables as $table) {
                $this->conn->exec("DELETE FROM $table");
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ["error" => $e->getMessage()];
        }
    }

    // === CATEGORY-SUBCATEGORY MAPPING FUNCTIONS ===

    // Add a new category-subcategory mapping
    public function addCategorySubcategoryMapping($categoryId, $subcategoryId) {
        try {
            $check = $this->conn->prepare("SELECT COUNT(*) FROM itam_catsub_map WHERE category_id = :categoryId AND subcategory_id = :subcategoryId");
            $check->execute([
                ':categoryId' => $categoryId,
                ':subcategoryId' => $subcategoryId
            ]);
            if ($check->fetchColumn() > 0) {
                return ["error" => "Mapping already exists."];
            }

            $stmt = $this->conn->prepare("INSERT INTO itam_catsub_map (category_id, subcategory_id) VALUES (:categoryId, :subcategoryId)");
            $stmt->execute([
                ':categoryId' => $categoryId,
                ':subcategoryId' => $subcategoryId
            ]);
            return true;
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Get all mappings
    public function getCategorySubcategoryMappings() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    m.map_id, 
                    c.category_id, 
                    c.category_name, 
                    s.subcategory_id, 
                    s.subcategory_name
                FROM itam_catsub_map m
                JOIN itam_asset_category c ON m.category_id = c.category_id
                JOIN itam_asset_subcategory s ON m.subcategory_id = s.subcategory_id
                ORDER BY c.category_name, s.subcategory_name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Delete a specific mapping
    public function deleteCategorySubcategoryMapping($mapId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM itam_catsub_map WHERE map_id = :mapId");
            $stmt->execute([':mapId' => $mapId]);
            return true;
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
