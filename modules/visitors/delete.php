<?php
/**
 * VAMS - Visitor Access Management System
 * Soft Delete Visitor Page
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

// Permission check: visitors.delete
require_permission('visitors.delete');

$visitor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

if ($visitor_id <= 0) {
    $error_message = 'Invalid visitor ID.';
}

// Validate CSRF token
if (empty($error_message)) {
    $csrf_token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? null;
    if (!$csrf_token || !validateCsrfToken($csrf_token)) {
        $error_message = 'Invalid form submission. Please try again.';
    }
}

if (empty($error_message)) {
    try {
        $pdo = getDbConnection();
        
        // Check if visitor exists
        $visitor = fetchOne($pdo, 
            "SELECT id, full_name, is_deleted FROM visitors WHERE id = :id LIMIT 1",
            ['id' => $visitor_id]
        );
        
        if (!$visitor) {
            $error_message = 'Visitor not found.';
        } elseif ($visitor['is_deleted']) {
            $error_message = 'Visitor is already deleted.';
        } else {
            // Soft delete the visitor
            $sql = "UPDATE visitors SET is_deleted = 1, updated_at = NOW() WHERE id = :id";
            $affected = updateRecord($pdo, $sql, ['id' => $visitor_id]);
            
            if ($affected > 0) {
                $success_message = 'Visitor deleted successfully.';
            } else {
                $error_message = 'Failed to delete visitor. Please try again.';
            }
        }
        
    } catch (PDOException $e) {
        $error_message = 'Failed to delete visitor. Please try again later.';
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $error_message .= ' ' . $e->getMessage();
        }
    }
}

// Set flash message and redirect
if ($success_message) {
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => $success_message
    ];
} elseif ($error_message) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => $error_message
    ];
}

header('Location: ' . BASE_URL . '/modules/visitors/list.php');
exit;
