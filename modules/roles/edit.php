<?php
/**
 * VAMS - Visitor Access Management System
 * Edit Role Page
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

$page_title = 'Edit Role';

$error_message = '';
$success_message = '';

$role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($role_id <= 0) {
    $error_message = 'Invalid role ID.';
}

// Permission check: roles.manage
require_permission('roles.manage');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $edit_role_id = intval($_POST['role_id'] ?? 0);
        $role_name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // Server-side validation
        if (empty($role_name)) {
            $error_message = 'Role Name is required.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Check duplicate role name (excluding current role)
                $existing = fetchOne($pdo, 
                    "SELECT id FROM roles WHERE role_name = :role_name AND id != :id LIMIT 1",
                    ['role_name' => $role_name, 'id' => $edit_role_id]
                );
                
                if ($existing) {
                    $error_message = 'Role Name already exists.';
                } else {
                    // Update role
                    $sql = "UPDATE roles SET role_name = :role_name, description = :description WHERE id = :id";
                    $affected = updateRecord($pdo, $sql, [
                        'role_name' => $role_name,
                        'description' => $description ?: null,
                        'id' => $edit_role_id
                    ]);
                    
                    if ($affected > 0) {
                        regenerateCsrfToken();
                        $success_message = 'Role updated successfully.';
                        
                        $_SESSION['flash_message'] = [
                            'type' => 'success',
                            'message' => $success_message
                        ];
                        
                        header('Location: ' . BASE_URL . '/modules/roles/list.php');
                        exit;
                    } else {
                        $error_message = 'No changes made to role.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to update role. Please try again later.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $error_message .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

// Get role data
try {
    $pdo = getDbConnection();
    
    $role = fetchOne($pdo, 
        "SELECT id, role_name, description FROM roles WHERE id = :id LIMIT 1",
        ['id' => $role_id]
    );
    
    if (!$role) {
        $error_message = 'Role not found.';
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load role data. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $role = null;
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
                            <h1 class="page-title">Edit Role</h1>
                            <p class="text-muted">Edit role information</p>
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
                
                <?php if ($role): ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Role Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="role_name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="role_name" name="role_name" 
                                               value="<?php echo htmlspecialchars($role['role_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/roles/list.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Update Role
                                        </button>
                                    </div>
                                </form>
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
