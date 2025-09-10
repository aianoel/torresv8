<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Attendance Management';
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_attendance':
                // Add attendance record
                $employee_id = $_POST['employee_id'];
                $check_in = $_POST['check_in'];
                $check_out = $_POST['check_out'] ?: null;
                $status = $_POST['status'];
                $notes = $_POST['notes'];
                
                $stmt = $conn->prepare("INSERT INTO attendance (employee_id, check_in, check_out, status, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $employee_id, $check_in, $check_out, $status, $notes);
                
                if ($stmt->execute()) {
                    $message = 'Attendance record added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding attendance record: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'update_attendance':
                // Update attendance record
                $attendance_id = $_POST['attendance_id'];
                $check_in = $_POST['check_in'];
                $check_out = $_POST['check_out'] ?: null;
                $status = $_POST['status'];
                $notes = $_POST['notes'];
                
                $stmt = $conn->prepare("UPDATE attendance SET check_in = ?, check_out = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $check_in, $check_out, $status, $notes, $attendance_id);
                
                if ($stmt->execute()) {
                    $message = 'Attendance record updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating attendance record: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_attendance':
                // Delete attendance record
                $attendance_id = $_POST['attendance_id'];
                
                $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
                $stmt->bind_param("i", $attendance_id);
                
                if ($stmt->execute()) {
                    $message = 'Attendance record deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting attendance record: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get filter parameters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$employee_filter = $_GET['employee'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

if ($date_filter) {
    $whereConditions[] = "DATE(a.check_in) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if ($employee_filter) {
    $whereConditions[] = "e.id = ?";
    $params[] = $employee_filter;
    $types .= 'i';
}

if ($status_filter) {
    $whereConditions[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get attendance records
$attendanceQuery = $conn->prepare("
    SELECT a.id, a.check_in, a.check_out, a.status, a.notes,
           CONCAT(u.first_name, ' ', u.last_name) as employee_name,
           e.position, e.department, e.id as employee_id
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    $whereClause
    ORDER BY a.check_in DESC
    LIMIT 100
");

if (!empty($params)) {
    $attendanceQuery->bind_param($types, ...$params);
}

$attendanceQuery->execute();
$attendanceRecords = $attendanceQuery->get_result();

// Get all employees for dropdowns
$employeesQuery = $conn->prepare("
    SELECT e.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, e.position, e.department
    FROM employees e
    JOIN users u ON e.user_id = u.id
    ORDER BY u.first_name, u.last_name
");
$employeesQuery->execute();
$employees = $employeesQuery->get_result();

// Get attendance statistics for today
$todayStats = $conn->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance a
    WHERE DATE(a.check_in) = CURDATE() OR (a.status = 'absent' AND DATE(CURDATE()) = DATE(CURDATE()))
");
$todayStats->execute();
$stats = $todayStats->get_result()->fetch_assoc();
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
                <a class="nav-link active" href="attendance.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Attendance Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                <i class="bi bi-plus-circle me-2"></i> Add Attendance
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
                        <i class="bi bi-people fs-1 mb-2"></i>
                        <h3><?php echo $stats['total_records'] ?? 0; ?></h3>
                        <p class="mb-0">Total Records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle fs-1 mb-2"></i>
                        <h3><?php echo $stats['present_count'] ?? 0; ?></h3>
                        <p class="mb-0">Present</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-clock fs-1 mb-2"></i>
                        <h3><?php echo $stats['late_count'] ?? 0; ?></h3>
                        <p class="mb-0">Late</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle fs-1 mb-2"></i>
                        <h3><?php echo $stats['absent_count'] ?? 0; ?></h3>
                        <p class="mb-0">Absent</p>
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
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="<?php echo $date_filter; ?>">
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
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="present" <?php echo $status_filter == 'present' ? 'selected' : ''; ?>>Present</option>
                            <option value="late" <?php echo $status_filter == 'late' ? 'selected' : ''; ?>>Late</option>
                            <option value="absent" <?php echo $status_filter == 'absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="on_leave" <?php echo $status_filter == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="attendance.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Records Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Attendance Records</h5>
            </div>
            <div class="card-body">
                <?php if ($attendanceRecords->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th>Hours</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = $attendanceRecords->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['position']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($record['check_in']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($record['check_in'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['check_out']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($record['check_out'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            switch($record['status']) {
                                                case 'present':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'late':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'absent':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                case 'on_leave':
                                                    $statusClass = 'bg-info';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($record['check_in'] && $record['check_out']) {
                                                $checkIn = new DateTime($record['check_in']);
                                                $checkOut = new DateTime($record['check_out']);
                                                $diff = $checkOut->diff($checkIn);
                                                echo $diff->format('%h:%I');
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($record['notes']): ?>
                                                <span title="<?php echo htmlspecialchars($record['notes']); ?>">
                                                    <?php echo htmlspecialchars(substr($record['notes'], 0, 30)) . (strlen($record['notes']) > 30 ? '...' : ''); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editAttendance(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteAttendance(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['employee_name']); ?>')">
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
                        <i class="bi bi-clock fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No attendance records found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Attendance Modal -->
    <div class="modal fade" id="addAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_attendance">
                        
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
                            <label for="check_in" class="form-label">Check In</label>
                            <input type="datetime-local" class="form-control" name="check_in" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="check_out" class="form-label">Check Out</label>
                            <input type="datetime-local" class="form-control" name="check_out">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="on_leave">On Leave</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Attendance Modal -->
    <div class="modal fade" id="editAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_attendance">
                        <input type="hidden" name="attendance_id" id="edit_attendance_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <input type="text" class="form-control" id="edit_employee_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_check_in" class="form-label">Check In</label>
                            <input type="datetime-local" class="form-control" name="check_in" id="edit_check_in" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_check_out" class="form-label">Check Out</label>
                            <input type="datetime-local" class="form-control" name="check_out" id="edit_check_out">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="on_leave">On Leave</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the attendance record for <strong id="delete_employee_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_attendance">
                        <input type="hidden" name="attendance_id" id="delete_attendance_id">
                        <button type="submit" class="btn btn-danger">Delete Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAttendance(record) {
            document.getElementById('edit_attendance_id').value = record.id;
            document.getElementById('edit_employee_name').value = record.employee_name;
            
            // Format datetime for input
            if (record.check_in) {
                const checkIn = new Date(record.check_in);
                document.getElementById('edit_check_in').value = checkIn.toISOString().slice(0, 16);
            }
            
            if (record.check_out) {
                const checkOut = new Date(record.check_out);
                document.getElementById('edit_check_out').value = checkOut.toISOString().slice(0, 16);
            } else {
                document.getElementById('edit_check_out').value = '';
            }
            
            document.getElementById('edit_status').value = record.status;
            document.getElementById('edit_notes').value = record.notes || '';
            
            new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
        }
        
        function deleteAttendance(id, employeeName) {
            document.getElementById('delete_attendance_id').value = id;
            document.getElementById('delete_employee_name').textContent = employeeName;
            
            new bootstrap.Modal(document.getElementById('deleteAttendanceModal')).show();
        }
        
        // Set default datetime to current time for new records
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const checkInInput = document.querySelector('#addAttendanceModal input[name="check_in"]');
            if (checkInInput) {
                checkInInput.value = now.toISOString().slice(0, 16);
            }
        });
    </script>
</body>
</html>