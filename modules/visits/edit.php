<?php
/**
 * VAMS - Visitor Access Management System
 * Edit Visit Page
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

// Permission check: visits.edit
require_permission('visits.edit');

$page_title = 'Edit Visit';

$current_user_id = getCurrentUserId();
$visit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

if ($visit_id <= 0) {
    $error_message = 'Invalid visit ID.';
}

// Get visit data
$visit = null;
if (empty($error_message)) {
    try {
        $pdo = getDbConnection();
        
        $sql = "SELECT v.*, vis.full_name as visitor_name, vis.phone as visitor_phone, vis.visitor_code as visitor_code
                FROM visits v
                INNER JOIN visitors vis ON v.visitor_id = vis.id
                WHERE v.id = :id LIMIT 1";
        
        $visit = fetchOne($pdo, $sql, ['id' => $visit_id]);
        
        if (!$visit) {
            $error_message = 'Visit not found.';
        } elseif ($visit['status'] !== 'Pending') {
            $error_message = 'Only pending visits can be edited.';
        }
        
    } catch (PDOException $e) {
        $error_message = 'Failed to load visit details. Please try again later.';
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $error_message .= ' ' . $e->getMessage();
        }
    }
}

// Get Employee users for host dropdown
try {
    $pdo = getDbConnection();
    $employees = fetchAll($pdo, 
        "SELECT id, full_name, employee_id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Employee') AND status = 'active' ORDER BY full_name ASC"
    );
} catch (PDOException $e) {
    $error_message = 'Failed to load employee list. Please try again later.';
    $employees = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $host_id = intval($_POST['host_id'] ?? 0);
        $department = trim($_POST['department'] ?? '');
        $visit_date = trim($_POST['visit_date'] ?? '');
        $expected_time = trim($_POST['expected_time'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $number_of_visitors = intval($_POST['number_of_visitors'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');
        
        // Server-side validation
        if ($host_id <= 0) {
            $error_message = 'Please select a host.';
        } elseif (empty($visit_date)) {
            $error_message = 'Visit date is required.';
        } elseif (strtotime($visit_date) < strtotime(date('Y-m-d'))) {
            $error_message = 'Visit date cannot be in the past.';
        } elseif (empty($expected_time)) {
            $error_message = 'Expected time is required.';
        } elseif (empty($purpose)) {
            $error_message = 'Purpose is required.';
        } elseif (strlen($purpose) < 3) {
            $error_message = 'Purpose must be at least 3 characters.';
        } elseif ($number_of_visitors < 1) {
            $error_message = 'Number of visitors must be at least 1.';
        } elseif ($number_of_visitors > 50) {
            $error_message = 'Number of visitors cannot exceed 50.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Verify host exists
                $host = fetchOne($pdo, 
                    "SELECT id, full_name FROM users WHERE id = :id LIMIT 1",
                    ['id' => $host_id]
                );
                
                if (!$host) {
                    $error_message = 'Selected host not found.';
                } else {
                    // Update visit
                    $sql = "UPDATE visits SET 
                            host_id = :host_id, 
                            department = :department, 
                            visit_date = :visit_date, 
                            expected_time = :expected_time, 
                            purpose = :purpose, 
                            number_of_visitors = :number_of_visitors, 
                            notes = :notes,
                            updated_at = NOW()
                            WHERE id = :id";
                    
                    $params = [
                        'host_id' => $host_id,
                        'department' => $department ?: null,
                        'visit_date' => $visit_date,
                        'expected_time' => $expected_time,
                        'purpose' => $purpose,
                        'number_of_visitors' => $number_of_visitors,
                        'notes' => $notes ?: null,
                        'id' => $visit_id
                    ];
                    
                    $affected = updateRecord($pdo, $sql, $params);
                    
                    if ($affected > 0) {
                        // Regenerate CSRF token
                        regenerateCsrfToken();
                        
                        $success_message = 'Visit updated successfully.';
                        
                        // Redirect to visit list
                        header('Location: ' . BASE_URL . '/modules/visits/list.php?success=' . urlencode($success_message));
                        exit;
                    } else {
                        $error_message = 'No changes were made to the visit.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to update visit. Please try again later.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $error_message .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

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
                        <div class="page-header">
                            <h1 class="page-title">Edit Visit</h1>
                            <p class="text-muted">Update visit information</p>
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
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Edit Visit: <?php echo htmlspecialchars($visit['visit_code']); ?></h5>
                            </div>
                            <div class="card-body">
                                <!-- Visitor Info (Read-only) -->
                                <div class="mb-4 p-3 bg-light rounded">
                                    <h6 class="mb-2">Visitor (Cannot be changed)</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if ($visit['visitor_photo']): ?>
                                            <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visit['visitor_photo']); ?>" 
                                                 alt="Visitor" class="rounded-circle" width="50" height="50">
                                            <?php else: ?>
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                 style="width:50px;height:50px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($visit['visitor_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($visit['visitor_phone']); ?></small>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($visit['visitor_code']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="host_id" class="form-label">Host (Employee) <span class="text-danger">*</span></label>
                                                <select class="form-select" id="host_id" name="host_id" required>
                                                    <option value="">Select Host</option>
                                                    <?php foreach ($employees as $employee): ?>
                                                    <option value="<?php echo $employee['id']; ?>" 
                                                            <?php echo ($visit['host_id'] == $employee['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                                        <?php if ($employee['employee_id']): ?>
                                                        (<?php echo htmlspecialchars($employee['employee_id']); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">Please select a host.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="department" class="form-label">Department</label>
                                                <input type="text" class="form-control" id="department" name="department" 
                                                       value="<?php echo htmlspecialchars($visit['department'] ?? ''); ?>"
                                                       placeholder="e.g., IT, HR, Finance">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="visit_date" class="form-label">Visit Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="visit_date" name="visit_date" 
                                                       value="<?php echo htmlspecialchars($visit['visit_date']); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>" required>
                                                <div class="invalid-feedback">Please select a valid date (today or future).</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="expected_time" class="form-label">Expected Time <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="expected_time" name="expected_time" 
                                                       value="<?php echo htmlspecialchars($visit['expected_time']); ?>" required>
                                                <div class="invalid-feedback">Please select expected time.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="purpose" class="form-label">Purpose of Visit <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="purpose" name="purpose" 
                                               value="<?php echo htmlspecialchars($visit['purpose']); ?>"
                                               placeholder="e.g., Meeting, Interview, Delivery" required minlength="3">
                                        <div class="invalid-feedback">Please enter purpose (minimum 3 characters).</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="number_of_visitors" class="form-label">Number of Visitors <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="number_of_visitors" name="number_of_visitors" 
                                               value="<?php echo $visit['number_of_visitors']; ?>" min="1" max="50" required>
                                        <div class="invalid-feedback">Please enter a valid number (1-50).</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                   placeholder="Any additional information..."><?php echo htmlspecialchars($visit['notes'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/visits/view.php?id=<?php echo $visit['id']; ?>" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Update Visit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visit Info</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Visit Code:</strong> <?php echo htmlspecialchars($visit['visit_code']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-warning">Pending</span>
                                </p>
                                <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($visit['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($visit['updated_at'])); ?></p>
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
