<?php
/**
 * VAMS - Visitor Access Management System
 * AJAX Endpoint: Search visits for check-in/check-out
 * 
 * Returns JSON response with visit list
 * For check-in: Approved visits for today only
 * For check-out: Checked-in visits only
 */

// Load configuration
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';

// Set JSON header
header('Content-Type: application/json');

// Get search query and mode from GET request
$search = trim($_GET['search'] ?? '');
$mode = trim($_GET['mode'] ?? 'checkin'); // 'checkin' or 'checkout'

// Validate input
if (empty($search) || strlen($search) < 2) {
    echo json_encode(['visits' => [], 'message' => 'Search query must be at least 2 characters']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    if ($mode === 'checkin') {
        // Search approved visits for today only
        $today = date('Y-m-d');
        $sql = "SELECT v.id, v.visit_code, v.visit_date, v.expected_time, v.purpose, v.department,
                       vis.full_name as visitor_name, vis.phone as visitor_phone, vis.photo as visitor_photo, vis.company_name as visitor_company,
                       h.full_name as host_name, h.employee_id as host_employee_id
                FROM visits v
                INNER JOIN visitors vis ON v.visitor_id = vis.id
                INNER JOIN users h ON v.host_id = h.id
                WHERE (v.visit_code LIKE :search OR vis.full_name LIKE :search OR vis.phone LIKE :search)
                AND v.status = 'Approved' 
                AND v.visit_date = :today
                AND v.is_currently_inside = 0
                ORDER BY v.expected_time ASC
                LIMIT 20";
        
        $visits = fetchAll($pdo, $sql, [
            'search' => '%' . $search . '%',
            'today' => $today
        ]);
        
    } elseif ($mode === 'checkout') {
        // Search checked-in visits (currently inside)
        $sql = "SELECT v.id, v.visit_code, v.visit_date, v.expected_time, v.purpose, v.department,
                       vis.full_name as visitor_name, vis.phone as visitor_phone, vis.photo as visitor_photo, vis.company_name as visitor_company,
                       h.full_name as host_name, h.employee_id as host_employee_id,
                       vp.check_in_time, vp.pass_number
                FROM visits v
                INNER JOIN visitors vis ON v.visitor_id = vis.id
                INNER JOIN users h ON v.host_id = h.id
                INNER JOIN visitor_passes vp ON v.id = vp.visit_id
                WHERE (v.visit_code LIKE :search OR vis.full_name LIKE :search OR vis.phone LIKE :search OR vp.pass_number LIKE :search)
                AND v.is_currently_inside = 1
                AND vp.check_out_time IS NULL
                ORDER BY vp.check_in_time ASC
                LIMIT 20";
        
        $visits = fetchAll($pdo, $sql, ['search' => '%' . $search . '%']);
        
    } else {
        echo json_encode(['visits' => [], 'error' => 'Invalid mode']);
        exit;
    }
    
    // Format visit data with photo URLs
    $formatted_visits = [];
    foreach ($visits as $visit) {
        $formatted_visits[] = [
            'id' => $visit['id'],
            'visit_code' => $visit['visit_code'],
            'visitor_name' => $visit['visitor_name'],
            'visitor_phone' => $visit['visitor_phone'],
            'visitor_company' => $visit['visitor_company'],
            'visitor_photo' => $visit['visitor_photo'] ? ASSETS_URL . '/uploads/visitors/' . $visit['visitor_photo'] : null,
            'host_name' => $visit['host_name'],
            'host_employee_id' => $visit['host_employee_id'],
            'department' => $visit['department'],
            'purpose' => $visit['purpose'],
            'expected_time' => $visit['expected_time'],
            'check_in_time' => $visit['check_in_time'] ?? null,
            'pass_number' => $visit['pass_number'] ?? null
        ];
    }
    
    echo json_encode([
        'visits' => $formatted_visits,
        'count' => count($formatted_visits),
        'message' => count($formatted_visits) > 0 ? 'Found ' . count($formatted_visits) . ' visits' : 'No visits found'
    ]);
    
} catch (PDOException $e) {
    // Log error
    logError("Visit search failed: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'visits' => [],
        'error' => 'Database error occurred',
        'message' => 'Unable to search visits'
    ]);
}
