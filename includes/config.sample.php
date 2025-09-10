<?php
// Sample configuration file for Torres Hotel Management System
// Copy this file to config.php and update with your actual database credentials

// Mark config as loaded
define('CONFIG_LOADED', true);

// Database configuration
// Update these values with your actual database credentials
define('DB_HOST', 'localhost');                    // Your database host
define('DB_USER', 'your_database_username');       // Your database username
define('DB_PASS', 'your_database_password');       // Your database password
define('DB_NAME', 'your_database_name');           // Your database name

// Application settings
define('APP_NAME', 'Torres Hotel Management System');
define('APP_THEME_COLOR', '#D4AF37'); // Gold color

// Error reporting (set to 0 for production)
ini_set('display_errors', 1); // Change to 0 for production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please check your configuration.");
    }
    
    // Set charset
    define('CHARSET', 'utf8mb4');
    $conn->set_charset(CHARSET);
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database connection error. Please check your configuration.");
}

// Create PDO connection for content management
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("PDO Connection failed: " . $e->getMessage());
    die("Database connection error. Please check your configuration.");
}
?>