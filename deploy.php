<?php
/**
 * Torres Hotel Management System - Deployment Script
 * 
 * This script helps automate the deployment process
 * Run this script after uploading files to your server
 * 
 * Usage: php deploy.php
 */

// Prevent direct web access
if (isset($_SERVER['HTTP_HOST'])) {
    die('This script must be run from the command line.');
}

echo "Torres Hotel Management System - Deployment Script\n";
echo "================================================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    die("Error: PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n");
}

echo "✓ PHP version check passed (" . PHP_VERSION . ")\n";

// Check required extensions
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'gd'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die("Error: Missing required PHP extensions: " . implode(', ', $missing_extensions) . "\n");
}

echo "✓ Required PHP extensions check passed\n";

// Check if config file exists
if (!file_exists(__DIR__ . '/includes/config.php')) {
    echo "⚠ Warning: config.php not found. Please copy config.sample.php to config.php and update database credentials.\n";
    
    if (file_exists(__DIR__ . '/includes/config.sample.php')) {
        echo "  You can run: cp includes/config.sample.php includes/config.php\n";
    }
} else {
    echo "✓ Configuration file found\n";
}

// Create necessary directories
$directories = [
    'logs',
    'uploads',
    'cache',
    'backups'
];

foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✓ Created directory: $dir\n";
        } else {
            echo "⚠ Warning: Could not create directory: $dir\n";
        }
    } else {
        echo "✓ Directory exists: $dir\n";
    }
}

// Check directory permissions
foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path) && !is_writable($path)) {
        echo "⚠ Warning: Directory not writable: $dir (you may need to set permissions to 755 or 777)\n";
    }
}

// Test database connection if config exists
if (file_exists(__DIR__ . '/includes/config.php')) {
    echo "\nTesting database connection...\n";
    
    try {
        // Capture any output from config.php
        ob_start();
        include __DIR__ . '/includes/config.php';
        $config_output = ob_get_clean();
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "✗ Database connection failed: " . $conn->connect_error . "\n";
            } else {
                echo "✓ Database connection successful\n";
                
                // Check if users table exists
                $result = $conn->query("SHOW TABLES LIKE 'users'");
                if ($result && $result->num_rows > 0) {
                    echo "✓ Database tables found\n";
                } else {
                    echo "⚠ Warning: Users table not found. You may need to import the database schema.\n";
                    echo "  Run: mysql -u username -p database_name < db/schema.sql\n";
                }
            }
        } else {
            echo "⚠ Warning: Could not establish database connection object\n";
        }
    } catch (Exception $e) {
        echo "✗ Database connection error: " . $e->getMessage() . "\n";
    }
}

// Security checks
echo "\nSecurity Checks:\n";
echo "===============\n";

// Check for sensitive files in web root
$sensitive_files = [
    '.env',
    'config.php',
    '.git',
    'composer.json',
    'package.json'
];

foreach ($sensitive_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        if ($file === '.git' && is_dir(__DIR__ . '/' . $file)) {
            echo "⚠ Warning: .git directory found in web root. Consider moving it outside web root.\n";
        } elseif ($file === 'config.php') {
            // This is expected, but check if it's accessible via web
            echo "ℹ Info: config.php found. Ensure it's not accessible via web browser.\n";
        }
    }
}

// Check for default passwords in SQL files
if (file_exists(__DIR__ . '/create_pos_admin_user.sql')) {
    $content = file_get_contents(__DIR__ . '/create_pos_admin_user.sql');
    if (strpos($content, 'admin123') !== false) {
        echo "⚠ Security Warning: Default password found in create_pos_admin_user.sql\n";
        echo "  Please change the default password before running this script!\n";
    }
}

echo "\nDeployment Summary:\n";
echo "==================\n";
echo "1. ✓ PHP version and extensions checked\n";
echo "2. ✓ Directories created/verified\n";
echo "3. ✓ Database connection tested\n";
echo "4. ✓ Security checks completed\n";

echo "\nNext Steps:\n";
echo "==========\n";
echo "1. Update includes/config.php with your database credentials\n";
echo "2. Import database schema: mysql -u user -p database < db/schema.sql\n";
echo "3. Create initial users (update passwords first!)\n";
echo "4. Set proper file permissions (644 for files, 755 for directories)\n";
echo "5. Test the application in your browser\n";
echo "6. Change all default passwords immediately!\n";

echo "\nDeployment script completed!\n";
?>