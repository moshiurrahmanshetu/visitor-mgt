<?php
/**
 * VAMS Installer - Step 1: Requirements Check
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

// Check requirements
$requirements = [];

// PHP version
$phpVersion = PHP_VERSION;
$requirements['php_version'] = [
    'name' => 'PHP Version',
    'current' => $phpVersion,
    'required' => '>= 8.0',
    'status' => version_compare($phpVersion, '8.0', '>=') ? 'pass' : 'fail',
    'blocking' => true
];

// PDO extension
$requirements['pdo'] = [
    'name' => 'PDO Extension',
    'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
    'required' => 'Enabled',
    'status' => extension_loaded('pdo') ? 'pass' : 'fail',
    'blocking' => true
];

// pdo_mysql driver
$requirements['pdo_mysql'] = [
    'name' => 'PDO MySQL Driver',
    'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
    'required' => 'Enabled',
    'status' => extension_loaded('pdo_mysql') ? 'pass' : 'fail',
    'blocking' => true
];

// fileinfo extension
$requirements['fileinfo'] = [
    'name' => 'Fileinfo Extension',
    'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
    'required' => 'Enabled (for upload validation)',
    'status' => extension_loaded('fileinfo') ? 'pass' : 'warning',
    'blocking' => false
];

// GD extension
$requirements['gd'] = [
    'name' => 'GD Extension',
    'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
    'required' => 'Enabled (for image handling)',
    'status' => extension_loaded('gd') ? 'pass' : 'warning',
    'blocking' => false
];

// Config folder writable
$configPath = __DIR__ . '/../config';
$configWritable = is_writable($configPath);
$requirements['config_writable'] = [
    'name' => '/config Folder Writable',
    'current' => $configWritable ? 'Writable' : 'Not Writable',
    'required' => 'Writable',
    'status' => $configWritable ? 'pass' : 'fail',
    'blocking' => true
];

// Assets/uploads folder writable
$uploadsPath = __DIR__ . '/../assets/uploads';
if (!is_dir($uploadsPath)) {
    mkdir($uploadsPath, 0755, true);
}
$uploadsWritable = is_writable($uploadsPath);
$requirements['uploads_writable'] = [
    'name' => '/assets/uploads Folder Writable',
    'current' => $uploadsWritable ? 'Writable' : 'Not Writable',
    'required' => 'Writable',
    'status' => $uploadsWritable ? 'pass' : 'fail',
    'blocking' => true
];

// Logs folder writable
$logsPath = __DIR__ . '/../logs';
if (!is_dir($logsPath)) {
    mkdir($logsPath, 0755, true);
}
$logsWritable = is_writable($logsPath);
$requirements['logs_writable'] = [
    'name' => '/logs Folder Writable',
    'current' => $logsWritable ? 'Writable' : 'Not Writable',
    'required' => 'Writable',
    'status' => $logsWritable ? 'pass' : 'fail',
    'blocking' => true
];

// Upload limits (informational only)
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

// Check if any blocking requirement fails
$hasBlockingFailures = false;
foreach ($requirements as $req) {
    if ($req['blocking'] && $req['status'] === 'fail') {
        $hasBlockingFailures = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAMS Installation - Step 1</title>
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
                <div class="step active">
                    <div class="step-circle">1</div>
                    <div class="step-label">Requirements</div>
                </div>
                <div class="step">
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
            
            <h2>System Requirements Check</h2>
            <p>Please ensure all required items pass before proceeding.</p>
            
            <table class="requirements-table">
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Required</th>
                        <th>Current</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requirements as $key => $req): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['name']); ?></td>
                        <td><?php echo htmlspecialchars($req['required']); ?></td>
                        <td><?php echo htmlspecialchars($req['current']); ?></td>
                        <td class="status-<?php echo $req['status']; ?>">
                            <?php
                            if ($req['status'] === 'pass') echo '✅ Pass';
                            elseif ($req['status'] === 'fail') echo '❌ Fail';
                            else echo '⚠️ Warning';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="alert alert-info">
                <strong>Upload Limits:</strong> Your server allows <code><?php echo htmlspecialchars($uploadMaxFilesize); ?></code> 
                for file uploads and <code><?php echo htmlspecialchars($postMaxSize); ?></code> for POST data. 
                If your SQL file is larger, please increase these values in php.ini.
            </div>
            
            <?php if ($hasBlockingFailures): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Installation Blocked:</strong> Please fix the failed requirements above before proceeding.
            </div>
            <?php endif; ?>
            
            <div class="nav-buttons">
                <div></div>
                <a href="index.php?step=2" class="btn btn-primary <?php echo $hasBlockingFailures ? 'disabled' : ''; ?>">
                    Next →
                </a>
            </div>
        </div>
    </div>
</body>
</html>
