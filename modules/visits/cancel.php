<?php
/**
 * VAMS - Visitor Access Management System
 * Cancel Visit Page
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

// Permission check: visits.cancel
require_permission('visits.cancel');

$visit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

if ($visit_id <= 0) {
    $error_message = 'Invalid visit ID.';
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
        
        // Check if visit exists and status
        $visit = fetchOne($pdo, 
            "SELECT id, visit_code, status FROM visits WHERE id = :id LIMIT 1",
            ['id' => $visit_id]
        );
        
        if (!$visit) {
            $error_message = 'Visit not found.';
        } elseif ($visit['status'] === 'Checked Out') {
            $error_message = 'Cannot cancel a visit that has been checked out.';
        } elseif ($visit['status'] === 'Cancelled') {
            $error_message = 'Visit is already cancelled.';
        } else {
            // Cancel the visit
            $sql = "UPDATE visits SET status = 'Cancelled', updated_at = NOW() WHERE id = :id";
            $affected = updateRecord($pdo, $sql, ['id' => $visit_id]);
            
            if ($affected > 0) {
                $success_message = 'Visit cancelled successfully.';
            } else {
                $error_message = 'Failed to cancel visit. Please try again.';
            }
        }
        
    } catch (PDOException $e) {
        $error_message = 'Failed to cancel visit. Please try again later.';
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

header('Location: ' . BASE_URL . '/modules/visits/list.php');
exit;
