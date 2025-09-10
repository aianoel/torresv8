<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/admin_layout.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Admin Dashboard';
?>
<?php renderAdminHeader($pageTitle, 'dashboard'); ?>
    <style>
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
    </style>
<?php renderAdminPageHeader('Admin Control Center', 'Complete overview of hotel operations'); ?>

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

<?php renderAdminFooter(); ?>
    
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