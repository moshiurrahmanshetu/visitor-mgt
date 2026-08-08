<?php
/**
 * VAMS - Visitor Access Management System
 * Visitor History / Search Page
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

$page_title = 'Visitor History';

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

// Search and filter parameters
$search_visitor = trim($_GET['search_visitor'] ?? '');
$search_phone = trim($_GET['search_phone'] ?? '');
$search_code = trim($_GET['search_code'] ?? '');
$filter_host = isset($_GET['filter_host']) ? intval($_GET['filter_host']) : 0;
$filter_department = trim($_GET['filter_department'] ?? '');
$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

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
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total 
                  FROM visits v
                  INNER JOIN visitors vis ON v.visitor_id = vis.id
                  WHERE " . $where_clause;
    $count_result = fetchOne($pdo, $count_sql, $params);
    $total_records = $count_result['total'] ?? 0;
    $total_pages = ceil($total_records / $per_page);
    
    // Get visits with pagination
    $sql = "SELECT v.*, vis.full_name as visitor_name, vis.phone as visitor_phone, vis.company_name as visitor_company,
                   h.full_name as host_name, h.employee_id as host_employee_id,
                   vp.check_in_time, vp.check_out_time
            FROM visits v
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            LEFT JOIN visitor_passes vp ON v.id = vp.visit_id
            WHERE " . $where_clause . "
            ORDER BY v.visit_date DESC, v.expected_time DESC
            LIMIT :per_page OFFSET :offset";
    
    $params['per_page'] = $per_page;
    $params['offset'] = $offset;
    
    $visits = fetchAll($pdo, $sql, $params);
    
    // Get hosts for filter dropdown
    $hosts = fetchAll($pdo, 
        "SELECT id, full_name, employee_id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Employee') ORDER BY full_name ASC"
    );
    
} catch (PDOException $e) {
    $error_message = 'Failed to load visitor history. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $visits = [];
    $total_records = 0;
    $total_pages = 0;
    $hosts = [];
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
                                <h1 class="page-title">Visitor History</h1>
                                <p class="text-muted">Search and filter visitor records</p>
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
                
                <!-- Search and Filter Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Filter Options</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="search_visitor" class="form-label">Visitor Name</label>
                                        <input type="text" class="form-control" id="search_visitor" name="search_visitor" 
                                               value="<?php echo htmlspecialchars($search_visitor); ?>">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="search_phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="search_phone" name="search_phone" 
                                               value="<?php echo htmlspecialchars($search_phone); ?>">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="search_code" class="form-label">Visitor Code</label>
                                        <input type="text" class="form-control" id="search_code" name="search_code" 
                                               value="<?php echo htmlspecialchars($search_code); ?>">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="filter_host" class="form-label">Host</label>
                                        <select class="form-select" id="filter_host" name="filter_host">
                                            <option value="">All Hosts</option>
                                            <?php foreach ($hosts as $host): ?>
                                            <option value="<?php echo $host['id']; ?>" 
                                                    <?php echo ($filter_host === $host['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($host['full_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="filter_department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="filter_department" name="filter_department" 
                                               value="<?php echo htmlspecialchars($filter_department); ?>">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter_date_from" class="form-label">From Date</label>
                                        <input type="date" class="form-control" id="filter_date_from" name="filter_date_from" 
                                               value="<?php echo htmlspecialchars($filter_date_from); ?>">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter_date_to" class="form-label">To Date</label>
                                        <input type="date" class="form-control" id="filter_date_to" name="filter_date_to" 
                                               value="<?php echo htmlspecialchars($filter_date_to); ?>">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter_status" class="form-label">Status</label>
                                        <select class="form-select" id="filter_status" name="filter_status">
                                            <option value="">All Statuses</option>
                                            <option value="Pending" <?php echo ($filter_status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Approved" <?php echo ($filter_status === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                            <option value="Rejected" <?php echo ($filter_status === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="Checked In" <?php echo ($filter_status === 'Checked In') ? 'selected' : ''; ?>>Checked In</option>
                                            <option value="Checked Out" <?php echo ($filter_status === 'Checked Out') ? 'selected' : ''; ?>>Checked Out</option>
                                            <option value="Cancelled" <?php echo ($filter_status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-search me-2"></i>Search
                                        </button>
                                    </div>
                                    
                                    <div class="col-md-1 d-flex align-items-end">
                                        <a href="<?php echo BASE_URL; ?>/modules/reports/visitor_history.php" class="btn btn-outline-secondary w-100">
                                            Clear
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Results Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Results</h5>
                                <form method="POST" action="<?php echo BASE_URL; ?>/modules/reports/export_csv.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="search_visitor" value="<?php echo htmlspecialchars($search_visitor); ?>">
                                    <input type="hidden" name="search_phone" value="<?php echo htmlspecialchars($search_phone); ?>">
                                    <input type="hidden" name="search_code" value="<?php echo htmlspecialchars($search_code); ?>">
                                    <input type="hidden" name="filter_host" value="<?php echo $filter_host; ?>">
                                    <input type="hidden" name="filter_department" value="<?php echo htmlspecialchars($filter_department); ?>">
                                    <input type="hidden" name="filter_date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                                    <input type="hidden" name="filter_date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                                    <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filter_status); ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-file-earmark-csv me-2"></i>Export to CSV
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <?php if (empty($visits)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-search" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h4 class="mt-3 text-muted">No results found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
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
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
