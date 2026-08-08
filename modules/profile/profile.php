<?php
/**
 * VAMS - Visitor Access Management System
 * Profile Management Page
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

$page_title = 'Profile Management';

$current_user_id = getCurrentUserId();
$error_message = '';
$success_message = '';

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Server-side validation
        if (empty($full_name)) {
            $error_message = 'Full name is required.';
        } elseif (empty($email)) {
            $error_message = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format.';
        } elseif (strlen($full_name) < 2) {
            $error_message = 'Full name must be at least 2 characters.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Check if email is already taken by another user
                $check_email = fetchOne($pdo, 
                    "SELECT id FROM users WHERE email = :email AND id != :user_id LIMIT 1",
                    ['email' => $email, 'user_id' => $current_user_id]
                );
                
                if ($check_email) {
                    $error_message = 'Email is already in use by another user.';
                } else {
                    // Update user profile
                    $sql = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, updated_at = NOW() 
                            WHERE id = :user_id";
                    
                    $params = [
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'user_id' => $current_user_id
                    ];
                    
                    $affected = updateRecord($pdo, $sql, $params);
                    
                    if ($affected > 0) {
                        // Update session variables
                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['email'] = $email;
                        
                        // Regenerate CSRF token
                        regenerateCsrfToken();
                        
                        $success_message = 'Profile updated successfully.';
                    } else {
                        $error_message = 'No changes were made to your profile.';
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to update profile. Please try again later.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $error_message .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

// Get current user data from database
try {
    $pdo = getDbConnection();
    $user_data = fetchOne($pdo, 
        "SELECT id, employee_id, full_name, email, phone, avatar, role_id, status, created_at 
         FROM users WHERE id = :user_id LIMIT 1",
        ['user_id' => $current_user_id]
    );
} catch (PDOException $e) {
    $user_data = [];
}

// Generate CSRF token
$csrf_token = generateCsrfToken();

// Get avatar URL
$avatar_url = $user_data['avatar'] ? ASSETS_URL . '/uploads/avatars/' . $user_data['avatar'] : 'https://via.placeholder.com/150';
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
                            <h1 class="page-title">Profile Management</h1>
                            <p class="text-muted">View and update your profile information</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-4">
                        <!-- Profile Card -->
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <img src="<?php echo $avatar_url; ?>" alt="Profile Avatar" 
                                         class="rounded-circle" width="120" height="120" id="currentAvatar">
                                </div>
                                <h4><?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                                <span class="badge bg-primary"><?php echo htmlspecialchars(getCurrentUserRole()); ?></span>
                                
                                <hr>
                                
                                <div class="text-start">
                                    <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($user_data['employee_id'] ?? 'N/A'); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user_data['phone'] ?? 'N/A'); ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                    <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                                </div>
                                
                                <div class="d-grid gap-2 mt-3">
                                    <a href="<?php echo BASE_URL; ?>/modules/profile/change_avatar.php" class="btn btn-outline-primary">
                                        <i class="bi bi-camera me-2"></i>Change Avatar
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/modules/profile/change_password.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-key me-2"></i>Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <!-- Edit Profile Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Edit Profile</h5>
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
                                
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" 
                                               required minlength="2">
                                        <div class="invalid-feedback">Please enter your full name (minimum 2 characters).</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter a valid email address.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                               pattern="[+0-9\s\-\(\)]+">
                                        <div class="invalid-feedback">Please enter a valid phone number.</div>
                                        <div class="form-text">Optional - Include country code if applicable</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['employee_id'] ?? ''); ?>" 
                                               disabled>
                                        <div class="form-text">Employee ID cannot be changed</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/dashboard/index.php" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Save Changes
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
