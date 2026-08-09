<?php
/**
 * VAMS - Visitor Access Management System
 * Edit Role Permissions Page
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

$page_title = 'Edit Permissions';

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
        $selected_permissions = $_POST['permissions'] ?? [];
        
        try {
            $pdo = getDbConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete all existing permissions for this role
            $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id")->execute(['role_id' => $edit_role_id]);
            
            // Insert new permissions
            if (!empty($selected_permissions)) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
                foreach ($selected_permissions as $permission_id) {
                    $stmt->execute([
                        'role_id' => $edit_role_id,
                        'permission_id' => intval($permission_id)
                    ]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            regenerateCsrfToken();
            $success_message = 'Permissions updated successfully.';
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => $success_message
            ];
            
            header('Location: ' . BASE_URL . '/modules/roles/list.php');
            exit;
            
        } catch (PDOException $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Failed to update permissions. Please try again later.';
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $error_message .= ' ' . $e->getMessage();
            }
        }
    }
}

// Get role data and permissions
try {
    $pdo = getDbConnection();
    
    $role = fetchOne($pdo, 
        "SELECT id, role_name FROM roles WHERE id = :id LIMIT 1",
        ['id' => $role_id]
    );
    
    if (!$role) {
        $error_message = 'Role not found.';
    } else {
        // Get all permissions grouped by module
        $all_permissions = fetchAll($pdo, 
            "SELECT id, permission_key, module_name, description 
             FROM permissions 
             ORDER BY module_name, permission_key"
        );
        
        // Get current role permissions
        $role_permissions = fetchAll($pdo, 
            "SELECT permission_id FROM role_permissions WHERE role_id = :role_id",
            ['role_id' => $role_id]
        );
        
        $role_permission_ids = array_column($role_permissions, 'permission_id');
        
        // Group permissions by module
        $grouped_permissions = [];
        foreach ($all_permissions as $perm) {
            $grouped_permissions[$perm['module_name']][] = $perm;
        }
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load data. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $role = null;
    $grouped_permissions = [];
    $role_permission_ids = [];
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
                            <h1 class="page-title">Edit Permissions</h1>
                            <p class="text-muted">Manage permissions for: <strong><?php echo htmlspecialchars($role['role_name'] ?? ''); ?></strong></p>
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
                
                <?php if ($role && !empty($grouped_permissions)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                    
                                    <div class="accordion" id="permissionsAccordion">
                                        <?php $accordion_index = 0; ?>
                                        <?php foreach ($grouped_permissions as $module_name => $permissions): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button <?php echo $accordion_index === 0 ? '' : 'collapsed'; ?>" 
                                                        type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?php echo $accordion_index; ?>" 
                                                        aria-expanded="<?php echo $accordion_index === 0 ? 'true' : 'false'; ?>"
                                                        aria-controls="collapse<?php echo $accordion_index; ?>">
                                                    <strong><?php echo htmlspecialchars($module_name); ?></strong>
                                                    <span class="badge bg-secondary ms-2"><?php echo count($permissions); ?> permissions</span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $accordion_index; ?>" 
                                                 class="accordion-collapse collapse <?php echo $accordion_index === 0 ? 'show' : ''; ?>" 
                                                 data-bs-parent="#permissionsAccordion">
                                                <div class="accordion-body">
                                                    <div class="row">
                                                        <?php foreach ($permissions as $perm): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       name="permissions[]" 
                                                                       value="<?php echo $perm['id']; ?>"
                                                                       id="perm_<?php echo $perm['id']; ?>"
                                                                       <?php echo in_array($perm['id'], $role_permission_ids) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="perm_<?php echo $perm['id']; ?>">
                                                                    <strong><?php echo htmlspecialchars($perm['permission_key']); ?></strong>
                                                                    <?php if ($perm['description']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($perm['description']); ?></small>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $accordion_index++; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mt-3 d-flex justify-content-between">
                                        <a href="<?php echo BASE_URL; ?>/modules/roles/list.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Save Permissions
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
