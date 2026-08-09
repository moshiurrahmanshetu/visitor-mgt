<?php
/**
 * VAMS - Visitor Access Management System
 * Role List Page
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

$page_title = 'Roles';

$error_message = '';
$success_message = '';

// Permission check: roles.manage
require_permission('roles.manage');

try {
    $pdo = getDbConnection();
    
    // Get all roles with user count and permission count
    $sql = "SELECT r.id, r.role_name, r.description,
                   (SELECT COUNT(*) FROM users WHERE role_id = r.id AND is_deleted = 0) as user_count,
                   (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permission_count
            FROM roles r
            ORDER BY r.id ASC";
    
    $roles = fetchAll($pdo, $sql);
    
} catch (PDOException $e) {
    $error_message = 'Failed to load roles. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $roles = [];
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
                        <div class="page-header d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title">Roles</h1>
                                <p class="text-muted">Manage system roles and permissions</p>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/modules/roles/add.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Role
                                </a>
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
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Role Name</th>
                                                <th>Description</th>
                                                <th>Users</th>
                                                <th>Permissions</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><?php echo $role['id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($role['role_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($role['description'] ?? 'No description'); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $role['user_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $role['permission_count']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo BASE_URL; ?>/modules/roles/permissions.php?id=<?php echo $role['id']; ?>" 
                                                           class="btn btn-sm btn-primary" title="Edit Permissions">
                                                            <i class="bi bi-shield-check"></i>
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/modules/roles/edit.php?id=<?php echo $role['id']; ?>" 
                                                           class="btn btn-sm btn-secondary" title="Edit Role Info">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <?php if ($role['user_count'] == 0): ?>
                                                        <a href="#" onclick="confirmDelete(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['role_name']); ?>')"
                                                           class="btn btn-sm btn-danger" title="Delete Role">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                        <?php else: ?>
                                                        <button class="btn btn-sm btn-danger" disabled title="Cannot delete role with assigned users">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteRoleName"></strong>?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(roleId, roleName) {
    document.getElementById('deleteRoleName').textContent = roleName;
    document.getElementById('deleteConfirmBtn').href = '<?php echo BASE_URL; ?>/modules/roles/delete.php?id=' + roleId + '&csrf_token=<?php echo $csrf_token; ?>';
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
