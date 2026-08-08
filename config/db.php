<?php
/**
 * VAMS - Visitor Access Management System
 * Database Configuration using PDO
 */

// Prevent direct access
if (!defined('VAMS_INCLUDED')) {
    define('VAMS_INCLUDED', true);
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'visitor_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 * 
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error to file
        logError("Database Connection Failed: " . $e->getMessage());
        
        // Show generic error to user (don't expose DB details in production)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        } else {
            throw new PDOException("Database connection failed. Please contact administrator.");
        }
    }
}

/**
 * Log error to file
 * 
 * @param string $message Error message to log
 * @return void
 */
function logError($message) {
    $log_file = defined('LOGS_PATH') ? LOGS_PATH . '/error.log' : __DIR__ . '/../logs/error.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Execute a prepared statement with parameters
 * 
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        logError("Query Failed: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

/**
 * Fetch a single row
 * 
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array|false Single row or false if not found
 */
function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}

/**
 * Fetch all rows
 * 
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array All rows
 */
function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insert a record and return last insert ID
 * 
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return int Last insert ID
 */
function insertRecord($pdo, $sql, $params = []) {
    executeQuery($pdo, $sql, $params);
    return $pdo->lastInsertId();
}

/**
 * Update a record and return affected rows
 * 
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function updateRecord($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->rowCount();
}

/**
 * Delete a record and return affected rows
 * 
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function deleteRecord($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->rowCount();
}
