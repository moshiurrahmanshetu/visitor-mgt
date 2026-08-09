<?php
/**
 * VAMS - Visitor Access Management System
 * User List Page
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

$page_title = 'User List';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';

// Permission check: users.view
require_permission('users.view');

// Pagination settings
$per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Tab: active or deleted
$tab = isset($_GET['tab']) && $_GET['tab'] === 'deleted' ? 'deleted' : 'active';

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['filter_role'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($tab === 'active') {
    $where_conditions[] = 'u.is_deleted = 0';
} else {
    $where_conditions[] = 'u.is_deleted = 1';
}

// Search by name, email, or employee_id
if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.employee_id LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

// Filter by role
if (!empty($filter_role)) {
    $where_conditions[] = 'u.role_id = :role_id';
    $params['role_id'] = $filter_role;
}

// Filter by status (only for active tab)
if ($tab === 'active' && !empty($filter_status)) {
    $where_conditions[] = 'u.status = :status';
    $params['status'] = $filter_status;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $pdo = getDbConnection();
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM users u WHERE " . $where_clause;
    $count_result = fetchOne($pdo, $count_sql, $params);
    $total_records = $count_result['total'] ?? 0;
    $total_pages = ceil($total_records / $per_page);
    
    // Get users with pagination
    $sql = "SELECT u.*, r.role_name 
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE " . $where_clause . "
            ORDER BY u.created_at DESC
            LIMIT :per_page OFFSET :offset";
    
    $params['per_page'] = $per_page;
    $params['offset'] = $offset;
    
    $users = fetchAll($pdo, $sql, $params);
    
    // Get roles for filter dropdown
    $roles = fetchAll($pdo, 
        "SELECT id, role_name FROM roles ORDER BY role_name ASC"
    );
    
} catch (PDOException $e) {
    $error_message = 'Failed to load users. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $users = [];
    $total_records = 0;
    $total_pages = 0;
    $roles = [];
}

// Role badge colors
$role_badges = [
    'Admin' => 'bg-danger',
    'Receptionist' => 'bg-primary',
    'Security' => 'bg-warning',
    'Employee' => 'bg-success'
];

// Status badge colors
$status_badges = [
    'active' => 'bg-success',
    'inactive' => 'bg-secondary'
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
                                <h1 class="page-title">User Management</h1>
                                <p class="text-muted">Manage system users</p>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/modules/users/add.php" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>Add User
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
                
                <!-- Tabs -->
                <div class="row mb-4">
                    <div class="col-12">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'active' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/modules/users/list.php?tab=active<?php echo !empty($search) || !empty($filter_role) || !empty($filter_status) ? '&search=' . urlencode($search) . '&filter_role=' . urlencode($filter_role) . '&filter_status=' . urlencode($filter_status) : ''; ?>">
                                    Active Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'deleted' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/modules/users/list.php?tab=deleted<?php echo !empty($search) || !empty($filter_role) ? '&search=' . urlencode($search) . '&filter_role=' . urlencode($filter_role) : ''; ?>">
                                    Deleted Users
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Search and Filter Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3">
                                    <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                                    
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Search by name, email, or employee ID..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <select class="form-select" name="filter_role">
                                            <option value="">All Roles</option>
                                            <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['id']; ?>" 
                                                    <?php echo ($filter_role === $role['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['role_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <?php if ($tab === 'active'): ?>
                                    <div class="col-md-3">
                                        <select class="form-select" name="filter_status">
                                            <option value="">All Statuses</option>
                                            <option value="active" <?php echo ($filter_status === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($filter_status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-search me-2"></i>Search
                                        </button>
                                    </div>
                                    
                                    <div class="col-md-1">
                                        <a href="<?php echo BASE_URL; ?>/modules/users/list.php?tab=<?php echo $tab; ?>" class="btn btn-outline-secondary w-100">
                                            Clear
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h4 class="mt-3 text-muted">No users found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Avatar</th>
                                                <th>Employee ID</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($user['avatar']): ?>
                                                    <img src="<?php echo ASSETS_URL; ?>/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                                         alt="Avatar" class="rounded-circle" width="35" height="35">
                                                    <?php else: ?>
                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                         style="width:35px;height:35px;">
                                                        <i class="bi bi-person text-white small"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $role_badges[$user['role_name']] ?? 'bg-secondary'; ?>">
                                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $status_badges[$user['status']] ?? 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- View -->
                                                        <a href="<?php echo BASE_URL; ?>/modules/users/view.php?id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-outline-primary" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($tab === 'active'): ?>
                                                        <!-- Edit -->
                                                        <a href="<?php echo BASE_URL; ?>/modules/users/edit.php?id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-outline-secondary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        
                                                        <!-- Reset Password -->
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="confirmResetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                                title="Reset Password">
                                                            <i class="bi bi-key"></i>
                                                        </button>
                                                        
                                                        <?php if ($user['id'] != $current_user_id): ?>
                                                        <!-- Toggle Status -->
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="confirmToggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo $user['status']; ?>')"
                                                                title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $user['status'] === 'active' ? 'dash-circle' : 'check-circle'; ?>"></i>
                                                        </button>
                                                        
                                                        <!-- Delete -->
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                                title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <?php else: ?>
                                                        <!-- Restore -->
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="confirmRestore(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                                title="Restore">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset the password for <strong id="resetPasswordUserName"></strong>?</p>
                <p class="text-muted small">You will set a new password for this user.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="resetPasswordConfirmBtn" class="btn btn-warning">Proceed</a>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusTitle">Toggle User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="toggleStatusMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="toggleStatusConfirmBtn" class="btn btn-info">Confirm</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-muted small">This user will be soft deleted and can be restored later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restore User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restore <strong id="restoreUserName"></strong>?</p>
                <p class="text-muted small">This user will be restored to active status.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="restoreConfirmBtn" class="btn btn-success">Restore</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmResetPassword(userId, userName) {
    document.getElementById('resetPasswordUserName').textContent = userName;
    document.getElementById('resetPasswordConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/users/reset_password.php?id=' + userId;
    
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

function confirmToggleStatus(userId, userName, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    document.getElementById('toggleStatusTitle').textContent = action.charAt(0).toUpperCase() + action.slice(1) + ' User';
    document.getElementById('toggleStatusMessage').textContent = 'Are you sure you want to ' + action + ' ' + userName + '?';
    document.getElementById('toggleStatusConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/users/toggle_status.php?id=' + userId + '&csrf_token=<?php echo $csrf_token; ?>';
    
    const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    modal.show();
}

function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/users/delete.php?id=' + userId + '&csrf_token=<?php echo $csrf_token; ?>';
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function confirmRestore(userId, userName) {
    document.getElementById('restoreUserName').textContent = userName;
    document.getElementById('restoreConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/users/restore.php?id=' + userId + '&csrf_token=<?php echo $csrf_token; ?>';
    
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
