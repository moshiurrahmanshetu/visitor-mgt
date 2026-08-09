<?php
/**
 * VAMS Installer - Step 4: Run Installation
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

// Process form submission if coming from Step 3
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['installer']['app_name'] = $_POST['app_name'] ?? '';
    $_SESSION['installer']['admin_name'] = $_POST['admin_name'] ?? '';
    $_SESSION['installer']['admin_email'] = $_POST['admin_email'] ?? '';
    $_SESSION['installer']['admin_password'] = $_POST['admin_password'] ?? '';
}

// Get installer data from session
$dbHost = $_SESSION['installer']['db_host'] ?? '';
$dbPort = $_SESSION['installer']['db_port'] ?? '';
$dbName = $_SESSION['installer']['db_name'] ?? '';
$dbUsername = $_SESSION['installer']['db_username'] ?? '';
$appName = $_SESSION['installer']['app_name'] ?? '';
$adminEmail = $_SESSION['installer']['admin_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAMS Installation - Step 4</title>
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
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Admin Setup</div>
                </div>
                <div class="step active">
                    <div class="step-circle">4</div>
                    <div class="step-label">Install</div>
                </div>
            </div>
            
            <h2>Ready to Install</h2>
            <p>Please review the configuration below and click "Install Now" to complete the installation.</p>
            
            <div class="success-summary">
                <p><strong>Database Host:</strong> <?php echo htmlspecialchars($dbHost); ?></p>
                <p><strong>Database Port:</strong> <?php echo htmlspecialchars($dbPort); ?></p>
                <p><strong>Database Name:</strong> <?php echo htmlspecialchars($dbName); ?></p>
                <p><strong>Database Username:</strong> <?php echo htmlspecialchars($dbUsername); ?></p>
                <p><strong>Application Name:</strong> <?php echo htmlspecialchars($appName); ?></p>
                <p><strong>Admin Email:</strong> <?php echo htmlspecialchars($adminEmail); ?></p>
            </div>
            
            <div class="alert alert-warning">
                <strong>⚠️ Important:</strong> This process will create the database and import the SQL file. 
                Please ensure your database credentials are correct before proceeding.
            </div>
            
            <form id="install_form">
                <button type="button" id="install_btn" class="btn btn-success btn-lg btn-block" onclick="runInstallation()">
                    🚀 Install Now
                </button>
            </form>
            
            <div id="install_progress" class="install-progress"></div>
            
            <!-- Success Page (hidden initially) -->
            <div id="success_page" style="display: none;" class="success-page">
                <div class="success-icon">✅</div>
                <h2>Installation Complete!</h2>
                <p>VAMS has been successfully installed on your server.</p>
                
                <div class="success-summary">
                    <p><strong>Admin Email:</strong> <span id="admin_email_display"></span></p>
                    <p><strong>Application Name:</strong> <?php echo htmlspecialchars($appName); ?></p>
                </div>
                
                <div class="alert alert-warning">
                    <strong>🔒 Security Reminder:</strong> Please delete the <code>/installer</code> folder from your server 
                    to prevent unauthorized re-installation attempts.
                </div>
                
                <a href="../modules/auth/login.php" class="btn btn-primary btn-lg">
                    Go to Login Page →
                </a>
            </div>
            
            <div class="nav-buttons" id="nav_buttons">
                <a href="index.php?step=3" class="btn btn-secondary">← Back</a>
                <div></div>
            </div>
        </div>
    </div>
    
    <script src="assets/installer.js"></script>
</body>
</html>
