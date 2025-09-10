<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Employee Management';
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_employee':
                // Add new employee
                $user_id = $_POST['user_id'];
                $position = $_POST['position'];
                $department = $_POST['department'];
                $hire_date = $_POST['hire_date'];
                $salary = $_POST['salary'];
                $emergency_contact = $_POST['emergency_contact'];
                $emergency_phone = $_POST['emergency_phone'];
                
                $stmt = $conn->prepare("INSERT INTO employees (user_id, position, department, hire_date, salary, emergency_contact, emergency_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdss", $user_id, $position, $department, $hire_date, $salary, $emergency_contact, $emergency_phone);
                
                if ($stmt->execute()) {
                    $message = 'Employee added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding employee: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'update_employee':
                // Update employee
                $employee_id = $_POST['employee_id'];
                $position = $_POST['position'];
                $department = $_POST['department'];
                $salary = $_POST['salary'];
                $emergency_contact = $_POST['emergency_contact'];
                $emergency_phone = $_POST['emergency_phone'];
                
                $stmt = $conn->prepare("UPDATE employees SET position = ?, department = ?, salary = ?, emergency_contact = ?, emergency_phone = ? WHERE id = ?");
                $stmt->bind_param("ssdssi", $position, $department, $salary, $emergency_contact, $emergency_phone, $employee_id);
                
                if ($stmt->execute()) {
                    $message = 'Employee updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating employee: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_employee':
                // Delete employee
                $employee_id = $_POST['employee_id'];
                
                $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->bind_param("i", $employee_id);
                
                if ($stmt->execute()) {
                    $message = 'Employee deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting employee: ' . $conn->error;
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get all employees
$employeesQuery = $conn->prepare("
    SELECT e.id, e.position, e.department, e.hire_date, e.salary, e.emergency_contact, e.emergency_phone,
           CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email, u.username, u.id as user_id
    FROM employees e
    JOIN users u ON e.user_id = u.id
    ORDER BY u.first_name, u.last_name
");
$employeesQuery->execute();
$employees = $employeesQuery->get_result();

// Get users without employee records for adding new employees
$availableUsersQuery = $conn->prepare("
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email, u.role
    FROM users u
    LEFT JOIN employees e ON u.id = e.user_id
    WHERE e.user_id IS NULL AND u.role != 'admin'
    ORDER BY u.first_name, u.last_name
");
$availableUsersQuery->execute();
$availableUsers = $availableUsersQuery->get_result();
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
                <a class="nav-link active" href="employees.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Employee Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="bi bi-plus-circle me-2"></i> Add Employee
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Employees Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Employees</h5>
            </div>
            <div class="card-body">
                <?php if ($employees->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Hire Date</th>
                                    <th>Salary</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($employee = $employees->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($employee['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($employee['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                                        <td>$<?php echo number_format($employee['salary'], 2); ?></td>
                                        <td>
                                            <?php if ($employee['emergency_contact']): ?>
                                                <?php echo htmlspecialchars($employee['emergency_contact']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($employee['emergency_phone']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Not provided</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['full_name']); ?>')">
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
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No employees found. Add your first employee to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_employee">
                        
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select User</label>
                            <select class="form-select" name="user_id" required>
                                <option value="">Choose a user...</option>
                                <?php while ($user = $availableUsers->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['role']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" name="department" required>
                                <option value="">Choose department...</option>
                                <option value="Front Desk">Front Desk</option>
                                <option value="Housekeeping">Housekeeping</option>
                                <option value="Accounting">Accounting</option>
                                <option value="Human Resources">Human Resources</option>
                                <option value="Food & Beverage">Food & Beverage</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Security">Security</option>
                                <option value="Management">Management</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hire_date" class="form-label">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="salary" class="form-label">Salary</label>
                            <input type="number" class="form-control" name="salary" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="emergency_contact" class="form-label">Emergency Contact</label>
                            <input type="text" class="form-control" name="emergency_contact">
                        </div>
                        
                        <div class="mb-3">
                            <label for="emergency_phone" class="form-label">Emergency Phone</label>
                            <input type="tel" class="form-control" name="emergency_phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_employee">
                        <input type="hidden" name="employee_id" id="edit_employee_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="edit_employee_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_position" class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" id="edit_position" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_department" class="form-label">Department</label>
                            <select class="form-select" name="department" id="edit_department" required>
                                <option value="Front Desk">Front Desk</option>
                                <option value="Housekeeping">Housekeeping</option>
                                <option value="Accounting">Accounting</option>
                                <option value="Human Resources">Human Resources</option>
                                <option value="Food & Beverage">Food & Beverage</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Security">Security</option>
                                <option value="Management">Management</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_salary" class="form-label">Salary</label>
                            <input type="number" class="form-control" name="salary" id="edit_salary" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_emergency_contact" class="form-label">Emergency Contact</label>
                            <input type="text" class="form-control" name="emergency_contact" id="edit_emergency_contact">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_emergency_phone" class="form-label">Emergency Phone</label>
                            <input type="tel" class="form-control" name="emergency_phone" id="edit_emergency_phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete employee <strong id="delete_employee_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_employee">
                        <input type="hidden" name="employee_id" id="delete_employee_id">
                        <button type="submit" class="btn btn-danger">Delete Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEmployee(employee) {
            document.getElementById('edit_employee_id').value = employee.id;
            document.getElementById('edit_employee_name').value = employee.full_name;
            document.getElementById('edit_position').value = employee.position;
            document.getElementById('edit_department').value = employee.department;
            document.getElementById('edit_salary').value = employee.salary;
            document.getElementById('edit_emergency_contact').value = employee.emergency_contact || '';
            document.getElementById('edit_emergency_phone').value = employee.emergency_phone || '';
            
            new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
        }
        
        function deleteEmployee(id, name) {
            document.getElementById('delete_employee_id').value = id;
            document.getElementById('delete_employee_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteEmployeeModal')).show();
        }
    </script>
</body>
</html>