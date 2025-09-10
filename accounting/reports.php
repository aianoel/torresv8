<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is accounting
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'accounting') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Financial Reports';

// Get date range from form or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Revenue Report
$revenueQuery = $conn->prepare("
    SELECT 
        DATE(p.transaction_date) as date,
        SUM(p.amount) as daily_revenue,
        COUNT(p.id) as transaction_count
    FROM payments p
    WHERE p.payment_status = 'completed'
    AND DATE(p.transaction_date) BETWEEN ? AND ?
    GROUP BY DATE(p.transaction_date)
    ORDER BY date DESC
");
$revenueQuery->bind_param('ss', $startDate, $endDate);
$revenueQuery->execute();
$revenueData = $revenueQuery->get_result();

// Payment Methods Report
$paymentMethodsQuery = $conn->prepare("
    SELECT 
        p.payment_method,
        COUNT(*) as count,
        SUM(p.amount) as total_amount
    FROM payments p
    WHERE p.payment_status = 'completed'
    AND DATE(p.transaction_date) BETWEEN ? AND ?
    GROUP BY p.payment_method
");
$paymentMethodsQuery->bind_param('ss', $startDate, $endDate);
$paymentMethodsQuery->execute();
$paymentMethods = $paymentMethodsQuery->get_result();

// Room Revenue Report
$roomRevenueQuery = $conn->prepare("
    SELECT 
        r.room_type,
        COUNT(b.id) as bookings,
        SUM(p.amount) as revenue
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN rooms r ON b.room_id = r.id
    WHERE p.payment_status = 'completed'
    AND DATE(p.transaction_date) BETWEEN ? AND ?
    GROUP BY r.room_type
    ORDER BY revenue DESC
");
$roomRevenueQuery->bind_param('ss', $startDate, $endDate);
$roomRevenueQuery->execute();
$roomRevenue = $roomRevenueQuery->get_result();

// Summary Statistics
$summaryQuery = $conn->prepare("
    SELECT 
        SUM(p.amount) as total_revenue,
        COUNT(p.id) as total_transactions,
        AVG(p.amount) as avg_transaction
    FROM payments p
    WHERE p.payment_status = 'completed'
    AND DATE(p.transaction_date) BETWEEN ? AND ?
");
$summaryQuery->bind_param('ss', $startDate, $endDate);
$summaryQuery->execute();
$summary = $summaryQuery->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center px-3 mb-3">
                        <img src="../assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo" class="me-2" style="height: 32px;">
                        <span class="fw-bold text-primary"><?php echo APP_NAME; ?></span>
                    </div>
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Accounting</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments.php">
                                <i class="bi bi-credit-card"></i> Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="invoices.php">
                                <i class="bi bi-receipt"></i> Invoices
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Financial Reports</h1>
                </div>

                <!-- Date Range Filter -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Generate Report</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Total Revenue</h5>
                                <h3>$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Total Transactions</h5>
                                <h3><?php echo number_format($summary['total_transactions'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Average Transaction</h5>
                                <h3>$<?php echo number_format($summary['avg_transaction'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Revenue Chart -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Daily Revenue Trend</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" width="400" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods and Room Revenue -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Payment Methods</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th>Count</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($method = $paymentMethods->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $method['payment_method'])); ?></td>
                                                <td><?php echo $method['count']; ?></td>
                                                <td>$<?php echo number_format($method['total_amount'], 2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Revenue by Room Type</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Room Type</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($room = $roomRevenue->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $room['room_type']; ?></td>
                                                <td><?php echo $room['bookings']; ?></td>
                                                <td>$<?php echo number_format($room['revenue'], 2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Revenue Table -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Daily Revenue Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Revenue</th>
                                                <th>Transactions</th>
                                                <th>Average</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($revenue = $revenueData->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($revenue['date'])); ?></td>
                                                <td>$<?php echo number_format($revenue['daily_revenue'], 2); ?></td>
                                                <td><?php echo $revenue['transaction_count']; ?></td>
                                                <td>$<?php echo number_format($revenue['daily_revenue'] / $revenue['transaction_count'], 2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php 
                    $revenueData->data_seek(0);
                    $dates = [];
                    while ($row = $revenueData->fetch_assoc()) {
                        $dates[] = date('M d', strtotime($row['date']));
                    }
                    echo json_encode(array_reverse($dates));
                ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php 
                        $revenueData->data_seek(0);
                        $amounts = [];
                        while ($row = $revenueData->fetch_assoc()) {
                            $amounts[] = floatval($row['daily_revenue']);
                        }
                        echo json_encode(array_reverse($amounts));
                    ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>