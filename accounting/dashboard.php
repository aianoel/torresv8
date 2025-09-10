<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is accounting
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'accounting') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Accounting Dashboard';

// Get financial statistics
$totalRevenueQuery = $conn->prepare("
    SELECT COALESCE(SUM(p.amount), 0) as total 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE p.payment_status = 'completed'
    AND MONTH(p.transaction_date) = MONTH(CURDATE())
    AND YEAR(p.transaction_date) = YEAR(CURDATE())
");
$totalRevenueQuery->execute();
$monthlyRevenue = $totalRevenueQuery->get_result()->fetch_assoc()['total'];

$pendingPaymentsQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM payments 
    WHERE payment_status = 'pending'
");
$pendingPaymentsQuery->execute();
$pendingPayments = $pendingPaymentsQuery->get_result()->fetch_assoc()['count'];

$todayRevenueQuery = $conn->prepare("
    SELECT COALESCE(SUM(p.amount), 0) as total 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE p.payment_status = 'completed'
    AND DATE(p.transaction_date) = CURDATE()
");
$todayRevenueQuery->execute();
$todayRevenue = $todayRevenueQuery->get_result()->fetch_assoc()['total'];

$recentTransactionsQuery = $conn->prepare("
    SELECT p.id, p.amount, p.payment_status, p.transaction_date,
           CONCAT(g.first_name, ' ', g.last_name) as guest_name,
           r.room_number
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY p.transaction_date DESC
    LIMIT 10
");
$recentTransactionsQuery->execute();
$recentTransactions = $recentTransactionsQuery->get_result();
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
            --primary: #2563eb;
            --gold: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #334155 100%);
            border: none;
            position: fixed;
            width: 280px;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #cbd5e1;
            border-radius: 12px;
            margin: 4px 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            transform: translateX(4px);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        /* Financial Cards */
        .financial-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .financial-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--info));
        }
        
        .financial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .expense-card {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .profit-card {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }
        
        .pending-card {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .metric-label {
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.9;
        }
        
        /* Transaction Cards */
        .transaction-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .transaction-item {
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        
        .transaction-item:hover {
            background: rgba(59, 130, 246, 0.02);
            border-radius: 8px;
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        /* Chart Cards */
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        
        /* Payment Status Cards */
        .payment-status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .payment-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .payment-item:last-child {
            border-bottom: none;
        }
        
        /* Quick Actions */
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .quick-action-btn i {
            font-size: 1.2rem;
            margin-right: 12px;
            width: 24px;
        }
        
        /* Avatar styles */
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }
        
        /* Tables */
        .table {
            background: transparent;
        }
        
        .table th {
            border: none;
            font-weight: 600;
            color: var(--dark);
            background: rgba(248, 250, 252, 0.8);
            padding: 15px;
        }
        
        .table td {
            border: none;
            padding: 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .table tbody tr:hover {
            background: rgba(59, 130, 246, 0.02);
        }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* Progress bars */
        .progress {
            height: 8px;
            border-radius: 10px;
            background: rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <div class="d-flex align-items-center mb-3">
            <img src="../assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo" class="me-2" style="height: 40px;">
            <span class="fs-4 text-primary fw-bold"><?php echo APP_NAME; ?></span>
        </div>
        <hr>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="invoices.php">
                    <i class="bi bi-receipt me-2"></i> Invoices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="payments.php">
                    <i class="bi bi-credit-card me-2"></i> Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="bi bi-graph-up me-2"></i> Financial Reports
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-2"></i>
                <strong><?php echo $_SESSION['first_name']; ?></strong>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2 text-dark fw-bold">Financial Management Dashboard</h1>
                        <p class="text-muted mb-0">Monitor revenue, track expenses, and manage financial operations</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex flex-column align-items-end">
                            <span class="text-muted small">Welcome back, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!</span>
                            <span class="text-dark fw-semibold"><?php echo date('F j, Y'); ?></span>
                            <span class="text-primary small" id="current-time"><?php echo date('g:i A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Overview Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="financial-card revenue-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value">$<?php echo number_format($monthlyRevenue, 0); ?></div>
                            <div class="metric-label">Monthly Revenue</div>
                            <div class="mt-2">
                                <small class="opacity-75">
                                    <i class="bi bi-arrow-up"></i> +12.5% from last month
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <i class="bi bi-currency-dollar" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="financial-card expense-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value">$<?php echo number_format($monthlyRevenue * 0.65, 0); ?></div>
                            <div class="metric-label">Monthly Expenses</div>
                            <div class="mt-2">
                                <small class="opacity-75">
                                    <i class="bi bi-arrow-down"></i> -3.2% from last month
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <i class="bi bi-graph-down" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="financial-card profit-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value">$<?php echo number_format($monthlyRevenue * 0.35, 0); ?></div>
                            <div class="metric-label">Net Profit</div>
                            <div class="mt-2">
                                <small class="opacity-75">
                                    <i class="bi bi-arrow-up"></i> +18.7% from last month
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <i class="bi bi-graph-up" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="financial-card pending-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value"><?php echo $pendingPayments; ?></div>
                            <div class="metric-label">Pending Payments</div>
                            <div class="mt-2">
                                <small class="opacity-75">
                                    <i class="bi bi-clock"></i> <?php echo $pendingPayments; ?> transactions
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <i class="bi bi-hourglass-split" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue vs Expenses Chart -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Revenue vs Expenses Trend</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active">7D</button>
                            <button type="button" class="btn btn-outline-primary">30D</button>
                            <button type="button" class="btn btn-outline-primary">90D</button>
                        </div>
                    </div>
                    <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.02); border-radius: 10px;">
                        <div class="text-center text-muted">
                            <i class="bi bi-bar-chart" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0">Chart will be rendered here</p>
                            <small>Integration with Chart.js pending</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="payment-status-card">
                    <h5 class="card-title mb-3">Payment Status Overview</h5>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-success">Completed</span>
                            <div class="small text-muted">This month</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">$<?php echo number_format($monthlyRevenue * 0.85, 0); ?></div>
                            <div class="small text-muted">85%</div>
                        </div>
                    </div>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-warning">Pending</span>
                            <div class="small text-muted">Awaiting payment</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-warning">$<?php echo number_format($monthlyRevenue * 0.12, 0); ?></div>
                            <div class="small text-muted">12%</div>
                        </div>
                    </div>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-danger">Overdue</span>
                            <div class="small text-muted">Past due date</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-danger">$<?php echo number_format($monthlyRevenue * 0.03, 0); ?></div>
                            <div class="small text-muted">3%</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                            <div class="progress-bar bg-warning" style="width: 12%"></div>
                            <div class="progress-bar bg-danger" style="width: 3%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Management & POS Integration -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="transaction-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Transactions</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <button class="btn btn-outline-success btn-sm">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <?php if ($recentTransactions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Type</th>
                                        <th>Guest/Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = $recentTransactions->fetch_assoc()): ?>
                                    <tr class="transaction-item">
                                        <td>
                                            <div class="fw-semibold">#<?php echo $transaction['id']; ?></div>
                                            <small class="text-muted">Room <?php echo $transaction['room_number']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="bi bi-building"></i> Hotel
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($transaction['guest_name']); ?></div>
                                                    <small class="text-muted">Guest</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">$<?php echo number_format($transaction['amount'], 2); ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            $statusIcon = '';
                                            switch($transaction['payment_status']) {
                                                case 'completed':
                                                    $statusClass = 'success';
                                                    $statusIcon = 'check-circle';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'warning';
                                                    $statusIcon = 'clock';
                                                    break;
                                                case 'failed':
                                                    $statusClass = 'danger';
                                                    $statusIcon = 'x-circle';
                                                    break;
                                                default:
                                                    $statusClass = 'secondary';
                                                    $statusIcon = 'question-circle';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <i class="bi bi-<?php echo $statusIcon; ?>"></i>
                                                <?php echo ucfirst($transaction['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></div>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($transaction['transaction_date'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Print Receipt">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <!-- Sample POS Transaction -->
                                    <tr class="transaction-item">
                                        <td>
                                            <div class="fw-semibold">#POS-001</div>
                                            <small class="text-muted">Restaurant</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-cup-hot"></i> POS
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">Walk-in Customer</div>
                                                    <small class="text-muted">Table 5</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">$1,250.00</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i>
                                                Paid
                                            </span>
                                        </td>
                                        <td>
                                            <div><?php echo date('M j, Y'); ?></div>
                                            <small class="text-muted"><?php echo date('g:i A'); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Print Receipt">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No recent transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="transaction-card">
                    <h5 class="card-title mb-3">POS Sales Summary</h5>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold">Restaurant Sales</span>
                            <div class="small text-muted">Today</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">$15,750</div>
                            <div class="small text-success">+8.5%</div>
                        </div>
                    </div>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold">Bar Sales</span>
                            <div class="small text-muted">Today</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">$8,920</div>
                            <div class="small text-success">+12.3%</div>
                        </div>
                    </div>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold">Room Service</span>
                            <div class="small text-muted">Today</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">$4,330</div>
                            <div class="small text-warning">-2.1%</div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total POS Sales</span>
                        <span class="fw-bold text-primary fs-5">$29,000</span>
                    </div>
                    <div class="mt-3">
                        <a href="../pos/reports.php" class="btn btn-primary w-100">
                            <i class="bi bi-graph-up"></i> View POS Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Tools & Quick Actions -->
        <div class="row mt-4">
            <div class="col-lg-8 mb-3">
                <div class="transaction-card">
                    <h5 class="card-title mb-3">Financial Tools & Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="invoices.php" class="quick-action-btn">
                                <i class="bi bi-file-earmark-plus"></i>
                                <div>
                                    <div class="fw-semibold">Generate Invoice</div>
                                    <small class="text-muted">Create new invoices for bookings</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="payments.php" class="quick-action-btn">
                                <i class="bi bi-credit-card"></i>
                                <div>
                                    <div class="fw-semibold">Process Payment</div>
                                    <small class="text-muted">Handle payment transactions</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="reports.php" class="quick-action-btn">
                                <i class="bi bi-graph-up"></i>
                                <div>
                                    <div class="fw-semibold">Financial Reports</div>
                                    <small class="text-muted">Access detailed financial reports</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="../pos/admin/dashboard.php" class="quick-action-btn">
                                <i class="bi bi-shop"></i>
                                <div>
                                    <div class="fw-semibold">POS Management</div>
                                    <small class="text-muted">Manage POS operations</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="#" class="quick-action-btn" onclick="exportFinancialData()">
                                <i class="bi bi-download"></i>
                                <div>
                                    <div class="fw-semibold">Export Data</div>
                                    <small class="text-muted">Download financial data</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="#" class="quick-action-btn" onclick="reconcileAccounts()">
                                <i class="bi bi-check2-square"></i>
                                <div>
                                    <div class="fw-semibold">Reconcile Accounts</div>
                                    <small class="text-muted">Balance account records</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="transaction-card">
                    <h5 class="card-title mb-3">Financial Summary</h5>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-success">Total Collected</span>
                            <div class="small text-muted">This month</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">$<?php echo number_format($monthlyRevenue * 0.85, 0); ?></div>
                            <div class="small text-success">85% of revenue</div>
                        </div>
                    </div>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-warning">Outstanding</span>
                            <div class="small text-muted">Pending collection</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-warning">$<?php echo number_format($monthlyRevenue * 0.15, 0); ?></div>
                            <div class="small text-warning">15% of revenue</div>
                        </div>
                    </div>
                    <div class="payment-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-info">Cash Flow</span>
                            <div class="small text-muted">Net movement</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-info">$<?php echo number_format($monthlyRevenue * 0.25, 0); ?></div>
                            <div class="small text-info">Positive trend</div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Collection Rate</span>
                        <span class="fw-bold text-success fs-5">85%</span>
                    </div>
                    <div class="mt-2">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary w-100" onclick="generateFinancialReport()">
                            <i class="bi bi-file-earmark-text"></i> Generate Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportFinancialData() {
            alert('Financial data export functionality will be implemented here.');
        }
        
        function reconcileAccounts() {
            alert('Account reconciliation functionality will be implemented here.');
        }
        
        function generateFinancialReport() {
            window.location.href = 'reports.php';
        }
        
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Update time every minute
        setInterval(updateTime, 60000);
    </script>
</body>
</html>