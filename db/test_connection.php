<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: text/plain');

try {
    // Test database connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Database connection successful!\n";
    echo "Server version: " . $conn->server_info . "\n";
    echo "Host info: " . $conn->host_info . "\n";
    
    // Test if database exists
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result->num_rows > 0) {
        echo "Database '" . DB_NAME . "' exists.\n";
        
        // Test if tables exist
        $tables = ['users', 'rooms', 'bookings', 'payments', 'housekeeping'];
        $missingTables = [];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '" . $table . "'");
            if ($result->num_rows === 0) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            echo "All required tables exist.\n";
        } else {
            echo "Missing tables: " . implode(', ', $missingTables) . "\n";
            echo "Run the schema.sql file to create the database structure.\n";
        }
    } else {
        echo "Database '" . DB_NAME . "' does not exist.\n";
        echo "Run the schema.sql file to create the database.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Check your database configuration in includes/config.php\n";
}

$conn->close();
?>