<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is accounting
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'accounting') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Invoice Management';
$message = '';

// Handle invoice actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_invoice') {
        $bookingId = $_POST['booking_id'];
        $totalAmount = $_POST['total_amount'];
        $taxAmount = $_POST['tax_amount'];
        $dueDate = $_POST['due_date'];
        
        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $insertQuery = $conn->prepare("
            INSERT INTO invoices (booking_id, invoice_number, issue_date, due_date, total_amount, tax_amount, created_by)
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?)
        ");
        $insertQuery->bind_param('issddi', $bookingId, $invoiceNumber, $dueDate, $totalAmount, $taxAmount, $_SESSION['user_id']);
        
        if ($insertQuery->execute()) {
            $message = '<div class="alert alert-success">Invoice created successfully! Invoice #' . $invoiceNumber . '</div>';
        } else {
            $message = '<div class="alert alert-danger">Error creating invoice.</div>';
        }
    }
    
    if ($_POST['action'] === 'update_status') {
        $invoiceId = $_POST['invoice_id'];
        $newStatus = $_POST['status'];
        
        $updateQuery = $conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $updateQuery->bind_param('si', $newStatus, $invoiceId);
        
        if ($updateQuery->execute()) {
            $message = '<div class="alert alert-success">Invoice status updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating invoice status.</div>';
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
    $whereConditions[] = "i.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($dateFrom) {
    $whereConditions[] = "DATE(i.issue_date) >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}

if ($dateTo) {
    $whereConditions[] = "DATE(i.issue_date) <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

if ($searchTerm) {
    $whereConditions[] = "(i.invoice_number LIKE ? OR CONCAT(g.first_name, ' ', g.last_name) LIKE ?)";
    $searchParam = '%' . $searchTerm . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get invoices with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$invoicesQuery = $conn->prepare("
    SELECT 
        i.id, i.invoice_number, i.issue_date, i.due_date, i.total_amount, i.tax_amount, i.status,
        CONCAT(g.first_name, ' ', g.last_name) as guest_name,
        r.room_number, r.room_type,
        b.check_in_date, b.check_out_date,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name
    FROM invoices i
    JOIN bookings b ON i.booking_id = b.id
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON i.created_by = u.id
    $whereClause
    ORDER BY i.issue_date DESC
    LIMIT ? OFFSET ?
");

if (!empty($params)) {
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $invoicesQuery->bind_param($types, ...$params);
} else {
    $invoicesQuery->bind_param('ii', $limit, $offset);
}

$invoicesQuery->execute();
$invoices = $invoicesQuery->get_result();

// Get total count for pagination
$countQuery = $conn->prepare("
    SELECT COUNT(*) as total
    FROM invoices i
    JOIN bookings b ON i.booking_id = b.id
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    $whereClause
");

if (!empty($whereConditions)) {
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    if (!empty($countParams)) {
        $countQuery->bind_param($countTypes, ...$countParams);
    }
}

$countQuery->execute();
$totalRecords = $countQuery->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get invoice statistics
$statsQuery = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(total_amount) as total_amount
    FROM invoices
    GROUP BY status
");
$statsQuery->execute();
$stats = $statsQuery->get_result();

// Get bookings without invoices for creating new invoices
$unbilledBookingsQuery = $conn->prepare("
    SELECT 
        b.id, b.check_in_date, b.check_out_date,
        CONCAT(g.first_name, ' ', g.last_name) as guest_name,
        r.room_number, r.price_per_night,
        DATEDIFF(b.check_out_date, b.check_in_date) as nights
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    LEFT JOIN invoices i ON b.id = i.booking_id
    WHERE i.id IS NULL AND b.status = 'checked_out'
    ORDER BY b.check_out_date DESC
    LIMIT 10
");
$unbilledBookingsQuery->execute();
$unbilledBookings = $unbilledBookingsQuery->get_result();
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
                            <a class="nav-link" href="payments.php">
                                <i class="bi bi-credit-card"></i> Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="invoices.php">
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
                    <h1 class="h2">Invoice Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
                        <i class="bi bi-plus"></i> Create Invoice
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- Invoice Statistics -->
                <div class="row mb-4">
                    <?php while ($stat = $stats->fetch_assoc()): ?>
                    <div class="col-md-3">
                        <div class="card <?php 
                            echo $stat['status'] === 'paid' ? 'text-white bg-success' : 
                                ($stat['status'] === 'sent' ? 'text-white bg-info' : 
                                ($stat['status'] === 'overdue' ? 'text-white bg-danger' : 'text-white bg-secondary'));
                        ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo ucfirst($stat['status']); ?> Invoices</h6>
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
                                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                                    <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                                <input type="text" class="form-control" id="search" name="search" placeholder="Invoice # or guest name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="invoices.php" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>Invoice Records (<?php echo $totalRecords; ?> total)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Tax</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($invoice = $invoices->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $invoice['invoice_number']; ?></td>
                                        <td><?php echo htmlspecialchars($invoice['guest_name']); ?></td>
                                        <td>
                                            <?php echo $invoice['room_number']; ?><br>
                                            <small class="text-muted"><?php echo $invoice['room_type']; ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                        <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                        <td>$<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $invoice['status'] === 'paid' ? 'bg-success' : 
                                                    ($invoice['status'] === 'sent' ? 'bg-info' : 
                                                    ($invoice['status'] === 'overdue' ? 'bg-danger' : 'bg-secondary'));
                                            ?>">
                                                <?php echo ucfirst($invoice['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($invoice['created_by_name']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($invoice['status'] === 'draft'): ?>
                                                <button type="button" class="btn btn-sm btn-info" onclick="updateInvoiceStatus(<?php echo $invoice['id']; ?>, 'sent')">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($invoice['status'] === 'sent'): ?>
                                                <button type="button" class="btn btn-sm btn-success" onclick="updateInvoiceStatus(<?php echo $invoice['id']; ?>, 'paid')">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Invoice pagination">
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

    <!-- Create Invoice Modal -->
    <div class="modal fade" id="createInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_invoice">
                        
                        <div class="mb-3">
                            <label for="booking_id" class="form-label">Select Booking</label>
                            <select class="form-select" id="booking_id" name="booking_id" required onchange="calculateInvoice()">
                                <option value="">Choose a booking...</option>
                                <?php while ($booking = $unbilledBookings->fetch_assoc()): ?>
                                <option value="<?php echo $booking['id']; ?>" 
                                        data-nights="<?php echo $booking['nights']; ?>" 
                                        data-rate="<?php echo $booking['price_per_night']; ?>">
                                    <?php echo $booking['guest_name']; ?> - Room <?php echo $booking['room_number']; ?> 
                                    (<?php echo date('M d', strtotime($booking['check_in_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subtotal" class="form-label">Subtotal</label>
                                    <input type="number" class="form-control" id="subtotal" step="0.01" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                    <input type="number" class="form-control" id="tax_rate" value="10" step="0.01" onchange="calculateInvoice()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tax_amount" class="form-label">Tax Amount</label>
                                    <input type="number" class="form-control" id="tax_amount" name="tax_amount" step="0.01" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Total Amount</label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Form (Hidden) -->
    <form id="updateStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="invoice_id" id="updateInvoiceId">
        <input type="hidden" name="status" id="updateStatus">
    </form>

    <script>
        function calculateInvoice() {
            const bookingSelect = document.getElementById('booking_id');
            const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
            
            if (selectedOption.value) {
                const nights = parseFloat(selectedOption.dataset.nights);
                const rate = parseFloat(selectedOption.dataset.rate);
                const taxRate = parseFloat(document.getElementById('tax_rate').value) / 100;
                
                const subtotal = nights * rate;
                const taxAmount = subtotal * taxRate;
                const total = subtotal + taxAmount;
                
                document.getElementById('subtotal').value = subtotal.toFixed(2);
                document.getElementById('tax_amount').value = taxAmount.toFixed(2);
                document.getElementById('total_amount').value = total.toFixed(2);
            } else {
                document.getElementById('subtotal').value = '';
                document.getElementById('tax_amount').value = '';
                document.getElementById('total_amount').value = '';
            }
        }
        
        function updateInvoiceStatus(invoiceId, status) {
            if (confirm('Are you sure you want to update this invoice status?')) {
                document.getElementById('updateInvoiceId').value = invoiceId;
                document.getElementById('updateStatus').value = status;
                document.getElementById('updateStatusForm').submit();
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>