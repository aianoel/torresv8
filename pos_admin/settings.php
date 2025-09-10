<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'System Settings';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_general':
                $app_name = trim($_POST['app_name']);
                $app_description = trim($_POST['app_description']);
                $currency = trim($_POST['currency']);
                $timezone = trim($_POST['timezone']);
                
                // Update settings in database or config file
                $settings = [
                    'app_name' => $app_name,
                    'app_description' => $app_description,
                    'currency' => $currency,
                    'timezone' => $timezone
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->bind_param("ss", $key, $value);
                    $stmt->execute();
                }
                
                $message = '<div class="alert alert-success">General settings updated successfully!</div>';
                break;
                
            case 'update_pos':
                $tax_rate = floatval($_POST['tax_rate']);
                $receipt_header = trim($_POST['receipt_header']);
                $receipt_footer = trim($_POST['receipt_footer']);
                $auto_print = isset($_POST['auto_print']) ? 1 : 0;
                $low_stock_threshold = intval($_POST['low_stock_threshold']);
                
                $pos_settings = [
                    'tax_rate' => $tax_rate,
                    'receipt_header' => $receipt_header,
                    'receipt_footer' => $receipt_footer,
                    'auto_print' => $auto_print,
                    'low_stock_threshold' => $low_stock_threshold
                ];
                
                foreach ($pos_settings as $key => $value) {
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->bind_param("ss", $key, $value);
                    $stmt->execute();
                }
                
                $message = '<div class="alert alert-success">POS settings updated successfully!</div>';
                break;
                
            case 'backup_database':
                // Simple backup functionality
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = '../backups/' . $backup_file;
                
                // Create backups directory if it doesn't exist
                if (!file_exists('../backups/')) {
                    mkdir('../backups/', 0755, true);
                }
                
                // Note: In a real application, you would use mysqldump or similar
                $message = '<div class="alert alert-info">Database backup functionality would be implemented here. Backup file: ' . $backup_file . '</div>';
                break;
                
            case 'clear_logs':
                // Clear application logs
                $stmt = $conn->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stmt->execute();
                
                $message = '<div class="alert alert-success">Old logs cleared successfully!</div>';
                break;
        }
    }
}

// Get current settings
$settings_query = "SELECT setting_key, setting_value FROM settings";
$settings_result = $conn->query($settings_query);
$current_settings = [];

if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Default values
$defaults = [
    'app_name' => APP_NAME,
    'app_description' => 'Hotel Management System with POS',
    'currency' => 'PHP',
    'timezone' => 'Asia/Manila',
    'tax_rate' => '12.00',
    'receipt_header' => 'Thank you for your purchase!',
    'receipt_footer' => 'Please come again!',
    'auto_print' => '0',
    'low_stock_threshold' => '10'
];

// Merge with current settings
foreach ($defaults as $key => $value) {
    if (!isset($current_settings[$key])) {
        $current_settings[$key] = $value;
    }
}

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $conn->server_info,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'disk_space' => function_exists('disk_free_space') ? disk_free_space('.') : 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
        }
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link:hover {
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            color: var(--primary-color);
            font-weight: bold;
        }
        .main-content {
            padding: 20px;
        }
        .settings-section {
            margin-bottom: 30px;
        }
        .system-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .system-info-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="logo.png" alt="Logo" class="me-2" style="height: 40px;">
                        <h5 class="mb-0">POS Admin</h5>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box me-2"></i>Products
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link" href="sales.php">
                            <i class="bi bi-graph-up me-2"></i>Sales
                        </a>
                        <a class="nav-link" href="inventory.php">
                            <i class="bi bi-boxes me-2"></i>Inventory
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Reports
                        </a>
                        <a class="nav-link active" href="settings.php">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                        <hr>
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>System Settings</h1>
                </div>

                <?php echo $message; ?>

                <div class="row">
                    <div class="col-md-8">
                        <!-- General Settings -->
                        <div class="settings-section">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>General Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_general">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Application Name</label>
                                                    <input type="text" class="form-control" name="app_name" value="<?php echo htmlspecialchars($current_settings['app_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Currency</label>
                                                    <select class="form-select" name="currency">
                                                        <option value="PHP" <?php echo $current_settings['currency'] === 'PHP' ? 'selected' : ''; ?>>Philippine Peso (PHP)</option>
                                                        <option value="USD" <?php echo $current_settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                                        <option value="EUR" <?php echo $current_settings['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Application Description</label>
                                            <textarea class="form-control" name="app_description" rows="3"><?php echo htmlspecialchars($current_settings['app_description']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-select" name="timezone">
                                                <option value="Asia/Manila" <?php echo $current_settings['timezone'] === 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila</option>
                                                <option value="UTC" <?php echo $current_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                <option value="America/New_York" <?php echo $current_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Save General Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- POS Settings -->
                        <div class="settings-section">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-shop me-2"></i>POS Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_pos">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Tax Rate (%)</label>
                                                    <input type="number" class="form-control" name="tax_rate" step="0.01" min="0" max="100" value="<?php echo $current_settings['tax_rate']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Low Stock Threshold</label>
                                                    <input type="number" class="form-control" name="low_stock_threshold" min="1" value="<?php echo $current_settings['low_stock_threshold']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Receipt Header</label>
                                            <textarea class="form-control" name="receipt_header" rows="2"><?php echo htmlspecialchars($current_settings['receipt_header']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Receipt Footer</label>
                                            <textarea class="form-control" name="receipt_footer" rows="2"><?php echo htmlspecialchars($current_settings['receipt_footer']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_print" id="auto_print" <?php echo $current_settings['auto_print'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="auto_print">
                                                    Auto-print receipts
                                                </label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Save POS Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- System Maintenance -->
                        <div class="settings-section">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-tools me-2"></i>System Maintenance</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="backup_database">
                                                    <button type="submit" class="btn btn-outline-primary">
                                                        <i class="bi bi-download me-2"></i>Backup Database
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear old logs?');">
                                                    <input type="hidden" name="action" value="clear_logs">
                                                    <button type="submit" class="btn btn-outline-warning">
                                                        <i class="bi bi-trash me-2"></i>Clear Old Logs
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-info" onclick="checkUpdates()">
                                                    <i class="bi bi-arrow-clockwise me-2"></i>Check for Updates
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-secondary" onclick="optimizeDatabase()">
                                                    <i class="bi bi-speedometer2 me-2"></i>Optimize Database
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- System Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="system-info-item">
                                    <span><strong>PHP Version:</strong></span>
                                    <span><?php echo $system_info['php_version']; ?></span>
                                </div>
                                <div class="system-info-item">
                                    <span><strong>MySQL Version:</strong></span>
                                    <span><?php echo $system_info['mysql_version']; ?></span>
                                </div>
                                <div class="system-info-item">
                                    <span><strong>Server Software:</strong></span>
                                    <span><?php echo $system_info['server_software']; ?></span>
                                </div>
                                <div class="system-info-item">
                                    <span><strong>Memory Limit:</strong></span>
                                    <span><?php echo $system_info['memory_limit']; ?></span>
                                </div>
                                <div class="system-info-item">
                                    <span><strong>Max Execution Time:</strong></span>
                                    <span><?php echo $system_info['max_execution_time']; ?>s</span>
                                </div>
                                <?php if (is_numeric($system_info['disk_space'])): ?>
                                <div class="system-info-item">
                                    <span><strong>Free Disk Space:</strong></span>
                                    <span><?php echo number_format($system_info['disk_space'] / 1024 / 1024 / 1024, 2); ?> GB</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-speedometer2 me-2"></i>View Dashboard
                                    </a>
                                    <a href="reports.php" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-file-earmark-text me-2"></i>Generate Reports
                                    </a>
                                    <a href="inventory.php" class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-boxes me-2"></i>Check Inventory
                                    </a>
                                    <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>System Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Database Connection</span>
                                    <span class="badge bg-success">Connected</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>POS System</span>
                                    <span class="badge bg-success">Online</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Last Backup</span>
                                    <span class="badge bg-warning">Never</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>System Health</span>
                                    <span class="badge bg-success">Good</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkUpdates() {
            alert('Update check functionality would be implemented here.');
        }
        
        function optimizeDatabase() {
            if (confirm('This will optimize the database tables. Continue?')) {
                alert('Database optimization functionality would be implemented here.');
            }
        }
    </script>
</body>
</html>