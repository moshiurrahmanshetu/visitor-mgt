<?php
/**
 * VAMS - Visitor Access Management System
 * Visitor Pass View/Print Page
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

$page_title = 'Visitor Pass';

$current_user_role = getCurrentUserRole();
$pass_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';

if ($pass_id <= 0) {
    $error_message = 'Invalid pass ID.';
}

try {
    $pdo = getDbConnection();
    
    // Get pass details with visitor and visit info
    $sql = "SELECT vp.*, v.visit_code, v.visit_date, v.expected_time, v.purpose, v.department,
                   vis.full_name as visitor_name, vis.phone as visitor_phone, vis.email as visitor_email, 
                   vis.company_name as visitor_company, vis.photo as visitor_photo,
                   h.full_name as host_name, h.employee_id as host_employee_id
            FROM visitor_passes vp
            INNER JOIN visits v ON vp.visit_id = v.id
            INNER JOIN visitors vis ON v.visitor_id = vis.id
            INNER JOIN users h ON v.host_id = h.id
            WHERE vp.id = :id LIMIT 1";
    
    $pass = fetchOne($pdo, $sql, ['id' => $pass_id]);
    
    if (!$pass) {
        $error_message = 'Pass not found.';
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load pass details. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $pass = null;
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<style>
/* Pass Card Styles */
.pass-card {
    max-width: 400px;
    margin: 0 auto;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.pass-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    text-align: center;
}

.pass-number {
    font-size: 2rem;
    font-weight: bold;
    letter-spacing: 2px;
}

.pass-body {
    padding: 20px;
}

.pass-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid #667eea;
    object-fit: cover;
    margin: 0 auto 15px;
    display: block;
}

.pass-info-row {
    margin-bottom: 10px;
}

.pass-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
}

.pass-value {
    font-size: 1rem;
    color: #212529;
}

.pass-footer {
    background-color: #f8f9fa;
    padding: 10px 20px;
    text-align: center;
    font-size: 0.85rem;
    color: #6c757d;
    border-top: 1px solid #dee2e6;
}

/* Print Styles */
@media print {
    .sidebar, .navbar, .main-content > div > div > div > div > div > div > .page-header,
    .main-content > div > div > div > div > div > div > .d-flex,
    .btn, .alert, .no-print {
        display: none !important;
    }
    
    .main-content {
        padding: 0;
    }
    
    .content-wrapper {
        padding: 0;
    }
    
    .container-fluid {
        padding: 0;
    }
    
    .card {
        border: none;
        box-shadow: none;
    }
    
    .card-body {
        padding: 0;
    }
    
    .pass-card {
        border: 2px solid #000;
        box-shadow: none;
        page-break-inside: avoid;
        margin: 0;
    }
    
    body {
        background: white;
    }
}
</style>

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
                        <div class="page-header d-flex justify-content-between align-items-center no-print">
                            <div>
                                <h1 class="page-title">Visitor Pass</h1>
                                <p class="text-muted">View and print visitor pass</p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" onclick="window.print()">
                                    <i class="bi bi-printer me-2"></i>Print Pass
                                </button>
                                <a href="<?php echo BASE_URL; ?>/modules/checkinout/currently_inside.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                <div class="row no-print">
                    <div class="col-12">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($pass): ?>
                <div class="row mt-4">
                    <div class="col-md-6 mx-auto">
                        <div class="pass-card">
                            <div class="pass-header">
                                <div class="pass-number"><?php echo htmlspecialchars($pass['pass_number']); ?></div>
                                <div class="small">VISITOR PASS</div>
                            </div>
                            
                            <div class="pass-body">
                                <?php if ($pass['visitor_photo']): ?>
                                <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($pass['visitor_photo']); ?>" 
                                     alt="Visitor Photo" class="pass-photo">
                                <?php else: ?>
                                <div class="pass-photo d-flex align-items-center justify-content-center bg-secondary">
                                    <i class="bi bi-person text-white" style="font-size: 3rem;"></i>
                                </div>
                                <?php endif; ?>
                                
                                <h4 class="text-center mb-3"><?php echo htmlspecialchars($pass['visitor_name']); ?></h4>
                                
                                <div class="pass-info-row">
                                    <div class="pass-label">Host</div>
                                    <div class="pass-value"><?php echo htmlspecialchars($pass['host_name']); ?></div>
                                </div>
                                
                                <div class="pass-info-row">
                                    <div class="pass-label">Department</div>
                                    <div class="pass-value"><?php echo htmlspecialchars($pass['department'] ?? 'N/A'); ?></div>
                                </div>
                                
                                <div class="pass-info-row">
                                    <div class="pass-label">Company</div>
                                    <div class="pass-value"><?php echo htmlspecialchars($pass['visitor_company'] ?? 'N/A'); ?></div>
                                </div>
                                
                                <div class="pass-info-row">
                                    <div class="pass-label">Purpose</div>
                                    <div class="pass-value"><?php echo htmlspecialchars($pass['purpose']); ?></div>
                                </div>
                                
                                <div class="pass-info-row">
                                    <div class="pass-label">Check-In Time</div>
                                    <div class="pass-value"><?php echo date('M d, Y H:i', strtotime($pass['check_in_time'])); ?></div>
                                </div>
                                
                                <?php if ($pass['check_out_time']): ?>
                                <div class="pass-info-row">
                                    <div class="pass-label">Check-Out Time</div>
                                    <div class="pass-value"><?php echo date('M d, Y H:i', strtotime($pass['check_out_time'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pass-footer">
                                <div class="small">
                                    <strong><?php echo APP_NAME; ?></strong><br>
                                    <?php echo date('M d, Y'); ?>
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
