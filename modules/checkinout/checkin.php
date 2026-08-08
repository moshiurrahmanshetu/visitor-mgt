<?php
/**
 * VAMS - Visitor Access Management System
 * Check-In Page
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

// Role check: Only Admin, Security, and Receptionist can check-in visitors
requireRole(['Admin', 'Security', 'Receptionist']);

$page_title = 'Check-In Visitor';

$current_user_id = getCurrentUserId();
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $visit_id = intval($_POST['visit_id'] ?? 0);
        
        if ($visit_id <= 0) {
            $error_message = 'Invalid visit ID.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Get visit details
                $visit = fetchOne($pdo, 
                    "SELECT v.*, vis.full_name as visitor_name, vis.photo as visitor_photo, vis.company_name as visitor_company,
                            h.full_name as host_name
                     FROM visits v
                     INNER JOIN visitors vis ON v.visitor_id = vis.id
                     INNER JOIN users h ON v.host_id = h.id
                     WHERE v.id = :id LIMIT 1",
                    ['id' => $visit_id]
                );
                
                if (!$visit) {
                    $error_message = 'Visit not found.';
                } elseif ($visit['is_currently_inside'] == 1) {
                    $error_message = 'This visitor is already checked in.';
                } elseif ($visit['status'] !== 'Approved') {
                    $error_message = 'Cannot check-in visitor. Visit status is: ' . htmlspecialchars($visit['status']);
                } elseif ($visit['visit_date'] !== date('Y-m-d')) {
                    $error_message = 'Cannot check-in visitor. Visit date is not today.';
                } else {
                    // Insert into visitor_passes
                    $sql = "INSERT INTO visitor_passes (visit_id, check_in_time, checked_in_by) 
                            VALUES (:visit_id, NOW(), :checked_in_by)";
                    
                    $pass_id = insertRecord($pdo, $sql, [
                        'visit_id' => $visit_id,
                        'checked_in_by' => $current_user_id
                    ]);
                    
                    if ($pass_id) {
                        // Generate pass_number using the auto-incremented ID
                        $pass_number = 'PASS-' . str_pad($pass_id, 6, '0', STR_PAD_LEFT);
                        
                        // Update the record with pass_number
                        $update_sql = "UPDATE visitor_passes SET pass_number = :pass_number WHERE id = :id";
                        updateRecord($pdo, $update_sql, [
                            'pass_number' => $pass_number,
                            'id' => $pass_id
                        ]);
                        
                        // Update visit status and is_currently_inside
                        $update_visit_sql = "UPDATE visits SET status = 'Checked In', is_currently_inside = 1, updated_at = NOW() WHERE id = :id";
                        updateRecord($pdo, $update_visit_sql, ['id' => $visit_id]);
                        
                        // Regenerate CSRF token
                        regenerateCsrfToken();
                        
                        $success_message = 'Visitor checked in successfully. Pass: ' . $pass_number;
                        
                        // Redirect to pass view
                        header('Location: ' . BASE_URL . '/modules/checkinout/pass.php?id=' . $pass_id);
                        exit;
                    } else {
                        $error_message = 'Failed to check-in visitor. Please try again.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to check-in visitor. Please try again later.';
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
                            <h1 class="page-title">Check-In Visitor</h1>
                            <p class="text-muted">Check-in approved visitors for today</p>
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
                    <div class="col-lg-6">
                        <!-- Search Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Search Approved Visit</h5>
                            </div>
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="visitSearch" 
                                           placeholder="Search by visit code, visitor name, or phone..." autocomplete="off">
                                </div>
                                
                                <div id="visitSearchResults" class="list-group" style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Only approved visits for today will appear in search results.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <!-- Instructions Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Instructions</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="bi bi-1-circle text-primary me-2"></i>Search for approved visit by code, name, or phone</li>
                                    <li class="mb-2"><i class="bi bi-2-circle text-primary me-2"></i>Select the visit from search results</li>
                                    <li class="mb-2"><i class="bi bi-3-circle text-primary me-2"></i>Review visitor information</li>
                                    <li class="mb-2"><i class="bi bi-4-circle text-primary me-2"></i>Confirm check-in to generate pass</li>
                                    <li class="mb-2"><i class="bi-5-circle text-success me-2"></i>Print visitor pass</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Confirmation Card (Hidden by default) -->
                <div id="confirmationCard" class="row mt-4" style="display: none;">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Confirm Check-In</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="visit_id" id="visit_id" value="">
                                    
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <img src="" alt="Visitor Photo" class="rounded border" width="120" height="120" id="visitorPhoto">
                                        </div>
                                        <div class="col-md-8">
                                            <h5 id="visitorName"></h5>
                                            <p class="mb-1"><strong>Phone:</strong> <span id="visitorPhone"></span></p>
                                            <p class="mb-1"><strong>Company:</strong> <span id="visitorCompany"></span></p>
                                            <p class="mb-1"><strong>Visit Code:</strong> <span id="visitCode" class="badge bg-info"></span></p>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Host:</strong> <span id="hostName"></span></p>
                                            <p><strong>Department:</strong> <span id="department"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Purpose:</strong> <span id="purpose"></span></p>
                                            <p><strong>Expected Time:</strong> <span id="expectedTime"></span></p>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearSelection()">Cancel</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-lg me-2"></i>Confirm Check-In
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const visitSearch = document.getElementById('visitSearch');
    const visitSearchResults = document.getElementById('visitSearchResults');
    const confirmationCard = document.getElementById('confirmationCard');
    let debounceTimer;
    
    visitSearch.addEventListener('input', function() {
        const search = this.value.trim();
        
        if (search.length >= 2) {
            clearTimeout(debounceTimer);
            
            debounceTimer = setTimeout(function() {
                fetch('<?php echo BASE_URL; ?>/modules/checkinout/search_visit.php?search=' + encodeURIComponent(search) + '&mode=checkin')
                    .then(response => response.json())
                    .then(data => {
                        if (data.visits && data.visits.length > 0) {
                            visitSearchResults.innerHTML = '';
                            
                            data.visits.forEach(visit => {
                                const item = document.createElement('a');
                                item.className = 'list-group-item list-group-item-action';
                                item.href = '#';
                                item.innerHTML = `
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            ${visit.visitor_photo ? 
                                                '<img src="' + visit.visitor_photo + '" class="rounded-circle" width="30" height="30">' : 
                                                '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:30px;height:30px;"><i class="bi bi-person text-white small"></i></div>'
                                            }
                                        </div>
                                        <div>
                                            <strong>${visit.visitor_name}</strong>
                                            <br><small class="text-muted">${visit.visit_code} - ${visit.host_name}</small>
                                        </div>
                                    </div>
                                `;
                                
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    selectVisit(visit);
                                });
                                
                                visitSearchResults.appendChild(item);
                            });
                            
                            visitSearchResults.style.display = 'block';
                        } else {
                            visitSearchResults.innerHTML = '<div class="list-group-item text-muted">No approved visits found for today</div>';
                            visitSearchResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error searching visits:', error);
                    });
            }, 500);
        } else {
            visitSearchResults.style.display = 'none';
        }
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!visitSearch.contains(e.target) && !visitSearchResults.contains(e.target)) {
            visitSearchResults.style.display = 'none';
        }
    });
});

function selectVisit(visit) {
    document.getElementById('visit_id').value = visit.id;
    document.getElementById('visitorName').textContent = visit.visitor_name;
    document.getElementById('visitorPhone').textContent = visit.visitor_phone;
    document.getElementById('visitorCompany').textContent = visit.visitor_company || 'N/A';
    document.getElementById('visitCode').textContent = visit.visit_code;
    document.getElementById('visitorPhoto').src = visit.visitor_photo || 'https://via.placeholder.com/120';
    document.getElementById('hostName').textContent = visit.host_name;
    document.getElementById('department').textContent = visit.department || 'N/A';
    document.getElementById('purpose').textContent = visit.purpose;
    document.getElementById('expectedTime').textContent = visit.expected_time;
    
    document.getElementById('confirmationCard').style.display = 'block';
    document.getElementById('visitSearchResults').style.display = 'none';
    document.getElementById('visitSearch').value = '';
}

function clearSelection() {
    document.getElementById('visit_id').value = '';
    document.getElementById('confirmationCard').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
