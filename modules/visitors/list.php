<?php
/**
 * VAMS - Visitor Access Management System
 * Visitor List Page
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

$page_title = 'Visitor List';

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
$filter_company = trim($_GET['filter_company'] ?? '');
$filter_id_type = $_GET['filter_id_type'] ?? '';
$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';
$show_deleted = isset($_GET['show_deleted']) && $current_user_role === 'Admin';

// Build WHERE clause
$where_conditions = ['1=1'];
$params = [];

// Search by name, phone, or visitor_code
if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE :search OR phone LIKE :search OR visitor_code LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

// Filter by company
if (!empty($filter_company)) {
    $where_conditions[] = "company_name LIKE :company";
    $params['company'] = '%' . $filter_company . '%';
}

// Filter by ID type
if (!empty($filter_id_type)) {
    $where_conditions[] = "id_type = :id_type";
    $params['id_type'] = $filter_id_type;
}

// Filter by date range
if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(created_at) >= :date_from";
    $params['date_from'] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(created_at) <= :date_to";
    $params['date_to'] = $filter_date_to;
}

// Filter by deleted status
if ($show_deleted) {
    $where_conditions[] = "is_deleted = 1";
} else {
    $where_conditions[] = "is_deleted = 0";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $pdo = getDbConnection();
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM visitors WHERE " . $where_clause;
    $count_result = fetchOne($pdo, $count_sql, $params);
    $total_records = $count_result['total'] ?? 0;
    $total_pages = ceil($total_records / $per_page);
    
    // Get visitors with pagination
    $sql = "SELECT v.id, v.visitor_code, v.full_name, v.phone, v.email, v.company_name, 
                   v.photo, v.id_type, v.is_deleted, v.created_at,
                   u.full_name as created_by_name
            FROM visitors v
            LEFT JOIN users u ON v.created_by = u.id
            WHERE " . $where_clause . "
            ORDER BY v.created_at DESC
            LIMIT :per_page OFFSET :offset";
    
    $params['per_page'] = $per_page;
    $params['offset'] = $offset;
    
    $visitors = fetchAll($pdo, $sql, $params);
    
    // Get unique companies for filter dropdown
    $companies = fetchAll($pdo, 
        "SELECT DISTINCT company_name FROM visitors WHERE company_name IS NOT NULL AND company_name != '' ORDER BY company_name"
    );
    
} catch (PDOException $e) {
    $error_message = 'Failed to load visitors. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $visitors = [];
    $total_records = 0;
    $total_pages = 0;
}
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
                                <h1 class="page-title">Visitor List</h1>
                                <p class="text-muted">Manage and search visitors</p>
                            </div>
                            <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                            <a href="<?php echo BASE_URL; ?>/modules/visitors/add.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Add Visitor
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
                                               placeholder="Name, phone, or code">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter_company" class="form-label">Company</label>
                                        <select class="form-select" id="filter_company" name="filter_company">
                                            <option value="">All Companies</option>
                                            <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo htmlspecialchars($company['company_name']); ?>" 
                                                    <?php echo ($filter_company === $company['company_name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($company['company_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter_id_type" class="form-label">ID Type</label>
                                        <select class="form-select" id="filter_id_type" name="filter_id_type">
                                            <option value="">All Types</option>
                                            <option value="NID" <?php echo ($filter_id_type === 'NID') ? 'selected' : ''; ?>>NID</option>
                                            <option value="Passport" <?php echo ($filter_id_type === 'Passport') ? 'selected' : ''; ?>>Passport</option>
                                            <option value="Driving License" <?php echo ($filter_id_type === 'Driving License') ? 'selected' : ''; ?>>Driving License</option>
                                            <option value="Other" <?php echo ($filter_id_type === 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
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
                
                <!-- Deleted Visitors Toggle (Admin Only) -->
                <?php if ($current_user_role === 'Admin'): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="<?php echo BASE_URL; ?>/modules/visitors/list.php" 
                               class="btn <?php echo !$show_deleted ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Active Visitors
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/visitors/list.php?show_deleted=1" 
                               class="btn <?php echo $show_deleted ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Deleted Visitors
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Visitors Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($visitors)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h4 class="mt-3 text-muted">No visitors found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                                    <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/visitors/add.php" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Add First Visitor
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>Visitor Code</th>
                                                <th>Full Name</th>
                                                <th>Phone</th>
                                                <th>Company</th>
                                                <th>ID Type</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($visitors as $visitor): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($visitor['photo']): ?>
                                                    <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visitor['photo']); ?>" 
                                                         alt="Photo" class="rounded-circle" width="40" height="40">
                                                    <?php else: ?>
                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                         width="40" height="40">
                                                        <i class="bi bi-person text-white"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($visitor['visitor_code']); ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($visitor['full_name']); ?></strong>
                                                    <?php if ($visitor['email']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($visitor['email']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($visitor['company_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($visitor['id_type'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($visitor['is_deleted']): ?>
                                                    <span class="badge bg-danger">Deleted</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($visitor['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?php echo BASE_URL; ?>/modules/visitors/view.php?id=<?php echo $visitor['id']; ?>" 
                                                           class="btn btn-outline-primary" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        
                                                        <?php if (!$visitor['is_deleted'] && in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                                        <a href="<?php echo BASE_URL; ?>/modules/visitors/edit.php?id=<?php echo $visitor['id']; ?>" 
                                                           class="btn btn-outline-secondary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="confirmDelete(<?php echo $visitor['id']; ?>, '<?php echo htmlspecialchars($visitor['full_name']); ?>')"
                                                                title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($visitor['is_deleted'] && $current_user_role === 'Admin'): ?>
                                                        <a href="<?php echo BASE_URL; ?>/modules/visitors/restore.php?id=<?php echo $visitor['id']; ?>" 
                                                           class="btn btn-outline-success" title="Restore">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </a>
                                                        <?php endif; ?>
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
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
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
                                    Showing <?php echo min($per_page, $total_records); ?> of <?php echo $total_records; ?> visitors
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteVisitorName"></strong>?</p>
                <p class="text-muted small">This visitor will be marked as deleted but can be restored later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(visitorId, visitorName) {
    document.getElementById('deleteVisitorName').textContent = visitorName;
    document.getElementById('deleteConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/visitors/delete.php?id=' + visitorId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
