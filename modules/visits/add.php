<?php
/**
 * VAMS - Visitor Access Management System
 * Create Visit Page
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

// Permission check: visits.add
require_permission('visits.add');

$page_title = 'Create Visit';

$current_user_id = getCurrentUserId();
$error_message = '';
$success_message = '';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $visitor_id = intval($_POST['visitor_id'] ?? 0);
        $host_id = intval($_POST['host_id'] ?? 0);
        $department = trim($_POST['department'] ?? '');
        $visit_date = trim($_POST['visit_date'] ?? '');
        $expected_time = trim($_POST['expected_time'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $number_of_visitors = intval($_POST['number_of_visitors'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');
        
        // Server-side validation
        if ($visitor_id <= 0) {
            $error_message = 'Please select a visitor.';
        } elseif ($host_id <= 0) {
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
                
                // Verify visitor exists and is not deleted
                $visitor = fetchOne($pdo, 
                    "SELECT id, full_name FROM visitors WHERE id = :id AND is_deleted = 0 LIMIT 1",
                    ['id' => $visitor_id]
                );
                
                if (!$visitor) {
                    $error_message = 'Selected visitor not found or has been deleted.';
                } else {
                    // Verify host exists
                    $host = fetchOne($pdo, 
                        "SELECT id, full_name FROM users WHERE id = :id LIMIT 1",
                        ['id' => $host_id]
                    );
                    
                    if (!$host) {
                        $error_message = 'Selected host not found.';
                    } else {
                        // Insert visit without visit_code first
                        $sql = "INSERT INTO visits (visitor_id, host_id, department, visit_date, expected_time, purpose, number_of_visitors, notes, created_by) 
                                VALUES (:visitor_id, :host_id, :department, :visit_date, :expected_time, :purpose, :number_of_visitors, :notes, :created_by)";
                        
                        $params = [
                            'visitor_id' => $visitor_id,
                            'host_id' => $host_id,
                            'department' => $department ?: null,
                            'visit_date' => $visit_date,
                            'expected_time' => $expected_time,
                            'purpose' => $purpose,
                            'number_of_visitors' => $number_of_visitors,
                            'notes' => $notes ?: null,
                            'created_by' => $current_user_id
                        ];
                        
                        $visit_id = insertRecord($pdo, $sql, $params);
                        
                        if ($visit_id) {
                            // Generate visit_code using the auto-incremented ID
                            $visit_code = 'VST-' . str_pad($visit_id, 6, '0', STR_PAD_LEFT);
                            
                            // Update the record with visit_code
                            $update_sql = "UPDATE visits SET visit_code = :visit_code WHERE id = :id";
                            updateRecord($pdo, $update_sql, [
                                'visit_code' => $visit_code,
                                'id' => $visit_id
                            ]);
                            
                            // Regenerate CSRF token
                            regenerateCsrfToken();
                            
                            $success_message = 'Visit created successfully. Visit Code: ' . $visit_code;
                            
                            // Redirect to visit list
                            header('Location: ' . BASE_URL . '/modules/visits/list.php?success=' . urlencode($success_message));
                            exit;
                        } else {
                            $error_message = 'Failed to create visit. Please try again.';
                        }
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to create visit. Please try again later.';
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
                            <h1 class="page-title">Create Visit</h1>
                            <p class="text-muted">Schedule a new visitor appointment</p>
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
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visit Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <!-- Step 1: Visitor Selection -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Step 1: Select Visitor</label>
                                        
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control" id="visitorSearch" 
                                                   placeholder="Search by name, phone, or visitor code..." autocomplete="off">
                                        </div>
                                        
                                        <div id="visitorSearchResults" class="list-group" style="max-height: 200px; overflow-y: auto; display: none;"></div>
                                        
                                        <div id="selectedVisitorInfo" class="mt-3" style="display: none;">
                                            <input type="hidden" name="visitor_id" id="visitor_id" value="">
                                            <div class="card bg-light">
                                                <div class="card-body py-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <img src="" alt="Visitor Photo" class="rounded-circle" width="50" height="50" id="selectedVisitorPhoto">
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0" id="selectedVisitorName"></h6>
                                                            <small class="text-muted" id="selectedVisitorPhone"></small>
                                                            <br><small class="text-muted" id="selectedVisitorCompany"></small>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="clearSelectedVisitor()">
                                                            <i class="bi bi-x"></i> Clear
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="addNewVisitorLink" class="mt-2" style="display: none;">
                                            <a href="<?php echo BASE_URL; ?>/modules/visitors/add.php" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-person-plus me-1"></i>Add New Visitor
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Step 2: Visit Details -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Step 2: Visit Details</label>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="host_id" class="form-label">Host (Employee) <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="host_id" name="host_id" required>
                                                        <option value="">Select Host</option>
                                                        <?php foreach ($employees as $employee): ?>
                                                        <option value="<?php echo $employee['id']; ?>">
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
                                                           placeholder="e.g., IT, HR, Finance">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="visit_date" class="form-label">Visit Date <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="visit_date" name="visit_date" 
                                                           min="<?php echo date('Y-m-d'); ?>" required>
                                                    <div class="invalid-feedback">Please select a valid date (today or future).</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="expected_time" class="form-label">Expected Time <span class="text-danger">*</span></label>
                                                    <input type="time" class="form-control" id="expected_time" name="expected_time" required>
                                                    <div class="invalid-feedback">Please select expected time.</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="purpose" class="form-label">Purpose of Visit <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="purpose" name="purpose" 
                                                   placeholder="e.g., Meeting, Interview, Delivery" required minlength="3">
                                            <div class="invalid-feedback">Please enter purpose (minimum 3 characters).</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="number_of_visitors" class="form-label">Number of Visitors <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="number_of_visitors" name="number_of_visitors" 
                                                   value="1" min="1" max="50" required>
                                            <div class="invalid-feedback">Please enter a valid number (1-50).</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Notes (Optional)</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                       placeholder="Any additional information..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/visits/list.php" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Create Visit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Instructions</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="bi bi-1-circle text-primary me-2"></i>Search for an existing visitor</li>
                                    <li class="mb-2"><i class="bi bi-2-circle text-primary me-2"></i>If not found, add new visitor first</li>
                                    <li class="mb-2"><i class="bi bi-3-circle text-primary me-2"></i>Select the host (employee)</li>
                                    <li class="mb-2"><i class="bi bi-4-circle text-primary me-2"></i>Fill in visit details</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Visit will be created as "Pending"</li>
                                </ul>
                                
                                <hr>
                                
                                <p class="small text-muted mb-0">
                                    <strong>Note:</strong> The visit will require approval from the host before the visitor can check in.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Visitor search with AJAX
document.addEventListener('DOMContentLoaded', function() {
    const visitorSearch = document.getElementById('visitorSearch');
    const visitorSearchResults = document.getElementById('visitorSearchResults');
    const selectedVisitorInfo = document.getElementById('selectedVisitorInfo');
    const addNewVisitorLink = document.getElementById('addNewVisitorLink');
    let debounceTimer;
    
    visitorSearch.addEventListener('input', function() {
        const search = this.value.trim();
        
        if (search.length >= 2) {
            clearTimeout(debounceTimer);
            
            debounceTimer = setTimeout(function() {
                fetch('<?php echo BASE_URL; ?>/modules/visits/search_visitor.php?search=' + encodeURIComponent(search))
                    .then(response => response.json())
                    .then(data => {
                        if (data.visitors && data.visitors.length > 0) {
                            visitorSearchResults.innerHTML = '';
                            
                            data.visitors.forEach(visitor => {
                                const item = document.createElement('a');
                                item.className = 'list-group-item list-group-item-action';
                                item.href = '#';
                                item.innerHTML = `
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            ${visitor.photo ? 
                                                '<img src="' + visitor.photo + '" class="rounded-circle" width="30" height="30">' : 
                                                '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:30px;height:30px;"><i class="bi bi-person text-white small"></i></div>'
                                            }
                                        </div>
                                        <div>
                                            <strong>${visitor.full_name}</strong>
                                            <br><small class="text-muted">${visitor.visitor_code}</small>
                                        </div>
                                    </div>
                                `;
                                
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    selectVisitor(visitor);
                                });
                                
                                visitorSearchResults.appendChild(item);
                            });
                            
                            visitorSearchResults.style.display = 'block';
                            addNewVisitorLink.style.display = 'none';
                        } else {
                            visitorSearchResults.innerHTML = '<div class="list-group-item text-muted">No visitors found</div>';
                            visitorSearchResults.style.display = 'block';
                            addNewVisitorLink.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error searching visitors:', error);
                    });
            }, 500);
        } else {
            visitorSearchResults.style.display = 'none';
            addNewVisitorLink.style.display = 'none';
        }
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!visitorSearch.contains(e.target) && !visitorSearchResults.contains(e.target)) {
            visitorSearchResults.style.display = 'none';
        }
    });
});

function selectVisitor(visitor) {
    document.getElementById('visitor_id').value = visitor.id;
    document.getElementById('selectedVisitorName').textContent = visitor.full_name;
    document.getElementById('selectedVisitorPhone').textContent = visitor.phone;
    document.getElementById('selectedVisitorCompany').textContent = visitor.company_name || 'No company';
    document.getElementById('selectedVisitorPhoto').src = visitor.photo || 'https://via.placeholder.com/50';
    document.getElementById('selectedVisitorInfo').style.display = 'block';
    document.getElementById('visitorSearchResults').style.display = 'none';
    document.getElementById('visitorSearch').value = '';
}

function clearSelectedVisitor() {
    document.getElementById('visitor_id').value = '';
    document.getElementById('selectedVisitorInfo').style.display = 'none';
    document.getElementById('visitorSearch').value = '';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
