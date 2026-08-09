<?php
/**
 * VAMS Installer - Step 2: Database Configuration
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

// Initialize session for database config
if (!isset($_SESSION['installer'])) {
    $_SESSION['installer'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAMS Installation - Step 2</title>
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
                <div class="step active">
                    <div class="step-circle">2</div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <div class="step-label">Admin Setup</div>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <div class="step-label">Install</div>
                </div>
            </div>
            
            <h2>Database Configuration</h2>
            <p>Enter your MySQL database connection details. The installer will create the database if it doesn't exist.</p>
            
            <form id="db_form" action="index.php?step=3" method="post" enctype="multipart/form-data" onsubmit="return validateStep2()">
                <div class="form-group">
                    <label for="db_host">Database Host *</label>
                    <input type="text" id="db_host" name="db_host" class="form-control" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_port">Database Port *</label>
                    <input type="text" id="db_port" name="db_port" class="form-control" value="3306" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name *</label>
                    <input type="text" id="db_name" name="db_name" class="form-control" placeholder="e.g., vams" required>
                </div>
                
                <div class="form-group">
                    <label for="db_username">Database Username *</label>
                    <input type="text" id="db_username" name="db_username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="db_password">Database Password</label>
                    <div class="input-group">
                        <input type="password" id="db_password" name="db_password" class="form-control">
                        <span class="input-group-text" onclick="togglePasswordVisibility('db_password', 'toggle_password')">👁️‍🗨️</span>
                    </div>
                    <small style="color: #6c757d; margin-top: 5px; display: block;">Leave empty for local development setups</small>
                </div>
                
                <div class="form-group">
                    <button type="button" id="test_connection_btn" class="btn btn-secondary" onclick="testConnection()">
                        Test Connection
                    </button>
                    <div id="connection_result" style="margin-top: 10px;"></div>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">
                
                <h3>Upload Database File</h3>
                <p>Upload the .sql database file provided with your purchase.</p>
                
                <div class="form-group">
                    <label class="file-upload" onclick="document.getElementById('db_file').click()">
                        <div class="file-upload-icon">📁</div>
                        <div class="file-upload-text">Click to select .sql file or drag and drop</div>
                        <input type="file" id="db_file" name="db_file" accept=".sql" required onchange="handleFileUpload('db_file', 'file_display')">
                    </div>
                    <div id="file_display" class="file-upload-filename"></div>
                    <small style="color: #6c757d; margin-top: 10px; display: block;">
                        Large files may take a few minutes to import after clicking Install in the final step.
                        Maximum file size: 50MB.
                    </small>
                </div>
                
                <div class="nav-buttons">
                    <a href="index.php?step=1" class="btn btn-secondary">← Back</a>
                    <button type="submit" class="btn btn-primary">Next →</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/installer.js"></script>
</body>
</html>
