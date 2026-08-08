<?php
/**
 * VAMS - Visitor Access Management System
 * Dashboard Page
 */

// Load configuration FIRST - before session starts
require_once __DIR__ . '/../config/constants.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Load database and auth check
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Dashboard';

// Get current user info
$current_user_name = getCurrentUserName();
$current_user_role = getCurrentUserRole();
$current_user_email = $_SESSION['email'] ?? '';
$current_user_id = getCurrentUserId();

// Get some basic stats (placeholder for future phases)
try {
    $pdo = getDbConnection();
    
    // Total users count
    $total_users = fetchOne($pdo, "SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $total_users_count = $total_users['count'] ?? 0;
    
    // Total roles count
    $total_roles = fetchOne($pdo, "SELECT COUNT(*) as count FROM roles");
    $total_roles_count = $total_roles['count'] ?? 0;
    
} catch (PDOException $e) {
    $total_users_count = 0;
    $total_roles_count = 0;
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!-- Main Content Wrapper -->
<div class="main-wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        
        <!-- Page Content -->
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-header">
                            <h1 class="page-title">Dashboard</h1>
                            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($current_user_name); ?>!</p>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $total_users_count; ?></h3>
                                    <p class="text-muted mb-0">Active Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $total_roles_count; ?></h3>
                                    <p class="text-muted mb-0">User Roles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>0</h3>
                                    <p class="text-muted mb-0">Today's Visits</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>0</h3>
                                    <p class="text-muted mb-0">Pending Approvals</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Info Card -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Your Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($current_user_name); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($current_user_email); ?></p>
                                        <p><strong>Role:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($current_user_role); ?></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($_SESSION['employee_id'] ?? 'N/A'); ?></p>
                                        <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="text-end">
                                    <a href="<?php echo BASE_URL; ?>/modules/profile/profile.php" class="btn btn-primary">
                                        <i class="bi bi-pencil me-2"></i>Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo BASE_URL; ?>/modules/profile/profile.php" class="btn btn-outline-primary">
                                        <i class="bi bi-person me-2"></i>View Profile
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/modules/profile/change_password.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-key me-2"></i>Change Password
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="btn btn-outline-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>VAMS Phase 1:</strong> Authentication System is now active. 
                                    Future phases will include Visitor Management, Visit Management, Approval System, Check-in/out, and Reports.
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong>Application:</strong> <?php echo APP_NAME; ?></p>
                                        <p><strong>Version:</strong> <?php echo APP_VERSION; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                        <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Database:</strong> MySQL</p>
                                        <p><strong>Status:</strong> <span class="badge bg-success">Connected</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
