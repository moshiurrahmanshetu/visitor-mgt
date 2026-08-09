<?php
/**
 * VAMS Installer - Test Database Connection (AJAX Endpoint)
 */

// Load configuration for session name
require_once __DIR__ . '/../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

header('Content-Type: application/json');

// Check if already installed
$lockFile = __DIR__ . '/../config/installed.lock';
if (file_exists($lockFile)) {
    echo json_encode(['success' => false, 'message' => 'System is already installed']);
    exit;
}

// Get POST data
$host = $_POST['host'] ?? '';
$port = $_POST['port'] ?? '3306';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($host) || empty($port) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Host, port, and username are required']);
    exit;
}

try {
    // Attempt to connect to MySQL server (without database name)
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3, // 3-second timeout
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Connection successful
    echo json_encode(['success' => true, 'message' => 'Connection successful!']);
    
} catch (PDOException $e) {
    // Connection failed
    $errorMessage = $e->getMessage();
    
    // Provide user-friendly error messages
    if (strpos($errorMessage, 'Access denied') !== false) {
        $message = 'Access denied for user. Please check your username and password.';
    } elseif (strpos($errorMessage, 'Connection refused') !== false) {
        $message = 'Could not connect to the MySQL server. Please check the host and port.';
    } elseif (strpos($errorMessage, 'getaddrinfo') !== false) {
        $message = 'Could not resolve host. Please check the database host address.';
    } else {
        $message = 'Connection failed: ' . $errorMessage;
    }
    
    echo json_encode(['success' => false, 'message' => $message]);
}
