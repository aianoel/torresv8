<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Payroll Management';
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_payroll':
                // Add payroll record
                $employee_id = $_POST['employee_id'];
                $pay_period_start = $_POST['pay_period_start'];
                $pay_period_end = $_POST['pay_period_end'];
                $basic_salary = $_POST['basic_salary'];
                $overtime_hours = $_POST['overtime_hours'] ?: 0;
                $overtime_rate = $_POST['overtime_rate'] ?: 0;
                $allowances = $_POST['allowances'] ?: 0;
                $deductions = $_POST['deductions'] ?: 0;
                $tax_deduction = $_POST['tax_deduction'] ?: 0;
                $status = $_POST['status'] ?? 'pending';
                
                // Calculate totals
                $overtime_pay = $overtime_hours * $overtime_rate;
                $gross_pay = $basic_salary + $overtime_pay + $allowances;
                $total_deductions = $deductions + $tax_deduction;
                $net_pay = $gross_pay - $total_deductions;
                
                $stmt = $conn->prepare("INSERT INTO payroll (employee_id, pay_period_start, pay_period_end, basic_salary, overtime_hours, overtime_rate, overtime_pay, allowances, deductions, tax_deduction, gross_pay, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issddddddddds", $employee_id, $pay_period_start, $pay_period_end, $basic_salary, $overtime_hours, $overtime_rate, $overtime_pay, $allowances, $deductions, $tax_deduction, $gross_pay, $net_pay, $status);
                
                if ($stmt->execute()) {
                    $message = 'Payroll record added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding payroll record: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'update_payroll':
                // Update payroll record
                $payroll_id = $_POST['payroll_id'];
                $basic_salary = $_POST['basic_salary'];
                $overtime_hours = $_POST['overtime_hours'] ?: 0;
                $overtime_rate = $_POST['overtime_rate'] ?: 0;
                $allowances = $_POST['allowances'] ?: 0;
                $deductions = $_POST['deductions'] ?: 0;
                $tax_deduction = $_POST['tax_deduction'] ?: 0;
                $status = $_POST['status'];
                
                // Calculate totals
                $overtime_pay = $overtime_hours * $overtime_rate;
                $gross_pay = $basic_salary + $overtime_pay + $allowances;
                $total_deductions = $deductions + $tax_deduction;
                $net_pay = $gross_pay - $total_deductions;
                
                $stmt = $conn->prepare("UPDATE payroll SET basic_salary = ?, overtime_hours = ?, overtime_rate = ?, overtime_pay = ?, allowances = ?, deductions = ?, tax_deduction = ?, gross_pay = ?, net_pay = ?, status = ? WHERE id = ?");
                $stmt->bind_param("dddddddddsi", $basic_salary, $overtime_hours, $overtime_rate, $overtime_pay, $allowances, $deductions, $tax_deduction, $gross_pay, $net_pay, $status, $payroll_id);
                
                if ($stmt->execute()) {
                    $message = 'Payroll record updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating payroll record: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_payroll':
                // Delete payroll record
                $payroll_id = $_POST['payroll_id'];
                
                $stmt = $conn->prepare("DELETE FROM payroll WHERE id = ?");
                $stmt->bind_param("i", $payroll_id);
                
                if ($stmt->execute()) {
                    $message = 'Payroll record deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting payroll record: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get filter parameters
$employee_filter = $_GET['employee'] ?? '';
$status_filter = $_GET['status'] ?? '';
$month_filter = $_GET['month'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

if ($employee_filter) {
    $whereConditions[] = "e.id = ?";
    $params[] = $employee_filter;
    $types .= 'i';
}

if ($status_filter) {
    $whereConditions[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($month_filter) {
    $whereConditions[] = "DATE_FORMAT(p.pay_period_start, '%Y-%m') = ?";
    $params[] = $month_filter;
    $types .= 's';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get payroll records
$payrollQuery = $conn->prepare("
    SELECT p.*, 
           CONCAT(u.first_name, ' ', u.last_name) as employee_name,
           e.position, e.department, e.id as employee_id
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    $whereClause
    ORDER BY p.pay_period_start DESC
    LIMIT 100
");

if (!empty($params)) {
    $payrollQuery->bind_param($types, ...$params);
}

$payrollQuery->execute();
$payrollRecords = $payrollQuery->get_result();

// Get all employees for dropdowns
$employeesQuery = $conn->prepare("
    SELECT e.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, e.position, e.department, e.salary
    FROM employees e
    JOIN users u ON e.user_id = u.id
    ORDER BY u.first_name, u.last_name
");
$employeesQuery->execute();
$employees = $employeesQuery->get_result();

// Get payroll statistics
$statsQuery = $conn->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'paid' THEN net_pay ELSE 0 END) as total_paid,
        SUM(gross_pay) as total_gross,
        SUM(net_pay) as total_net
    FROM payroll p
    WHERE DATE_FORMAT(p.pay_period_start, '%Y-%m') = ?
");
$statsQuery->bind_param('s', $month_filter);
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
        .payroll-details {
            font-size: 0.9em;
        }
        .currency {
            font-weight: 600;
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
                <a class="nav-link" href="leave_requests.php">
                    <i class="bi bi-calendar-x me-2"></i> Leave Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="payroll.php">
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
            <h1>Payroll Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPayrollModal">
                <i class="bi bi-plus-circle me-2"></i> Add Payroll
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
                        <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                        <h3><?php echo $stats['total_records'] ?? 0; ?></h3>
                        <p class="mb-0">Total Records</p>
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
                        <h3><?php echo $stats['paid_count'] ?? 0; ?></h3>
                        <p class="mb-0">Paid</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-currency-dollar fs-1 mb-2"></i>
                        <h3>$<?php echo number_format($stats['total_paid'] ?? 0, 2); ?></h3>
                        <p class="mb-0">Total Paid</p>
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
                        <label for="month" class="form-label">Pay Period Month</label>
                        <input type="month" class="form-control" name="month" value="<?php echo $month_filter; ?>">
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
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="payroll.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payroll Records Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payroll Records</h5>
            </div>
            <div class="card-body">
                <?php if ($payrollRecords->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Pay Period</th>
                                    <th>Basic Salary</th>
                                    <th>Overtime</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = $payrollRecords->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['position']); ?></small>
                                        </td>
                                        <td class="payroll-details">
                                            <strong>From:</strong> <?php echo date('M d, Y', strtotime($record['pay_period_start'])); ?><br>
                                            <strong>To:</strong> <?php echo date('M d, Y', strtotime($record['pay_period_end'])); ?>
                                        </td>
                                        <td class="currency">$<?php echo number_format($record['basic_salary'], 2); ?></td>
                                        <td class="payroll-details">
                                            <?php if ($record['overtime_hours'] > 0): ?>
                                                <?php echo $record['overtime_hours']; ?>h @ $<?php echo number_format($record['overtime_rate'], 2); ?><br>
                                                <strong>$<?php echo number_format($record['overtime_pay'], 2); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="currency text-success">$<?php echo number_format($record['gross_pay'], 2); ?></td>
                                        <td class="payroll-details">
                                            <?php if ($record['deductions'] > 0 || $record['tax_deduction'] > 0): ?>
                                                <?php if ($record['deductions'] > 0): ?>
                                                    Other: $<?php echo number_format($record['deductions'], 2); ?><br>
                                                <?php endif; ?>
                                                <?php if ($record['tax_deduction'] > 0): ?>
                                                    Tax: $<?php echo number_format($record['tax_deduction'], 2); ?><br>
                                                <?php endif; ?>
                                                <strong class="text-danger">$<?php echo number_format($record['deductions'] + $record['tax_deduction'], 2); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="currency text-primary"><strong>$<?php echo number_format($record['net_pay'], 2); ?></strong></td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            switch($record['status']) {
                                                case 'paid':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'approved':
                                                    $statusClass = 'bg-info';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editPayroll(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-1" 
                                                    onclick="viewPayrollDetails(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deletePayroll(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['employee_name']); ?>')">
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
                        <i class="bi bi-cash-stack fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No payroll records found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Payroll Modal -->
    <div class="modal fade" id="addPayrollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payroll Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_payroll">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Employee</label>
                                    <select class="form-select" name="employee_id" id="employee_select" required>
                                        <option value="">Choose an employee...</option>
                                        <?php 
                                        $employees->data_seek(0); // Reset result pointer
                                        while ($emp = $employees->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $emp['id']; ?>" data-salary="<?php echo $emp['salary']; ?>">
                                                <?php echo htmlspecialchars($emp['full_name']); ?> - <?php echo htmlspecialchars($emp['position']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pay_period_start" class="form-label">Pay Period Start</label>
                                    <input type="date" class="form-control" name="pay_period_start" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pay_period_end" class="form-label">Pay Period End</label>
                                    <input type="date" class="form-control" name="pay_period_end" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="basic_salary" class="form-label">Basic Salary</label>
                                    <input type="number" step="0.01" class="form-control" name="basic_salary" id="basic_salary" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="allowances" class="form-label">Allowances</label>
                                    <input type="number" step="0.01" class="form-control" name="allowances" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="overtime_hours" class="form-label">Overtime Hours</label>
                                    <input type="number" step="0.5" class="form-control" name="overtime_hours" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="overtime_rate" class="form-label">Overtime Rate (per hour)</label>
                                    <input type="number" step="0.01" class="form-control" name="overtime_rate" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deductions" class="form-label">Other Deductions</label>
                                    <input type="number" step="0.01" class="form-control" name="deductions" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tax_deduction" class="form-label">Tax Deduction</label>
                                    <input type="number" step="0.01" class="form-control" name="tax_deduction" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Payroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payroll Modal -->
    <div class="modal fade" id="editPayrollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payroll Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_payroll">
                        <input type="hidden" name="payroll_id" id="edit_payroll_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <input type="text" class="form-control" id="edit_employee_name" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_basic_salary" class="form-label">Basic Salary</label>
                                    <input type="number" step="0.01" class="form-control" name="basic_salary" id="edit_basic_salary" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_allowances" class="form-label">Allowances</label>
                                    <input type="number" step="0.01" class="form-control" name="allowances" id="edit_allowances">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_overtime_hours" class="form-label">Overtime Hours</label>
                                    <input type="number" step="0.5" class="form-control" name="overtime_hours" id="edit_overtime_hours">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_overtime_rate" class="form-label">Overtime Rate (per hour)</label>
                                    <input type="number" step="0.01" class="form-control" name="overtime_rate" id="edit_overtime_rate">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_deductions" class="form-label">Other Deductions</label>
                                    <input type="number" step="0.01" class="form-control" name="deductions" id="edit_deductions">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_tax_deduction" class="form-label">Tax Deduction</label>
                                    <input type="number" step="0.01" class="form-control" name="tax_deduction" id="edit_tax_deduction">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Payroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Payroll Details Modal -->
    <div class="modal fade" id="viewPayrollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payroll Details</h5>
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
                            <h6>Pay Period</h6>
                            <p><strong>Start:</strong> <span id="view_period_start"></span></p>
                            <p><strong>End:</strong> <span id="view_period_end"></span></p>
                            <p><strong>Status:</strong> <span id="view_status"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Earnings</h6>
                            <p><strong>Basic Salary:</strong> $<span id="view_basic_salary"></span></p>
                            <p><strong>Overtime Pay:</strong> $<span id="view_overtime_pay"></span></p>
                            <p><strong>Allowances:</strong> $<span id="view_allowances"></span></p>
                            <p class="text-success"><strong>Gross Pay:</strong> $<span id="view_gross_pay"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Deductions</h6>
                            <p><strong>Other Deductions:</strong> $<span id="view_deductions"></span></p>
                            <p><strong>Tax Deduction:</strong> $<span id="view_tax_deduction"></span></p>
                            <p class="text-danger"><strong>Total Deductions:</strong> $<span id="view_total_deductions"></span></p>
                            <p class="text-primary"><strong>Net Pay:</strong> $<span id="view_net_pay"></span></p>
                        </div>
                    </div>
                    <div class="row" id="overtime_details" style="display: none;">
                        <div class="col-12">
                            <hr>
                            <h6>Overtime Details</h6>
                            <p><strong>Hours:</strong> <span id="view_overtime_hours"></span></p>
                            <p><strong>Rate:</strong> $<span id="view_overtime_rate"></span> per hour</p>
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
    <div class="modal fade" id="deletePayrollModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the payroll record for <strong id="delete_employee_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_payroll">
                        <input type="hidden" name="payroll_id" id="delete_payroll_id">
                        <button type="submit" class="btn btn-danger">Delete Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill basic salary when employee is selected
        document.getElementById('employee_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const salary = selectedOption.getAttribute('data-salary');
            if (salary) {
                document.getElementById('basic_salary').value = salary;
            }
        });
        
        function editPayroll(record) {
            document.getElementById('edit_payroll_id').value = record.id;
            document.getElementById('edit_employee_name').value = record.employee_name;
            document.getElementById('edit_basic_salary').value = record.basic_salary;
            document.getElementById('edit_allowances').value = record.allowances || 0;
            document.getElementById('edit_overtime_hours').value = record.overtime_hours || 0;
            document.getElementById('edit_overtime_rate').value = record.overtime_rate || 0;
            document.getElementById('edit_deductions').value = record.deductions || 0;
            document.getElementById('edit_tax_deduction').value = record.tax_deduction || 0;
            document.getElementById('edit_status').value = record.status;
            
            new bootstrap.Modal(document.getElementById('editPayrollModal')).show();
        }
        
        function viewPayrollDetails(record) {
            document.getElementById('view_employee_name').textContent = record.employee_name;
            document.getElementById('view_position').textContent = record.position;
            document.getElementById('view_department').textContent = record.department;
            document.getElementById('view_period_start').textContent = new Date(record.pay_period_start).toLocaleDateString();
            document.getElementById('view_period_end').textContent = new Date(record.pay_period_end).toLocaleDateString();
            document.getElementById('view_status').textContent = record.status.charAt(0).toUpperCase() + record.status.slice(1);
            document.getElementById('view_basic_salary').textContent = parseFloat(record.basic_salary).toFixed(2);
            document.getElementById('view_overtime_pay').textContent = parseFloat(record.overtime_pay || 0).toFixed(2);
            document.getElementById('view_allowances').textContent = parseFloat(record.allowances || 0).toFixed(2);
            document.getElementById('view_gross_pay').textContent = parseFloat(record.gross_pay).toFixed(2);
            document.getElementById('view_deductions').textContent = parseFloat(record.deductions || 0).toFixed(2);
            document.getElementById('view_tax_deduction').textContent = parseFloat(record.tax_deduction || 0).toFixed(2);
            document.getElementById('view_total_deductions').textContent = (parseFloat(record.deductions || 0) + parseFloat(record.tax_deduction || 0)).toFixed(2);
            document.getElementById('view_net_pay').textContent = parseFloat(record.net_pay).toFixed(2);
            
            if (record.overtime_hours > 0) {
                document.getElementById('view_overtime_hours').textContent = record.overtime_hours;
                document.getElementById('view_overtime_rate').textContent = parseFloat(record.overtime_rate).toFixed(2);
                document.getElementById('overtime_details').style.display = 'block';
            } else {
                document.getElementById('overtime_details').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewPayrollModal')).show();
        }
        
        function deletePayroll(payrollId, employeeName) {
            document.getElementById('delete_payroll_id').value = payrollId;
            document.getElementById('delete_employee_name').textContent = employeeName;
            
            new bootstrap.Modal(document.getElementById('deletePayrollModal')).show();
        }
        
        // Set default pay period dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            const startInput = document.querySelector('#addPayrollModal input[name="pay_period_start"]');
            const endInput = document.querySelector('#addPayrollModal input[name="pay_period_end"]');
            
            if (startInput) {
                startInput.value = firstDay.toISOString().split('T')[0];
            }
            if (endInput) {
                endInput.value = lastDay.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>