<?php
/**
 * VAMS - Visitor Access Management System
 * Delete Role Page
 */

// Load configuration FIRST - before session starts
require_once __DIR__ . '/../../config/constants.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Load database and auth check
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

$error_message = '';

$role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($role_id <= 0) {
    $error_message = 'Invalid role ID.';
}

// Permission check: roles.manage
require_permission('roles.manage');

// Validate CSRF token
if (!isset($_GET['csrf_token']) || !validateCsrfToken($_GET['csrf_token'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid form submission. Please try again.'
    ];
    header('Location: ' . BASE_URL . '/modules/roles/list.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Check if role exists
    $role = fetchOne($pdo, 
        "SELECT id, role_name FROM roles WHERE id = :id LIMIT 1",
        ['id' => $role_id]
    );
    
    if (!$role) {
        $error_message = 'Role not found.';
    } else {
        // Check if any users are assigned to this role
        $user_count = fetchOne($pdo, 
            "SELECT COUNT(*) as count FROM users WHERE role_id = :role_id AND is_deleted = 0",
            ['role_id' => $role_id]
        );
        
        if ($user_count['count'] > 0) {
            $error_message = 'Cannot delete role with assigned users. Please reassign users first.';
        } else {
            // Delete role (CASCADE will delete role_permissions automatically)
            $sql = "DELETE FROM roles WHERE id = :id";
            $affected = $pdo->prepare($sql)->execute(['id' => $role_id]);
            
            if ($affected > 0) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Role deleted successfully.'
                ];
            } else {
                $error_message = 'Failed to delete role.';
            }
        }
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to delete role. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
}

if ($error_message) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => $error_message
    ];
}

header('Location: ' . BASE_URL . '/modules/roles/list.php');
exit;
