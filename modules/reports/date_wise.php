<?php
/**
 * VAMS - Visitor Access Management System
 * Date-Wise Visitor Report Page
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

// Permission check: reports.view_all
require_permission('reports.view_all');

$page_title = 'Date-Wise Visitor Report';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';

// Role check: Admin and Receptionist only
if (!in_array($current_user_role, ['Admin', 'Receptionist'])) {
    $error_message = 'You do not have permission to access this page.';
}

// Pagination settings
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Date range parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Validate dates
if (empty($date_from) || empty($date_to)) {
    $date_from = date('Y-m-d');
    $date_to = date('Y-m-d');
}

try {
    $pdo = getDbConnection();
    
    // Get summary counts for the date range
    $summary_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'Checked In' THEN 1 ELSE 0 END) as checked_in,
                    SUM(CASE WHEN status = 'Checked Out' THEN 1 ELSE 0 END) as checked_out,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
                    FROM visits 
                    WHERE visit_date BETWEEN :date_from AND :date_to";
    
    $summary = fetchOne($pdo, $summary_sql, [
        'date_from' => $date_from,
        'date_to' => $date_to
    ]);
    
    // Get detailed visits with pagination
    $sql = "SELECT v.*, vis.full_name as visitor_name, vis.phone as visitor_phone, vis.company_name as visitor_company,
                   h.full_name as host_name, h.employee_id as host_employee_id,
                   vp.check_in_time, vp.check_out_time
            FROM visits v
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            LEFT JOIN visitor_passes vp ON v.id = vp.visit_id
            WHERE v.visit_date BETWEEN :date_from AND :date_to
            ORDER BY v.visit_date DESC, v.expected_time DESC
            LIMIT :per_page OFFSET :offset";
    
    $visits = fetchAll($pdo, $sql, [
        'date_from' => $date_from,
        'date_to' => $date_to,
        'per_page' => $per_page,
        'offset' => $offset
    ]);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM visits WHERE visit_date BETWEEN :date_from AND :date_to";
    $count_result = fetchOne($pdo, $count_sql, [
        'date_from' => $date_from,
        'date_to' => $date_to
    ]);
    $total_records = $count_result['total'] ?? 0;
    $total_pages = ceil($total_records / $per_page);
    
} catch (PDOException $e) {
    $error_message = 'Failed to load date-wise report. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $summary = null;
    $visits = [];
    $total_records = 0;
    $total_pages = 0;
}

// Status badge colors
$status_badges = [
    'Pending' => 'bg-warning',
    'Approved' => 'bg-success',
    'Rejected' => 'bg-danger',
    'Checked In' => 'bg-info',
    'Checked Out' => 'bg-secondary',
    'Cancelled' => 'bg-dark'
];

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<!-- Main Content Wrapper -->
<div class="main-wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
        
        <!-- Page Content -->
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-header d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title">Date-Wise Visitor Report</h1>
                                <p class="text-muted">View visitor statistics for a specific date range</p>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/modules/reports/index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Date Range Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label">From Date</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" 
                                               value="<?php echo htmlspecialchars($date_from); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label">To Date</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" 
                                               value="<?php echo htmlspecialchars($date_to); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-calendar-range me-2"></i>Generate Report
                                        </button>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <?php if ($summary): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>/modules/reports/export_csv.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                            <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-file-earmark-csv me-2"></i>Export to CSV
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($summary): ?>
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $summary['total'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Total Visits</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $summary['approved'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Approved</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-danger">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $summary['rejected'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Rejected</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $summary['checked_in'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Checked In</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-secondary">
                                    <i class="bi bi-box-arrow-right"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $summary['checked_out'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Checked Out</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-dark">
                                    <i class="bi bi-x"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $summary['cancelled'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Cancelled</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Results Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Detailed Records</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($visits)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-calendar-x" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h4 class="mt-3 text-muted">No visits found for selected date range</h4>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Visitor Code</th>
                                                <th>Visitor Name</th>
                                                <th>Host</th>
                                                <th>Department</th>
                                                <th>Purpose</th>
                                                <th>Visit Date</th>
                                                <th>Check-In Time</th>
                                                <th>Check-Out Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($visits as $visit): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($visit['visit_code']); ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($visit['visitor_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($visit['visitor_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($visit['host_name']); ?>
                                                    <?php if ($visit['host_employee_id']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($visit['host_employee_id']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($visit['department'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($visit['purpose']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                                                <td><?php echo $visit['check_in_time'] ? date('M d, Y H:i', strtotime($visit['check_in_time'])) : '-'; ?></td>
                                                <td><?php echo $visit['check_out_time'] ? date('M d, Y H:i', strtotime($visit['check_out_time'])) : '-'; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $status_badges[$visit['status']] ?? 'bg-secondary'; ?>">
                                                        <?php echo htmlspecialchars($visit['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i=1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $i; ?></span>
                                        </li>
                                        <?php else: ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                                
                                <div class="text-muted small mt-2">
                                    Showing <?php echo min($per_page, $total_records); ?> of <?php echo $total_records; ?> records
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
