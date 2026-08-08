<?php
/**
 * VAMS - Visitor Access Management System
 * Edit Visitor Page
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

// Role check: Only Admin and Receptionist can edit visitors
requireRole(['Admin', 'Receptionist']);

$page_title = 'Edit Visitor';

$current_user_id = getCurrentUserId();
$visitor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

if ($visitor_id <= 0) {
    $error_message = 'Invalid visitor ID.';
}

// Get visitor data
$visitor = null;
if (empty($error_message)) {
    try {
        $pdo = getDbConnection();
        
        $sql = "SELECT * FROM visitors WHERE id = :id LIMIT 1";
        $visitor = fetchOne($pdo, $sql, ['id' => $visitor_id]);
        
        if (!$visitor) {
            $error_message = 'Visitor not found.';
        } elseif ($visitor['is_deleted']) {
            $error_message = 'Cannot edit deleted visitor. Please restore it first.';
        }
        
    } catch (PDOException $e) {
        $error_message = 'Failed to load visitor details. Please try again later.';
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $error_message .= ' ' . $e->getMessage();
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $id_type = $_POST['id_type'] ?? '';
        $id_number = trim($_POST['id_number'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        
        // Server-side validation
        if (empty($full_name)) {
            $error_message = 'Full name is required.';
        } elseif (strlen($full_name) < 2) {
            $error_message = 'Full name must be at least 2 characters.';
        } elseif (empty($phone)) {
            $error_message = 'Phone number is required.';
        } elseif (!preg_match('/^[\+0-9\s\-\(\)]+$/', $phone)) {
            $error_message = 'Invalid phone number format.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format.';
        } elseif (!empty($emergency_contact) && !preg_match('/^[\+0-9\s\-\(\)]+$/', $emergency_contact)) {
            $error_message = 'Invalid emergency contact format.';
        } else {
            try {
                $pdo = getDbConnection();
                
                // Check for duplicate phone (excluding current visitor)
                $duplicate = fetchOne($pdo, 
                    "SELECT id, full_name FROM visitors WHERE phone = :phone AND id != :id AND is_deleted = 0 LIMIT 1",
                    ['phone' => $phone, 'id' => $visitor_id]
                );
                
                if ($duplicate) {
                    $error_message = 'A visitor with this phone number already exists: ' . htmlspecialchars($duplicate['full_name']);
                } else {
                    // Handle photo upload
                    $photo_filename = $visitor['photo']; // Keep existing photo by default
                    $old_photo = $visitor['photo'];
                    
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['photo'];
                        
                        // Validate file size
                        if ($file['size'] > MAX_FILE_SIZE) {
                            $error_message = 'Photo size exceeds maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
                        }
                        // Validate file type
                        elseif (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
                            $error_message = 'Invalid photo type. Only JPG and PNG images are allowed.';
                        }
                        else {
                            // Get file extension
                            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            
                            if (!in_array($file_extension, ALLOWED_IMAGE_EXTENSIONS)) {
                                $error_message = 'Invalid photo extension. Only .jpg and .png files are allowed.';
                            } else {
                                // Create uploads directory if it doesn't exist
                                $visitors_upload_path = ROOT_PATH . '/assets/uploads/visitors';
                                if (!is_dir($visitors_upload_path)) {
                                    mkdir($visitors_upload_path, 0755, true);
                                }
                                
                                // Generate unique filename
                                $photo_filename = uniqid('visitor_', true) . '.' . $file_extension;
                                $filepath = $visitors_upload_path . '/' . $photo_filename;
                                
                                // Move uploaded file
                                if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                                    $error_message = 'Failed to upload photo. Please try again.';
                                }
                            }
                        }
                    }
                    
                    // If no error, proceed with database update
                    if (empty($error_message)) {
                        $sql = "UPDATE visitors SET 
                                full_name = :full_name, 
                                phone = :phone, 
                                email = :email, 
                                company_name = :company_name, 
                                address = :address, 
                                photo = :photo, 
                                id_type = :id_type, 
                                id_number = :id_number, 
                                emergency_contact = :emergency_contact,
                                updated_at = NOW()
                                WHERE id = :id";
                        
                        $params = [
                            'full_name' => $full_name,
                            'phone' => $phone,
                            'email' => $email ?: null,
                            'company_name' => $company_name ?: null,
                            'address' => $address ?: null,
                            'photo' => $photo_filename,
                            'id_type' => $id_type ?: null,
                            'id_number' => $id_number ?: null,
                            'emergency_contact' => $emergency_contact ?: null,
                            'id' => $visitor_id
                        ];
                        
                        $affected = updateRecord($pdo, $sql, $params);
                        
                        if ($affected > 0) {
                            // Delete old photo if it was replaced
                            if ($old_photo && $old_photo !== $photo_filename) {
                                $old_filepath = $visitors_upload_path . '/' . $old_photo;
                                if (file_exists($old_filepath)) {
                                    unlink($old_filepath);
                                }
                            }
                            
                            // Regenerate CSRF token
                            regenerateCsrfToken();
                            
                            $success_message = 'Visitor updated successfully.';
                            
                            // Redirect to visitor list
                            header('Location: ' . BASE_URL . '/modules/visitors/list.php?success=' . urlencode($success_message));
                            exit;
                        } else {
                            $error_message = 'No changes were made to the visitor.';
                            
                            // Delete newly uploaded photo if no changes
                            if ($photo_filename !== $old_photo && file_exists($visitors_upload_path . '/' . $photo_filename)) {
                                unlink($visitors_upload_path . '/' . $photo_filename);
                            }
                        }
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to update visitor. Please try again later.';
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
                            <h1 class="page-title">Edit Visitor</h1>
                            <p class="text-muted">Update visitor information</p>
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
                
                <?php if ($visitor): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Edit Visitor: <?php echo htmlspecialchars($visitor['visitor_code']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                                       value="<?php echo htmlspecialchars($visitor['full_name']); ?>" 
                                                       required minlength="2">
                                                <div class="invalid-feedback">Please enter full name (minimum 2 characters).</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                                           value="<?php echo htmlspecialchars($visitor['phone']); ?>" 
                                                           required pattern="[\+0-9\s\-\(\)]+">
                                                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($visitor['email'] ?? ''); ?>">
                                                <div class="invalid-feedback">Please enter a valid email address.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company_name" class="form-label">Company Name</label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                                       value="<?php echo htmlspecialchars($visitor['company_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($visitor['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="id_type" class="form-label">ID Type</label>
                                                <select class="form-select" id="id_type" name="id_type">
                                                    <option value="">Select ID Type</option>
                                                    <option value="NID" <?php echo ($visitor['id_type'] === 'NID') ? 'selected' : ''; ?>>National ID (NID)</option>
                                                    <option value="Passport" <?php echo ($visitor['id_type'] === 'Passport') ? 'selected' : ''; ?>>Passport</option>
                                                    <option value="Driving License" <?php echo ($visitor['id_type'] === 'Driving License') ? 'selected' : ''; ?>>Driving License</option>
                                                    <option value="Other" <?php echo ($visitor['id_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="id_number" class="form-label">ID Number</label>
                                                <input type="text" class="form-control" id="id_number" name="id_number" 
                                                       value="<?php echo htmlspecialchars($visitor['id_number'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                        <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact" 
                                               value="<?php echo htmlspecialchars($visitor['emergency_contact'] ?? ''); ?>" 
                                               pattern="[\+0-9\s\-\(\)]+">
                                        <div class="invalid-feedback">Please enter a valid phone number.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="photo" class="form-label">Photo</label>
                                        <input type="file" class="form-control" id="photo" name="photo" 
                                               accept="image/jpeg,image/png,image/jpg">
                                        <div class="form-text">Leave empty to keep current photo. Allowed: JPG, PNG. Maximum size: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB</div>
                                        
                                        <!-- Current Photo Preview -->
                                        <div class="mt-2">
                                            <small class="text-muted">Current Photo:</small>
                                            <?php if ($visitor['photo']): ?>
                                            <img src="<?php echo ASSETS_URL; ?>/uploads/visitors/<?php echo htmlspecialchars($visitor['photo']); ?>" 
                                                 alt="Current Photo" class="img-thumbnail" width="100" id="currentPhotoPreview">
                                            <?php else: ?>
                                            <div class="rounded bg-secondary d-inline-flex align-items-center justify-content-center" 
                                                 style="width: 100px; height: 100px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- New Photo Preview -->
                                        <div id="newPhotoPreviewContainer" class="mt-2" style="display: none;">
                                            <small class="text-muted">New Photo Preview:</small>
                                            <img src="" alt="New Photo Preview" class="img-thumbnail" width="100" id="newPhotoPreview">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/visitors/view.php?id=<?php echo $visitor['id']; ?>" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Update Visitor
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visitor Info</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Visitor Code:</strong> <?php echo htmlspecialchars($visitor['visitor_code']); ?></p>
                                <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($visitor['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($visitor['updated_at'])); ?></p>
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
// Photo preview
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photo');
    const newPhotoPreview = document.getElementById('newPhotoPreview');
    const newPhotoPreviewContainer = document.getElementById('newPhotoPreviewContainer');
    
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                newPhotoPreview.src = e.target.result;
                newPhotoPreviewContainer.style.display = 'block';
            };
            
            reader.readAsDataURL(file);
        } else {
            newPhotoPreviewContainer.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
