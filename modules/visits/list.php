<?php
/**
 * VAMS - Visitor Access Management System
 * Visit List Page
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

$page_title = 'Visit List';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';
$success_message = '';

// Get success message from URL
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Pagination settings
$per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['filter_status'] ?? '';
$filter_host = isset($_GET['filter_host']) ? intval($_GET['filter_host']) : 0;
$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';

// Build WHERE clause
$where_conditions = ['1=1'];
$params = [];

// Role-based visibility: Employee sees only their own visits
if ($current_user_role === 'Employee') {
    $where_conditions[] = "v.host_id = :current_user_id";
    $params['current_user_id'] = $current_user_id;
}

// Search by visit_code or visitor name
if (!empty($search)) {
    $where_conditions[] = "(v.visit_code LIKE :search OR vis.full_name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

// Filter by status
if (!empty($filter_status)) {
    $where_conditions[] = "v.status = :status";
    $params['status'] = $filter_status;
}

// Filter by host (not shown to Employee role)
if ($current_user_role !== 'Employee' && $filter_host > 0) {
    $where_conditions[] = "v.host_id = :host_id";
    $params['host_id'] = $filter_host;
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
    $sql = "SELECT v.*, vis.full_name as visitor_name, vis.phone as visitor_phone, vis.photo as visitor_photo, vis.company_name as visitor_company,
                   h.full_name as host_name, h.employee_id as host_employee_id,
                   a.full_name as approved_by_name
            FROM visits v
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            LEFT JOIN users a ON v.approved_by = a.id
            WHERE " . $where_clause . "
            ORDER BY v.visit_date DESC, v.expected_time DESC
            LIMIT :per_page OFFSET :offset";
    
    $params['per_page'] = $per_page;
    $params['offset'] = $offset;
    
    $visits = fetchAll($pdo, $sql, $params);
    
    // Get hosts for filter dropdown (Admin/Receptionist/Security only)
    $hosts = [];
    if ($current_user_role !== 'Employee') {
        $hosts = fetchAll($pdo, 
            "SELECT id, full_name, employee_id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Employee') ORDER BY full_name ASC"
        );
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load visits. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
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
                                <h1 class="page-title">Visit List</h1>
                                <p class="text-muted">Manage and track visits</p>
                            </div>
                            <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                            <a href="<?php echo BASE_URL; ?>/modules/visits/add.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Create Visit
                            </a>
                            <?php endif; ?>
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
                
                <?php if ($success_message): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               placeholder="Visit code or visitor name">
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
                                    
                                    <?php if ($current_user_role !== 'Employee'): ?>
                                    <div class="col-md-2">
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
                                    <?php endif; ?>
                                    
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
                                    
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Visits Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($visits)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-calendar-check" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h4 class="mt-3 text-muted">No visits found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                                    <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/visits/add.php" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Create First Visit
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Visit Code</th>
                                                <th>Visitor</th>
                                                <th>Host</th>
                                                <th>Department</th>
                                                <th>Visit Date</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($visits as $visit): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($visit['visit_code']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <?php if ($visit['visitor_photo']): ?>
                                                            <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visit['visitor_photo']); ?>" 
                                                                 alt="Visitor" class="rounded-circle" width="35" height="35">
                                                            <?php else: ?>
                                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                                 style="width:35px;height:35px;">
                                                                <i class="bi bi-person text-white small"></i>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($visit['visitor_name']); ?></strong>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($visit['visitor_phone']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($visit['host_name']); ?>
                                                    <?php if ($visit['host_employee_id']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($visit['host_employee_id']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($visit['department'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($visit['expected_time'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $status_badges[$visit['status']] ?? 'bg-secondary'; ?>">
                                                        <?php echo htmlspecialchars($visit['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- View - All roles -->
                                                        <a href="<?php echo BASE_URL; ?>/modules/visits/view.php?id=<?php echo $visit['id']; ?>" 
                                                           class="btn btn-outline-primary" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        
                                                        <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                                            <!-- Edit - Only if Pending -->
                                                            <?php if ($visit['status'] === 'Pending'): ?>
                                                            <a href="<?php echo BASE_URL; ?>/modules/visits/edit.php?id=<?php echo $visit['id']; ?>" 
                                                               class="btn btn-outline-secondary" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Cancel - Not if Checked Out -->
                                                            <?php if ($visit['status'] !== 'Checked Out'): ?>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="confirmCancel(<?php echo $visit['id']; ?>, '<?php echo htmlspecialchars($visit['visit_code']); ?>')"
                                                                    title="Cancel">
                                                                <i class="bi bi-x"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Approve/Reject - Only if Pending -->
                                                            <?php if ($visit['status'] === 'Pending'): ?>
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="confirmApprove(<?php echo $visit['id']; ?>, '<?php echo htmlspecialchars($visit['visit_code']); ?>')"
                                                                    title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-warning" 
                                                                    onclick="confirmReject(<?php echo $visit['id']; ?>, '<?php echo htmlspecialchars($visit['visit_code']); ?>')"
                                                                    title="Reject">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($current_user_role === 'Employee' && $visit['host_id'] == $current_user_id): ?>
                                                            <!-- Approve/Reject - Only if Pending and host is self -->
                                                            <?php if ($visit['status'] === 'Pending'): ?>
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="confirmApprove(<?php echo $visit['id']; ?>, '<?php echo htmlspecialchars($visit['visit_code']); ?>')"
                                                                    title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-warning" 
                                                                    onclick="confirmReject(<?php echo $visit['id']; ?>, '<?php echo htmlspecialchars($visit['visit_code']); ?>')"
                                                                    title="Reject">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Security - View only, no actions -->
                                                    </div>
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
                                    Showing <?php echo min($per_page, $total_records); ?> of <?php echo $total_records; ?> visits
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

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel visit <strong id="cancelVisitCode"></strong>?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="cancelConfirmBtn" class="btn btn-danger">Confirm Cancel</a>
            </div>
        </div>
    </div>
</div>

<!-- Approve Confirmation Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve visit <strong id="approveVisitCode"></strong>?</p>
                <p class="text-muted small">The visitor will be allowed to check in.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="approveConfirmBtn" class="btn btn-success">Approve</a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Confirmation Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject visit <strong id="rejectVisitCode"></strong>?</p>
                <div class="mb-3">
                    <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejection_reason" rows="3" required minlength="10" 
                              placeholder="Please provide a reason for rejection (minimum 10 characters)"></textarea>
                    <div class="invalid-feedback">Please provide a reason (minimum 10 characters).</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="rejectConfirmBtn" class="btn btn-warning">Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmCancel(visitId, visitCode) {
    document.getElementById('cancelVisitCode').textContent = visitCode;
    document.getElementById('cancelConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/visits/cancel.php?id=' + visitId;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function confirmApprove(visitId, visitCode) {
    document.getElementById('approveVisitCode').textContent = visitCode;
    document.getElementById('approveConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/visits/approve.php?id=' + visitId;
    
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function confirmReject(visitId, visitCode) {
    document.getElementById('rejectVisitCode').textContent = visitCode;
    document.getElementById('rejection_reason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
    
    // Set up confirm button
    document.getElementById('rejectConfirmBtn').onclick = function() {
        const reason = document.getElementById('rejection_reason').value.trim();
        
        if (reason.length < 10) {
            alert('Please provide a reason (minimum 10 characters).');
            return;
        }
        
        // Submit with reason
        window.location.href = '<?php echo BASE_URL; ?>/modules/visits/reject.php?id=' + visitId + '&reason=' + encodeURIComponent(reason);
    };
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
