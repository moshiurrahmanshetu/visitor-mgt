<?php
/**
 * VAMS - Visitor Access Management System
 * Edit User Page
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

$page_title = 'Edit User';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';
$success_message = '';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    $error_message = 'Invalid user ID.';
}

// Role check: Admin only
if ($current_user_role !== 'Admin') {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Access Denied. You do not have permission to access this page.'
    ];
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $edit_user_id = intval($_POST['user_id'] ?? 0);
        $employee_id = trim($_POST['employee_id'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $avatar = $_FILES['avatar'] ?? null;
        $remove_avatar = isset($_POST['remove_avatar']) ? 1 : 0;
        
        // Self-protection: Admin cannot change own role or status
        if ($edit_user_id === $current_user_id) {
            // Get current user's role and status
            try {
                $pdo = getDbConnection();
                $current_user = fetchOne($pdo, 
                    "SELECT role_id, status FROM users WHERE id = :id LIMIT 1",
                    ['id' => $current_user_id]
                );
                
                if ($current_user) {
                    if ($role_id != $current_user['role_id']) {
                        $error_message = 'You cannot change your own role. Use Profile settings instead.';
                    } elseif ($status != $current_user['status']) {
                        $error_message = 'You cannot change your own status. Use Profile settings instead.';
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Failed to validate self-protection rules.';
            }
        }
        
        // Server-side validation
        if (!$error_message) {
            if (empty($employee_id)) {
                $error_message = 'Employee ID is required.';
            } elseif (empty($full_name)) {
                $error_message = 'Full Name is required.';
            } elseif (empty($email)) {
                $error_message = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Invalid email format.';
            } elseif ($role_id <= 0) {
                $error_message = 'Role is required.';
            } else {
                try {
                    $pdo = getDbConnection();
                    
                    // Check duplicate employee_id (excluding current user)
                    $existing = fetchOne($pdo, 
                        "SELECT id FROM users WHERE employee_id = :employee_id AND id != :id AND is_deleted = 0 LIMIT 1",
                        ['employee_id' => $employee_id, 'id' => $edit_user_id]
                    );
                    if ($existing) {
                        $error_message = 'Employee ID already exists.';
                    } else {
                        // Check duplicate email (excluding current user)
                        $existing = fetchOne($pdo, 
                            "SELECT id FROM users WHERE email = :email AND id != :id AND is_deleted = 0 LIMIT 1",
                            ['email' => $email, 'id' => $edit_user_id]
                        );
                        if ($existing) {
                            $error_message = 'Email already exists.';
                        } else {
                            // Get current avatar
                            $current_user = fetchOne($pdo, 
                                "SELECT avatar FROM users WHERE id = :id LIMIT 1",
                                ['id' => $edit_user_id]
                            );
                            $current_avatar = $current_user['avatar'] ?? null;
                            
                            // Handle avatar upload or removal
                            $avatar_filename = $current_avatar;
                            
                            if ($remove_avatar && $current_avatar) {
                                // Delete old avatar file
                                $avatar_path = __DIR__ . '/../../assets/uploads/avatars/' . $current_avatar;
                                if (file_exists($avatar_path)) {
                                    unlink($avatar_path);
                                }
                                $avatar_filename = null;
                            }
                            
                            if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
                                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                                $max_size = 2 * 1024 * 1024; // 2MB
                                
                                if (!in_array($avatar['type'], $allowed_types)) {
                                    $error_message = 'Avatar must be JPG or PNG format.';
                                } elseif ($avatar['size'] > $max_size) {
                                    $error_message = 'Avatar must be less than 2MB.';
                                } else {
                                    $upload_dir = __DIR__ . '/../../assets/uploads/avatars/';
                                    if (!is_dir($upload_dir)) {
                                        mkdir($upload_dir, 0755, true);
                                    }
                                    
                                    // Delete old avatar if exists
                                    if ($current_avatar) {
                                        $old_avatar_path = $upload_dir . $current_avatar;
                                        if (file_exists($old_avatar_path)) {
                                            unlink($old_avatar_path);
                                        }
                                    }
                                    
                                    $extension = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                                    $avatar_filename = uniqid() . '_' . time() . '.' . $extension;
                                    $upload_path = $upload_dir . $avatar_filename;
                                    
                                    if (!move_uploaded_file($avatar['tmp_name'], $upload_path)) {
                                        $error_message = 'Failed to upload avatar.';
                                    }
                                }
                            }
                            
                            if (!$error_message) {
                                // Get current user's role before update
                                $current_user_before = fetchOne($pdo, 
                                    "SELECT role_id FROM users WHERE id = :id LIMIT 1",
                                    ['id' => $edit_user_id]
                                );
                                $old_role_id = $current_user_before['role_id'] ?? 0;
                                
                                // Update user
                                $sql = "UPDATE users SET employee_id = :employee_id, full_name = :full_name, 
                                        email = :email, phone = :phone, role_id = :role_id, status = :status, 
                                        avatar = :avatar, updated_at = NOW() 
                                        WHERE id = :id";
                                
                                $affected = updateRecord($pdo, $sql, [
                                    'employee_id' => $employee_id,
                                    'full_name' => $full_name,
                                    'email' => $email,
                                    'phone' => $phone,
                                    'role_id' => $role_id,
                                    'status' => $status,
                                    'avatar' => $avatar_filename,
                                    'id' => $edit_user_id
                                ]);
                                
                                if ($affected > 0) {
                                    regenerateCsrfToken();
                                    
                                    // Check if role was changed
                                    if ($old_role_id != $role_id) {
                                        $success_message = 'User updated successfully. Role changed.';
                                    } else {
                                        $success_message = 'User updated successfully.';
                                    }
                                    
                                    $_SESSION['flash_message'] = [
                                        'type' => 'success',
                                        'message' => $success_message
                                    ];
                                    
                                    header('Location: ' . BASE_URL . '/modules/users/list.php');
                                    exit;
                                } else {
                                    $error_message = 'No changes made to user.';
                                }
                            }
                        }
                    }
                    
                } catch (PDOException $e) {
                    $error_message = 'Failed to update user. Please try again later.';
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        $error_message .= ' ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Get user data and roles
try {
    $pdo = getDbConnection();
    
    $user = fetchOne($pdo, 
        "SELECT u.*, r.role_name FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE u.id = :id LIMIT 1",
        ['id' => $user_id]
    );
    
    if (!$user) {
        $error_message = 'User not found.';
    }
    
    $roles = fetchAll($pdo, "SELECT id, role_name FROM roles ORDER BY role_name ASC");
    
} catch (PDOException $e) {
    $error_message = 'Failed to load user data. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $user = null;
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
                        <div class="page-header">
                            <h1 class="page-title">Edit User</h1>
                            <p class="text-muted">Update user information</p>
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
                
                <?php if ($user): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                                       value="<?php echo htmlspecialchars($user['employee_id']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone</label>
                                                <input type="text" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                                <select class="form-select" id="role_id" name="role_id" required
                                                        <?php echo ($user['id'] === $current_user_id) ? 'disabled' : ''; ?>>
                                                    <option value="">Select Role</option>
                                                    <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo $role['id']; ?>" 
                                                            <?php echo ($user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if ($user['id'] === $current_user_id): ?>
                                                <input type="hidden" name="role_id" value="<?php echo $user['role_id']; ?>">
                                                <small class="text-muted">You cannot change your own role.</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status"
                                                        <?php echo ($user['id'] === $current_user_id) ? 'disabled' : ''; ?>>
                                                    <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                                <?php if ($user['id'] === $current_user_id): ?>
                                                <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                                <small class="text-muted">You cannot change your own status.</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="avatar" class="form-label">Avatar</label>
                                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg">
                                        <small class="text-muted">JPG or PNG, max 2MB. Leave empty to keep current avatar.</small>
                                    </div>
                                    
                                    <?php if ($user['avatar']): ?>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar">
                                            <label class="form-check-label" for="remove_avatar">
                                                Remove current avatar
                                            </label>
                                        </div>
                                        <div class="mt-2">
                                            <img src="<?php echo ASSETS_URL; ?>/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                                 alt="Current Avatar" class="rounded-circle" width="80" height="80">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Update User
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
