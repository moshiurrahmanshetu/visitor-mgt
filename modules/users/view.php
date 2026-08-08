<?php
/**
 * VAMS - Visitor Access Management System
 * View User Details Page
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

$page_title = 'View User';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    $error_message = 'Invalid user ID.';
}

// Role check: Admin only
if ($current_user_role !== 'Admin') {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Access Denied. You do not have permission to access this page.'
    ];
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get user data
    $user = fetchOne($pdo, 
        "SELECT u.*, r.role_name FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE u.id = :id LIMIT 1",
        ['id' => $user_id]
    );
    
    if (!$user) {
        $error_message = 'User not found.';
    }
    
    // Get activity summary based on role
    $activity_summary = null;
    if ($user && in_array($user['role_name'], ['Receptionist', 'Employee'])) {
        if ($user['role_name'] === 'Receptionist') {
            // Count visitors added and visits created
            $visitors_added = fetchOne($pdo, 
                "SELECT COUNT(*) as count FROM visitors WHERE created_by = :user_id AND is_deleted = 0",
                ['user_id' => $user_id]
            );
            
            $visits_created = fetchOne($pdo, 
                "SELECT COUNT(*) as count FROM visits WHERE created_by = :user_id",
                ['user_id' => $user_id]
            );
            
            $activity_summary = [
                'visitors_added' => $visitors_added['count'] ?? 0,
                'visits_created' => $visits_created['count'] ?? 0
            ];
            
        } elseif ($user['role_name'] === 'Employee') {
            // Count visits hosted, approved, rejected
            $visits_hosted = fetchOne($pdo, 
                "SELECT COUNT(*) as count FROM visits WHERE host_id = :user_id",
                ['user_id' => $user_id]
            );
            
            $visits_approved = fetchOne($pdo, 
                "SELECT COUNT(*) as count FROM visits WHERE host_id = :user_id AND status = 'Approved'",
                ['user_id' => $user_id]
            );
            
            $visits_rejected = fetchOne($pdo, 
                "SELECT COUNT(*) as count FROM visits WHERE host_id = :user_id AND status = 'Rejected'",
                ['user_id' => $user_id]
            );
            
            $activity_summary = [
                'visits_hosted' => $visits_hosted['count'] ?? 0,
                'visits_approved' => $visits_approved['count'] ?? 0,
                'visits_rejected' => $visits_rejected['count'] ?? 0
            ];
        }
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load user data. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $user = null;
    $activity_summary = null;
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
                                <h1 class="page-title">View User Details</h1>
                                <p class="text-muted">User information and activity summary</p>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to List
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
                
                <?php if ($user): ?>
                <div class="row">
                    <div class="col-lg-4">
                        <!-- User Profile Card -->
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if ($user['avatar']): ?>
                                <img src="<?php echo ASSETS_URL; ?>/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                     alt="Avatar" class="rounded-circle mb-3" width="120" height="120">
                                <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3" 
                                     style="width:120px;height:120px;">
                                    <i class="bi bi-person text-white" style="font-size: 3rem;"></i>
                                </div>
                                <?php endif; ?>
                                
                                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></p>
                                
                                <div class="mt-3">
                                    <span class="badge <?php echo $role_badges[$user['role_name']] ?? 'bg-secondary'; ?> fs-6">
                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                    </span>
                                </div>
                                
                                <div class="mt-2">
                                    <span class="badge <?php echo $status_badges[$user['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <!-- User Information Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                                        <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role_name']); ?></p>
                                        <p><strong>Status:</strong> <?php echo ucfirst($user['status']); ?></p>
                                        <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></p>
                                        <p><strong>Updated At:</strong> <?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Summary Card (for Receptionist and Employee) -->
                        <?php if ($activity_summary): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Activity Summary</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($user['role_name'] === 'Receptionist'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stat-card p-3">
                                            <div class="stat-icon bg-primary">
                                                <i class="bi bi-person-plus"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3><?php echo $activity_summary['visitors_added']; ?></h3>
                                                <p class="text-muted mb-0">Visitors Added</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card p-3">
                                            <div class="stat-icon bg-info">
                                                <i class="bi bi-calendar-plus"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3><?php echo $activity_summary['visits_created']; ?></h3>
                                                <p class="text-muted mb-0">Visits Created</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif ($user['role_name'] === 'Employee'): ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="stat-card p-3">
                                            <div class="stat-icon bg-info">
                                                <i class="bi bi-calendar-check"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3><?php echo $activity_summary['visits_hosted']; ?></h3>
                                                <p class="text-muted mb-0">Visits Hosted</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card p-3">
                                            <div class="stat-icon bg-success">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3><?php echo $activity_summary['visits_approved']; ?></h3>
                                                <p class="text-muted mb-0">Approved</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card p-3">
                                            <div class="stat-icon bg-danger">
                                                <i class="bi bi-x-circle"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3><?php echo $activity_summary['visits_rejected']; ?></h3>
                                                <p class="text-muted mb-0">Rejected</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Actions Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="<?php echo BASE_URL; ?>/modules/users/edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="bi bi-pencil me-2"></i>Edit User
                                    </a>
                                    
                                    <a href="<?php echo BASE_URL; ?>/modules/users/reset_password.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-warning">
                                        <i class="bi bi-key me-2"></i>Reset Password
                                    </a>
                                    
                                    <?php if ($user['id'] != $current_user_id): ?>
                                    <a href="#" onclick="confirmToggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo $user['status']; ?>')"
                                       class="btn btn-info">
                                        <i class="bi bi-<?php echo $user['status'] === 'active' ? 'dash-circle' : 'check-circle'; ?> me-2"></i>
                                        <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    
                                    <a href="#" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                       class="btn btn-danger">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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

<script>
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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
