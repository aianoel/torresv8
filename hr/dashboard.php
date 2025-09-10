<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'HR Dashboard';

// Get employee statistics
$totalEmployeesQuery = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM employees e
    JOIN users u ON e.user_id = u.id
    WHERE u.role != 'admin'
");
$totalEmployeesQuery->execute();
$totalEmployees = $totalEmployeesQuery->get_result()->fetch_assoc()['total'];

$presentTodayQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE DATE(a.check_in) = CURDATE()
    AND a.status = 'present'
");
$presentTodayQuery->execute();
$presentToday = $presentTodayQuery->get_result()->fetch_assoc()['count'];

$pendingLeaveQuery = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM leave_requests 
    WHERE status = 'pending'
");
$pendingLeaveQuery->execute();
$pendingLeave = $pendingLeaveQuery->get_result()->fetch_assoc()['count'];

// Get recent leave requests
$recentLeaveQuery = $conn->prepare("
    SELECT lr.id, lr.start_date, lr.end_date, lr.type, lr.status, lr.reason,
           CONCAT(u.first_name, ' ', u.last_name) as employee_name,
           e.position, e.department
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    ORDER BY lr.id DESC
    LIMIT 10
");
$recentLeaveQuery->execute();
$recentLeave = $recentLeaveQuery->get_result();

// Get today's attendance
$todayAttendanceQuery = $conn->prepare("
    SELECT a.check_in, a.check_out, a.status,
           CONCAT(u.first_name, ' ', u.last_name) as employee_name,
           e.position, e.department
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    WHERE DATE(a.check_in) = CURDATE() OR DATE(CURDATE()) = DATE(CURDATE())
    ORDER BY a.check_in DESC
    LIMIT 10
");
$todayAttendanceQuery->execute();
$todayAttendance = $todayAttendanceQuery->get_result();
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
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
            --gold: #D4AF37;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--gold));
            border-right: none;
            position: fixed;
            width: 280px;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 12px;
            margin: 4px 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--primary-color));
        }
        
        .staff-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
        }
        
        .staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .attendance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .leave-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .payroll-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .department-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--gold);
            transition: all 0.3s ease;
        }
        
        .department-card:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .employee-item {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 3px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .employee-item:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
            display: block;
            text-align: center;
        }
        
        .quick-action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .table th {
            background-color: var(--light);
            border-top: none;
            font-weight: 600;
            color: var(--dark);
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .current-time {
            font-size: 1.2rem;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.8;
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
                <a class="nav-link" href="employees.php">
                    <i class="bi bi-people me-2"></i> Employees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="attendance.php">
                    <i class="bi bi-clock me-2"></i> Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="leave_requests.php">
                    <i class="bi bi-calendar-x me-2"></i> Leave Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="payroll.php">
                    <i class="bi bi-cash-stack me-2"></i> Payroll
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
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">HR Management Dashboard</h1>
                    <p class="mb-0 opacity-75">Welcome back, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>! Here's your staff overview for today.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="current-time"><?php echo date('l, F j, Y'); ?></div>
                    <div class="current-time"><?php echo date('g:i A'); ?></div>
                </div>
            </div>
        </div>

        <!-- Staff Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="staff-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="metric-value text-primary"><?php echo $totalEmployees; ?></div>
                        <i class="bi bi-people fs-1 text-primary opacity-75"></i>
                    </div>
                    <div class="metric-label text-muted">Total Staff Members</div>
                    <div class="small text-success mt-2">
                        <i class="bi bi-arrow-up"></i> 5 new hires this month
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="attendance-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="metric-value"><?php echo $presentToday; ?></div>
                        <i class="bi bi-check-circle fs-1 opacity-75"></i>
                    </div>
                    <div class="metric-label">Present Today</div>
                    <div class="small mt-2 opacity-75">
                        <?php echo round(($presentToday / $totalEmployees) * 100, 1); ?>% attendance rate
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="leave-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="metric-value"><?php echo $pendingLeave; ?></div>
                        <i class="bi bi-calendar-x fs-1 opacity-75"></i>
                    </div>
                    <div class="metric-label">Pending Leave Requests</div>
                    <div class="small mt-2 opacity-75">
                        Requires immediate review
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="payroll-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="metric-value">12</div>
                        <i class="bi bi-cash-stack fs-1 opacity-75"></i>
                    </div>
                    <div class="metric-label">Payroll Processing</div>
                    <div class="small mt-2 opacity-75">
                        Due in 3 days
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Department Overview -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="staff-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Department Overview</h5>
                        <span class="badge bg-primary">5 Departments</span>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="department-card text-center">
                                <i class="bi bi-reception-4 fs-2 text-primary mb-2"></i>
                                <div class="fw-bold">8</div>
                                <div class="small text-muted">Front Desk</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="department-card text-center">
                                <i class="bi bi-house fs-2 text-success mb-2"></i>
                                <div class="fw-bold">12</div>
                                <div class="small text-muted">Housekeeping</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="department-card text-center">
                                <i class="bi bi-tools fs-2 text-warning mb-2"></i>
                                <div class="fw-bold">6</div>
                                <div class="small text-muted">Maintenance</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="department-card text-center">
                                <i class="bi bi-cup-hot fs-2 text-info mb-2"></i>
                                <div class="fw-bold">15</div>
                                <div class="small text-muted">Restaurant</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="department-card text-center">
                                <i class="bi bi-shield-check fs-2 text-danger mb-2"></i>
                                <div class="fw-bold">4</div>
                                <div class="small text-muted">Security</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="department-card text-center">
                                <i class="bi bi-people fs-2 text-secondary mb-2"></i>
                                <div class="fw-bold">3</div>
                                <div class="small text-muted">Management</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary & Leave Management -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="attendance-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Today's Attendance Summary</h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-success">On Time: <?php echo $presentToday; ?></span>
                            <span class="badge bg-warning">Late: 2</span>
                            <span class="badge bg-danger">Absent: <?php echo $totalEmployees - $presentToday; ?></span>
                        </div>
                    </div>
                    
                    <?php if ($todayAttendance->num_rows > 0): ?>
                        <div class="employee-list">
                            <?php 
                            $todayAttendance->data_seek(0);
                            $count = 0;
                            while ($attendance = $todayAttendance->fetch_assoc() && $count < 6): 
                                $count++;
                            ?>
                                <div class="employee-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="employee-avatar me-3">
                                            <i class="bi bi-person-circle fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($attendance['employee_name']); ?></div>
                                            <div class="small text-muted"><?php echo $attendance['position']; ?></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small">
                                            <span class="text-muted">In:</span> <?php echo $attendance['check_in'] ? date('H:i', strtotime($attendance['check_in'])) : '-'; ?>
                                            <span class="text-muted ms-2">Out:</span> <?php echo $attendance['check_out'] ? date('H:i', strtotime($attendance['check_out'])) : '-'; ?>
                                        </div>
                                        <?php
                                        $statusClass = '';
                                        switch($attendance['status']) {
                                            case 'present':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'late':
                                                $statusClass = 'bg-warning';
                                                break;
                                            case 'absent':
                                                $statusClass = 'bg-danger';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?> mt-1"><?php echo ucfirst($attendance['status']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <?php if ($todayAttendance->num_rows > 6): ?>
                                <div class="text-center mt-3">
                                    <a href="attendance.php" class="btn btn-outline-primary btn-sm">View All Attendance</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No attendance records for today.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="leave-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-calendar-x me-2"></i>Leave Requests</h5>
                        <span class="badge bg-warning"><?php echo $pendingLeave; ?> Pending</span>
                    </div>
                    
                    <?php if ($recentLeave->num_rows > 0): ?>
                        <div class="leave-requests">
                            <?php 
                            $recentLeave->data_seek(0);
                            $count = 0;
                            while ($leave = $recentLeave->fetch_assoc() && $count < 4): 
                                $count++;
                            ?>
                                <div class="leave-request-item mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($leave['type']); ?></div>
                                            <div class="small text-muted"><?php echo date('M d', strtotime($leave['start_date'])) . ' - ' . date('M d', strtotime($leave['end_date'])); ?></div>
                                        </div>
                                        <div>
                                            <?php
                                            $statusClass = '';
                                            switch ($leave['status']) {
                                                case 'approved':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-warning';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($leave['status']); ?></span>
                                        </div>
                                    </div>
                                    <?php if ($leave['status'] == 'pending'): ?>
                                        <div class="mt-2">
                                            <button class="btn btn-success btn-sm me-1">Approve</button>
                                            <button class="btn btn-danger btn-sm">Reject</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                            <div class="text-center mt-3">
                                <a href="leave_requests.php" class="btn btn-outline-primary btn-sm">Manage All Requests</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent leave requests.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payroll Processing Queue & Quick Actions -->
        <div class="row">
            <div class="col-md-8">
                <div class="payroll-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Payroll Processing Queue</h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-warning">12 Pending</span>
                            <span class="badge bg-info">Due: Dec 31</span>
                        </div>
                    </div>
                    
                    <div class="payroll-progress mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Processing Progress</span>
                            <span class="small fw-medium">75% Complete</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 75%"></div>
                        </div>
                    </div>
                    
                    <div class="payroll-items">
                        <div class="payroll-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building text-primary me-3 fs-5"></i>
                                <div>
                                    <div class="fw-medium">Front Desk Department</div>
                                    <div class="small text-muted">8 employees • $12,400 total</div>
                                </div>
                            </div>
                            <span class="badge bg-success">Processed</span>
                        </div>
                        
                        <div class="payroll-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-house text-success me-3 fs-5"></i>
                                <div>
                                    <div class="fw-medium">Housekeeping Department</div>
                                    <div class="small text-muted">12 employees • $18,600 total</div>
                                </div>
                            </div>
                            <span class="badge bg-success">Processed</span>
                        </div>
                        
                        <div class="payroll-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-cup-hot text-info me-3 fs-5"></i>
                                <div>
                                    <div class="fw-medium">Restaurant Department</div>
                                    <div class="small text-muted">15 employees • $22,500 total</div>
                                </div>
                            </div>
                            <span class="badge bg-warning">Pending</span>
                        </div>
                        
                        <div class="payroll-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-tools text-warning me-3 fs-5"></i>
                                <div>
                                    <div class="fw-medium">Maintenance Department</div>
                                    <div class="small text-muted">6 employees • $9,600 total</div>
                                </div>
                            </div>
                            <span class="badge bg-warning">Pending</span>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button class="btn btn-primary me-2">Process Pending</button>
                        <a href="payroll.php" class="btn btn-outline-primary">View Full Payroll</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="staff-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="employees.php" class="quick-action-btn">
                            <i class="bi bi-person-plus"></i>
                            <div>
                                <div class="fw-medium">Add Employee</div>
                                <div class="small text-muted">Register new staff member</div>
                            </div>
                        </a>
                        
                        <a href="attendance.php" class="quick-action-btn">
                            <i class="bi bi-clock-history"></i>
                            <div>
                                <div class="fw-medium">Attendance Report</div>
                                <div class="small text-muted">View detailed attendance</div>
                            </div>
                        </a>
                        
                        <a href="leave_requests.php" class="quick-action-btn">
                            <i class="bi bi-calendar-check"></i>
                            <div>
                                <div class="fw-medium">Approve Leaves</div>
                                <div class="small text-muted"><?php echo $pendingLeave; ?> requests pending</div>
                            </div>
                        </a>
                        
                        <a href="payroll.php" class="quick-action-btn">
                            <i class="bi bi-calculator"></i>
                            <div>
                                <div class="fw-medium">Generate Payslips</div>
                                <div class="small text-muted">Create monthly payslips</div>
                            </div>
                        </a>
                        
                        <a href="employees.php?action=performance" class="quick-action-btn">
                            <i class="bi bi-graph-up"></i>
                            <div>
                                <div class="fw-medium">Performance Review</div>
                                <div class="small text-muted">Evaluate staff performance</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update time display
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            const dateString = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if (timeElement) timeElement.textContent = timeString;
            if (dateElement) timeElement.textContent = dateString;
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Handle leave request actions
        function handleLeaveAction(action, requestId) {
            if (confirm(`Are you sure you want to ${action} this leave request?`)) {
                // Here you would make an AJAX call to process the leave request
                showNotification(`Leave request ${action}d successfully!`, 'success');
                
                // Update the UI
                const requestElement = document.querySelector(`[data-request-id="${requestId}"]`);
                if (requestElement) {
                    const badge = requestElement.querySelector('.badge');
                    if (badge) {
                        badge.className = `badge bg-${action === 'approve' ? 'success' : 'danger'}`;
                        badge.textContent = action === 'approve' ? 'Approved' : 'Rejected';
                    }
                    
                    const buttons = requestElement.querySelectorAll('.btn');
                    buttons.forEach(btn => btn.remove());
                }
            }
        }
        
        // Handle payroll processing
        function processPayroll() {
            if (confirm('Are you sure you want to process pending payroll items?')) {
                showNotification('Processing payroll... This may take a few minutes.', 'info');
                
                // Simulate processing
                setTimeout(() => {
                    showNotification('Payroll processed successfully!', 'success');
                    
                    // Update progress bar
                    const progressBar = document.querySelector('.progress-bar');
                    if (progressBar) {
                        progressBar.style.width = '100%';
                    }
                    
                    // Update pending badges
                    const pendingBadges = document.querySelectorAll('.badge.bg-warning');
                    pendingBadges.forEach(badge => {
                        if (badge.textContent.includes('Pending')) {
                            badge.className = 'badge bg-success';
                            badge.textContent = 'Processed';
                        }
                    });
                }, 2000);
            }
        }
        
        // Refresh dashboard data
        function refreshDashboard() {
            showNotification('Refreshing dashboard data...', 'info');
            
            // In a real application, you would fetch updated data from the server
            setTimeout(() => {
                showNotification('Dashboard updated successfully!', 'success');
            }, 1000);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'r':
                        e.preventDefault();
                        refreshDashboard();
                        break;
                    case 'n':
                        e.preventDefault();
                        window.location.href = 'employees.php';
                        break;
                }
            }
        });
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            updateTime();
            setInterval(updateTime, 60000); // Update every minute
            
            // Add event listeners for leave request buttons
            document.querySelectorAll('.btn-success').forEach(btn => {
                if (btn.textContent.includes('Approve')) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const requestId = this.closest('.leave-request-item').dataset.requestId || Math.random();
                        handleLeaveAction('approve', requestId);
                    });
                }
            });
            
            document.querySelectorAll('.btn-danger').forEach(btn => {
                if (btn.textContent.includes('Reject')) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const requestId = this.closest('.leave-request-item').dataset.requestId || Math.random();
                        handleLeaveAction('reject', requestId);
                    });
                }
            });
            
            // Add event listener for payroll processing
            const processBtn = document.querySelector('button:contains("Process Pending")');
            if (processBtn) {
                processBtn.addEventListener('click', processPayroll);
            }
            
            // Auto-refresh every 10 minutes
            setInterval(refreshDashboard, 600000);
            
            console.log('HR Dashboard initialized successfully!');
        });
    </script>
</body>
</html>