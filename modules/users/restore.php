<?php
/**
 * VAMS - Visitor Access Management System
 * Restore Soft-Deleted User
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

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    $error_message = 'Invalid user ID.';
}

// Permission check: users.delete (restore is part of delete)
require_permission('users.delete');

// Validate CSRF token
if (!isset($_GET['csrf_token']) || !validateCsrfToken($_GET['csrf_token'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid form submission. Please try again.'
    ];
    header('Location: ' . BASE_URL . '/modules/users/list.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Check if user exists and is deleted
    $user = fetchOne($pdo, 
        "SELECT id FROM users WHERE id = :id AND is_deleted = 1 LIMIT 1",
        ['id' => $user_id]
    );
    
    if (!$user) {
        $error_message = 'User not found or not deleted.';
    } else {
        // Restore user
        $sql = "UPDATE users SET is_deleted = 0, updated_at = NOW() WHERE id = :id";
        $affected = updateRecord($pdo, $sql, ['id' => $user_id]);
        
        if ($affected > 0) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'User restored successfully.'
            ];
        } else {
            $error_message = 'Failed to restore user.';
        }
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to restore user. Please try again later.';
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

header('Location: ' . BASE_URL . '/modules/users/list.php?tab=deleted');
exit;
