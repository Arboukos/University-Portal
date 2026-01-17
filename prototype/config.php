<?php

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'university_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new PDOException("Database connection failed");
        }
    }
    
    return $pdo;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']) && 
           isset($_SESSION['role_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentRoleId() {
    return $_SESSION['role_id'] ?? null;
}


function redirect($page) {
    header("Location: $page");
    exit();
}


function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>