<?php
/**
 * VAMS - Visitor Access Management System
 * Report Dashboard Page
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

$page_title = 'Reports Dashboard';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();

// Employee role should not access this page - redirect to their visit list
if ($current_user_role === 'Employee') {
    header('Location: ' . BASE_URL . '/modules/visits/list.php?status=Pending');
    exit;
}

$error_message = '';

// Get report statistics based on role
try {
    $pdo = getDbConnection();
    $today = date('Y-m-d');
    
    // Today's Visitors (all roles)
    $today_visits = fetchOne($pdo, 
        "SELECT COUNT(*) as count FROM visits WHERE visit_date = :today",
        ['today' => $today]
    );
    $today_visits_count = $today_visits['count'] ?? 0;
    
    // Currently Inside (all roles)
    $currently_inside = fetchOne($pdo, 
        "SELECT COUNT(*) as count FROM visits WHERE is_currently_inside = 1",
        []
    );
    $currently_inside_count = $currently_inside['count'] ?? 0;
    
    // Completed Today (all roles)
    $completed_today = fetchOne($pdo, 
        "SELECT COUNT(*) as count FROM visits WHERE status = 'Checked Out' AND visit_date = :today",
        ['today' => $today]
    );
    $completed_today_count = $completed_today['count'] ?? 0;
    
    // Pending Visits (all roles)
    $pending_visits = fetchOne($pdo, 
        "SELECT COUNT(*) as count FROM visits WHERE status = 'Pending'",
        []
    );
    $pending_visits_count = $pending_visits['count'] ?? 0;
    
    // Rejected Visits (Admin/Receptionist only)
    $rejected_visits_count = 0;
    if (in_array($current_user_role, ['Admin', 'Receptionist'])) {
        $rejected_visits = fetchOne($pdo, 
            "SELECT COUNT(*) as count FROM visits WHERE status = 'Rejected'",
            []
        );
        $rejected_visits_count = $rejected_visits['count'] ?? 0;
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load report statistics. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $today_visits_count = 0;
    $currently_inside_count = 0;
    $completed_today_count = 0;
    $pending_visits_count = 0;
    $rejected_visits_count = 0;
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
                        <div class="page-header">
                            <h1 class="page-title">Reports Dashboard</h1>
                            <p class="text-muted">Quick access to visitor statistics and reports</p>
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
                
                <!-- Report Cards -->
                <div class="row">
                    <!-- Today's Visitors -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-calendar-day"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $today_visits_count; ?></h3>
                                    <p class="text-muted mb-0">Today's Visitors</p>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/reports/visitor_history.php?date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-primary w-100">
                                    View Details
                                </a>
                                <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/modules/reports/visitor_history.php?date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-primary w-100">
                                    View Today's List
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Currently Inside -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $currently_inside_count; ?></h3>
                                    <p class="text-muted mb-0">Currently Inside</p>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="<?php echo BASE_URL; ?>/modules/checkinout/currently_inside.php" class="btn btn-sm btn-outline-success w-100">
                                    View List
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Completed Today -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $completed_today_count; ?></h3>
                                    <p class="text-muted mb-0">Completed Today</p>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="<?php echo BASE_URL; ?>/modules/reports/visitor_history.php?date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d'); ?>&status=Checked+Out" class="btn btn-sm btn-outline-info w-100">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Visits -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $pending_visits_count; ?></h3>
                                    <p class="text-muted mb-0">Pending Visits</p>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="<?php echo BASE_URL; ?>/modules/visits/list.php?status=Pending" class="btn btn-sm btn-outline-warning w-100">
                                    View List
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rejected Visits (Admin/Receptionist only) -->
                    <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon bg-danger">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $rejected_visits_count; ?></h3>
                                    <p class="text-muted mb-0">Rejected Visits</p>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="<?php echo BASE_URL; ?>/modules/visits/list.php?status=Rejected" class="btn btn-sm btn-outline-danger w-100">
                                    View List
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Additional Info -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Report Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Available Reports:</strong></p>
                                        <ul>
                                            <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                            <li><a href="<?php echo BASE_URL; ?>/modules/reports/visitor_history.php">Visitor History / Search</a></li>
                                            <li><a href="<?php echo BASE_URL; ?>/modules/reports/date_wise.php">Date-Wise Visitor Report</a></li>
                                            <?php endif; ?>
                                            <li><a href="<?php echo BASE_URL; ?>/modules/checkinout/currently_inside.php">Currently Inside List</a></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Export Options:</strong></p>
                                        <p class="text-muted">CSV export available for Visitor History and Date-Wise reports.</p>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
