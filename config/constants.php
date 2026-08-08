<?php
/**
 * VAMS - Visitor Access Management System
 * Configuration Constants
 */

// Prevent direct access
if (!defined('VAMS_INCLUDED')) {
    define('VAMS_INCLUDED', true);
}

// Application Settings
define('APP_NAME', 'VAMS - Visitor Access Management System');
define('APP_VERSION', '1.0.0');

// Base URL Configuration
// Detect if running on localhost or production
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Get the project root from the current file's directory
// This file is in /config/, so we need to go up one level to get the project root
$project_root = dirname(__DIR__);

// Convert to web path by removing document root
$document_root = $_SERVER['DOCUMENT_ROOT'];
// Normalize paths
$project_root = str_replace('\\', '/', $project_root);
$document_root = str_replace('\\', '/', $document_root);

// Get the web path (remove document root from project root)
if (strpos($project_root, $document_root) === 0) {
    $web_path = substr($project_root, strlen($document_root));
} else {
    // Fallback: use SCRIPT_NAME to determine path
    $web_path = dirname(dirname($_SERVER['SCRIPT_NAME']));
}

// Remove trailing slash and ensure leading slash
$web_path = '/' . trim($web_path, '/\\');

define('BASE_URL', $protocol . '://' . $host . $web_path);
define('ASSETS_URL', BASE_URL . '/assets');

// File Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads');
define('AVATARS_PATH', UPLOADS_PATH . '/avatars');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Session Configuration
define('SESSION_NAME', 'VAMS_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Password Settings
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPER', true);
define('PASSWORD_REQUIRE_LOWER', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// File Upload Settings
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Pagination
define('RECORDS_PER_PAGE', 20);

// Date/Time Settings
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('TIMEZONE', 'UTC');

// Set default timezone
date_default_timezone_set(TIMEZONE);

// Error Reporting (set to 0 in production)
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
