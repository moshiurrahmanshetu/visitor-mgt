<?php
/**
 * VAMS - Visitor Access Management System
 * Permission Check Functions
 */

if (!defined('VAMS_INCLUDED')) {
    require_once __DIR__ . '/../config/constants.php';
}

/**
 * Check if current user has a specific permission
 * 
 * @param string $permission_key The permission key to check (e.g., 'visitors.add')
 * @return bool True if user has the permission, false otherwise
 */
function has_permission($permission_key) {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check if permissions array exists in session
    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        return false;
    }
    
    // Check if the specific permission exists in the user's permission list
    return in_array($permission_key, $_SESSION['permissions']);
}

/**
 * Check if current user has ANY of the specified permissions
 * 
 * @param array $permission_keys Array of permission keys to check
 * @return bool True if user has at least one of the permissions
 */
function has_any_permission($permission_keys) {
    foreach ($permission_keys as $key) {
        if (has_permission($key)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if current user has ALL of the specified permissions
 * 
 * @param array $permission_keys Array of permission keys to check
 * @return bool True if user has all of the permissions
 */
function has_all_permissions($permission_keys) {
    foreach ($permission_keys as $key) {
        if (!has_permission($key)) {
            return false;
        }
    }
    return true;
}

/**
 * Require a specific permission - redirect to dashboard with error if not authorized
 * 
 * @param string $permission_key The permission key to require
 * @param string $redirect_url Optional redirect URL (defaults to dashboard)
 */
function require_permission($permission_key, $redirect_url = null) {
    if (!has_permission($permission_key)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Access Denied. You do not have permission to access this page.'
        ];
        
        if ($redirect_url === null) {
            $redirect_url = BASE_URL . '/dashboard/index.php';
        }
        
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Load permissions for a role into an array
 * 
 * @param int $role_id The role ID
 * @return array Array of permission keys
 */
function load_permissions_for_role($role_id) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $pdo = getDbConnection();
        
        $sql = "SELECT p.permission_key 
                FROM role_permissions rp
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id";
        
        $results = fetchAll($pdo, $sql, ['role_id' => $role_id]);
        
        $permission_keys = [];
        foreach ($results as $row) {
            $permission_keys[] = $row['permission_key'];
        }
        
        return $permission_keys;
        
    } catch (PDOException $e) {
        logError("Failed to load permissions for role_id $role_id: " . $e->getMessage());
        return [];
    }
}
