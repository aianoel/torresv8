<?php
// Debug script to identify HTTP 500 errors
// IMPORTANT: Remove this file after debugging for security

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Torres Hotel Management System - Debug Information</h1>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check PHP version
echo "<h2>1. PHP Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Check required extensions
echo "<h2>2. Required Extensions</h2>";
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅ Loaded' : '❌ Missing';
    echo "<p><strong>{$ext}:</strong> {$status}</p>";
}

// Check file permissions
echo "<h2>3. File Permissions</h2>";
$important_files = [
    'includes/config.php',
    'includes/db.php',
    'includes/auth.php',
    'index.php'
];

foreach ($important_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file) ? '✅' : '❌';
        echo "<p><strong>{$file}:</strong> {$perms} {$readable}</p>";
    } else {
        echo "<p><strong>{$file}:</strong> ❌ File not found</p>";
    }
}

// Test database connection
echo "<h2>4. Database Connection Test</h2>";
try {
    if (file_exists('includes/config.php')) {
        echo "<p>✅ Config file exists</p>";
        
        // Capture any errors from config file
        ob_start();
        $error_occurred = false;
        
        try {
            require_once 'includes/config.php';
            echo "<p>✅ Config file loaded successfully</p>";
        } catch (Exception $e) {
            $error_occurred = true;
            echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            $error_occurred = true;
            echo "<p>❌ Fatal error in config: " . $e->getMessage() . "</p>";
        }
        
        $output = ob_get_clean();
        echo $output;
        
        if (!$error_occurred && isset($conn)) {
            echo "<p>✅ Database connection object created</p>";
            echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
            echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
            echo "<p><strong>User:</strong> " . DB_USER . "</p>";
            
            if ($conn->connect_error) {
                echo "<p>❌ Connection failed: " . $conn->connect_error . "</p>";
            } else {
                echo "<p>✅ Database connected successfully</p>";
                echo "<p><strong>Server version:</strong> " . $conn->server_info . "</p>";
                
                // Test if database exists
                $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
                if ($result && $result->num_rows > 0) {
                    echo "<p>✅ Database '" . DB_NAME . "' exists</p>";
                    
                    // Check for required tables
                    $tables = ['users', 'rooms', 'bookings'];
                    foreach ($tables as $table) {
                        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
                        if ($result && $result->num_rows > 0) {
                            echo "<p>✅ Table '{$table}' exists</p>";
                        } else {
                            echo "<p>❌ Table '{$table}' missing</p>";
                        }
                    }
                } else {
                    echo "<p>❌ Database '" . DB_NAME . "' does not exist</p>";
                }
            }
        }
    } else {
        echo "<p>❌ Config file not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
} catch (Error $e) {
    echo "<p>❌ Fatal Error: " . $e->getMessage() . "</p>";
}

// Check logs directory
echo "<h2>5. Logs Directory</h2>";
if (!file_exists('logs')) {
    echo "<p>❌ Logs directory doesn't exist. Creating...</p>";
    if (mkdir('logs', 0755, true)) {
        echo "<p>✅ Logs directory created</p>";
    } else {
        echo "<p>❌ Failed to create logs directory</p>";
    }
} else {
    echo "<p>✅ Logs directory exists</p>";
    $writable = is_writable('logs') ? '✅ Writable' : '❌ Not writable';
    echo "<p><strong>Writable:</strong> {$writable}</p>";
}

// Show recent error log if exists
if (file_exists('logs/error.log')) {
    echo "<h2>6. Recent Error Log</h2>";
    $log_content = file_get_contents('logs/error.log');
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -10); // Last 10 lines
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_lines)) . "</pre>";
}

echo "<h2>7. Next Steps</h2>";
echo "<ol>";
echo "<li>If database connection failed, update credentials in includes/config.php</li>";
echo "<li>If database doesn't exist, create it and import db/schema.sql</li>";
echo "<li>If tables are missing, import db/schema.sql</li>";
echo "<li>Check file permissions (folders: 755, files: 644)</li>";
echo "<li>Remove this debug.php file after fixing issues</li>";
echo "</ol>";

echo "<p><strong>⚠️ SECURITY WARNING:</strong> Delete this debug.php file after troubleshooting!</p>";
?>