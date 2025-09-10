<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pos_cashier') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['username'];

// Get today's sales summary
$today = date('Y-m-d');
$sales_query = "SELECT 
    COUNT(*) as total_sales,
    COALESCE(SUM(final_amount), 0) as total_revenue
    FROM sales 
    WHERE DATE(sale_date) = ?";
$stmt = $conn->prepare($sales_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$sales_summary = $stmt->get_result()->fetch_assoc();

// Get recent sales
$recent_sales_query = "SELECT 
    s.id,
    s.sale_number,
    s.final_amount,
    s.sale_date,
    s.payment_method
    FROM sales s
    WHERE DATE(s.sale_date) = ?
    ORDER BY s.sale_date DESC
    LIMIT 10";
$stmt = $conn->prepare($recent_sales_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$recent_sales = $stmt->get_result();

// Get low stock products
$low_stock_query = "SELECT 
    p.name,
    p.stock_quantity,
    p.min_stock
    FROM products p
    WHERE p.stock_quantity <= p.min_stock
    ORDER BY p.stock_quantity ASC
    LIMIT 5";
$low_stock_result = $conn->query($low_stock_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - Torres Farm Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #0891b2;
            --light: #f8fafc;
            --dark: #1e293b;
            --cashier-primary: #7c3aed;
            --cashier-secondary: #a855f7;
            --pos-accent: #06b6d4;
            --gradient-primary: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            --gradient-secondary: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            --gradient-success: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --gradient-warning: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
        }

        body {
            background-color: var(--light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--gradient-primary);
            box-shadow: 4px 0 20px rgba(124, 58, 237, 0.15);
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.3;
        }

        .sidebar .position-sticky {
            position: relative;
            z-index: 1;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            margin: 4px 8px;
            padding: 12px 16px;
            font-weight: 500;
        }

        .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.15);
            transform: translateX(4px);
            backdrop-filter: blur(10px);
        }

        .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .logo {
            max-height: 45px;
            filter: brightness(0) invert(1);
        }

        .main-content {
            background: var(--light);
            min-height: 100vh;
        }

        .dashboard-header {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .sales-card {
            background: var(--gradient-primary);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .revenue-card {
            background: var(--gradient-secondary);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .performance-card {
            background: var(--gradient-success);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .alert-card {
            background: var(--gradient-warning);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: scale(1.5);
        }

        .quick-action-btn {
            border-radius: 12px;
            padding: 16px 24px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
        }

        .btn-info {
            background: var(--gradient-secondary);
            border: none;
        }

        .btn-warning {
            background: var(--gradient-warning);
            border: none;
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead th {
            background: var(--light);
            border: none;
            font-weight: 600;
            color: var(--dark);
            padding: 16px;
        }

        .table tbody td {
            border: none;
            padding: 16px;
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(124, 58, 237, 0.05);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .alert {
            border: none;
            border-radius: 12px;
            border-left: 4px solid;
        }

        .alert-warning {
            background: rgba(217, 119, 6, 0.1);
            border-left-color: var(--warning);
            color: var(--warning);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px 24px 16px;
        }

        .card-body {
            padding: 24px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: rgba(0,0,0,0.1);
        }

        .progress-bar {
            border-radius: 10px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                z-index: 1050;
                transition: left 0.3s ease;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .dashboard-header {
                margin: 16px;
                padding: 20px;
            }

            .card {
                margin: 0 16px 16px;
            }
        }

        .time-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: var(--cashier-primary);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="logo.png" alt="Torres Farm Hotel" class="logo mb-2">
                        <h6 class="text-white">POS Cashier</h6>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales.php">
                                <i class="fas fa-cash-register me-2"></i>
                                Point of Sale
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="transactions.php">
                                <i class="fas fa-receipt me-2"></i>
                                Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stat-icon me-3" style="background: var(--gradient-primary);">
                                    <i class="fas fa-cash-register text-white fa-lg"></i>
                                </div>
                                <div>
                                    <h1 class="h3 mb-0 fw-bold">POS Cashier Dashboard</h1>
                                    <p class="text-muted mb-0">Process sales and manage transactions efficiently</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-md-end">
                                <div class="text-muted small mb-1">Welcome back,</div>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($user_name); ?></div>
                                <div class="time-display" id="currentDateTime"><?php echo date('M j, Y - g:i A'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Overview Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card sales-card">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-1 opacity-90">Today's Sales</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo $sales_summary['total_sales']; ?></h2>
                                        <small class="opacity-75">+12% from yesterday</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-shopping-cart fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card revenue-card">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-1 opacity-90">Today's Revenue</h6>
                                        <h2 class="mb-0 fw-bold">₱<?php echo number_format($sales_summary['total_revenue'], 2); ?></h2>
                                        <small class="opacity-75">+8% from yesterday</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-peso-sign fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card performance-card">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-1 opacity-90">Avg. Transaction</h6>
                                        <h2 class="mb-0 fw-bold">₱<?php echo $sales_summary['total_sales'] > 0 ? number_format($sales_summary['total_revenue'] / $sales_summary['total_sales'], 2) : '0.00'; ?></h2>
                                        <small class="opacity-75">Per sale amount</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-chart-line fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card alert-card">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-1 opacity-90">Low Stock Items</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo $low_stock_result->num_rows; ?></h2>
                                        <small class="opacity-75">Need attention</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Overview -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Sales Performance</h5>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary active">Today</button>
                                        <button type="button" class="btn btn-outline-primary">Week</button>
                                        <button type="button" class="btn btn-outline-primary">Month</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-primary mb-1"><?php echo $sales_summary['total_sales']; ?></h4>
                                            <small class="text-muted">Total Sales</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-success mb-1">₱<?php echo number_format($sales_summary['total_revenue'], 0); ?></h4>
                                            <small class="text-muted">Revenue</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info mb-1">₱<?php echo $sales_summary['total_sales'] > 0 ? number_format($sales_summary['total_revenue'] / $sales_summary['total_sales'], 0) : '0'; ?></h4>
                                        <small class="text-muted">Avg. Sale</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Sales Target Progress</span>
                                    <span class="fw-semibold">75%</span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-primary" style="width: 75%"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Revenue Target Progress</span>
                                    <span class="fw-semibold">68%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 68%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Payment Methods</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-success text-white me-2">
                                            <i class="fas fa-money-bill"></i>
                                        </div>
                                        <span>Cash</span>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold">65%</div>
                                        <small class="text-muted">₱3,250</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary text-white me-2">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <span>Card</span>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold">25%</div>
                                        <small class="text-muted">₱1,250</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-info text-white me-2">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <span>Digital</span>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold">10%</div>
                                        <small class="text-muted">₱500</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <small class="text-muted">Total Processed: ₱5,000</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Tools -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Quick Actions & Tools</h5>
                                    <span class="badge bg-primary">Cashier Tools</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <a href="sales.php" class="btn btn-primary quick-action-btn w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-cash-register fa-2x mb-2"></i>
                                            <span class="fw-semibold">New Sale</span>
                                            <small class="opacity-75">Process Order</small>
                                        </a>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <a href="transactions.php" class="btn btn-info quick-action-btn w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-receipt fa-2x mb-2"></i>
                                            <span class="fw-semibold">Transactions</span>
                                            <small class="opacity-75">View History</small>
                                        </a>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <button class="btn btn-warning quick-action-btn w-100 h-100 d-flex flex-column justify-content-center" onclick="printDailyReport()">
                                            <i class="fas fa-print fa-2x mb-2"></i>
                                            <span class="fw-semibold">Daily Report</span>
                                            <small class="opacity-75">Print Summary</small>
                                        </button>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <button class="btn btn-success quick-action-btn w-100 h-100 d-flex flex-column justify-content-center" onclick="openCashDrawer()">
                                            <i class="fas fa-cash-register fa-2x mb-2"></i>
                                            <span class="fw-semibold">Cash Drawer</span>
                                            <small class="opacity-75">Open/Close</small>
                                        </button>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <button class="btn btn-secondary quick-action-btn w-100 h-100 d-flex flex-column justify-content-center" onclick="voidTransaction()">
                                            <i class="fas fa-times-circle fa-2x mb-2"></i>
                                            <span class="fw-semibold">Void Sale</span>
                                            <small class="opacity-75">Cancel Order</small>
                                        </button>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <button class="btn btn-dark quick-action-btn w-100 h-100 d-flex flex-column justify-content-center" onclick="endShift()">
                                            <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                                            <span class="fw-semibold">End Shift</span>
                                            <small class="opacity-75">Close Session</small>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History & Inventory Status -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Transactions</h5>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTransactions()">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                        <a href="transactions.php" class="btn btn-sm btn-primary">
                                            <i class="fas fa-external-link-alt"></i> View All
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sale #</th>
                                                <th>Amount</th>
                                                <th>Payment</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_sales->num_rows > 0): ?>
                                                <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar bg-primary text-white me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                                    <i class="fas fa-receipt"></i>
                                                                </div>
                                                                <span class="fw-semibold"><?php echo htmlspecialchars($sale['sale_number']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold text-success">₱<?php echo number_format($sale['final_amount'], 2); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $sale['payment_method'] === 'cash' ? 'success' : ($sale['payment_method'] === 'card' ? 'primary' : 'info'); ?>">
                                                                <i class="fas fa-<?php echo $sale['payment_method'] === 'cash' ? 'money-bill' : ($sale['payment_method'] === 'card' ? 'credit-card' : 'mobile-alt'); ?> me-1"></i>
                                                                <?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted"><?php echo date('g:i A', strtotime($sale['sale_date'])); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Completed
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="fas fa-receipt fa-2x mb-2 opacity-50"></i>
                                                            <p class="mb-0">No sales transactions today</p>
                                                            <small>Start processing orders to see them here</small>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Inventory Alerts</h5>
                                    <?php if ($low_stock_result->num_rows > 0): ?>
                                        <span class="notification-badge"><?php echo $low_stock_result->num_rows; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($low_stock_result->num_rows > 0): ?>
                                    <?php while ($product = $low_stock_result->fetch_assoc()): ?>
                                        <div class="alert alert-warning d-flex align-items-center py-2 mb-2">
                                            <div class="avatar bg-warning text-white me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <small class="text-muted">
                                                    Stock: <span class="fw-semibold"><?php echo $product['stock_quantity']; ?></span> / 
                                                    Min: <span class="fw-semibold"><?php echo $product['min_stock']; ?></span>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                    <div class="text-center mt-3">
                                        <button class="btn btn-sm btn-warning" onclick="notifyManager()">
                                            <i class="fas fa-bell me-1"></i>Notify Manager
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <div class="text-success">
                                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                                            <p class="mb-0 fw-semibold">All Good!</p>
                                            <small class="text-muted">All products are well stocked</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Shift Summary -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Shift Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Shift Started:</span>
                                    <span class="fw-semibold">8:00 AM</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Hours Worked:</span>
                                    <span class="fw-semibold" id="hoursWorked">4h 30m</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Transactions:</span>
                                    <span class="fw-semibold"><?php echo $sales_summary['total_sales']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Total Handled:</span>
                                    <span class="fw-semibold text-success">₱<?php echo number_format($sales_summary['total_revenue'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print Daily Report Function
        function printDailyReport() {
            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleDateString();
            const currentTime = new Date().toLocaleTimeString();
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Daily Sales Report - ${currentDate}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .summary { margin-bottom: 20px; }
                        .transactions { margin-top: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Torres Farm Hotel - Daily Sales Report</h2>
                        <p>Date: ${currentDate} | Time: ${currentTime}</p>
                        <p>Cashier: <?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                    
                    <div class="summary">
                        <h3>Sales Summary</h3>
                        <p>Total Sales: <?php echo $sales_summary['total_sales']; ?></p>
                        <p>Total Revenue: ₱<?php echo number_format($sales_summary['total_revenue'], 2); ?></p>
                    </div>
                    
                    <div class="transactions">
                        <h3>Transaction Details</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Sale Number</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
            `);
            
            <?php 
            // Reset the result pointer for printing
            $recent_sales->data_seek(0);
            while ($sale = $recent_sales->fetch_assoc()): 
            ?>
                printWindow.document.write(`
                    <tr>
                        <td><?php echo htmlspecialchars($sale['sale_number']); ?></td>
                        <td>₱<?php echo number_format($sale['final_amount'], 2); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($sale['sale_date'])); ?></td>
                    </tr>
                `);
            <?php endwhile; ?>
            
            printWindow.document.write(`
                        </tbody>
                    </table>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }

        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const timeElement = document.getElementById('currentDateTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        // Update hours worked
        function updateHoursWorked() {
            const startTime = new Date();
            startTime.setHours(8, 0, 0, 0); // 8:00 AM
            const now = new Date();
            const diff = now - startTime;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            const hoursElement = document.getElementById('hoursWorked');
            if (hoursElement) {
                hoursElement.textContent = `${hours}h ${minutes}m`;
            }
        }

        // Refresh transactions
        function refreshTransactions() {
            showNotification('Refreshing transactions...', 'info');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Notify manager about low stock
        function notifyManager() {
            showNotification('Manager has been notified about low stock items', 'success');
        }

        // Cash drawer function
        function openCashDrawer() {
            showNotification('Cash drawer opened', 'info');
        }

        // Void transaction function
        function voidTransaction() {
            const saleNumber = prompt('Enter sale number to void:');
            if (saleNumber) {
                showNotification(`Sale ${saleNumber} has been voided`, 'warning');
            }
        }

        // End shift function
        function endShift() {
            if (confirm('Are you sure you want to end your shift?')) {
                showNotification('Shift ended successfully', 'success');
                setTimeout(() => {
                    window.location.href = '../logout.php';
                }, 2000);
            }
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} notification-toast`;
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case 'n':
                        e.preventDefault();
                        window.location.href = 'sales.php';
                        break;
                    case 'p':
                        e.preventDefault();
                        printDailyReport();
                        break;
                    case 'r':
                        e.preventDefault();
                        refreshTransactions();
                        break;
                }
            }
        });

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            updateTime();
            updateHoursWorked();
            
            // Update time every second
            setInterval(updateTime, 1000);
            
            // Update hours worked every minute
            setInterval(updateHoursWorked, 60000);
            
            // Add CSS animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                .notification-toast {
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border: none;
                }
            `;
            document.head.appendChild(style);
            
            // Show welcome notification
            setTimeout(() => {
                showNotification('Welcome back! Your shift is active.', 'success');
            }, 1000);
        });
    </script>
</body>
</html>