<?php
/**
 * VAMS - Visitor Access Management System
 * AJAX Endpoint: Search visitors by name, phone, or visitor_code
 * 
 * Returns JSON response with visitor list for visit creation
 */

// Load configuration
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';

// Set JSON header
header('Content-Type: application/json');

// Get search query from GET request
$search = trim($_GET['search'] ?? '');

// Validate input
if (empty($search) || strlen($search) < 2) {
    echo json_encode(['visitors' => [], 'message' => 'Search query must be at least 2 characters']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Search visitors (only non-deleted)
    $sql = "SELECT id, visitor_code, full_name, phone, email, company_name, photo 
            FROM visitors 
            WHERE (full_name LIKE :search OR phone LIKE :search OR visitor_code LIKE :search) 
            AND is_deleted = 0 
            ORDER BY full_name ASC 
            LIMIT 20";
    
    $visitors = fetchAll($pdo, $sql, ['search' => '%' . $search . '%']);
    
    // Format visitor data with photo URLs
    $formatted_visitors = [];
    foreach ($visitors as $visitor) {
        $formatted_visitors[] = [
            'id' => $visitor['id'],
            'visitor_code' => $visitor['visitor_code'],
            'full_name' => $visitor['full_name'],
            'phone' => $visitor['phone'],
            'email' => $visitor['email'],
            'company_name' => $visitor['company_name'],
            'photo' => $visitor['photo'] ? ASSETS_URL . '/uploads/visitors/' . $visitor['photo'] : null
        ];
    }
    
    echo json_encode([
        'visitors' => $formatted_visitors,
        'count' => count($formatted_visitors),
        'message' => count($formatted_visitors) > 0 ? 'Found ' . count($formatted_visitors) . ' visitors' : 'No visitors found'
    ]);
    
} catch (PDOException $e) {
    // Log error
    logError("Visitor search failed: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'visitors' => [],
        'error' => 'Database error occurred',
        'message' => 'Unable to search visitors'
    ]);
}
