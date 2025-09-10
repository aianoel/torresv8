<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Admin Dashboard';
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
            --gold-color: #D4AF37;
            --dark-bg: #1a1a1a;
            --card-bg: #ffffff;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            border-right: none;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: white;
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: white;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .main-content {
            padding: 30px;
            background-color: #f8f9fa;
        }
        
        .kpi-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        
        .kpi-card .card-body {
            padding: 25px;
        }
        
        .kpi-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .kpi-icon {
            font-size: 3rem;
            opacity: 0.8;
        }
        
        .chart-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .pending-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .performance-badge {
            background: linear-gradient(45deg, var(--primary-color), var(--gold-color));
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
                        <span class="fs-4 text-primary"><?php echo APP_NAME; ?></span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link active">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="users.php" class="nav-link">
                                <i class="bi bi-people me-2"></i>
                                Manage Users
                            </a>
                        </li>
                        <li>
                            <a href="rooms.php" class="nav-link">
                                <i class="bi bi-door-open me-2"></i>
                                Manage Rooms
                            </a>
                        </li>
                        <li>
                            <a href="content_management.php" class="nav-link">
                                <i class="bi bi-file-text me-2"></i>
                                Content Management
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="nav-link">
                                <i class="bi bi-graph-up me-2"></i>
                                Reports
                            </a>
                        </li>
                        <li>
                            <a href="qr_generator.php" class="nav-link">
                                <i class="bi bi-qr-code me-2"></i>
                                QR Generator
                            </a>
                        </li>
                        <li>
                            <a href="leave_management.php" class="nav-link">
                                <i class="bi bi-calendar-check me-2"></i>
                                Leave Management
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <strong>Admin</strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-1">Admin Control Center</h1>
                            <p class="mb-0">Complete overview of hotel operations</p>
                        </div>
                        <div class="text-end">
                            <div class="h5 mb-0"><?php echo date('F j, Y'); ?></div>
                            <small><?php echo date('l, g:i A'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards Row 1 -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card kpi-card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-check kpi-icon"></i>
                                <h5 class="card-title mt-2">Today's Bookings</h5>
                                <div class="kpi-number">12</div>
                                <small>+3 from yesterday</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card kpi-card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-month kpi-icon"></i>
                                <h5 class="card-title mt-2">Monthly Bookings</h5>
                                <div class="kpi-number">287</div>
                                <small>+15% from last month</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card kpi-card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-pie-chart kpi-icon"></i>
                                <h5 class="card-title mt-2">Occupancy Rate</h5>
                                <div class="kpi-number">78%</div>
                                <small>Above target (75%)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card kpi-card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-currency-dollar kpi-icon"></i>
                                <h5 class="card-title mt-2">Daily Revenue</h5>
                                <div class="kpi-number">$8,450</div>
                                <small>Target: $7,500</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-4 mb-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Occupancy Rate</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="occupancyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 mb-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Revenue Overview (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Performance & Pending Approvals Row -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Staff Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="performance-badge mb-2">Excellent</div>
                                        <div class="h4">8</div>
                                        <small class="text-muted">Staff Members</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="badge bg-warning mb-2">Good</div>
                                        <div class="h4">12</div>
                                        <small class="text-muted">Staff Members</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="badge bg-secondary mb-2">Needs Improvement</div>
                                        <div class="h4">3</div>
                                        <small class="text-muted">Staff Members</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="mt-3">
                                    <h6>Top Performers This Month:</h6>
                                    <ul class="list-unstyled">
                                        <li class="d-flex justify-content-between align-items-center mb-2">
                                            <span><i class="bi bi-star-fill text-warning me-2"></i>Maria Santos (Front Desk)</span>
                                            <span class="performance-badge">98%</span>
                                        </li>
                                        <li class="d-flex justify-content-between align-items-center mb-2">
                                            <span><i class="bi bi-star-fill text-warning me-2"></i>John Rodriguez (Housekeeping)</span>
                                            <span class="performance-badge">96%</span>
                                        </li>
                                        <li class="d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-star-fill text-warning me-2"></i>Ana Garcia (Restaurant)</span>
                                            <span class="performance-badge">94%</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card chart-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Pending Approvals</h5>
                                <span class="badge bg-danger">5</span>
                            </div>
                            <div class="card-body">
                                <div class="pending-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">New User Registration</h6>
                                            <small class="text-muted">Carlos Martinez - Front Desk Role</small>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-success me-1"><i class="bi bi-check"></i></button>
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-x"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pending-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Role Change Request</h6>
                                            <small class="text-muted">Lisa Chen - Housekeeping to Front Desk</small>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-success me-1"><i class="bi bi-check"></i></button>
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-x"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pending-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Leave Request</h6>
                                            <small class="text-muted">David Kim - 3 days vacation</small>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-success me-1"><i class="bi bi-check"></i></button>
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-x"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="#" class="btn btn-outline-primary btn-sm">View All Pending Items</a>
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
        // Occupancy Rate Donut Chart
        const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
        new Chart(occupancyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Available', 'Maintenance'],
                datasets: [{
                    data: [78, 18, 4],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107', 
                        '#dc3545'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // Revenue Overview Line Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Room Revenue',
                    data: [6500, 7200, 6800, 8100, 9200, 10500, 8450],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'POS Revenue',
                    data: [1200, 1400, 1100, 1600, 1800, 2100, 1650],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>
</html>