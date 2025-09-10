<?php
// Production configuration for online deployment
// Mark config as loaded
define('CONFIG_LOADED', true);

// Database configuration for production
// IMPORTANT: Update these values with your hosting provider's database credentials
define('DB_HOST', 'your_host_here');        // Usually provided by hosting provider
define('DB_USER', 'your_username_here');    // Database username from hosting provider
define('DB_PASS', 'your_password_here');    // Database password from hosting provider
define('DB_NAME', 'your_database_name');    // Database name from hosting provider

// Application settings
define('APP_NAME', 'Torres Hotel Management System');
define('APP_THEME_COLOR', '#D4AF37'); // Gold color

// Error reporting for production (disable detailed errors)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Create database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please check the error log.");
    }
    
    // Set charset
    define('CHARSET', 'utf8mb4');
    $conn->set_charset(CHARSET);
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database connection error. Please check the error log.");
}

// Create PDO connection for content management
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("PDO connection failed: " . $e->getMessage());
    die("Database connection error. Please check the error log.");
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

?>