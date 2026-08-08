<?php
/**
 * VAMS - Visitor Access Management System
 * View Visitor Details Page
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

$page_title = 'View Visitor';

$visitor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';

if ($visitor_id <= 0) {
    $error_message = 'Invalid visitor ID.';
}

try {
    $pdo = getDbConnection();
    
    // Get visitor details
    $sql = "SELECT v.*, u.full_name as created_by_name 
            FROM visitors v
            LEFT JOIN users u ON v.created_by = u.id
            WHERE v.id = :id LIMIT 1";
    
    $visitor = fetchOne($pdo, $sql, ['id' => $visitor_id]);
    
    if (!$visitor) {
        $error_message = 'Visitor not found.';
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load visitor details. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $visitor = null;
}

$current_user_role = getCurrentUserRole();
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
                                <h1 class="page-title">View Visitor</h1>
                                <p class="text-muted">Visitor details and information</p>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/modules/visitors/list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to List
                                </a>
                                <?php if ($visitor && !$visitor['is_deleted'] && in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/visitors/edit.php?id=<?php echo $visitor['id']; ?>" class="btn btn-primary ms-2">
                                    <i class="bi bi-pencil me-2"></i>Edit Visitor
                                </a>
                                <?php endif; ?>
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
                
                <?php if ($visitor): ?>
                <div class="row">
                    <!-- Visitor Photo Card -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php if ($visitor['photo']): ?>
                                    <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visitor['photo']); ?>" 
                                         alt="Visitor Photo" class="rounded border" width="200" height="200">
                                    <?php else: ?>
                                    <div class="rounded bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                         style="width: 200px; height: 200px;">
                                        <i class="bi bi-person text-white" style="font-size: 4rem;"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h4><?php echo htmlspecialchars($visitor['full_name']); ?></h4>
                                <p class="text-muted mb-1">
                                    <span class="badge bg-info"><?php echo htmlspecialchars($visitor['visitor_code']); ?></span>
                                </p>
                                <p class="text-muted">
                                    <?php if ($visitor['is_deleted']): ?>
                                    <span class="badge bg-danger">Deleted</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </p>
                                
                                <hr>
                                
                                <div class="text-start">
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($visitor['phone']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($visitor['email'] ?? 'N/A'); ?></p>
                                    <p><strong>Company:</strong> <?php echo htmlspecialchars($visitor['company_name'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visitor Details Card -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visitor Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Visitor Code:</strong> <?php echo htmlspecialchars($visitor['visitor_code']); ?></p>
                                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($visitor['full_name']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($visitor['phone']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($visitor['email'] ?? 'N/A'); ?></p>
                                        <p><strong>Company:</strong> <?php echo htmlspecialchars($visitor['company_name'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>ID Type:</strong> <?php echo htmlspecialchars($visitor['id_type'] ?? 'N/A'); ?></p>
                                        <p><strong>ID Number:</strong> <?php echo htmlspecialchars($visitor['id_number'] ?? 'N/A'); ?></p>
                                        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($visitor['emergency_contact'] ?? 'N/A'); ?></p>
                                        <p><strong>Status:</strong> 
                                            <?php if ($visitor['is_deleted']): ?>
                                            <span class="badge bg-danger">Deleted</span>
                                            <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Created By:</strong> <?php echo htmlspecialchars($visitor['created_by_name'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <strong>Address:</strong>
                                    <p class="text-muted"><?php echo htmlspecialchars($visitor['address'] ?? 'N/A'); ?></p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($visitor['created_at'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($visitor['updated_at'])); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($visitor['is_deleted'] && $current_user_role === 'Admin'): ?>
                                <hr>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    This visitor has been deleted. You can restore this visitor if needed.
                                    <a href="<?php echo BASE_URL; ?>/modules/visitors/restore.php?id=<?php echo $visitor['id']; ?>" class="btn btn-sm btn-success ms-2">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Visit History Placeholder -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visit History</h5>
                            </div>
                            <div class="card-body">
                                <!-- TODO Phase 3: Visit history table will be inserted here -->
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Visit history will be available in Phase 3 (Visit Management).
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
