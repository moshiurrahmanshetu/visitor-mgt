<?php
/**
 * VAMS - Visitor Access Management System
 * Add Role Page
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

$page_title = 'Add Role';

$error_message = '';
$success_message = '';

// Permission check: roles.manage
require_permission('roles.manage');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $role_name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // Server-side validation
        if (empty($role_name)) {
            $error_message = 'Role Name is required.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Check duplicate role name
                $existing = fetchOne($pdo, 
                    "SELECT id FROM roles WHERE role_name = :role_name LIMIT 1",
                    ['role_name' => $role_name]
                );
                
                if ($existing) {
                    $error_message = 'Role Name already exists.';
                } else {
                    // Insert role
                    $sql = "INSERT INTO roles (role_name, description) VALUES (:role_name, :description)";
                    $role_id = insertRecord($pdo, $sql, [
                        'role_name' => $role_name,
                        'description' => $description ?: null
                    ]);
                    
                    if ($role_id) {
                        regenerateCsrfToken();
                        $success_message = 'Role added successfully.';
                        
                        $_SESSION['flash_message'] = [
                            'type' => 'success',
                            'message' => $success_message
                        ];
                        
                        // Redirect to permissions page for this new role
                        header('Location: ' . BASE_URL . '/modules/roles/permissions.php?id=' . $role_id);
                        exit;
                    } else {
                        $error_message = 'Failed to add role. Please try again.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to add role. Please try again later.';
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
                            <h1 class="page-title">Add Role</h1>
                            <p class="text-muted">Create a new system role</p>
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
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Role Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="role_name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="role_name" name="role_name" 
                                               value="<?php echo htmlspecialchars($_POST['role_name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/roles/list.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Add Role
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
