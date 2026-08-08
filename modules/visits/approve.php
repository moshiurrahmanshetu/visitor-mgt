<?php
/**
 * VAMS - Visitor Access Management System
 * Approve Visit Page
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

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();

$visit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

if ($visit_id <= 0) {
    $error_message = 'Invalid visit ID.';
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    }
}

if (empty($error_message)) {
    try {
        $pdo = getDbConnection();
        
        // Check if visit exists and get details
        $visit = fetchOne($pdo, 
            "SELECT id, visit_code, status, host_id FROM visits WHERE id = :id LIMIT 1",
            ['id' => $visit_id]
        );
        
        if (!$visit) {
            $error_message = 'Visit not found.';
        } elseif ($visit['status'] !== 'Pending') {
            $error_message = 'Only pending visits can be approved.';
        } else {
            // Check if user is the host OR Admin
            if ($current_user_role !== 'Admin' && $visit['host_id'] != $current_user_id) {
                $error_message = 'You do not have permission to approve this visit.';
            } else {
                // Approve the visit
                $sql = "UPDATE visits SET 
                        status = 'Approved', 
                        approved_by = :approved_by, 
                        approved_at = NOW(),
                        updated_at = NOW() 
                        WHERE id = :id";
                
                $params = [
                    'approved_by' => $current_user_id,
                    'id' => $visit_id
                ];
                
                $affected = updateRecord($pdo, $sql, $params);
                
                if ($affected > 0) {
                    $success_message = 'Visit approved successfully.';
                } else {
                    $error_message = 'Failed to approve visit. Please try again.';
                }
            }
        }
        
    } catch (PDOException $e) {
        $error_message = 'Failed to approve visit. Please try again later.';
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
