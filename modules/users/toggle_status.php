<?php
/**
 * VAMS - Visitor Access Management System
 * Toggle User Status (Activate/Deactivate)
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

// Permission check: users.edit (toggle status is part of edit)
require_permission('users.edit');

// Self-protection: Admin cannot deactivate self
if ($user_id === $current_user_id) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'You cannot change your own status. Use Profile settings instead.'
    ];
    header('Location: ' . BASE_URL . '/modules/users/list.php');
    exit;
}

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
    
    // Get current status
    $user = fetchOne($pdo, 
        "SELECT id, status FROM users WHERE id = :id LIMIT 1",
        ['id' => $user_id]
    );
    
    if (!$user) {
        $error_message = 'User not found.';
    } else {
        // Toggle status
        $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
        
        $sql = "UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id";
        $affected = updateRecord($pdo, $sql, [
            'status' => $new_status,
            'id' => $user_id
        ]);
        
        if ($affected > 0) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'User status updated successfully.'
            ];
        } else {
            $error_message = 'Failed to update user status.';
        }
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to update user status. Please try again later.';
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

header('Location: ' . BASE_URL . '/modules/users/list.php');
exit;
