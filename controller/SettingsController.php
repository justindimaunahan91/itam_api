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

public function getCategorySubcategoryMappings() {
    try {
        $stmt = $this->conn->prepare("
            SELECT m.map_id, c.name AS category_name, s.name AS subcategory_name, 
                   m.category_id, m.subcategory_id
            FROM itam_catsub_map m
            JOIN itam_asset_category c ON m.category_id = c.id
            JOIN itam_asset_subcategory s ON m.subcategory_id = s.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}

public function addCategorySubcategoryMapping($category_id, $subcategory_id) {
    try {
        $check = $this->conn->prepare("
            SELECT COUNT(*) FROM itam_catsub_map 
            WHERE category_id = :category_id AND subcategory_id = :subcategory_id
        ");
        $check->execute([':category_id' => $category_id, ':subcategory_id' => $subcategory_id]);
        if ($check->fetchColumn() > 0) {
            return ["error" => "Mapping already exists."];
        }

        $stmt = $this->conn->prepare("
            INSERT INTO itam_catsub_map (category_id, subcategory_id)
            VALUES (:category_id, :subcategory_id)
        ");
        $stmt->execute([':category_id' => $category_id, ':subcategory_id' => $subcategory_id]);
        return true;
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}

public function updateCategorySubcategoryMapping($map_id, $category_id, $subcategory_id) {
    try {
        $check = $this->conn->prepare("
            SELECT COUNT(*) FROM itam_catsub_map 
            WHERE category_id = :category_id AND subcategory_id = :subcategory_id AND map_id != :map_id
        ");
        $check->execute([
            ':category_id' => $category_id,
            ':subcategory_id' => $subcategory_id,
            ':map_id' => $map_id
        ]);
        if ($check->fetchColumn() > 0) {
            return ["error" => "Mapping already exists."];
        }

        $stmt = $this->conn->prepare("
            UPDATE itam_catsub_map
            SET category_id = :category_id, subcategory_id = :subcategory_id
            WHERE map_id = :map_id
        ");
        $stmt->execute([
            ':map_id' => $map_id,
            ':category_id' => $category_id,
            ':subcategory_id' => $subcategory_id
        ]);
        return true;
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}

public function deleteCategorySubcategoryMapping($map_id) {
    try {
        $stmt = $this->conn->prepare("DELETE FROM itam_catsub_map WHERE map_id = :map_id");
        $stmt->execute([':map_id' => $map_id]);
        return true;
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}


// === SUBCATEGORY-TYPE MAPPING FUNCTIONS ===

public function getSubTypeMappings() {
    try {
        $stmt = $this->conn->prepare("
            SELECT m.map_id, s.name AS subcategory_name, t.name AS type_name, 
                   m.subcategory_id, m.type_id
            FROM itam_subtype_map m
            JOIN itam_asset_subcategory s ON m.subcategory_id = s.id
            JOIN itam_asset_type t ON m.type_id = t.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}

public function addSubTypeMapping($subcategory_id, $type_id) {
    try {
        $check = $this->conn->prepare("
            SELECT COUNT(*) FROM itam_subtype_map 
            WHERE subcategory_id = :subcategory_id AND type_id = :type_id
        ");
        $check->execute([':subcategory_id' => $subcategory_id, ':type_id' => $type_id]);
        if ($check->fetchColumn() > 0) {
            return ["error" => "Mapping already exists."];
        }

        $stmt = $this->conn->prepare("
            INSERT INTO itam_subtype_map (subcategory_id, type_id)
            VALUES (:subcategory_id, :type_id)
        ");
        $stmt->execute([':subcategory_id' => $subcategory_id, ':type_id' => $type_id]);
        return true;
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}

public function updateSubTypeMapping($map_id, $subcategory_id, $type_id) {
    try {
        $check = $this->conn->prepare("
            SELECT COUNT(*) FROM itam_subtype_map 
            WHERE subcategory_id = :subcategory_id AND type_id = :type_id AND map_id != :map_id
        ");
        $check->execute([
            ':subcategory_id' => $subcategory_id,
            ':type_id' => $type_id,
            ':map_id' => $map_id
        ]);
        if ($check->fetchColumn() > 0) {
            return ["error" => "Mapping already exists."];
        }

        $stmt = $this->conn->prepare("
            UPDATE itam_subtype_map
            SET subcategory_id = :subcategory_id, type_id = :type_id
            WHERE map_id = :map_id
        ");
        $stmt->execute([
            ':map_id' => $map_id,
            ':subcategory_id' => $subcategory_id,
            ':type_id' => $type_id
        ]);
        return true;
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}

public function deleteSubTypeMapping($map_id) {
    try {
        $stmt = $this->conn->prepare("DELETE FROM itam_subtype_map WHERE map_id = :map_id");
        $stmt->execute([':map_id' => $map_id]);
        return true;
    } catch (PDOException $e) {
        return ["error" => $e->getMessage()];
    }
}
}