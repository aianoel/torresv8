<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is accounting
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'accounting') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Payment Management';
$message = '';

// Handle payment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $paymentId = $_POST['payment_id'];
        $newStatus = $_POST['status'];
        
        $updateQuery = $conn->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
        $updateQuery->bind_param('si', $newStatus, $paymentId);
        
        if ($updateQuery->execute()) {
            $message = '<div class="alert alert-success">Payment status updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating payment status.</div>';
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter !== 'all') {
    $whereConditions[] = "p.payment_status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($dateFrom) {
    $whereConditions[] = "DATE(p.transaction_date) >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}

if ($dateTo) {
    $whereConditions[] = "DATE(p.transaction_date) <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

if ($searchTerm) {
    $whereConditions[] = "(CONCAT(g.first_name, ' ', g.last_name) LIKE ? OR r.room_number LIKE ?)";
    $searchParam = '%' . $searchTerm . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get payments with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$paymentsQuery = $conn->prepare("
    SELECT 
        p.id, p.amount, p.payment_method, p.payment_status, p.transaction_date,
        CONCAT(g.first_name, ' ', g.last_name) as guest_name,
        r.room_number, r.room_type,
        b.check_in_date, b.check_out_date,
        CONCAT(u.first_name, ' ', u.last_name) as processed_by_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON p.processed_by = u.id
    $whereClause
    ORDER BY p.transaction_date DESC
    LIMIT ? OFFSET ?
");

if (!empty($params)) {
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $paymentsQuery->bind_param($types, ...$params);
} else {
    $paymentsQuery->bind_param('ii', $limit, $offset);
}

$paymentsQuery->execute();
$payments = $paymentsQuery->get_result();

// Get total count for pagination
$countQuery = $conn->prepare("
    SELECT COUNT(*) as total
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    $whereClause
");

if (!empty($whereConditions)) {
    $countTypes = substr($types, 0, -2); // Remove the 'ii' for limit/offset
    $countParams = array_slice($params, 0, -2); // Remove limit/offset params
    if (!empty($countParams)) {
        $countQuery->bind_param($countTypes, ...$countParams);
    }
}

$countQuery->execute();
$totalRecords = $countQuery->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get payment statistics
$statsQuery = $conn->prepare("
    SELECT 
        payment_status,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM payments
    GROUP BY payment_status
");
$statsQuery->execute();
$stats = $statsQuery->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center px-3 mb-3">
                        <img src="../assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo" class="me-2" style="height: 32px;">
                        <span class="fw-bold text-primary"><?php echo APP_NAME; ?></span>
                    </div>
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Accounting</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="payments.php">
                                <i class="bi bi-credit-card"></i> Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="invoices.php">
                                <i class="bi bi-receipt"></i> Invoices
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Payment Management</h1>
                </div>

                <?php echo $message; ?>

                <!-- Payment Statistics -->
                <div class="row mb-4">
                    <?php while ($stat = $stats->fetch_assoc()): ?>
                    <div class="col-md-3">
                        <div class="card <?php 
                            echo $stat['payment_status'] === 'completed' ? 'text-white bg-success' : 
                                ($stat['payment_status'] === 'pending' ? 'text-white bg-warning' : 'text-white bg-danger');
                        ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo ucfirst($stat['payment_status']); ?> Payments</h6>
                                <h4><?php echo $stat['count']; ?></h4>
                                <small>$<?php echo number_format($stat['total_amount'], 2); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Guest name or room number" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="payments.php" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>Payment Records (<?php echo $totalRecords; ?> total)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $payment['id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['guest_name']); ?></td>
                                        <td>
                                            <?php echo $payment['room_number']; ?><br>
                                            <small class="text-muted"><?php echo $payment['room_type']; ?></small>
                                        </td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $payment['payment_status'] === 'completed' ? 'bg-success' : 
                                                    ($payment['payment_status'] === 'pending' ? 'bg-warning' : 'bg-danger');
                                            ?>">
                                                <?php echo ucfirst($payment['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($payment['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['processed_by_name']); ?></td>
                                        <td>
                                            <?php if ($payment['payment_status'] === 'pending'): ?>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-success" onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'completed')">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'refunded')">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Payment pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&search=<?php echo urlencode($searchTerm); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Update Status Form (Hidden) -->
    <form id="updateStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="payment_id" id="updatePaymentId">
        <input type="hidden" name="status" id="updateStatus">
    </form>

    <script>
        function updatePaymentStatus(paymentId, status) {
            if (confirm('Are you sure you want to update this payment status?')) {
                document.getElementById('updatePaymentId').value = paymentId;
                document.getElementById('updateStatus').value = status;
                document.getElementById('updateStatusForm').submit();
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>