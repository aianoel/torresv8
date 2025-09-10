<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Check if user is logged in and has housekeeping role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'housekeeping') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['username'];

// Get room status data
$roomStatusQuery = $conn->prepare("
    SELECT 
        r.room_number,
        r.room_type,
        r.status,
        CASE 
            WHEN b.id IS NOT NULL AND b.status IN ('confirmed', 'checked_in') THEN 'Occupied'
            ELSE r.status
        END as current_status,
        b.check_out_date
    FROM rooms r
    LEFT JOIN bookings b ON r.id = b.room_id 
        AND b.status IN ('confirmed', 'checked_in') 
        AND CURDATE() BETWEEN b.check_in_date AND b.check_out_date
    ORDER BY r.room_number
");
$roomStatusQuery->execute();
$rooms = $roomStatusQuery->get_result();

// Get maintenance requests
$maintenanceQuery = $conn->prepare("
    SELECT 
        r.room_number,
        'Maintenance Required' as issue,
        r.updated_at as reported_date
    FROM rooms r
    WHERE r.status = 'maintenance'
    ORDER BY r.updated_at DESC
");
$maintenanceQuery->execute();
$maintenanceRequests = $maintenanceQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeeping Dashboard - Torres Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --gold-color: #f39c12;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-content {
            padding: 2rem;
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            margin: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--gold-color) 0%, #e67e22 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .room-clean {
            background: linear-gradient(135deg, var(--success-color) 0%, #2ecc71 100%);
            color: white;
        }
        
        .room-dirty {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
        }
        
        .room-in-progress {
            background: linear-gradient(135deg, var(--warning-color) 0%, #e67e22 100%);
            color: white;
        }
        
        .room-occupied {
            background: linear-gradient(135deg, var(--info-color) 0%, #2980b9 100%);
            color: white;
        }
        
        .room-maintenance {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }
        
        .task-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
        }
        
        .notification-card {
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .progress-card {
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .room-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .room-status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }
        
        .task-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--gold-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        
        .btn-action {
            border-radius: 20px;
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-house-gear me-2"></i>Housekeeping Control
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($user_name); ?>
                </span>
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">Housekeeping Command Center</h1>
                    <p class="mb-0">Room status tracking & task management</p>
                </div>
                <div class="text-end">
                    <div class="h5 mb-0"><?php echo date('l, F j, Y'); ?></div>
                    <small><?php echo date('g:i A'); ?></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Room Status Grid -->
            <div class="col-lg-8 mb-4">
                <div class="card task-card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">
                            <i class="bi bi-grid-3x3 me-2"></i>Room Status Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="room-grid">
                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                <?php 
                                    $roomClass = 'room-clean';
                                    $statusIcon = 'bi bi-check-circle';
                                    $statusText = 'Clean';
                                    $statusBadge = 'bg-success';
                                    
                                    if ($room['current_status'] === 'Occupied') {
                                        $roomClass = 'room-occupied';
                                        $statusIcon = 'bi bi-person-fill';
                                        $statusText = 'Occupied';
                                        $statusBadge = 'bg-info';
                                    } elseif ($room['status'] === 'maintenance') {
                                        $roomClass = 'room-maintenance';
                                        $statusIcon = 'bi bi-tools';
                                        $statusText = 'Maintenance';
                                        $statusBadge = 'bg-warning';
                                    } elseif ($room['status'] === 'cleaning') {
                                        $roomClass = 'room-in-progress';
                                        $statusIcon = 'bi bi-arrow-clockwise';
                                        $statusText = 'In Progress';
                                        $statusBadge = 'bg-warning';
                                    } elseif ($room['status'] === 'dirty') {
                                        $roomClass = 'room-dirty';
                                        $statusIcon = 'bi bi-exclamation-triangle';
                                        $statusText = 'Dirty';
                                        $statusBadge = 'bg-danger';
                                    }
                                ?>
                                <div class="room-card <?php echo $roomClass; ?>" onclick="selectRoom('<?php echo $room['room_number']; ?>')">
                                    <div class="card-body text-center">
                                        <div class="room-number mb-2"><?php echo htmlspecialchars($room['room_number']); ?></div>
                                        <i class="<?php echo $statusIcon; ?> fs-3 mb-2"></i>
                                        <div class="room-status-badge badge <?php echo $statusBadge; ?> mb-2">
                                            <?php echo $statusText; ?>
                                        </div>
                                        <div class="small"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                        <?php if ($room['check_out_date']): ?>
                                            <div class="small mt-1 opacity-75">
                                                Checkout: <?php echo date('M j', strtotime($room['check_out_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($room['current_status'] !== 'Occupied'): ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-light btn-action me-1" onclick="event.stopPropagation(); updateRoomStatus('<?php echo $room['room_number']; ?>', 'cleaning')">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light btn-action" onclick="event.stopPropagation(); updateRoomStatus('<?php echo $room['room_number']; ?>', 'maintenance')">
                                                    <i class="bi bi-tools"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Tasks -->
            <div class="col-md-6">
                <div class="task-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Today's Tasks</h5>
                        <span class="badge bg-primary">5 pending</span>
                    </div>
                    <div class="card-body">
                        <div class="task-item">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-3" id="task1">
                                <div class="flex-grow-1">
                                    <div class="task-title">Clean Room 101</div>
                                    <div class="task-meta">Standard cleaning • Est. 30 min</div>
                                </div>
                                <span class="badge bg-warning">High</span>
                            </div>
                        </div>
                        <div class="task-item">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-3" id="task2">
                                <div class="flex-grow-1">
                                    <div class="task-title">Restock Room 205</div>
                                    <div class="task-meta">Towels and amenities • Est. 15 min</div>
                                </div>
                                <span class="badge bg-info">Medium</span>
                            </div>
                        </div>
                        <div class="task-item">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-3" id="task3">
                                <div class="flex-grow-1">
                                    <div class="task-title">Deep clean Room 310</div>
                                    <div class="task-meta">Post-checkout cleaning • Est. 45 min</div>
                                </div>
                                <span class="badge bg-danger">Urgent</span>
                            </div>
                        </div>
                        <?php if ($maintenanceRequests->num_rows > 0): ?>
                            <?php while ($request = $maintenanceRequests->fetch_assoc()): ?>
                                <div class="task-item">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input me-3" id="maintenance_<?php echo $request['room_number']; ?>">
                                        <div class="flex-grow-1">
                                            <div class="task-title">Maintenance: Room <?php echo htmlspecialchars($request['room_number']); ?></div>
                                            <div class="task-meta"><?php echo htmlspecialchars($request['issue']); ?> • Reported: <?php echo date('M j', strtotime($request['reported_date'])); ?></div>
                                        </div>
                                        <span class="badge bg-warning">Maintenance</span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Notifications & Progress -->
            <div class="col-md-6">
                <div class="notification-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="notification-item urgent">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-3 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="notification-title">Urgent: Room 205 checkout in 30 min</div>
                                    <div class="notification-time">2 minutes ago</div>
                                </div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill text-info me-3 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="notification-title">New guest checking into Room 310 at 3 PM</div>
                                    <div class="notification-time">15 minutes ago</div>
                                </div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="notification-title">Room 101 cleaning completed</div>
                                    <div class="notification-time">1 hour ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="progress-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Today's Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress-item mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="progress-label">Tasks Completed</span>
                                <span class="progress-value">8/12</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 67%"></div>
                            </div>
                        </div>
                        <div class="progress-item mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="progress-label">Rooms Cleaned</span>
                                <span class="progress-value">15/20</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 75%"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="progress-label">Maintenance Issues</span>
                                <span class="progress-value">2/5</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 40%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedRoom = null;
        
        function updateRoomStatus(roomNumber, status) {
            fetch('../api/update_room_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_number: roomNumber,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update room card visually without full reload
                    updateRoomCardVisually(roomNumber, status);
                    showNotification(`Room ${roomNumber} status updated to ${status}`, 'success');
                } else {
                    showNotification('Error updating room status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating room status', 'error');
            });
        }
        
        function selectRoom(roomNumber) {
            // Remove previous selection
            document.querySelectorAll('.room-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked room
            event.currentTarget.classList.add('selected');
            selectedRoom = roomNumber;
            
            showNotification(`Room ${roomNumber} selected`, 'info');
        }
        
        function updateRoomCardVisually(roomNumber, status) {
            const roomCard = document.querySelector(`[onclick="selectRoom('${roomNumber}')"]`);
            if (roomCard) {
                // Remove all status classes
                roomCard.classList.remove('room-clean', 'room-dirty', 'room-in-progress', 'room-occupied', 'room-maintenance');
                
                // Add new status class
                const statusClasses = {
                    'clean': 'room-clean',
                    'dirty': 'room-dirty',
                    'cleaning': 'room-in-progress',
                    'occupied': 'room-occupied',
                    'maintenance': 'room-maintenance'
                };
                
                roomCard.classList.add(statusClasses[status] || 'room-clean');
                
                // Update badge
                const badge = roomCard.querySelector('.room-status-badge');
                const icon = roomCard.querySelector('i');
                
                const statusConfig = {
                    'clean': { text: 'Clean', class: 'bg-success', icon: 'bi bi-check-circle' },
                    'dirty': { text: 'Dirty', class: 'bg-danger', icon: 'bi bi-exclamation-triangle' },
                    'cleaning': { text: 'In Progress', class: 'bg-warning', icon: 'bi bi-arrow-clockwise' },
                    'occupied': { text: 'Occupied', class: 'bg-info', icon: 'bi bi-person-fill' },
                    'maintenance': { text: 'Maintenance', class: 'bg-warning', icon: 'bi bi-tools' }
                };
                
                const config = statusConfig[status] || statusConfig['clean'];
                badge.textContent = config.text;
                badge.className = `room-status-badge badge ${config.class} mb-2`;
                icon.className = `${config.icon} fs-3 mb-2`;
            }
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Task completion handling
        document.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox' && e.target.closest('.task-item')) {
                const taskItem = e.target.closest('.task-item');
                const taskTitle = taskItem.querySelector('.task-title').textContent;
                
                if (e.target.checked) {
                    taskItem.style.opacity = '0.6';
                    taskItem.style.textDecoration = 'line-through';
                    showNotification(`Task completed: ${taskTitle}`, 'success');
                } else {
                    taskItem.style.opacity = '1';
                    taskItem.style.textDecoration = 'none';
                }
            }
        });
        
        // Update time display
        function updateDateTime() {
            const now = new Date();
            const timeElement = document.querySelector('.current-time');
            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            }
        }
        
        // Update time every minute
        setInterval(updateDateTime, 60000);
        updateDateTime();
        
        // Auto-refresh every 10 minutes
        setInterval(function() {
            location.reload();
        }, 600000);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case '1':
                        if (selectedRoom) updateRoomStatus(selectedRoom, 'clean');
                        e.preventDefault();
                        break;
                    case '2':
                        if (selectedRoom) updateRoomStatus(selectedRoom, 'cleaning');
                        e.preventDefault();
                        break;
                    case '3':
                        if (selectedRoom) updateRoomStatus(selectedRoom, 'maintenance');
                        e.preventDefault();
                        break;
                }
            }
        });
    </script>
</body>
</html>