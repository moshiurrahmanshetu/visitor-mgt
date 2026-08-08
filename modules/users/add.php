<?php
/**
 * VAMS - Visitor Access Management System
 * Add User Page
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

$page_title = 'Add User';

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$error_message = '';
$success_message = '';

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
        $employee_id = trim($_POST['employee_id'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $avatar = $_FILES['avatar'] ?? null;
        
        // Server-side validation
        if (empty($employee_id)) {
            $error_message = 'Employee ID is required.';
        } elseif (empty($full_name)) {
            $error_message = 'Full Name is required.';
        } elseif (empty($email)) {
            $error_message = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format.';
        } elseif (empty($password)) {
            $error_message = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $error_message = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error_message = 'Password must contain at least 1 letter and 1 number.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif ($role_id <= 0) {
            $error_message = 'Role is required.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Check duplicate employee_id
                $existing = fetchOne($pdo, 
                    "SELECT id FROM users WHERE employee_id = :employee_id AND is_deleted = 0 LIMIT 1",
                    ['employee_id' => $employee_id]
                );
                if ($existing) {
                    $error_message = 'Employee ID already exists.';
                } else {
                    // Check duplicate email
                    $existing = fetchOne($pdo, 
                        "SELECT id FROM users WHERE email = :email AND is_deleted = 0 LIMIT 1",
                        ['email' => $email]
                    );
                    if ($existing) {
                        $error_message = 'Email already exists.';
                    } else {
                        // Handle avatar upload
                        $avatar_filename = null;
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
                                
                                $extension = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                                $avatar_filename = uniqid() . '_' . time() . '.' . $extension;
                                $upload_path = $upload_dir . $avatar_filename;
                                
                                if (!move_uploaded_file($avatar['tmp_name'], $upload_path)) {
                                    $error_message = 'Failed to upload avatar.';
                                }
                            }
                        }
                        
                        if (!$error_message) {
                            // Hash password
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert user
                            $sql = "INSERT INTO users (employee_id, full_name, email, phone, role_id, password_hash, status, avatar, created_at) 
                                    VALUES (:employee_id, :full_name, :email, :phone, :role_id, :password_hash, :status, :avatar, NOW())";
                            
                            $user_id = insertRecord($pdo, $sql, [
                                'employee_id' => $employee_id,
                                'full_name' => $full_name,
                                'email' => $email,
                                'phone' => $phone,
                                'role_id' => $role_id,
                                'password_hash' => $password_hash,
                                'status' => $status,
                                'avatar' => $avatar_filename
                            ]);
                            
                            if ($user_id) {
                                regenerateCsrfToken();
                                $success_message = 'User added successfully.';
                                
                                $_SESSION['flash_message'] = [
                                    'type' => 'success',
                                    'message' => $success_message
                                ];
                                
                                header('Location: ' . BASE_URL . '/modules/users/list.php');
                                exit;
                            } else {
                                $error_message = 'Failed to add user. Please try again.';
                            }
                        }
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to add user. Please try again later.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $error_message .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

// Get roles for dropdown
try {
    $pdo = getDbConnection();
    $roles = fetchAll($pdo, "SELECT id, role_name FROM roles ORDER BY role_name ASC");
} catch (PDOException $e) {
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
                            <h1 class="page-title">Add User</h1>
                            <p class="text-muted">Create a new system user</p>
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
                                <h5 class="card-title mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                                       value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone</label>
                                                <input type="text" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                                <select class="form-select" id="role_id" name="role_id" required>
                                                    <option value="">Select Role</option>
                                                    <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo $role['id']; ?>" 
                                                            <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="password" name="password" 
                                                           required minlength="8">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                        <i class="bi bi-eye" id="password-icon"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Min 8 characters, at least 1 letter and 1 number</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                           required minlength="8">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                                        <i class="bi bi-eye" id="confirm_password-icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="avatar" class="form-label">Avatar (Optional)</label>
                                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg">
                                        <small class="text-muted">JPG or PNG, max 2MB</small>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Add User
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
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Client-side password validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters.');
        return false;
    }
    
    if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
        e.preventDefault();
        alert('Password must contain at least 1 letter and 1 number.');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
