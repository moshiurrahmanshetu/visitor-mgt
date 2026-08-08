<?php
/**
 * VAMS - Visitor Access Management System
 * CSV Export Handler
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

// Role check: Admin and Receptionist only
if (!in_array($current_user_role, ['Admin', 'Receptionist'])) {
    die('Access denied.');
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        die('Invalid form submission.');
    }
}

// Get filter parameters
$search_visitor = trim($_POST['search_visitor'] ?? $_GET['search_visitor'] ?? '');
$search_phone = trim($_POST['search_phone'] ?? $_GET['search_phone'] ?? '');
$search_code = trim($_POST['search_code'] ?? $_GET['search_code'] ?? '');
$filter_host = isset($_POST['filter_host']) ? intval($_POST['filter_host']) : (isset($_GET['filter_host']) ? intval($_GET['filter_host']) : 0);
$filter_department = trim($_POST['filter_department'] ?? $_GET['filter_department'] ?? '');
$filter_date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? '';
$filter_date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? '';
$filter_status = $_POST['filter_status'] ?? $_GET['filter_status'] ?? '';

// Build WHERE clause
$where_conditions = ['1=1'];
$params = [];

// Search by visitor name
if (!empty($search_visitor)) {
    $where_conditions[] = "vis.full_name LIKE :search_visitor";
    $params['search_visitor'] = '%' . $search_visitor . '%';
}

// Search by phone
if (!empty($search_phone)) {
    $where_conditions[] = "vis.phone LIKE :search_phone";
    $params['search_phone'] = '%' . $search_phone . '%';
}

// Search by visitor code
if (!empty($search_code)) {
    $where_conditions[] = "v.visit_code LIKE :search_code";
    $params['search_code'] = '%' . $search_code . '%';
}

// Filter by host
if ($filter_host > 0) {
    $where_conditions[] = "v.host_id = :host_id";
    $params['host_id'] = $filter_host;
}

// Filter by department
if (!empty($filter_department)) {
    $where_conditions[] = "v.department LIKE :department";
    $params['department'] = '%' . $filter_department . '%';
}

// Filter by date range
if (!empty($filter_date_from)) {
    $where_conditions[] = "v.visit_date >= :date_from";
    $params['date_from'] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "v.visit_date <= :date_to";
    $params['date_to'] = $filter_date_to;
}

// Filter by status
if (!empty($filter_status)) {
    $where_conditions[] = "v.status = :status";
    $params['status'] = $filter_status;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $pdo = getDbConnection();
    
    // Get visits data
    $sql = "SELECT v.visit_code, vis.full_name as visitor_name, vis.phone as visitor_phone, vis.company_name as visitor_company,
                   h.full_name as host_name, v.department, v.purpose, v.visit_date, v.expected_time,
                   vp.check_in_time, vp.check_out_time, v.status
            FROM visits v
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            LEFT JOIN visitor_passes vp ON v.id = vp.visit_id
            WHERE " . $where_clause . "
            ORDER BY v.visit_date DESC, v.expected_time DESC";
    
    $visits = fetchAll($pdo, $sql, $params);
    
    // Generate CSV
    $filename = 'visitor_report_' . date('Y-m-d_His') . '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV header row
    fputcsv($output, [
        'Visitor Code',
        'Visitor Name',
        'Phone',
        'Company',
        'Host',
        'Department',
        'Purpose',
        'Visit Date',
        'Expected Time',
        'Check-In Time',
        'Check-Out Time',
        'Status'
    ]);
    
    // Add data rows
    foreach ($visits as $visit) {
        fputcsv($output, [
            $visit['visit_code'],
            $visit['visitor_name'],
            $visit['visitor_phone'],
            $visit['visitor_company'] ?? '',
            $visit['host_name'],
            $visit['department'] ?? '',
            $visit['purpose'],
            $visit['visit_date'],
            $visit['expected_time'],
            $visit['check_in_time'] ?? '',
            $visit['check_out_time'] ?? '',
            $visit['status']
        ]);
    }
    
    // Close output stream
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    logError("CSV export failed: " . $e->getMessage());
    die('Failed to generate CSV. Please try again later.');
}
