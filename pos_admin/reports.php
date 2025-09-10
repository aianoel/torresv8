<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Reports & Analytics';

// Get date range from request or default to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'sales';

// Sales Summary
$sales_summary_query = "SELECT 
    COUNT(*) as total_sales,
    SUM(s.final_amount) as total_revenue,
    AVG(s.final_amount) as avg_sale_amount,
    COALESCE(SUM(si.quantity), 0) as total_items_sold
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE s.sale_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sales_summary_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales_summary = $stmt->get_result()->fetch_assoc();

// Daily Sales Chart Data
$daily_sales_query = "SELECT 
    DATE(sale_date) as sale_day,
    COUNT(*) as daily_sales,
    SUM(final_amount) as daily_revenue
    FROM sales 
    WHERE sale_date BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY sale_day";
$stmt = $conn->prepare($daily_sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_sales_result = $stmt->get_result();

// Top Selling Products
$top_products_query = "SELECT 
    p.name as product_name,
    p.price,
    SUM(si.quantity) as total_sold,
    SUM(si.total_price) as total_revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY si.product_id
    ORDER BY total_sold DESC
    LIMIT 10";
$stmt = $conn->prepare($top_products_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_products_result = $stmt->get_result();

// Category Performance
$category_performance_query = "SELECT 
    c.name as category_name,
    COUNT(DISTINCT si.product_id) as products_sold,
    SUM(si.quantity) as total_quantity,
    SUM(si.total_price) as total_revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total_revenue DESC";
$stmt = $conn->prepare($category_performance_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$category_performance_result = $stmt->get_result();

// Low Stock Alert
$low_stock_query = "SELECT 
    p.name,
    p.stock_quantity,
    p.min_stock,
    c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock_quantity <= p.min_stock
    ORDER BY (p.stock_quantity / NULLIF(p.min_stock, 0)) ASC";
$low_stock_result = $conn->query($low_stock_query);

// Recent Sales
$recent_sales_query = "SELECT 
    s.id,
    s.sale_date,
    s.final_amount,
    COALESCE(SUM(si.quantity), 0) as total_items,
    u.username as cashier
    FROM sales s
    LEFT JOIN users u ON s.cashier_id = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY s.id, s.sale_date, s.final_amount, u.username
    ORDER BY s.sale_date DESC
    LIMIT 20";
$stmt = $conn->prepare($recent_sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$recent_sales_result = $stmt->get_result();

// Prepare chart data
$chart_labels = [];
$chart_sales = [];
$chart_revenue = [];

if ($daily_sales_result) {
    while ($row = $daily_sales_result->fetch_assoc()) {
        $chart_labels[] = date('M j', strtotime($row['sale_day']));
        $chart_sales[] = $row['daily_sales'];
        $chart_revenue[] = $row['daily_revenue'];
    }
}
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
        .stat-card {
            border-left: 4px solid var(--primary-color);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .report-filters {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
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
                        <a class="nav-link active" href="reports.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Reports & Analytics</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="exportReport()">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                        <button class="btn btn-primary" onclick="printReport()">
                            <i class="bi bi-printer me-2"></i>Print
                        </button>
                    </div>
                </div>

                <!-- Report Filters -->
                <div class="report-filters">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" name="report_type">
                                <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                                <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                                <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Product Performance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">
                                <i class="bi bi-funnel me-2"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Total Sales</h6>
                                        <h3 class="mb-0"><?php echo number_format($sales_summary['total_sales'] ?? 0); ?></h3>
                                    </div>
                                    <i class="bi bi-receipt display-6 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Total Revenue</h6>
                                        <h3 class="mb-0 text-success">₱<?php echo number_format($sales_summary['total_revenue'] ?? 0, 2); ?></h3>
                                    </div>
                                    <i class="bi bi-currency-dollar display-6 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-left: 4px solid #17a2b8;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Avg Sale Amount</h6>
                                        <h3 class="mb-0 text-info">₱<?php echo number_format($sales_summary['avg_sale_amount'] ?? 0, 2); ?></h3>
                                    </div>
                                    <i class="bi bi-graph-up display-6 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Items Sold</h6>
                                        <h3 class="mb-0 text-warning"><?php echo number_format($sales_summary['total_items_sold'] ?? 0); ?></h3>
                                    </div>
                                    <i class="bi bi-box-seam display-6 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Sales Chart -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daily Sales Trend</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Low Stock Alert</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($item = $low_stock_result->fetch_assoc()): ?>
                                            <div class="list-group-item px-0 py-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?: 'No Category'); ?></small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-<?php echo $item['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                                            <?php echo $item['stock_quantity']; ?>/<?php echo $item['min_stock']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">All products are well stocked!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Top Products -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Sold</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($top_products_result && $top_products_result->num_rows > 0): ?>
                                                <?php while ($product = $top_products_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                                            <br><small class="text-muted">₱<?php echo number_format($product['price'], 2); ?></small>
                                                        </td>
                                                        <td><?php echo $product['total_sold']; ?></td>
                                                        <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">No sales data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Performance -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Category Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Products</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($category_performance_result && $category_performance_result->num_rows > 0): ?>
                                                <?php while ($category = $category_performance_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                                            <br><small class="text-muted"><?php echo $category['total_quantity']; ?> items sold</small>
                                                        </td>
                                                        <td><?php echo $category['products_sold']; ?></td>
                                                        <td>₱<?php echo number_format($category['total_revenue'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">No category data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Sales</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Date & Time</th>
                                        <th>Items</th>
                                        <th>Total Amount</th>
                                        <th>Cashier</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_sales_result && $recent_sales_result->num_rows > 0): ?>
                                        <?php while ($sale = $recent_sales_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?></td>
                                                <td><?php echo $sale['total_items']; ?></td>
                                                <td>₱<?php echo number_format($sale['final_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($sale['cashier'] ?: 'Unknown'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-receipt display-1"></i>
                                                <p class="mt-2">No sales found for the selected period</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Daily Sales',
                    data: <?php echo json_encode($chart_sales); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Daily Revenue (₱)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Sales'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₱)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        function exportReport() {
            // Simple CSV export functionality
            const startDate = '<?php echo $start_date; ?>';
            const endDate = '<?php echo $end_date; ?>';
            window.open(`export_report.php?start_date=${startDate}&end_date=${endDate}&format=csv`, '_blank');
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>