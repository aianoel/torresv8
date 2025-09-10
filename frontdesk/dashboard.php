<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is frontdesk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'frontdesk') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Front Desk Dashboard';

// Get dashboard statistics
$todayBookingsQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE DATE(check_in_date) = CURDATE() 
    AND status IN ('confirmed', 'checked_in')
");
$todayBookingsQuery->execute();
$todayBookings = $todayBookingsQuery->get_result()->fetch_assoc()['count'];

$todayCheckoutsQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE DATE(check_out_date) = CURDATE() 
    AND status = 'checked_in'
");
$todayCheckoutsQuery->execute();
$todayCheckouts = $todayCheckoutsQuery->get_result()->fetch_assoc()['count'];

$currentGuestsQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE status = 'checked_in'
");
$currentGuestsQuery->execute();
$currentGuests = $currentGuestsQuery->get_result()->fetch_assoc()['count'];

$availableRoomsQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM rooms r 
    WHERE r.id NOT IN (
        SELECT DISTINCT room_id 
        FROM bookings 
        WHERE status = 'checked_in'
    )
");
$availableRoomsQuery->execute();
$availableRooms = $availableRoomsQuery->get_result()->fetch_assoc()['count'];

// Get recent bookings
$recentBookingsQuery = $conn->prepare("
    SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type 
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    ORDER BY b.created_at DESC 
    LIMIT 10
");
$recentBookingsQuery->execute();
$recentBookings = $recentBookingsQuery->get_result();

// Get today's check-ins
$todayCheckinsQuery = $conn->prepare("
    SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type 
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE DATE(b.check_in_date) = CURDATE() 
    AND b.status = 'confirmed'
    ORDER BY b.check_in_date ASC
");
$todayCheckinsQuery->execute();
$todayCheckins = $todayCheckinsQuery->get_result();

// Get today's check-outs
$todayCheckoutsListQuery = $conn->prepare("
    SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type 
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE DATE(b.check_out_date) = CURDATE() 
    AND b.status = 'checked_in'
    ORDER BY b.check_out_date ASC
");
$todayCheckoutsListQuery->execute();
$todayCheckoutsList = $todayCheckoutsListQuery->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
            --gold-color: #D4AF37;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
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
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
            }
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .search-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .calendar-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .room-card {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            color: white;
            font-weight: bold;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .room-card:hover {
            transform: scale(1.05);
        }
        
        .room-available {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .room-occupied {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
        }
        
        .room-maintenance {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        
        .room-checkout {
            background: linear-gradient(135deg, #6f42c1, #e83e8c);
        }
        
        .arrivals-card, .departures-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 400px;
            overflow-y: auto;
        }
        
        .guest-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        
        .guest-item:hover {
            background-color: #f8f9fa;
        }
        
        .guest-item:last-child {
            border-bottom: none;
        }
        
        .time-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .fc-event {
            border: none !important;
            background: var(--primary-color) !important;
        }
        
        .search-input {
            border-radius: 25px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="col-md-2 sidebar position-fixed">
        <div class="p-3">
            <h4 class="text-white"><?php echo APP_NAME; ?></h4>
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reservations.php">
                        <i class="bi bi-calendar-check"></i> Reservations
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person"></i> <?php echo $_SESSION['first_name']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-md-10 main-content">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">Front Desk Control Center</h1>
                        <p class="mb-0">Real-time reservations & guest management</p>
                    </div>
                    <div class="text-end">
                        <div class="h5 mb-0">Welcome, <?php echo $_SESSION['first_name']; ?></div>
                        <small><?php echo date('l, F j, Y - g:i A'); ?></small>
                    </div>
                </div>
            </div>

            <!-- Quick Search -->
            <div class="search-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control search-input border-start-0" 
                                   placeholder="Search guest name, booking reference, room number..." 
                                   id="guestSearch">
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle me-2"></i>New Booking
                        </button>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-calendar-check me-2"></i>Quick Check-in
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-plus fs-1 mb-2"></i>
                            <h3><?php echo $todayBookings; ?></h3>
                            <p class="mb-0">Today's Arrivals</p>
                            <small>Expected check-ins</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-minus fs-1 mb-2"></i>
                            <h3><?php echo $todayCheckouts; ?></h3>
                            <p class="mb-0">Today's Departures</p>
                            <small>Expected check-outs</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-people fs-1 mb-2"></i>
                            <h3><?php echo $currentGuests; ?></h3>
                            <p class="mb-0">Current Guests</p>
                            <small>In-house</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-door-open fs-1 mb-2"></i>
                            <h3><?php echo $availableRooms; ?></h3>
                            <p class="mb-0">Available Rooms</p>
                            <small>Ready for booking</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="row">
                <!-- Calendar View -->
                <div class="col-lg-8 mb-4">
                    <div class="card calendar-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar3 me-2"></i>Booking Calendar
                            </h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary active">Month</button>
                                <button class="btn btn-outline-primary">Week</button>
                                <button class="btn btn-outline-primary">Day</button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>

                <!-- Room Availability Map -->
                <div class="col-lg-4 mb-4">
                    <div class="card room-grid-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-grid-3x3 me-2"></i>Room Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="room-legend mb-3">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-success">Available</span>
                                    <span class="badge bg-danger">Occupied</span>
                                    <span class="badge bg-warning">Maintenance</span>
                                    <span class="badge bg-secondary">Cleaning</span>
                                </div>
                            </div>
                            <div class="room-grid">
                                <!-- Room grid will be populated by JavaScript -->
                                <div class="row g-2">
                                    <?php for($i = 101; $i <= 120; $i++): ?>
                                    <div class="col-3">
                                        <div class="room-item available" data-room="<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Arrivals and Departures -->
            <div class="row">
                <!-- Today's Arrivals -->
                <div class="col-lg-6 mb-4">
                    <div class="card arrivals-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-down-circle text-success me-2"></i>Today's Arrivals
                            </h5>
                            <span class="badge bg-success"><?php echo $todayCheckins->num_rows; ?> guests</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="arrivals-list">
                                <?php if ($todayCheckins->num_rows > 0): ?>
                                    <?php while ($checkin = $todayCheckins->fetch_assoc()): ?>
                                    <div class="guest-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="guest-info">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($checkin['first_name'] . ' ' . $checkin['last_name']); ?></h6>
                                                <small class="text-muted">
                                                    Room <?php echo $checkin['room_number']; ?> • 
                                                    <?php echo date('g:i A', strtotime($checkin['check_in_date'])); ?>
                                                </small>
                                            </div>
                                            <div class="arrival-actions">
                                                <a href="reservations.php?action=checkin&id=<?php echo $checkin['id']; ?>" class="btn btn-sm btn-success me-1">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-x text-muted fs-1"></i>
                                        <p class="text-muted mt-2">No arrivals scheduled for today</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Departures -->
                <div class="col-lg-6 mb-4">
                    <div class="card departures-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-up-circle text-warning me-2"></i>Today's Departures
                            </h5>
                            <span class="badge bg-warning"><?php echo $todayCheckoutsList->num_rows; ?> guests</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="departures-list">
                                <?php if ($todayCheckoutsList->num_rows > 0): ?>
                                    <?php while ($checkout = $todayCheckoutsList->fetch_assoc()): ?>
                                    <div class="guest-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="guest-info">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($checkout['first_name'] . ' ' . $checkout['last_name']); ?></h6>
                                                <small class="text-muted">
                                                    Room <?php echo $checkout['room_number']; ?> • 
                                                    <?php echo date('g:i A', strtotime($checkout['check_out_date'])); ?>
                                                </small>
                                            </div>
                                            <div class="departure-actions">
                                                <a href="reservations.php?action=checkout&id=<?php echo $checkout['id']; ?>" class="btn btn-sm btn-warning me-1">
                                                    <i class="bi bi-box-arrow-right"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-receipt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-check text-muted fs-1"></i>
                                        <p class="text-muted mt-2">No departures scheduled for today</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Bookings</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $booking['id']; ?></td>
                                                <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                                <td><?php echo $booking['room_number'] . ' (' . ucfirst($booking['room_type']) . ')'; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $booking['status'] === 'confirmed' ? 'primary' : 
                                                            ($booking['status'] === 'checked_in' ? 'success' : 
                                                            ($booking['status'] === 'checked_out' ? 'secondary' : 'warning')); 
                                                    ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    // Sample events - replace with actual booking data
                    {
                        title: 'Check-in: John Doe',
                        start: '<?php echo date('Y-m-d'); ?>',
                        color: '#28a745'
                    },
                    {
                        title: 'Check-out: Jane Smith',
                        start: '<?php echo date('Y-m-d'); ?>',
                        color: '#ffc107'
                    }
                ],
                eventClick: function(info) {
                    alert('Event: ' + info.event.title);
                },
                height: 'auto'
            });
            calendar.render();
            
            // Room grid interactions
            document.querySelectorAll('.room-item').forEach(function(room) {
                room.addEventListener('click', function() {
                    const roomNumber = this.dataset.room;
                    alert('Room ' + roomNumber + ' selected');
                });
            });
            
            // Guest search functionality
            document.getElementById('guestSearch').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                // Implement search logic here
                console.log('Searching for:', searchTerm);
            });
        });
    </script>
</body>
</html>