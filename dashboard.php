<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();

// Get basic statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Torres Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --gold-color: #D4AF37;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.2s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            color: white;
        }
        
        .quick-action {
            background: white;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .quick-action:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color)) !important;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hotel"></i> Torres Hotel
            </a>
            <div class="navbar-nav ms-auto">
                <?php if ($role === 'admin'): ?>
                    <a class="nav-link" href="admin/dashboard.php">Admin Panel</a>
                <?php elseif ($role === 'hr'): ?>
                    <a class="nav-link" href="hr/dashboard.php">HR Panel</a>
                <?php elseif ($role === 'accounting'): ?>
                    <a class="nav-link" href="accounting/dashboard.php">Accounting Panel</a>
                <?php elseif ($role === 'pos_admin'): ?>
                    <a class="nav-link" href="pos_admin/dashboard.php">POS Admin</a>
                <?php elseif ($role === 'pos_cashier'): ?>
                    <a class="nav-link" href="pos_cashier/dashboard.php">POS Cashier</a>
                <?php elseif ($role === 'frontdesk'): ?>
                    <a class="nav-link" href="frontdesk/dashboard.php">Front Desk</a>
                <?php elseif ($role === 'housekeeping'): ?>
                    <a class="nav-link" href="housekeeping/dashboard.php">Housekeeping</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
                    <p class="mb-0 opacity-75">Here's your personal dashboard for Torres Hotel</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="h5 mb-0"><?php echo date('F j, Y'); ?></div>
                    <small><?php echo date('l, g:i A'); ?></small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <a href="leave_request.php" class="card dashboard-card quick-action text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-plus fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Request Leave</h5>
                        <p class="card-text">Submit a new leave request</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 mb-3">
                <a href="public/feedback.php" class="card dashboard-card quick-action text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-comment-dots fa-3x mb-3 text-success"></i>
                        <h5 class="card-title">Give Feedback</h5>
                        <p class="card-text">Share your experience with us</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Leave Request Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                        <h3><?php echo $leave_stats['total'] ?? 0; ?></h3>
                        <p class="mb-0">Total Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card" style="background: #ffc107; color: white;">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h3><?php echo $leave_stats['pending'] ?? 0; ?></h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card" style="background: #28a745; color: white;">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo $leave_stats['approved'] ?? 0; ?></h3>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card" style="background: #dc3545; color: white;">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h3><?php echo $leave_stats['rejected'] ?? 0; ?></h3>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Leave Requests -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Leave Requests</h5>
                            <a href="leave_request.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Request
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_leave_requests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No leave requests yet</h5>
                                <p class="text-muted">Click the button above to submit your first leave request</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_leave_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo ucfirst(htmlspecialchars($request['type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['end_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $request['status'] == 'approved' ? 'success' : ($request['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="leave_request.php" class="btn btn-outline-primary">
                                    View All Requests
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>