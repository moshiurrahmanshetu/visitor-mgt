<?php
/**
 * VAMS Installer - Step Router
 * Routes to the appropriate installation step
 */

// Load configuration for session name
require_once __DIR__ . '/../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if already installed
$lockFile = __DIR__ . '/../config/installed.lock';
if (file_exists($lockFile)) {
    // Show "Already Installed" page
    require_once __DIR__ . '/step_already_installed.php';
    exit;
}

// Determine current step
$currentStep = isset($_GET['step']) ? intval($_GET['step']) : 1;
$currentStep = max(1, min(4, $currentStep)); // Clamp between 1 and 4

// Route to appropriate step
switch ($currentStep) {
    case 1:
        require_once __DIR__ . '/step1_requirements.php';
        break;
    case 2:
        require_once __DIR__ . '/step2_database.php';
        break;
    case 3:
        require_once __DIR__ . '/step3_admin_account.php';
        break;
    case 4:
        require_once __DIR__ . '/step4_install.php';
        break;
    default:
        require_once __DIR__ . '/step1_requirements.php';
        break;
}
