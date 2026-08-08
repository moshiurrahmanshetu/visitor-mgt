<?php
/**
 * VAMS - Visitor Access Management System
 * Login Page
 */

// Load configuration FIRST - before session starts
require_once __DIR__ . '/../../config/constants.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Load database helpers
require_once __DIR__ . '/../../config/db.php';

// Load CSRF helper functions
require_once __DIR__ . '/../../includes/auth_check.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? true : false;
        
        // Server-side validation
        if (empty($email)) {
            $error_message = 'Email is required.';
        } elseif (empty($password)) {
            $error_message = 'Password is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Get user by email with role information
                $sql = "SELECT u.id, u.employee_id, u.full_name, u.email, u.password_hash, u.role_id, u.avatar, u.status,
                               r.role_name
                        FROM users u
                        INNER JOIN roles r ON u.role_id = r.id
                        WHERE u.email = :email LIMIT 1";
                
                $user = fetchOne($pdo, $sql, ['email' => $email]);
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Check if user is active
                    if ($user['status'] !== 'active') {
                        $error_message = 'Your account is inactive. Please contact administrator.';
                    } else {
                        // Successful login - set session variables
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['employee_id'] = $user['employee_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role_id'] = $user['role_id'];
                        $_SESSION['role_name'] = $user['role_name'];
                        $_SESSION['avatar'] = $user['avatar'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['last_activity'] = time();
                        
                        // Set remember me cookie if requested
                        if ($remember) {
                            $cookie_value = base64_encode($user['id'] . ':' . bin2hex(random_bytes(16)));
                            setcookie('remember_me', $cookie_value, time() + (86400 * 30), '/'); // 30 days
                        }
                        
                        // Regenerate CSRF token
                        regenerateCsrfToken();
                        
                        // Redirect to dashboard or original requested page
                        $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : BASE_URL . '/dashboard/index.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        header('Location: ' . $redirect_url);
                        exit;
                    }
                } else {
                    // Generic error message - don't reveal if email or password is wrong
                    $error_message = 'Invalid email or password.';
                }
                
            } catch (PDOException $e) {
                $error_message = 'Login failed. Please try again later.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $error_message .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

// Generate CSRF token for form
$csrf_token = generateCsrfToken();

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            padding: 30px 30px 10px;
        }
        .login-header i {
            font-size: 3rem;
            color: #667eea;
        }
        .login-body {
            padding: 20px 30px 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="login-header">
                        <i class="bi bi-shield-check"></i>
                        <h3 class="mt-3"><?php echo APP_NAME; ?></h3>
                        <p class="text-muted">Sign in to your account</p>
                    </div>
                    <div class="login-body">
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
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           required autofocus>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback">Please enter your password.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>/modules/auth/forgot_password.php" class="text-decoration-none">
                                Forgot your password?
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form Validation Script -->
    <script>
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
