<?php
/**
 * VAMS - Visitor Access Management System
 * Settings Helper Functions
 */

if (!defined('VAMS_INCLUDED')) {
    require_once __DIR__ . '/../config/constants.php';
}

/**
 * Get a setting value by key
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if setting not found
 * @return mixed The setting value or default
 */
function get_setting($key, $default = null) {
    static $settings_cache = [];
    
    // Return from cache if available
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    try {
        require_once __DIR__ . '/../config/db.php';
        $pdo = getDbConnection();
        
        $result = fetchOne($pdo, 
            "SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1",
            ['key' => $key]
        );
        
        $value = $result['setting_value'] ?? $default;
        $settings_cache[$key] = $value;
        
        return $value;
        
    } catch (PDOException $e) {
        logError("Failed to get setting '$key': " . $e->getMessage());
        return $default;
    }
}

/**
 * Set a setting value by key
 * 
 * @param string $key The setting key
 * @param mixed $value The setting value
 * @return bool True on success, false on failure
 */
function set_setting($key, $value) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $pdo = getDbConnection();
        
        // Check if setting exists
        $existing = fetchOne($pdo, 
            "SELECT id FROM site_settings WHERE setting_key = :key LIMIT 1",
            ['key' => $key]
        );
        
        if ($existing) {
            // Update existing
            $affected = updateRecord($pdo, 
                "UPDATE site_settings SET setting_value = :value WHERE setting_key = :key",
                ['value' => $value, 'key' => $key]
            );
            return $affected > 0;
        } else {
            // Insert new
            $id = insertRecord($pdo, 
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)",
                ['key' => $key, 'value' => $value]
            );
            return $id > 0;
        }
        
    } catch (PDOException $e) {
        logError("Failed to set setting '$key': " . $e->getMessage());
        return false;
    }
}
