<?php
/**
 * VAMS - Visitor Access Management System
 * Root Index File
 *
 * Redirects to login or dashboard based on session status
 */

// Load configuration FIRST
require_once __DIR__ . '/config/constants.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {

    // Redirect to dashboard
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;

} else {

    // Redirect to login
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}