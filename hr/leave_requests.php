<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Leave Requests';
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_leave':
                // Add leave request
                $employee_id = $_POST['employee_id'];
                $leave_type = $_POST['leave_type'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $reason = $_POST['reason'];
                $status = $_POST['status'] ?? 'pending';
                
                $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $employee_id, $leave_type, $start_date, $end_date, $reason, $status);
                
                if ($stmt->execute()) {
                    $message = 'Leave request added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding leave request: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'update_status':
                // Update leave request status
                $request_id = $_POST['request_id'];
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
                $stmt->bind_param("sii", $status, $_SESSION['user_id'], $request_id);
                
                if ($stmt->execute()) {
                    $message = 'Leave request status updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating leave request: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_leave':
                // Delete leave request
                $request_id = $_POST['request_id'];
                
                $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
                $stmt->bind_param("i", $request_id);
                
                if ($stmt->execute()) {
                    $message = 'Leave request deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting leave request: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$employee_filter = $_GET['employee'] ?? '';
$leave_type_filter = $_GET['leave_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

if ($status_filter) {
    $whereConditions[] = "lr.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($employee_filter) {
    $whereConditions[] = "e.id = ?";
    $params[] = $employee_filter;
    $types .= 'i';
}

if ($leave_type_filter) {
    $whereConditions[] = "lr.leave_type = ?";
    $params[] = $leave_type_filter;
    $types .= 's';
}

if ($date_from) {
    $whereConditions[] = "lr.start_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $whereConditions[] = "lr.end_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get leave requests
$leaveQuery = $conn->prepare("
    SELECT lr.id, lr.type as leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, 
           lr.processed_at as reviewed_at,
           CONCAT(u.first_name, ' ', u.last_name) as employee_name,
           e.position, e.department, e.id as employee_id,
           CASE WHEN lr.processed_by IS NOT NULL THEN CONCAT(reviewer.first_name, ' ', reviewer.last_name) ELSE NULL END as reviewed_by_name,
           DATEDIFF(lr.end_date, lr.start_date) + 1 as days_requested
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    LEFT JOIN users reviewer ON lr.processed_by = reviewer.id
    $whereClause
    ORDER BY lr.id DESC
    LIMIT 100
");

if (!empty($params)) {
    $leaveQuery->bind_param($types, ...$params);
}

$leaveQuery->execute();
$leaveRequests = $leaveQuery->get_result();

// Get all employees for dropdowns
$employeesQuery = $conn->prepare("
    SELECT e.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, e.position, e.department
    FROM employees e
    JOIN users u ON e.user_id = u.id
    ORDER BY u.first_name, u.last_name
");
$employeesQuery->execute();
$employees = $employeesQuery->get_result();

// Get leave statistics
$statsQuery = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as total_approved_days
    FROM leave_requests lr
    WHERE YEAR(lr.start_date) = YEAR(CURDATE())
");
$statsQuery->execute();
$stats = $statsQuery->get_result()->fetch_assoc();
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
        }
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            position: fixed;
            width: 250px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: #333;
            border-radius: 8px;
            margin: 2px 0;
        }
        .nav-link:hover {
            background-color: #e9ecef;
            color: var(--primary-color);
        }
        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .leave-details {
            font-size: 0.9em;
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
                <a class="nav-link" href="dashboard.php">
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
                <a class="nav-link active" href="leave_requests.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Leave Requests</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveModal">
                <i class="bi bi-plus-circle me-2"></i> Add Leave Request
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check fs-1 mb-2"></i>
                        <h3><?php echo $stats['total_requests'] ?? 0; ?></h3>
                        <p class="mb-0">Total Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-clock fs-1 mb-2"></i>
                        <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle fs-1 mb-2"></i>
                        <h3><?php echo $stats['approved_count'] ?? 0; ?></h3>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-range fs-1 mb-2"></i>
                        <h3><?php echo $stats['total_approved_days'] ?? 0; ?></h3>
                        <p class="mb-0">Days Approved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="employee" class="form-label">Employee</label>
                        <select class="form-select" name="employee">
                            <option value="">All Employees</option>
                            <?php 
                            $employees->data_seek(0); // Reset result pointer
                            while ($emp = $employees->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo $employee_filter == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="leave_type" class="form-label">Leave Type</label>
                        <select class="form-select" name="leave_type">
                            <option value="">All Types</option>
                            <option value="annual" <?php echo $leave_type_filter == 'annual' ? 'selected' : ''; ?>>Annual Leave</option>
                            <option value="sick" <?php echo $leave_type_filter == 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                            <option value="personal" <?php echo $leave_type_filter == 'personal' ? 'selected' : ''; ?>>Personal Leave</option>
                            <option value="maternity" <?php echo $leave_type_filter == 'maternity' ? 'selected' : ''; ?>>Maternity Leave</option>
                            <option value="emergency" <?php echo $leave_type_filter == 'emergency' ? 'selected' : ''; ?>>Emergency Leave</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                    </div>
                </form>
                <div class="mt-2">
                    <a href="leave_requests.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Leave Requests</h5>
            </div>
            <div class="card-body">
                <?php if ($leaveRequests->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Duration</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($request = $leaveRequests->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['employee_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['position']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst(str_replace('_', ' ', $request['leave_type'])); ?>
                                            </span>
                                        </td>
                                        <td class="leave-details">
                                            <strong>From:</strong> <?php echo date('M d, Y', strtotime($request['start_date'])); ?><br>
                                            <strong>To:</strong> <?php echo date('M d, Y', strtotime($request['end_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $request['days_requested']; ?> days</span>
                                        </td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            switch($request['status']) {
                                                case 'approved':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                            <?php if ($request['reviewed_at']): ?>
                                                <br><small class="text-muted">
                                                    by <?php echo htmlspecialchars($request['reviewed_by_name']); ?><br>
                                                    <?php echo date('M d, Y', strtotime($request['reviewed_at'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['reason']): ?>
                                                <span title="<?php echo htmlspecialchars($request['reason']); ?>">
                                    <?php echo htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : ''); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>

                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success me-1" 
                                                        onclick="reviewLeave(<?php echo $request['id']; ?>, 'approved', '<?php echo htmlspecialchars($request['employee_name']); ?>')">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger me-1" 
                                                        onclick="reviewLeave(<?php echo $request['id']; ?>, 'rejected', '<?php echo htmlspecialchars($request['employee_name']); ?>')">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="viewLeaveDetails(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteLeave(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['employee_name']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No leave requests found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Leave Request Modal -->
    <div class="modal fade" id="addLeaveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_leave">
                        
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">Choose an employee...</option>
                                <?php 
                                $employees->data_seek(0); // Reset result pointer
                                while ($emp = $employees->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['full_name']); ?> - <?php echo htmlspecialchars($emp['position']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="leave_type" class="form-label">Leave Type</label>
                            <select class="form-select" name="leave_type" required>
                                <option value="">Choose leave type...</option>
                                <option value="annual">Annual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="personal">Personal Leave</option>
                                <option value="maternity">Maternity Leave</option>
                                <option value="emergency">Emergency Leave</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Review Leave Modal -->
    <div class="modal fade" id="reviewLeaveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="request_id" id="review_request_id">
                        <input type="hidden" name="status" id="review_status">
                        
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <input type="text" class="form-control" id="review_employee_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Decision</label>
                            <input type="text" class="form-control" id="review_decision" readonly>
                        </div>
                        

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Leave Details Modal -->
    <div class="modal fade" id="viewLeaveModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Employee Information</h6>
                            <p><strong>Name:</strong> <span id="view_employee_name"></span></p>
                            <p><strong>Position:</strong> <span id="view_position"></span></p>
                            <p><strong>Department:</strong> <span id="view_department"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Leave Information</h6>
                            <p><strong>Type:</strong> <span id="view_leave_type"></span></p>
                            <p><strong>Duration:</strong> <span id="view_duration"></span></p>
                            <p><strong>Days:</strong> <span id="view_days"></span></p>
                            <p><strong>Status:</strong> <span id="view_status"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6>Reason</h6>
                            <p id="view_reason"></p>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> <span id="view_start_date"></span></p>
                        </div>
                        <div class="col-md-6" id="reviewed_section" style="display: none;">
                            <p><strong>Reviewed:</strong> <span id="view_reviewed_at"></span></p>
                            <p><strong>Reviewed by:</strong> <span id="view_reviewed_by"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteLeaveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the leave request for <strong id="delete_employee_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_leave">
                        <input type="hidden" name="request_id" id="delete_request_id">
                        <button type="submit" class="btn btn-danger">Delete Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function reviewLeave(requestId, status, employeeName) {
            document.getElementById('review_request_id').value = requestId;
            document.getElementById('review_status').value = status;
            document.getElementById('review_employee_name').value = employeeName;
            document.getElementById('review_decision').value = status.charAt(0).toUpperCase() + status.slice(1);
            
            new bootstrap.Modal(document.getElementById('reviewLeaveModal')).show();
        }
        
        function viewLeaveDetails(request) {
            document.getElementById('view_employee_name').textContent = request.employee_name;
            document.getElementById('view_position').textContent = request.position;
            document.getElementById('view_department').textContent = request.department;
            document.getElementById('view_leave_type').textContent = request.leave_type.charAt(0).toUpperCase() + request.leave_type.slice(1).replace('_', ' ');
            
            const startDate = new Date(request.start_date).toLocaleDateString();
            const endDate = new Date(request.end_date).toLocaleDateString();
            document.getElementById('view_duration').textContent = startDate + ' to ' + endDate;
            document.getElementById('view_days').textContent = request.days_requested + ' days';
            document.getElementById('view_status').textContent = request.status.charAt(0).toUpperCase() + request.status.slice(1);
            document.getElementById('view_reason').textContent = request.reason || 'No reason provided';
            document.getElementById('view_start_date').textContent = new Date(request.start_date).toLocaleDateString();
            

            
            if (request.reviewed_at) {
                document.getElementById('view_reviewed_at').textContent = new Date(request.reviewed_at).toLocaleDateString();
                document.getElementById('view_reviewed_by').textContent = request.reviewed_by_name || 'Unknown';
                document.getElementById('reviewed_section').style.display = 'block';
            } else {
                document.getElementById('reviewed_section').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewLeaveModal')).show();
        }
        
        function deleteLeave(requestId, employeeName) {
            document.getElementById('delete_request_id').value = requestId;
            document.getElementById('delete_employee_name').textContent = employeeName;
            
            new bootstrap.Modal(document.getElementById('deleteLeaveModal')).show();
        }
        
        // Set minimum date to today for new leave requests
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.querySelector('#addLeaveModal input[name="start_date"]');
            const endDateInput = document.querySelector('#addLeaveModal input[name="end_date"]');
            
            if (startDateInput) {
                startDateInput.min = today;
                startDateInput.addEventListener('change', function() {
                    endDateInput.min = this.value;
                });
            }
        });
    </script>
</body>
</html>