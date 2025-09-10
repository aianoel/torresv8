<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $type = $_POST['type'];
    $reason = $_POST['reason'];
    
    // Validate dates
    if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = 'Start date cannot be in the past.';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = 'End date cannot be before start date.';
    } elseif (empty($reason)) {
        $error = 'Reason is required.';
    } else {
        // Insert leave request
        $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, start_date, end_date, type, reason) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $start_date, $end_date, $type, $reason])) {
            $message = 'Leave request submitted successfully!';
        } else {
            $error = 'Failed to submit leave request. Please try again.';
        }
    }
}

// Get user's leave requests
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$leave_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request - Torres Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        .leave-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-pending { color: #ffc107; }
        .status-approved { color: #198754; }
        .status-rejected { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hotel"></i> Torres Hotel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card leave-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-calendar-alt"></i> Leave Request</h4>
                    </div>
                    <div class="card-body">
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

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Leave Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select leave type</option>
                                    <option value="vacation">Vacation</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="personal">Personal</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="maternity">Maternity/Paternity</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Please provide a reason for your leave request..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Leave History -->
                <div class="card leave-card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> My Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leave_requests)): ?>
                            <p class="text-muted text-center">No leave requests found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
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
                                        <?php foreach ($leave_requests as $request): ?>
                                            <tr>
                                                <td><?php echo ucfirst(htmlspecialchars($request['type'])); ?></td>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum end date based on start date
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    </script>
</body>
</html>