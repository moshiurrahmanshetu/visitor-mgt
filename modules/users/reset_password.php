<?php
/**
 * VAMS - Visitor Access Management System
 * Reset Password Page
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

// Permission check: users.reset_password
require_permission('users.reset_password');

$page_title = 'Reset Password';

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
        $target_user_id = intval($_POST['user_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Server-side validation
        if (empty($password)) {
            $error_message = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $error_message = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error_message = 'Password must contain at least 1 letter and 1 number.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Check if user exists
                $user = fetchOne($pdo, 
                    "SELECT id, full_name FROM users WHERE id = :id LIMIT 1",
                    ['id' => $target_user_id]
                );
                
                if (!$user) {
                    $error_message = 'User not found.';
                } else {
                    // Hash password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id";
                    $affected = updateRecord($pdo, $sql, [
                        'password_hash' => $password_hash,
                        'id' => $target_user_id
                    ]);
                    
                    if ($affected > 0) {
                        regenerateCsrfToken();
                        $success_message = 'Password reset successfully for ' . htmlspecialchars($user['full_name']) . '.';
                        
                        $_SESSION['flash_message'] = [
                            'type' => 'success',
                            'message' => $success_message
                        ];
                        
                        header('Location: ' . BASE_URL . '/modules/users/list.php');
                        exit;
                    } else {
                        $error_message = 'Failed to reset password. Please try again.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to reset password. Please try again later.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $error_message .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

// Get user data
try {
    $pdo = getDbConnection();
    
    $user = fetchOne($pdo, 
        "SELECT id, full_name, employee_id FROM users WHERE id = :id LIMIT 1",
        ['id' => $user_id]
    );
    
    if (!$user) {
        $error_message = 'User not found.';
    }
    
} catch (PDOException $e) {
    $error_message = 'Failed to load user data. Please try again later.';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $error_message .= ' ' . $e->getMessage();
    }
    $user = null;
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
                            <h1 class="page-title">Reset Password</h1>
                            <p class="text-muted">Set a new password for a user</p>
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
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Reset Password for <?php echo htmlspecialchars($user['full_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    This will set a new password for the user. Please inform the user of their new password after resetting.
                                </div>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   required minlength="8">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                <i class="bi bi-eye" id="password-icon"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Min 8 characters, at least 1 letter and 1 number</small>
                                    </div>
                                    
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
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-key me-2"></i>Reset Password
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
