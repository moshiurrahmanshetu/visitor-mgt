<?php
/**
 * VAMS - Visitor Access Management System
 * Currently Inside List Page
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

$page_title = 'Currently Inside';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';
$success_message = '';

// Get success message from session
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    if ($flash['type'] === 'success') {
        $success_message = $flash['message'];
    } else {
        $error_message = $flash['message'];
    }
}

try {
    $pdo = getDbConnection();
    
    // Build WHERE clause based on role
    $where_conditions = ['v.is_currently_inside = 1'];
    $params = [];
    
    if ($current_user_role === 'Employee') {
        // Employee sees only their own visits
        $where_conditions[] = 'v.host_id = :user_id';
        $params['user_id'] = $current_user_id;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get currently inside visitors
    $sql = "SELECT v.id, v.visit_code, v.visit_date, v.expected_time, v.purpose, v.department,
                   vis.full_name as visitor_name, vis.phone as visitor_phone, vis.photo as visitor_photo, vis.company_name as visitor_company,
                   h.full_name as host_name, h.employee_id as host_employee_id,
                   vp.id as pass_id, vp.pass_number, vp.check_in_time
            FROM visits v
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            INNER JOIN visitor_passes vp ON v.id = vp.visit_id
            WHERE " . $where_clause . "
            AND vp.check_out_time IS NULL
            ORDER BY vp.check_in_time DESC";
    
    $visitors = fetchAll($pdo, $sql, $params);
    
} catch (PDOException $e) {
    $error_message = 'Failed to load visitors. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $visitors = [];
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
                                <h1 class="page-title">Currently Inside</h1>
                                <p class="text-muted">Visitors currently inside the premises</p>
                            </div>
                            <div>
                                <span class="badge bg-info fs-5"><?php echo count($visitors); ?> Visitors</span>
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
                
                <!-- Visitors Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($visitors)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-person-exclamation" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h4 class="mt-3 text-muted">No visitors currently inside</h4>
                                    <p class="text-muted">All visitors have checked out or no visits are active</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Visitor</th>
                                                <th>Host</th>
                                                <th>Department</th>
                                                <th>Pass Number</th>
                                                <th>Check-In Time</th>
                                                <th>Duration Inside</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($visitors as $visitor): ?>
                                            <tr data-check-in-time="<?php echo $visitor['check_in_time']; ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <?php if ($visitor['visitor_photo']): ?>
                                                            <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visitor['visitor_photo']); ?>" 
                                                                 alt="Visitor" class="rounded-circle" width="35" height="35">
                                                            <?php else: ?>
                                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                                 style="width:35px;height:35px;">
                                                                <i class="bi bi-person text-white small"></i>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($visitor['visitor_name']); ?></strong>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($visitor['visitor_phone']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($visitor['host_name']); ?>
                                                    <?php if ($visitor['host_employee_id']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($visitor['host_employee_id']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($visitor['department'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo htmlspecialchars($visitor['pass_number']); ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($visitor['check_in_time'])); ?></td>
                                                <td>
                                                    <span class="duration-badge badge" data-check-in-time="<?php echo $visitor['check_in_time']; ?>">Calculating...</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- View Pass - All roles -->
                                                        <a href="<?php echo BASE_URL; ?>/modules/checkinout/pass.php?id=<?php echo $visitor['pass_id']; ?>" 
                                                           class="btn btn-outline-primary" title="View Pass">
                                                            <i class="bi bi-card-text"></i>
                                                        </a>
                                                        
                                                        <!-- Check-Out - Admin, Security, Receptionist only -->
                                                        <?php if (in_array($current_user_role, ['Admin', 'Security', 'Receptionist'])): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="confirmCheckOut(<?php echo $visitor['id']; ?>, '<?php echo htmlspecialchars($visitor['visitor_name']); ?>')"
                                                                title="Check-Out">
                                                            <i class="bi bi-box-arrow-right"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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

<!-- Check-Out Confirmation Modal -->
<div class="modal fade" id="checkOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Check-Out Visitor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to check-out <strong id="checkOutVisitorName"></strong>?</p>
                <p class="text-muted small">This will record the check-out time and update the visit status.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="checkOutConfirmBtn" class="btn btn-danger">Check-Out</a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh duration every 60 seconds
function updateDurations() {
    const now = new Date();
    
    document.querySelectorAll('.duration-badge').forEach(badge => {
        const checkInTime = new Date(badge.dataset.checkInTime);
        const diff = now - checkInTime;
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        let durationText = hours + 'h ' + minutes + 'm';
        
        // Color coding
        let badgeClass = 'bg-success';
        if (hours >= 3) {
            badgeClass = 'bg-danger';
        } else if (hours >= 1) {
            badgeClass = 'bg-warning';
        }
        
        badge.className = 'duration-badge badge ' + badgeClass;
        badge.textContent = durationText;
    });
}

// Update immediately on load
updateDurations();

// Update every 60 seconds
setInterval(updateDurations, 60000);

function confirmCheckOut(visitId, visitorName) {
    document.getElementById('checkOutVisitorName').textContent = visitorName;
    document.getElementById('checkOutConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/checkinout/checkout.php?visit_id=' + visitId;
    
    const modal = new bootstrap.Modal(document.getElementById('checkOutModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
