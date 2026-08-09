<?php
/**
 * VAMS Installer - Step 3: Admin Account Setup
 */

// Load configuration for session name
require_once __DIR__ . '/../config/constants.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if already installed
$lockFile = __DIR__ . '/../config/installed.lock';
if (file_exists($lockFile)) {
    require_once __DIR__ . '/step_already_installed.php';
    exit;
}

// Initialize session for installer data
if (!isset($_SESSION['installer'])) {
    $_SESSION['installer'] = [];
}

// Process form submission if coming from Step 2
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['installer']['db_host'] = $_POST['db_host'] ?? '';
    $_SESSION['installer']['db_port'] = $_POST['db_port'] ?? '';
    $_SESSION['installer']['db_name'] = $_POST['db_name'] ?? '';
    $_SESSION['installer']['db_username'] = $_POST['db_username'] ?? '';
    $_SESSION['installer']['db_password'] = $_POST['db_password'] ?? '';
    
    // Handle file upload
    if (isset($_FILES['db_file']) && $_FILES['db_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['db_file'];
        
        // Validate file extension
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            die('Invalid file type. Only .sql files are allowed.');
        }
        
        // Validate file size (50MB max)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($uploadedFile['size'] > $maxSize) {
            die('File too large. Maximum size is 50MB.');
        }
        
        // Validate file content (basic check for SQL patterns)
        $content = file_get_contents($uploadedFile['tmp_name']);
        if ($content === false) {
            die('Could not read uploaded file.');
        }
        
        // Basic SQL pattern check
        $sqlPatterns = ['/CREATE TABLE/i', '/INSERT INTO/i', '/--/', '/\*\*/'];
        $hasSqlPattern = false;
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $hasSqlPattern = true;
                break;
            }
        }
        
        if (!$hasSqlPattern) {
            die('Invalid SQL file. File does not contain valid SQL patterns.');
        }
        
        // Move to temp location
        $tempDir = __DIR__ . '/temp';
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                die('Failed to create temp directory. Please check folder permissions.');
            }
        }
        
        if (!is_writable($tempDir)) {
            die('Temp directory is not writable. Please check folder permissions.');
        }
        
        $tempFile = $tempDir . '/uploaded_db.sql';
        if (move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
            $_SESSION['installer']['db_file_path'] = $tempFile;
        } else {
            die('Failed to move uploaded file. Please check temp folder permissions.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAMS Installation - Step 3</title>
    <link rel="stylesheet" href="assets/installer.css">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🚀 VAMS Installation</h1>
            <p>Visitor Access Management System - Setup Wizard</p>
        </div>
        
        <div class="installer-body">
            <!-- Progress Stepper -->
            <div class="stepper">
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Requirements</div>
                </div>
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step active">
                    <div class="step-circle">3</div>
                    <div class="step-label">Admin Setup</div>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <div class="step-label">Install</div>
                </div>
            </div>
            
            <h2>Site & Admin Account Setup</h2>
            <p>Configure your application name and create your administrator account.</p>
            
            <form id="admin_form" action="index.php?step=4" method="post" onsubmit="return validateStep3()">
                <div class="form-group">
                    <label for="app_name">Application Name *</label>
                    <input type="text" id="app_name" name="app_name" class="form-control" 
                           value="Visitor Access Management System" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_name">Admin Full Name *</label>
                    <input type="text" id="admin_name" name="admin_name" class="form-control" 
                           placeholder="e.g., John Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Admin Email *</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" 
                           placeholder="e.g., admin@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Admin Password *</label>
                    <div class="input-group">
                        <input type="password" id="admin_password" name="admin_password" 
                               class="form-control" required minlength="8">
                        <span class="input-group-text" onclick="togglePasswordVisibility('admin_password', 'toggle_admin_password')">👁️‍🗨️</span>
                    </div>
                    <small style="color: #6c757d; margin-top: 5px; display: block;">
                        Minimum 8 characters, at least 1 letter and 1 number
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" required minlength="8">
                        <span class="input-group-text" onclick="togglePasswordVisibility('confirm_password', 'toggle_confirm_password')">👁️‍🗨️</span>
                    </div>
                </div>
                
                <div class="nav-buttons">
                    <a href="index.php?step=2" class="btn btn-secondary">← Back</a>
                    <button type="submit" class="btn btn-primary">Next →</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/installer.js"></script>
</body>
</html>
