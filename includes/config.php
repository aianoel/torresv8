<?php
// Mark config as loaded
define('CONFIG_LOADED', true);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u870495195_torres');
define('DB_PASS', '8B7]ML~FA/f');
define('DB_NAME', 'u870495195_torres');

// Application settings
define('APP_NAME', 'Torres Hotel Management System');
define('APP_THEME_COLOR', '#D4AF37'); // Gold color

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
define('CHARSET', 'utf8mb4');
$conn->set_charset(CHARSET);

// Create PDO connection for content management
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>