<?php
/**
 * VAMS - Visitor Access Management System
 * View Visit Details Page
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

$page_title = 'View Visit';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$visit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';

if ($visit_id <= 0) {
    $error_message = 'Invalid visit ID.';
}

try {
    $pdo = getDbConnection();
    
    // Get visit details with visitor and host info
    $sql = "SELECT v.*, 
                   vis.full_name as visitor_name, vis.phone as visitor_phone, vis.email as visitor_email, 
                   vis.company_name as visitor_company, vis.photo as visitor_photo, vis.address as visitor_address,
                   h.full_name as host_name, h.employee_id as host_employee_id, h.phone as host_phone,
                   a.full_name as approved_by_name
            FROM visits v
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            LEFT JOIN users a ON v.approved_by = a.id
            WHERE v.id = :id LIMIT 1";
    
    $visit = fetchOne($pdo, $sql, ['id' => $visit_id]);
    
    if (!$visit) {
        $error_message = 'Visit not found.';
    }
    
    // Role-based access check: Employee can only view their own visits
    if ($visit && $current_user_role === 'Employee' && $visit['host_id'] != $current_user_id) {
        $error_message = 'You do not have permission to view this visit.';
    }
    
    // Get pass information if visit has been checked in
    $pass = null;
    if ($visit && in_array($visit['status'], ['Checked In', 'Checked Out'])) {
        $pass_sql = "SELECT vp.* FROM visitor_passes vp WHERE vp.visit_id = :visit_id LIMIT 1";
        $pass = fetchOne($pdo, $pass_sql, ['visit_id' => $visit_id]);
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load visit details. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $visit = null;
    $pass = null;
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
                                <h1 class="page-title">View Visit</h1>
                                <p class="text-muted">Visit details and information</p>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/modules/visits/list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to List
                                </a>
                                <?php if ($visit && $visit['status'] === 'Pending' && in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/visits/edit.php?id=<?php echo $visit['id']; ?>" class="btn btn-primary ms-2">
                                    <i class="bi bi-pencil me-2"></i>Edit Visit
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
                
                <?php if ($visit): ?>
                <div class="row">
                    <!-- Visit Status Card -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-3">Visit Status</h5>
                                <span class="badge <?php echo $status_badges[$visit['status']] ?? 'bg-secondary'; ?> fs-4 px-4 py-3">
                                    <?php echo htmlspecialchars($visit['status']); ?>
                                </span>
                                
                                <hr>
                                
                                <p><strong>Visit Code:</strong> <?php echo htmlspecialchars($visit['visit_code']); ?></p>
                                <p><strong>Visit Date:</strong> <?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></p>
                                <p><strong>Expected Time:</strong> <?php echo date('H:i', strtotime($visit['expected_time'])); ?></p>
                                
                                <?php if ($visit['approved_by_name']): ?>
                                <p><strong>Approved By:</strong> <?php echo htmlspecialchars($visit['approved_by_name']); ?></p>
                                <p><strong>Approved At:</strong> <?php echo date('M d, Y H:i', strtotime($visit['approved_at'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($visit['rejection_reason']): ?>
                                <div class="alert alert-danger mt-3">
                                    <strong>Rejection Reason:</strong>
                                    <p class="mb-0"><?php echo htmlspecialchars($visit['rejection_reason']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <?php if ($visit['status'] === 'Pending'): ?>
                                <div class="d-grid gap-2 mt-3">
                                    <?php if (in_array($current_user_role, ['Admin', 'Receptionist']) || ($current_user_role === 'Employee' && $visit['host_id'] == $current_user_id)): ?>
                                    <button type="button" class="btn btn-success" onclick="confirmApprove(<?php echo $visit['id']; ?>)">
                                        <i class="bi bi-check-lg me-2"></i>Approve
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="confirmReject(<?php echo $visit['id']; ?>)">
                                        <i class="bi bi-x-lg me-2"></i>Reject
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmCancel(<?php echo $visit['id']; ?>)">
                                        <i class="bi bi-x me-2"></i>Cancel
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visit Details Card -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visit Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Visit Code:</strong> <?php echo htmlspecialchars($visit['visit_code']); ?></p>
                                        <p><strong>Visit Date:</strong> <?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></p>
                                        <p><strong>Expected Time:</strong> <?php echo date('H:i', strtotime($visit['expected_time'])); ?></p>
                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($visit['department'] ?? 'N/A'); ?></p>
                                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($visit['purpose']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Number of Visitors:</strong> <?php echo $visit['number_of_visitors']; ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge <?php echo $status_badges[$visit['status']] ?? 'bg-secondary'; ?>">
                                                <?php echo htmlspecialchars($visit['status']); ?>
                                            </span>
                                        </p>
                                        <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($visit['created_at'])); ?></p>
                                        <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($visit['updated_at'])); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($visit['notes']): ?>
                                <hr>
                                <div class="mb-3">
                                    <strong>Notes:</strong>
                                    <p class="text-muted"><?php echo htmlspecialchars($visit['notes']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Visitor Information Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visitor Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <?php if ($visit['visitor_photo']): ?>
                                        <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visit['visitor_photo']); ?>" 
                                             alt="Visitor Photo" class="rounded border" width="100" height="100">
                                        <?php else: ?>
                                        <div class="rounded bg-secondary d-flex align-items-center justify-content-center" 
                                             style="width:100px;height:100px;">
                                            <i class="bi bi-person text-white" style="font-size: 2rem;"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5><?php echo htmlspecialchars($visit['visitor_name']); ?></h5>
                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($visit['visitor_phone']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($visit['visitor_email'] ?? 'N/A'); ?></p>
                                        <p class="mb-1"><strong>Company:</strong> <?php echo htmlspecialchars($visit['visitor_company'] ?? 'N/A'); ?></p>
                                        <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($visit['visitor_address'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Host Information Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Host Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($visit['host_name']); ?></p>
                                <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($visit['host_employee_id'] ?? 'N/A'); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($visit['host_phone'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Check-in/Check-out Information -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Check-in / Check-out</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($pass): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Pass Number:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($pass['pass_number']); ?></span></p>
                                        <p><strong>Check-In Time:</strong> <?php echo date('M d, Y H:i', strtotime($pass['check_in_time'])); ?></p>
                                        <?php if ($pass['check_out_time']): ?>
                                        <p><strong>Check-Out Time:</strong> <?php echo date('M d, Y H:i', strtotime($pass['check_out_time'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="<?php echo BASE_URL; ?>/modules/checkinout/pass.php?id=<?php echo $pass['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bi bi-card-text me-2"></i>View/Print Pass
                                        </a>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    This visit has not been checked in yet.
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

<!-- Approve Confirmation Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this visit?</p>
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
                <p>Are you sure you want to reject this visit?</p>
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

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this visit?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="cancelConfirmBtn" class="btn btn-danger">Confirm Cancel</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmApprove(visitId) {
    document.getElementById('approveConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/visits/approve.php?id=' + visitId;
    
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function confirmReject(visitId) {
    document.getElementById('rejection_reason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
    
    document.getElementById('rejectConfirmBtn').onclick = function() {
        const reason = document.getElementById('rejection_reason').value.trim();
        
        if (reason.length < 10) {
            alert('Please provide a reason (minimum 10 characters).');
            return;
        }
        
        window.location.href = '<?php echo BASE_URL; ?>/modules/visits/reject.php?id=' + visitId + '&reason=' + encodeURIComponent(reason);
    };
}

function confirmCancel(visitId) {
    document.getElementById('cancelConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/visits/cancel.php?id=' + visitId;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
