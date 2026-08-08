<?php
/**
 * VAMS - Visitor Access Management System
 * User Registration Page (Admin Only)
 * 
 * This page is a scaffold for future implementation.
 * Currently redirects to dashboard with a message.
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

// Check if user is Admin
requireRole(['Admin']);

$page_title = 'User Registration';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">User Registration</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            User registration functionality will be implemented in a future phase.
                            This page is currently a placeholder for the admin-only user creation feature.
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>/dashboard/index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
