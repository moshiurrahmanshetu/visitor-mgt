<?php
/**
 * VAMS - Visitor Access Management System
 * Add Visitor Page
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

// Role check: Only Admin and Receptionist can add visitors
requireRole(['Admin', 'Receptionist']);

$page_title = 'Add Visitor';

$current_user_id = getCurrentUserId();
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                
                // Check for duplicate phone (non-deleted visitors)
                $duplicate = fetchOne($pdo, 
                    "SELECT id, full_name FROM visitors WHERE phone = :phone AND is_deleted = 0 LIMIT 1",
                    ['phone' => $phone]
                );
                
                if ($duplicate) {
                    $error_message = 'A visitor with this phone number already exists: ' . htmlspecialchars($duplicate['full_name']) . 
                                   '. <a href="' . BASE_URL . '/modules/visitors/view.php?id=' . $duplicate['id'] . '">View existing visitor</a>';
                } else {
                    // Handle photo upload
                    $photo_filename = null;
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
                    
                    // If no error, proceed with database insert
                    if (empty($error_message)) {
                        // Insert visitor without visitor_code first
                        $sql = "INSERT INTO visitors (full_name, phone, email, company_name, address, photo, id_type, id_number, emergency_contact, created_by) 
                                VALUES (:full_name, :phone, :email, :company_name, :address, :photo, :id_type, :id_number, :emergency_contact, :created_by)";
                        
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
                            'created_by' => $current_user_id
                        ];
                        
                        $visitor_id = insertRecord($pdo, $sql, $params);
                        
                        if ($visitor_id) {
                            // Generate visitor_code using the auto-incremented ID
                            $visitor_code = 'VIS-' . str_pad($visitor_id, 6, '0', STR_PAD_LEFT);
                            
                            // Update the record with visitor_code
                            $update_sql = "UPDATE visitors SET visitor_code = :visitor_code WHERE id = :id";
                            updateRecord($pdo, $update_sql, [
                                'visitor_code' => $visitor_code,
                                'id' => $visitor_id
                            ]);
                            
                            // Regenerate CSRF token
                            regenerateCsrfToken();
                            
                            $success_message = 'Visitor added successfully. Visitor Code: ' . $visitor_code;
                            
                            // Redirect to visitor list
                            header('Location: ' . BASE_URL . '/modules/visitors/list.php?success=' . urlencode($success_message));
                            exit;
                        } else {
                            $error_message = 'Failed to add visitor. Please try again.';
                            
                            // Delete uploaded photo if insert failed
                            if ($photo_filename && file_exists($visitors_upload_path . '/' . $photo_filename)) {
                                unlink($visitors_upload_path . '/' . $photo_filename);
                            }
                        }
                    }
                }
                
            } catch (PDOException $e) {
                $error_message = 'Failed to add visitor. Please try again later.';
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
                            <h1 class="page-title">Add Visitor</h1>
                            <p class="text-muted">Register a new visitor</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Visitor Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
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
                                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                                           required pattern="[\+0-9\s\-\(\)]+">
                                                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                                                </div>
                                                <div id="phone-warning" class="form-text text-warning" style="display: none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                                <div class="invalid-feedback">Please enter a valid email address.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company_name" class="form-label">Company Name</label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                                       value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="id_type" class="form-label">ID Type</label>
                                                <select class="form-select" id="id_type" name="id_type">
                                                    <option value="">Select ID Type</option>
                                                    <option value="NID" <?php echo (($_POST['id_type'] ?? '') === 'NID') ? 'selected' : ''; ?>>National ID (NID)</option>
                                                    <option value="Passport" <?php echo (($_POST['id_type'] ?? '') === 'Passport') ? 'selected' : ''; ?>>Passport</option>
                                                    <option value="Driving License" <?php echo (($_POST['id_type'] ?? '') === 'Driving License') ? 'selected' : ''; ?>>Driving License</option>
                                                    <option value="Other" <?php echo (($_POST['id_type'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="id_number" class="form-label">ID Number</label>
                                                <input type="text" class="form-control" id="id_number" name="id_number" 
                                                       value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                        <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact" 
                                               value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>" 
                                               pattern="[\+0-9\s\-\(\)]+">
                                        <div class="invalid-feedback">Please enter a valid phone number.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="photo" class="form-label">Photo</label>
                                        <input type="file" class="form-control" id="photo" name="photo" 
                                               accept="image/jpeg,image/png,image/jpg">
                                        <div class="form-text">Allowed: JPG, PNG. Maximum size: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB</div>
                                        
                                        <!-- Photo Preview -->
                                        <div id="photo-preview-container" class="mt-2" style="display: none;">
                                            <img src="" alt="Photo Preview" class="img-thumbnail" width="150" id="photo-preview">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/visitors/list.php" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Add Visitor
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Instructions</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Fields marked with * are required</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Phone number must be unique</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Visitor code will be auto-generated</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Photo should be clear and recent</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Use valid email format</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Photo preview
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photo-preview');
    const photoPreviewContainer = document.getElementById('photo-preview-container');
    
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreviewContainer.style.display = 'block';
            };
            
            reader.readAsDataURL(file);
        } else {
            photoPreviewContainer.style.display = 'none';
        }
    });
});

// Duplicate phone check via AJAX
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    const phoneWarning = document.getElementById('phone-warning');
    let debounceTimer;
    
    phoneInput.addEventListener('blur', function() {
        const phone = this.value.trim();
        
        if (phone.length >= 3) {
            // Clear previous timer
            clearTimeout(debounceTimer);
            
            // Debounce to avoid too many requests
            debounceTimer = setTimeout(function() {
                fetch('<?php echo BASE_URL; ?>/modules/visitors/check_phone.php?phone=' + encodeURIComponent(phone))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            phoneWarning.innerHTML = '<strong>Warning:</strong> ' + data.message + 
                                '. <a href="' + data.visitor.view_url + '" target="_blank">View existing visitor</a> or ' +
                                '<a href="#" onclick="useExistingVisitor(' + data.visitor.id + '); return false;">Use this visitor</a>';
                            phoneWarning.style.display = 'block';
                        } else {
                            phoneWarning.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error checking phone:', error);
                    });
            }, 500);
        }
    });
});

// Function to use existing visitor (placeholder for future enhancement)
function useExistingVisitor(visitorId) {
    alert('This feature will be implemented in a future phase. For now, please view the existing visitor manually.');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
