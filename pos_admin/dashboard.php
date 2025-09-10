<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'POS Admin Dashboard';
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
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --gold-color: #f1c40f;
            --purple-color: #9b59b6;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-right: none;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
            font-weight: 600;
        }
        
        .sidebar .nav-link i {
            width: 20px;
        }
        
        .main-content {
            padding: 30px;
            background-color: #f8f9fa;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .dashboard-header h1 {
            margin: 0;
            font-weight: 700;
            font-size: 2.2rem;
        }
        
        .dashboard-header .subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
            margin-top: 5px;
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .stats-card.products {
            background: linear-gradient(135deg, var(--info-color), #5dade2);
            color: white;
        }
        
        .stats-card.sales {
            background: linear-gradient(135deg, var(--success-color), #58d68d);
            color: white;
        }
        
        .stats-card.revenue {
            background: linear-gradient(135deg, var(--warning-color), #f7dc6f);
            color: white;
        }
        
        .stats-card.stock {
            background: linear-gradient(135deg, var(--danger-color), #ec7063);
            color: white;
        }
        
        .stats-card .card-body {
            padding: 25px;
        }
        
        .stats-card .display-4 {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stats-card .card-text {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .chart-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .chart-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            border: none;
        }
        
        .chart-card .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .activity-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .activity-card .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            border: none;
        }
        
        .quick-actions .btn {
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }
        
        .quick-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .quick-actions .btn-primary {
            background: linear-gradient(135deg, var(--info-color), #5dade2);
        }
        
        .quick-actions .btn-outline-primary {
            border: 2px solid var(--info-color);
            color: var(--info-color);
        }
        
        .quick-actions .btn-outline-primary:hover {
            background: var(--info-color);
            border-color: var(--info-color);
        }
        
        .quick-actions .btn-outline-secondary {
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .quick-actions .btn-outline-secondary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .quick-actions .btn-outline-info {
            border: 2px solid var(--purple-color);
            color: var(--purple-color);
        }
        
        .quick-actions .btn-outline-info:hover {
            background: var(--purple-color);
            border-color: var(--purple-color);
        }
        
        .sales-table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .sales-table th {
            background-color: var(--light-color);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .sales-table td {
            border: none;
            vertical-align: middle;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--info-color), var(--purple-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                height: auto;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .dashboard-header {
                padding: 20px;
                text-align: center;
            }
            
            .stats-card {
                margin-bottom: 20px;
            }
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="rfid_cards.php">
                            <i class="bi bi-credit-card me-2"></i>RFID Cards
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="settings.php">
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
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1><i class="bi bi-shop me-3"></i>POS Management Dashboard</h1>
                            <p class="subtitle mb-0">Monitor sales performance, manage inventory, and track business metrics</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="welcome-section">
                                <p class="mb-1">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                                <p class="mb-0"><i class="bi bi-calendar3 me-2"></i><?php echo date('F j, Y'); ?></p>
                                <p class="mb-0"><i class="bi bi-clock me-2"></i><span id="current-time"><?php echo date('g:i A'); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card products text-center">
                            <div class="card-body">
                                <i class="bi bi-box display-4"></i>
                                <h3 class="mt-2">247</h3>
                                <p class="card-text">Total Products</p>
                                <small class="d-block mt-2"><i class="bi bi-arrow-up"></i> +12 this month</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card sales text-center">
                            <div class="card-body">
                                <i class="bi bi-cart-check display-4"></i>
                                <h3 class="mt-2">89</h3>
                                <p class="card-text">Today's Orders</p>
                                <small class="d-block mt-2"><i class="bi bi-arrow-up"></i> +15% vs yesterday</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card revenue text-center">
                            <div class="card-body">
                                <i class="bi bi-currency-dollar display-4"></i>
                                <h3 class="mt-2">₱24,580</h3>
                                <p class="card-text">Today's Revenue</p>
                                <small class="d-block mt-2"><i class="bi bi-arrow-up"></i> +8.5% vs yesterday</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card stock text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle display-4"></i>
                                <h3 class="mt-2">7</h3>
                                <p class="card-text">Low Stock Alerts</p>
                                <small class="d-block mt-2"><i class="bi bi-arrow-down"></i> -3 resolved today</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Analytics & Category Performance -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card chart-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5><i class="bi bi-graph-up me-2"></i>Sales Analytics</h5>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-light active">Today</button>
                                        <button type="button" class="btn btn-outline-light">Week</button>
                                        <button type="button" class="btn btn-outline-light">Month</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <h4 class="text-success">₱24,580</h4>
                                        <small class="text-muted">Total Sales</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info">89</h4>
                                        <small class="text-muted">Orders</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning">₱276</h4>
                                        <small class="text-muted">Avg. Order</small>
                                    </div>
                                </div>
                                <div class="chart-placeholder bg-light rounded p-4 text-center">
                                    <i class="bi bi-bar-chart display-1 text-muted"></i>
                                    <p class="text-muted mt-2">Sales chart will be displayed here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5><i class="bi bi-pie-chart me-2"></i>Top Categories</h5>
                            </div>
                            <div class="card-body">
                                <div class="category-item d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary me-3">F</div>
                                        <div>
                                            <h6 class="mb-0">Food & Beverages</h6>
                                            <small class="text-muted">45 orders</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong>₱12,450</strong>
                                        <div class="progress mt-1" style="width: 80px;">
                                            <div class="progress-bar bg-primary" style="width: 65%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="category-item d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-success me-3">R</div>
                                        <div>
                                            <h6 class="mb-0">Room Service</h6>
                                            <small class="text-muted">28 orders</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong>₱8,920</strong>
                                        <div class="progress mt-1" style="width: 80px;">
                                            <div class="progress-bar bg-success" style="width: 45%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="category-item d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-warning me-3">B</div>
                                        <div>
                                            <h6 class="mb-0">Bar & Drinks</h6>
                                            <small class="text-muted">16 orders</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong>₱3,210</strong>
                                        <div class="progress mt-1" style="width: 80px;">
                                            <div class="progress-bar bg-warning" style="width: 25%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="reports.php" class="btn btn-outline-primary btn-sm">View Detailed Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Management & Staff Performance -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card activity-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-boxes me-2"></i>Inventory Status & Recent Transactions</h5>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-light active">Stock Alerts</button>
                                        <button class="btn btn-outline-light">Recent Sales</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="alert alert-warning d-flex align-items-center">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <div>
                                                <strong>7 items</strong> are running low on stock
                                                <br><small>Requires immediate attention</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="bi bi-graph-up me-2"></i>
                                            <div>
                                                <strong>15 products</strong> are top sellers
                                                <br><small>Consider restocking soon</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table sales-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Stock Level</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-3">C</div>
                                                        <div>
                                                            <h6 class="mb-0">Classic Burger</h6>
                                                            <small class="text-muted">SKU: FB001</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Food & Beverages</td>
                                                <td><span class="badge bg-danger">5 left</span></td>
                                                <td><span class="badge bg-warning">Low Stock</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">Restock</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-3">P</div>
                                                        <div>
                                                            <h6 class="mb-0">Pizza Margherita</h6>
                                                            <small class="text-muted">SKU: FB002</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Food & Beverages</td>
                                                <td><span class="badge bg-success">45 left</span></td>
                                                <td><span class="badge bg-success">In Stock</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary">View</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-3">C</div>
                                                        <div>
                                                            <h6 class="mb-0">Coffee Latte</h6>
                                                            <small class="text-muted">SKU: BD001</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Bar & Drinks</td>
                                                <td><span class="badge bg-warning">12 left</span></td>
                                                <td><span class="badge bg-warning">Low Stock</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">Restock</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="inventory.php" class="btn btn-primary">View Full Inventory</a>
                                    <a href="products.php" class="btn btn-outline-primary ms-2">Manage Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Staff Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="staff-member d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-success me-3">M</div>
                                        <div>
                                            <h6 class="mb-0">Maria Santos</h6>
                                            <small class="text-muted">Cashier</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-success">₱8,450</strong>
                                        <br><small class="text-muted">32 orders</small>
                                    </div>
                                </div>
                                
                                <div class="staff-member d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-info me-3">J</div>
                                        <div>
                                            <h6 class="mb-0">Juan Dela Cruz</h6>
                                            <small class="text-muted">Cashier</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-info">₱6,720</strong>
                                        <br><small class="text-muted">28 orders</small>
                                    </div>
                                </div>
                                
                                <div class="staff-member d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-warning me-3">A</div>
                                        <div>
                                            <h6 class="mb-0">Ana Rodriguez</h6>
                                            <small class="text-muted">Cashier</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-warning">₱5,890</strong>
                                        <br><small class="text-muted">24 orders</small>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="quick-actions">
                                    <h6 class="mb-3">Quick Actions</h6>
                                    <div class="d-grid gap-2">
                                        <a href="products.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Add Product
                                        </a>
                                        <a href="categories.php" class="btn btn-outline-primary">
                                            <i class="bi bi-tags me-2"></i>Manage Categories
                                        </a>
                                        <a href="inventory.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-boxes me-2"></i>Check Inventory
                                        </a>
                                        <a href="reports.php" class="btn btn-outline-info">
                                            <i class="bi bi-file-earmark-text me-2"></i>View Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Chart period toggle functionality
        function initChartToggles() {
            const chartButtons = document.querySelectorAll('.btn-group .btn');
            chartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons in the same group
                    this.parentNode.querySelectorAll('.btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Here you would typically update the chart data
                    console.log('Chart period changed to:', this.textContent);
                });
            });
        }
        
        // Inventory tab toggle
        function initInventoryTabs() {
            const tabButtons = document.querySelectorAll('.card-header .btn-group .btn');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from siblings
                    this.parentNode.querySelectorAll('.btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Toggle content based on selection
                    const isStockAlerts = this.textContent.includes('Stock Alerts');
                    console.log('Inventory tab changed to:', this.textContent);
                });
            });
        }
        
        // Restock button functionality
        function initRestockButtons() {
            const restockButtons = document.querySelectorAll('button:contains("Restock")');
            document.querySelectorAll('button').forEach(button => {
                if (button.textContent.includes('Restock')) {
                    button.addEventListener('click', function() {
                        const productName = this.closest('tr').querySelector('h6').textContent;
                        if (confirm(`Restock ${productName}?`)) {
                            // Here you would typically make an API call
                            alert(`Restock request submitted for ${productName}`);
                        }
                    });
                }
            });
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Dashboard refresh functionality
        function refreshDashboard() {
            showNotification('Dashboard data refreshed successfully!', 'success');
            // Here you would typically reload the dashboard data
        }
        
        // Keyboard shortcuts
        function initKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + R for refresh
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    refreshDashboard();
                }
                
                // Ctrl/Cmd + N for new product
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    window.location.href = 'products.php';
                }
            });
        }
        
        // Initialize all functionality when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            updateTime();
            setInterval(updateTime, 1000); // Update time every second
            
            initChartToggles();
            initInventoryTabs();
            initRestockButtons();
            initKeyboardShortcuts();
            
            // Show welcome notification
            setTimeout(() => {
                showNotification('Welcome to POS Admin Dashboard! Use Ctrl+R to refresh data.', 'info');
            }, 1000);
            
            console.log('POS Admin Dashboard initialized successfully');
        });
    </script>
</body>
</html>