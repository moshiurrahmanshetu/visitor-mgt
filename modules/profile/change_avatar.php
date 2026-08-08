<?php
/**
 * VAMS - Visitor Access Management System
 * Change Avatar Page
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

$page_title = 'Change Avatar';

$current_user_id = getCurrentUserId();
$error_message = '';
$success_message = '';

// Handle avatar upload form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        // Check if file was uploaded
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'Please select a file to upload.';
        } else {
            $file = $_FILES['avatar'];
            
            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $error_message = 'File size exceeds maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
            }
            // Validate file type
            elseif (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
                $error_message = 'Invalid file type. Only JPG and PNG images are allowed.';
            }
            else {
                // Get file extension
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, ALLOWED_IMAGE_EXTENSIONS)) {
                    $error_message = 'Invalid file extension. Only .jpg and .png files are allowed.';
                } else {
                    try {
                        // Create uploads directory if it doesn't exist
                        if (!is_dir(AVATARS_PATH)) {
                            mkdir(AVATARS_PATH, 0755, true);
                        }
                        
                        // Generate unique filename
                        $filename = uniqid('avatar_', true) . '_' . $current_user_id . '.' . $file_extension;
                        $filepath = AVATARS_PATH . '/' . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            // Get old avatar to delete
                            $pdo = getDbConnection();
                            $user = fetchOne($pdo, 
                                "SELECT avatar FROM users WHERE id = :user_id LIMIT 1",
                                ['user_id' => $current_user_id]
                            );
                            
                            // Update database
                            $sql = "UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :user_id";
                            $affected = updateRecord($pdo, $sql, [
                                'avatar' => $filename,
                                'user_id' => $current_user_id
                            ]);
                            
                            if ($affected > 0) {
                                // Delete old avatar file if exists
                                if ($user && $user['avatar'] && file_exists(AVATARS_PATH . '/' . $user['avatar'])) {
                                    unlink(AVATARS_PATH . '/' . $user['avatar']);
                                }
                                
                                // Update session
                                $_SESSION['avatar'] = $filename;
                                
                                // Regenerate CSRF token
                                regenerateCsrfToken();
                                
                                $success_message = 'Avatar updated successfully.';
                            } else {
                                // Delete uploaded file if database update failed
                                unlink($filepath);
                                $error_message = 'Failed to update avatar in database.';
                            }
                        } else {
                            $error_message = 'Failed to upload file. Please try again.';
                        }
                        
                    } catch (PDOException $e) {
                        $error_message = 'Failed to update avatar. Please try again later.';
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            $error_message .= ' ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Get current user data
try {
    $pdo = getDbConnection();
    $user_data = fetchOne($pdo, 
        "SELECT avatar, full_name FROM users WHERE id = :user_id LIMIT 1",
        ['user_id' => $current_user_id]
    );
} catch (PDOException $e) {
    $user_data = [];
}

// Generate CSRF token
$csrf_token = generateCsrfToken();

// Get current avatar URL
$current_avatar_url = $user_data['avatar'] ? ASSETS_URL . '/uploads/avatars/' . $user_data['avatar'] : 'https://via.placeholder.com/150';
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
                            <h1 class="page-title">Change Avatar</h1>
                            <p class="text-muted">Update your profile picture</p>
                        </div>
                    </div>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Upload New Avatar</h5>
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
                                    Allowed file types: JPG, PNG. Maximum file size: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB.
                                </div>
                                
                                <!-- Current Avatar Preview -->
                                <div class="text-center mb-4">
                                    <p class="text-muted">Current Avatar:</p>
                                    <img src="<?php echo $current_avatar_url; ?>" alt="Current Avatar" 
                                         class="rounded-circle border" width="150" height="150" id="currentAvatarPreview">
                                </div>
                                
                                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="avatar" class="form-label">Select New Avatar</label>
                                        <input type="file" class="form-control" id="avatar" name="avatar" 
                                               accept="image/jpeg,image/png,image/jpg" required>
                                        <div class="invalid-feedback">Please select an image file.</div>
                                    </div>
                                    
                                    <!-- New Avatar Preview -->
                                    <div class="mb-3 text-center" id="newAvatarPreviewContainer" style="display: none;">
                                        <p class="text-muted">New Avatar Preview:</p>
                                        <img src="" alt="New Avatar Preview" 
                                             class="rounded-circle border" width="150" height="150" id="newAvatarPreview">
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/profile/profile.php" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-upload me-2"></i>Upload Avatar
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
// Preview selected image
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar');
    const newAvatarPreview = document.getElementById('newAvatarPreview');
    const newAvatarPreviewContainer = document.getElementById('newAvatarPreviewContainer');
    
    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                newAvatarPreview.src = e.target.result;
                newAvatarPreviewContainer.style.display = 'block';
            };
            
            reader.readAsDataURL(file);
        } else {
            newAvatarPreviewContainer.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
