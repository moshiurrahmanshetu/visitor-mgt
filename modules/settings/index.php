<?php
/**
 * VAMS - Visitor Access Management System
 * Site Settings Page
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
require_once __DIR__ . '/../../includes/settings_helper.php';

$page_title = 'Site Settings';

$current_user_role = getCurrentUserRole();
$error_message = '';
$success_message = '';

// Permission check: settings.manage
require_permission('settings.manage');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Get all settings
            $settings = [
                'app_name' => trim($_POST['app_name'] ?? ''),
                'company_name' => trim($_POST['company_name'] ?? ''),
                'company_address' => trim($_POST['company_address'] ?? ''),
                'company_phone' => trim($_POST['company_phone'] ?? ''),
                'company_email' => trim($_POST['company_email'] ?? ''),
                'pass_validity_hours' => intval($_POST['pass_validity_hours'] ?? 12),
                'items_per_page' => intval($_POST['items_per_page'] ?? 15)
            ];
            
            // Server-side validation
            if (empty($settings['app_name'])) {
                $error_message = 'App Name is required.';
            } elseif (!filter_var($settings['company_email'], FILTER_VALIDATE_EMAIL) && !empty($settings['company_email'])) {
                $error_message = 'Company Email must be a valid email address.';
            } elseif ($settings['pass_validity_hours'] < 1 || $settings['pass_validity_hours'] > 72) {
                $error_message = 'Pass Validity Hours must be between 1 and 72.';
            } elseif ($settings['items_per_page'] < 5 || $settings['items_per_page'] > 100) {
                $error_message = 'Items Per Page must be between 5 and 100.';
            } else {
                // Handle logo upload
                $logo = $_FILES['app_logo'] ?? null;
                $remove_logo = isset($_POST['remove_logo']) ? 1 : 0;
                
                if ($remove_logo) {
                    // Delete old logo file
                    $current_logo = get_setting('app_logo');
                    if ($current_logo) {
                        $logo_path = __DIR__ . '/../../assets/uploads/settings/' . $current_logo;
                        if (file_exists($logo_path)) {
                            unlink($logo_path);
                        }
                    }
                    set_setting('app_logo', null);
                }
                
                if ($logo && $logo['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $max_size = 1 * 1024 * 1024; // 1MB
                    
                    if (!in_array($logo['type'], $allowed_types)) {
                        $error_message = 'App Logo must be JPG or PNG format.';
                    } elseif ($logo['size'] > $max_size) {
                        $error_message = 'App Logo must be less than 1MB.';
                    } else {
                        $upload_dir = __DIR__ . '/../../assets/uploads/settings/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Delete old logo if exists
                        $current_logo = get_setting('app_logo');
                        if ($current_logo) {
                            $old_logo_path = $upload_dir . $current_logo;
                            if (file_exists($old_logo_path)) {
                                unlink($old_logo_path);
                            }
                        }
                        
                        $extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
                        $logo_filename = uniqid() . '_' . time() . '.' . $extension;
                        $upload_path = $upload_dir . $logo_filename;
                        
                        if (!move_uploaded_file($logo['tmp_name'], $upload_path)) {
                            $error_message = 'Failed to upload logo.';
                        } else {
                            set_setting('app_logo', $logo_filename);
                        }
                    }
                }
                
                if (!$error_message) {
                    // Update all settings
                    foreach ($settings as $key => $value) {
                        set_setting($key, $value);
                    }
                    
                    regenerateCsrfToken();
                    $success_message = 'Settings saved successfully.';
                    
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => $success_message
                    ];
                    
                    header('Location: ' . BASE_URL . '/modules/settings/index.php');
                    exit;
                }
            }
            
        } catch (PDOException $e) {
            $error_message = 'Failed to save settings. Please try again later.';
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $error_message .= ' ' . $e->getMessage();
            }
        }
    }
}

// Get current settings
$current_settings = [
    'app_name' => get_setting('app_name', 'Visitor Access Management System'),
    'app_logo' => get_setting('app_logo', null),
    'company_name' => get_setting('company_name', ''),
    'company_address' => get_setting('company_address', ''),
    'company_phone' => get_setting('company_phone', ''),
    'company_email' => get_setting('company_email', ''),
    'pass_validity_hours' => get_setting('pass_validity_hours', '12'),
    'items_per_page' => get_setting('items_per_page', '15')
];

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
                            <h1 class="page-title">Site Settings</h1>
                            <p class="text-muted">Configure system-wide settings</p>
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
                                <h5 class="card-title mb-0">General Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="app_name" class="form-label">App Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" 
                                               value="<?php echo htmlspecialchars($current_settings['app_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="app_logo" class="form-label">App Logo</label>
                                        <?php if ($current_settings['app_logo']): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo ASSETS_URL; ?>/uploads/settings/<?php echo htmlspecialchars($current_settings['app_logo']); ?>" 
                                                 alt="Current Logo" style="max-height: 80px;">
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo">
                                            <label class="form-check-label" for="remove_logo">
                                                Remove current logo
                                            </label>
                                        </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="app_logo" name="app_logo" accept="image/jpeg,image/png,image/jpg">
                                        <small class="text-muted">JPG or PNG, max 1MB. Leave empty to keep current logo.</small>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6 class="mb-3">Company Information</h6>
                                    
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" 
                                               value="<?php echo htmlspecialchars($current_settings['company_name']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="company_address" class="form-label">Company Address</label>
                                        <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($current_settings['company_address']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company_phone" class="form-label">Company Phone</label>
                                                <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                                       value="<?php echo htmlspecialchars($current_settings['company_phone']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company_email" class="form-label">Company Email</label>
                                                <input type="email" class="form-control" id="company_email" name="company_email" 
                                                       value="<?php echo htmlspecialchars($current_settings['company_email']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6 class="mb-3">System Configuration</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="pass_validity_hours" class="form-label">Pass Validity Hours</label>
                                                <input type="number" class="form-control" id="pass_validity_hours" name="pass_validity_hours" 
                                                       value="<?php echo htmlspecialchars($current_settings['pass_validity_hours']); ?>" min="1" max="72">
                                                <small class="text-muted">How many hours a visitor pass stays valid (1-72)</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                                <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                                                       value="<?php echo htmlspecialchars($current_settings['items_per_page']); ?>" min="5" max="100">
                                                <small class="text-muted">Default pagination size (5-100)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Save Settings
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
