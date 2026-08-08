<?php
/**
 * VAMS - Visitor Access Management System
 * Authentication Check and Session Guard
 * 
 * This file must be included at the top of every protected page
 * to ensure the user is authenticated and has the required role.
 */

// Load configuration FIRST - before session starts
require_once __DIR__ . '/../config/constants.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * Check if user is authenticated
 * Redirects to login page if not authenticated
 * 
 * @return void
 */
function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        $_SESSION['error_message'] = 'Please login to access this page.';
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
    
    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        $_SESSION['error_message'] = 'Your session has expired. Please login again.';
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user has one of the required roles
 * Redirects to dashboard with error if not authorized
 * 
 * @param array $allowed_roles Array of role names that are allowed
 * @return void
 */
function requireRole($allowed_roles) {
    requireAuth();
    
    if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], $allowed_roles)) {
        $_SESSION['error_message'] = 'You do not have permission to access this page.';
        header('Location: ' . BASE_URL . '/dashboard/index.php');
        exit;
    }
}

/**
 * Check if user has a specific role
 * 
 * @param string $role_name Role name to check
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role_name) {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

/**
 * Check if user has any of the specified roles
 * 
 * @param array $role_names Array of role names to check
 * @return bool True if user has any of the roles, false otherwise
 */
function hasAnyRole($role_names) {
    if (!isset($_SESSION['role_name'])) {
        return false;
    }
    return in_array($_SESSION['role_name'], $role_names);
}

/**
 * Generate CSRF token for forms
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from form submission
 * 
 * @param string $token Token to validate
 * @return bool True if token is valid, false otherwise
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token after successful form submission
 * 
 * @return void
 */
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Set flash message for display on next page load
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null if none exists
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Get current user ID from session
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's full name from session
 * 
 * @return string|null User's full name or null if not logged in
 */
function getCurrentUserName() {
    return $_SESSION['full_name'] ?? null;
}

/**
 * Get current user's role name from session
 * 
 * @return string|null Role name or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['role_name'] ?? null;
}

/**
 * Get current user's avatar path from session
 * 
 * @return string|null Avatar path or null if not logged in
 */
function getCurrentUserAvatar() {
    return $_SESSION['avatar'] ?? null;
}

// Note: Auto-require authentication is disabled to allow public pages (like login.php)
// to use CSRF functions without enforcing authentication.
// Protected pages should manually call requireAuth() after including this file.
