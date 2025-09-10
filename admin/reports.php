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

$pageTitle = 'Reports';

// Get date range from form or default to current month
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Occupancy Report
$occupancyQuery = $conn->prepare("
    SELECT 
        DATE(b.check_in_date) as date,
        COUNT(DISTINCT b.room_id) as occupied_rooms,
        (SELECT COUNT(*) FROM rooms) as total_rooms,
        ROUND((COUNT(DISTINCT b.room_id) / (SELECT COUNT(*) FROM rooms)) * 100, 2) as occupancy_rate
    FROM bookings b 
    WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
    AND DATE(b.check_in_date) BETWEEN ? AND ?
    GROUP BY DATE(b.check_in_date)
    ORDER BY date DESC
");
$occupancyQuery->bind_param("ss", $startDate, $endDate);
$occupancyQuery->execute();
$occupancyData = $occupancyQuery->get_result();

// Revenue Report
$revenueQuery = $conn->prepare("
    SELECT 
        DATE(b.check_in_date) as date,
        SUM(COALESCE(p.amount, r.price_per_night * DATEDIFF(b.check_out_date, b.check_in_date))) as daily_revenue,
        COUNT(b.id) as bookings_count
    FROM bookings b 
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.payment_status = 'completed'
    WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
    AND DATE(b.check_in_date) BETWEEN ? AND ?
    GROUP BY DATE(b.check_in_date)
    ORDER BY date DESC
");
$revenueQuery->bind_param("ss", $startDate, $endDate);
$revenueQuery->execute();
$revenueData = $revenueQuery->get_result();

// Room Type Performance
$roomTypeQuery = $conn->prepare("
    SELECT 
        r.room_type,
        COUNT(b.id) as bookings,
        SUM(COALESCE(p.amount, r.price_per_night * DATEDIFF(b.check_out_date, b.check_in_date))) as revenue,
        AVG(COALESCE(p.amount, r.price_per_night * DATEDIFF(b.check_out_date, b.check_in_date))) as avg_booking_value
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.payment_status = 'completed'
    WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
    AND DATE(b.check_in_date) BETWEEN ? AND ?
    GROUP BY r.room_type
    ORDER BY revenue DESC
");
$roomTypeQuery->bind_param("ss", $startDate, $endDate);
$roomTypeQuery->execute();
$roomTypeData = $roomTypeQuery->get_result();

// Guest Statistics
$guestStatsQuery = $conn->prepare("
    SELECT 
        COUNT(DISTINCT b.guest_id) as unique_guests,
        COUNT(b.id) as total_bookings,
        AVG(DATEDIFF(b.check_out_date, b.check_in_date)) as avg_stay_duration
    FROM bookings b 
    WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
    AND DATE(b.check_in_date) BETWEEN ? AND ?
");
$guestStatsQuery->bind_param("ss", $startDate, $endDate);
$guestStatsQuery->execute();
$guestStats = $guestStatsQuery->get_result()->fetch_assoc();

// Calculate total revenue for the period
$totalRevenueQuery = $conn->prepare("
    SELECT SUM(COALESCE(p.amount, r.price_per_night * DATEDIFF(b.check_out_date, b.check_in_date))) as total_revenue 
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.payment_status = 'completed'
    WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
    AND DATE(b.check_in_date) BETWEEN ? AND ?
");
$totalRevenueQuery->bind_param("ss", $startDate, $endDate);
$totalRevenueQuery->execute();
$totalRevenue = $totalRevenueQuery->get_result()->fetch_assoc()['total_revenue'] ?? 0;

// Calculate average occupancy rate
$avgOccupancyQuery = $conn->prepare("
    SELECT AVG(occupancy_rate) as avg_occupancy FROM (
        SELECT 
            DATE(b.check_in_date) as date,
            (COUNT(DISTINCT b.room_id) / (SELECT COUNT(*) FROM rooms)) * 100 as occupancy_rate
        FROM bookings b 
        WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
        AND DATE(b.check_in_date) BETWEEN ? AND ?
        GROUP BY DATE(b.check_in_date)
    ) as daily_occupancy
");
$avgOccupancyQuery->bind_param("ss", $startDate, $endDate);
$avgOccupancyQuery->execute();
$avgOccupancy = $avgOccupancyQuery->get_result()->fetch_assoc()['avg_occupancy'] ?? 0;

?>

<?php renderAdminHeader($pageTitle); ?>

<style>
    .stat-card {
        background: linear-gradient(135deg, var(--gold-color), #6c757d);
        color: white;
    }
    .chart-container {
        position: relative;
        height: 400px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php renderAdminPageHeader($pageTitle); ?>

<!-- Reports Content Section -->
<div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Reports & Analytics</h1>
                <form method="GET" class="d-flex gap-2">
                    <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                    <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar fs-1 mb-2"></i>
                            <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                            <p class="mb-0">Total Revenue</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="bi bi-graph-up fs-1 mb-2"></i>
                            <h3><?php echo number_format($avgOccupancy, 1); ?>%</h3>
                            <p class="mb-0">Avg Occupancy</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="bi bi-people fs-1 mb-2"></i>
                            <h3><?php echo $guestStats['unique_guests'] ?? 0; ?></h3>
                            <p class="mb-0">Unique Guests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check fs-1 mb-2"></i>
                            <h3><?php echo number_format($guestStats['avg_stay_duration'] ?? 0, 1); ?></h3>
                            <p class="mb-0">Avg Stay (days)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daily Revenue</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Room Type Performance</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="roomTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Tables -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daily Occupancy Report</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Occupied</th>
                                            <th>Total</th>
                                            <th>Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $occupancyData->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['occupied_rooms']; ?></td>
                                                <td><?php echo $row['total_rooms']; ?></td>
                                                <td><?php echo $row['occupancy_rate']; ?>%</td>
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
                            <h5>Daily Revenue Report</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Revenue</th>
                                            <th>Bookings</th>
                                            <th>Avg/Booking</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $revenueData->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td>$<?php echo number_format($row['daily_revenue'], 2); ?></td>
                                                <td><?php echo $row['bookings_count']; ?></td>
                                                <td>$<?php echo number_format($row['daily_revenue'] / $row['bookings_count'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        <?php
        $revenueData->data_seek(0);
        $revenueDates = [];
        $revenueAmounts = [];
        while ($row = $revenueData->fetch_assoc()) {
            $revenueDates[] = date('M d', strtotime($row['date']));
            $revenueAmounts[] = $row['daily_revenue'];
        }
        ?>
        
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse($revenueDates)); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode(array_reverse($revenueAmounts)); ?>,
                    borderColor: '<?php echo APP_THEME_COLOR; ?>',
                    backgroundColor: '<?php echo APP_THEME_COLOR; ?>20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Room Type Chart
        <?php
        $roomTypes = [];
        $roomTypeRevenues = [];
        while ($row = $roomTypeData->fetch_assoc()) {
            $roomTypes[] = ucfirst($row['room_type']);
            $roomTypeRevenues[] = $row['revenue'];
        }
        ?>
        
        const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
        new Chart(roomTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($roomTypes); ?>,
                datasets: [{
                    data: <?php echo json_encode($roomTypeRevenues); ?>,
                    backgroundColor: [
                        '<?php echo APP_THEME_COLOR; ?>',
                        '#6c757d',
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

<?php renderAdminFooter(); ?>