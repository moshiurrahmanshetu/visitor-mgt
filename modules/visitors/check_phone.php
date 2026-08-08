<?php
/**
 * VAMS - Visitor Access Management System
 * AJAX Endpoint: Check for duplicate phone number
 * 
 * Returns JSON response with visitor info if phone exists and is not deleted
 */

// Load configuration
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';

// Set JSON header
header('Content-Type: application/json');

// Get phone number from GET request
$phone = trim($_GET['phone'] ?? '');

// Validate input
if (empty($phone)) {
    echo json_encode(['exists' => false, 'message' => 'Phone number is required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Check if phone exists (only non-deleted visitors)
    $sql = "SELECT id, visitor_code, full_name, phone, company_name 
            FROM visitors 
            WHERE phone = :phone AND is_deleted = 0 
            LIMIT 1";
    
    $visitor = fetchOne($pdo, $sql, ['phone' => $phone]);
    
    if ($visitor) {
        // Phone exists - return visitor info
        echo json_encode([
            'exists' => true,
            'visitor' => [
                'id' => $visitor['id'],
                'visitor_code' => $visitor['visitor_code'],
                'full_name' => $visitor['full_name'],
                'phone' => $visitor['phone'],
                'company_name' => $visitor['company_name'],
                'view_url' => BASE_URL . '/modules/visitors/view.php?id=' . $visitor['id']
            ],
            'message' => 'A visitor with this phone number already exists'
        ]);
    } else {
        // Phone doesn't exist
        echo json_encode(['exists' => false, 'message' => 'Phone number is available']);
    }
    
} catch (PDOException $e) {
    // Log error
    logError("Duplicate phone check failed: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'exists' => false,
        'error' => 'Database error occurred',
        'message' => 'Unable to check phone number'
    ]);
}
