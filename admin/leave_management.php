<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/admin_layout.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'];
    
    if ($action == 'approve' || $action == 'reject') {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $admin_id, $request_id])) {
            $message = "Leave request has been {$status} successfully!";
        } else {
            $error = 'Failed to update leave request status.';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter != 'all') {
    $where_conditions[] = "lr.status = ?";
    $params[] = $status_filter;
}

if ($type_filter != 'all') {
    $where_conditions[] = "lr.type = ?";
    $params[] = $type_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get leave requests with user information
$sql = "SELECT lr.*, u.username, u.email, e.position, e.department
        FROM leave_requests lr 
        JOIN employees e ON lr.employee_id = e.id
        JOIN users u ON e.user_id = u.id 
        {$where_clause}
        ORDER BY lr.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leave_requests = $stmt->fetchAll();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM leave_requests";
$stats = $pdo->query($stats_sql)->fetch();

$pageTitle = 'Leave Management';
?>

<?php renderAdminHeader($pageTitle); ?>

<style>
    .stats-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .leave-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
</style>

<?php renderAdminPageHeader($pageTitle); ?>

<!-- Leave Management Content Section -->
<div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-calendar-check"></i> Leave Request Management</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total']; ?></h4>
                                        <p class="mb-0">Total Requests</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clipboard-list fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['pending']; ?></h4>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['approved']; ?></h4>
                                        <p class="mb-0">Approved</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['rejected']; ?></h4>
                                        <p class="mb-0">Rejected</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card leave-card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">Filter by Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <option value="vacation" <?php echo $type_filter == 'vacation' ? 'selected' : ''; ?>>Vacation</option>
                                    <option value="sick" <?php echo $type_filter == 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                                    <option value="personal" <?php echo $type_filter == 'personal' ? 'selected' : ''; ?>>Personal</option>
                                    <option value="emergency" <?php echo $type_filter == 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                                    <option value="maternity" <?php echo $type_filter == 'maternity' ? 'selected' : ''; ?>>Maternity/Paternity</option>
                                    <option value="other" <?php echo $type_filter == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="leave_management.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Leave Requests Table -->
                <div class="card leave-card">
                    <div class="card-header">
                        <h5 class="mb-0">Leave Requests (<?php echo count($leave_requests); ?> found)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leave_requests)): ?>
                            <p class="text-muted text-center">No leave requests found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leave_requests as $request): 
                                            $days = (strtotime($request['end_date']) - strtotime($request['start_date'])) / (60 * 60 * 24) + 1;
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['username']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                </td>
                                                <td><?php echo ucfirst(htmlspecialchars($request['type'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['end_date'])); ?></td>
                                                <td><?php echo $days; ?> day(s)</td>
                                                <td>
                                                    <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($request['reason']); ?>">
                                                        <?php echo strlen($request['reason']) > 50 ? substr(htmlspecialchars($request['reason']), 0, 50) . '...' : htmlspecialchars($request['reason']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $request['status'] == 'approved' ? 'success' : ($request['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                                                <td>
                                                    <?php if ($request['status'] == 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Approve this leave request?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this leave request?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">Processed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

<?php renderAdminFooter(); ?>