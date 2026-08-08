<?php
/**
 * VAMS - Visitor Access Management System
 * Change Password Page
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

$page_title = 'Change Password';

$current_user_id = getCurrentUserId();
$error_message = '';
$success_message = '';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Server-side validation
        if (empty($current_password)) {
            $error_message = 'Current password is required.';
        } elseif (empty($new_password)) {
            $error_message = 'New password is required.';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $error_message = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif (empty($confirm_password)) {
            $error_message = 'Please confirm your new password.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New password and confirm password do not match.';
        } elseif ($current_password === $new_password) {
            $error_message = 'New password must be different from current password.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Get current password hash
                $user = fetchOne($pdo, 
                    "SELECT password_hash FROM users WHERE id = :user_id LIMIT 1",
                    ['user_id' => $current_user_id]
                );
                
                if (!$user) {
                    $error_message = 'User not found.';
                } elseif (!password_verify($current_password, $user['password_hash'])) {
                    $error_message = 'Current password is incorrect.';
                } else {
                    // Hash new password
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() 
                            WHERE id = :user_id";
                    
                    $affected = updateRecord($pdo, $sql, [
                        'password_hash' => $new_password_hash,
                        'user_id' => $current_user_id
                    ]);
                    
                    if ($affected > 0) {
                        // Regenerate CSRF token
                        regenerateCsrfToken();
                        
                        $success_message = 'Password changed successfully. Please login with your new password.';
                        
                        // Optional: Force logout after password change
                        // Uncomment the following lines to force logout
                        // session_unset();
                        // session_destroy();
                        // header('Location: ' . BASE_URL . '/modules/auth/login.php');
                        // exit;
                    } else {
                        $error_message = 'Failed to change password. Please try again.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to change password. Please try again later.';
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
                            <h1 class="page-title">Change Password</h1>
                            <p class="text-muted">Update your account password</p>
                        </div>
                    </div>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Change Your Password</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long and include uppercase, lowercase, numbers, and special characters.
                                </div>
                                
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="current_password" name="current_password" 
                                                   required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Please enter your current password.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">
                                                Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters with uppercase, lowercase, number, and special character.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Please confirm your new password.</div>
                                        </div>
                                        <div id="password-match-feedback" class="form-text"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/profile/profile.php" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Change Password
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
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
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

// Real-time password match validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const feedback = document.getElementById('password-match-feedback');
    
    confirmPassword.addEventListener('input', function() {
        if (confirmPassword.value !== newPassword.value) {
            feedback.textContent = 'Passwords do not match.';
            feedback.classList.add('text-danger');
            feedback.classList.remove('text-success');
        } else {
            feedback.textContent = 'Passwords match.';
            feedback.classList.remove('text-danger');
            feedback.classList.add('text-success');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
