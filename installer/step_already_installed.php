<?php
/**
 * VAMS Installer - Already Installed Page
 */

// Load configuration for session name
require_once __DIR__ . '/../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAMS - Already Installed</title>
    <link rel="stylesheet" href="assets/installer.css">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🚀 VAMS Installation</h1>
            <p>Visitor Access Management System</p>
        </div>
        
        <div class="installer-body">
            <div class="already-installed">
                <div class="already-installed-icon">🔒</div>
                <h2>Already Installed</h2>
                <p>
                    This system is already installed. For security reasons, the installer cannot be run again.
                </p>
                <p>
                    <strong>Please delete the <code>/installer</code> folder from your server</strong> to prevent 
                    unauthorized access to the installation wizard.
                </p>
                <p>
                    If you need to reinstall the system, you must:
                </p>
                <ol style="text-align: left; display: inline-block; color: #6c757d; line-height: 2;">
                    <li>Delete the <code>/config/installed.lock</code> file</li>
                    <li>Drop all tables from your database</li>
                    <li>Re-run the installer</li>
                </ol>
                <br><br>
                <a href="../modules/auth/login.php" class="btn btn-primary btn-lg">
                    Go to Login Page →
                </a>
            </div>
        </div>
    </div>
</body>
</html>
