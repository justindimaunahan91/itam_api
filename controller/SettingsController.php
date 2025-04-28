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
}
