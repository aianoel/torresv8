<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pos_cashier') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get date range for filtering
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get transactions for the cashier
$transactions_query = "SELECT 
    s.id,
    s.sale_number,
    s.subtotal,
    s.tax_amount,
    s.discount_amount,
    s.final_amount,
    s.payment_method,
    s.sale_date,
    COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE s.cashier_id = ? AND DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY s.sale_date DESC";

$stmt = $conn->prepare($transactions_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$transactions_result = $stmt->get_result();

// Get summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_transactions,
    COALESCE(SUM(final_amount), 0) as total_revenue,
    COALESCE(AVG(final_amount), 0) as avg_transaction
    FROM sales 
    WHERE cashier_id = ? AND DATE(sale_date) BETWEEN ? AND ?";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Torres Farm Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .revenue-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .avg-card {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        .logo {
            max-height: 40px;
        }
        .transaction-row {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .transaction-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="logo.png" alt="Torres Farm Hotel" class="logo mb-2">
                        <h6 class="text-white">POS Cashier</h6>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales.php">
                                <i class="fas fa-cash-register me-2"></i>
                                Point of Sale
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="transactions.php">
                                <i class="fas fa-receipt me-2"></i>
                                Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Transactions</h1>
                    <div class="text-muted">
                        Cashier: <?php echo htmlspecialchars($user_name); ?> | <?php echo date('F j, Y'); ?>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-2"></i>
                                            Filter
                                        </button>
                                        <a href="transactions.php" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-refresh me-2"></i>
                                            Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Transactions</h6>
                                        <h2 class="mb-0"><?php echo $summary['total_transactions']; ?></h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-receipt fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card revenue-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Revenue</h6>
                                        <h2 class="mb-0">₱<?php echo number_format($summary['total_revenue'], 2); ?></h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-peso-sign fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card avg-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Average Sale</h6>
                                        <h2 class="mb-0">₱<?php echo number_format($summary['avg_transaction'], 2); ?></h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Transaction History</h5>
                                <button class="btn btn-outline-primary btn-sm" onclick="printTransactions()">
                                    <i class="fas fa-print me-2"></i>
                                    Print Report
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sale #</th>
                                                <th>Date & Time</th>
                                                <th>Items</th>
                                                <th>Subtotal</th>
                                                <th>Discount</th>
                                                <th>Tax</th>
                                                <th>Total</th>
                                                <th>Payment</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($transactions_result->num_rows > 0): ?>
                                                <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                                                    <tr class="transaction-row" onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>)">
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($transaction['sale_number']); ?></strong>
                                                        </td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($transaction['sale_date'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $transaction['item_count']; ?> items</span>
                                                        </td>
                                                        <td>₱<?php echo number_format($transaction['subtotal'], 2); ?></td>
                                                        <td>₱<?php echo number_format($transaction['discount_amount'], 2); ?></td>
                                                        <td>₱<?php echo number_format($transaction['tax_amount'], 2); ?></td>
                                                        <td>
                                                            <strong>₱<?php echo number_format($transaction['final_amount'], 2); ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $transaction['payment_method'] === 'cash' ? 'success' : 'primary'; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); viewTransactionDetails(<?php echo $transaction['id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        <i class="fas fa-receipt fa-3x mb-3 opacity-50"></i>
                                                        <br>
                                                        No transactions found for the selected date range.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="transactionDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentTransactionId = null;

        function viewTransactionDetails(transactionId) {
            currentTransactionId = transactionId;
            const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
            modal.show();
            
            // Load transaction details via AJAX
            fetch(`get_transaction_details.php?id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTransactionDetails(data.transaction, data.items);
                    } else {
                        document.getElementById('transactionDetails').innerHTML = 
                            '<div class="alert alert-danger">Error loading transaction details.</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('transactionDetails').innerHTML = 
                        '<div class="alert alert-danger">Error loading transaction details.</div>';
                });
        }

        function displayTransactionDetails(transaction, items) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Transaction Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Sale Number:</strong></td><td>${transaction.sale_number}</td></tr>
                            <tr><td><strong>Date & Time:</strong></td><td>${new Date(transaction.sale_date).toLocaleString()}</td></tr>
                            <tr><td><strong>Payment Method:</strong></td><td>${transaction.payment_method.replace('_', ' ').toUpperCase()}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Amount Breakdown</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Subtotal:</strong></td><td>₱${parseFloat(transaction.subtotal).toFixed(2)}</td></tr>
                            <tr><td><strong>Discount:</strong></td><td>₱${parseFloat(transaction.discount_amount).toFixed(2)}</td></tr>
                            <tr><td><strong>Tax (12%):</strong></td><td>₱${parseFloat(transaction.tax_amount).toFixed(2)}</td></tr>
                            <tr class="table-active"><td><strong>Total:</strong></td><td><strong>₱${parseFloat(transaction.final_amount).toFixed(2)}</strong></td></tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <h6>Items Purchased</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            items.forEach(item => {
                html += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>${item.quantity}</td>
                        <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('transactionDetails').innerHTML = html;
        }

        function printReceipt() {
            if (!currentTransactionId) return;
            
            window.open(`print_receipt.php?id=${currentTransactionId}`, '_blank');
        }

        function printTransactions() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Transaction Report - ${startDate} to ${endDate}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
                        .summary-box { text-align: center; padding: 10px; border: 1px solid #ddd; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Torres Farm Hotel - Transaction Report</h2>
                        <p>Cashier: <?php echo htmlspecialchars($user_name); ?></p>
                        <p>Period: ${startDate} to ${endDate}</p>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-box">
                            <h3><?php echo $summary['total_transactions']; ?></h3>
                            <p>Total Transactions</p>
                        </div>
                        <div class="summary-box">
                            <h3>₱<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                        <div class="summary-box">
                            <h3>₱<?php echo number_format($summary['avg_transaction'], 2); ?></h3>
                            <p>Average Transaction</p>
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Sale Number</th>
                                <th>Date & Time</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
            `);
            
            <?php 
            // Reset the result pointer for printing
            $transactions_result->data_seek(0);
            while ($transaction = $transactions_result->fetch_assoc()): 
            ?>
                printWindow.document.write(`
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['sale_number']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($transaction['sale_date'])); ?></td>
                        <td><?php echo $transaction['item_count']; ?> items</td>
                        <td>₱<?php echo number_format($transaction['final_amount'], 2); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?></td>
                    </tr>
                `);
            <?php endwhile; ?>
            
            printWindow.document.write(`
                        </tbody>
                    </table>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>