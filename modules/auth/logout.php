<?php
/**
 * VAMS - Visitor Access Management System
 * Logout Page
 */

// Load configuration FIRST - before session starts
require_once __DIR__ . '/../../config/constants.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy remember me cookie if exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Start new session for flash message
session_name(SESSION_NAME);
session_start();

// Set flash message directly (function not available after session destroy)
$_SESSION['flash_message'] = [
    'type' => 'success',
    'message' => 'You have been logged out successfully.'
];

// Redirect to login page
header('Location: ' . BASE_URL . '/modules/auth/login.php');
exit;
