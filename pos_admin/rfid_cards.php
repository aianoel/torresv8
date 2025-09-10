<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Check if user is logged in and has pos_admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Handle RFID card operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_card':
                $card_number = trim($_POST['card_number']);
                $card_name = trim($_POST['card_name']);
                $initial_balance = floatval($_POST['initial_balance']);
                
                try {
                    $conn->begin_transaction();
                    
                    // Insert RFID card
                    $stmt = $conn->prepare("INSERT INTO rfid_cards (card_number, card_name, balance, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssdi", $card_number, $card_name, $initial_balance, $user_id);
                    $stmt->execute();
                    
                    $card_id = $conn->insert_id;
                    
                    // Log initial balance if > 0
                    if ($initial_balance > 0) {
                        $stmt = $conn->prepare("INSERT INTO rfid_transactions (rfid_card_id, transaction_type, amount, balance_before, balance_after, processed_by, notes) VALUES (?, 'load', ?, 0, ?, ?, 'Initial balance')");
                        $stmt->bind_param("iddi", $card_id, $initial_balance, $initial_balance, $user_id);
                        $stmt->execute();
                    }
                    
                    $conn->commit();
                    $success_message = "RFID card created successfully!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Error creating RFID card: " . $e->getMessage();
                }
                break;
                
            case 'load_balance':
                $card_id = intval($_POST['card_id']);
                $load_amount = floatval($_POST['load_amount']);
                
                try {
                    $conn->begin_transaction();
                    
                    // Get current balance
                    $stmt = $conn->prepare("SELECT balance FROM rfid_cards WHERE id = ?");
                    $stmt->bind_param("i", $card_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $card = $result->fetch_assoc();
                    
                    if ($card) {
                        $old_balance = $card['balance'];
                        $new_balance = $old_balance + $load_amount;
                        
                        // Update card balance
                        $stmt = $conn->prepare("UPDATE rfid_cards SET balance = ? WHERE id = ?");
                        $stmt->bind_param("di", $new_balance, $card_id);
                        $stmt->execute();
                        
                        // Log transaction
                        $stmt = $conn->prepare("INSERT INTO rfid_transactions (rfid_card_id, transaction_type, amount, balance_before, balance_after, processed_by, notes) VALUES (?, 'load', ?, ?, ?, ?, 'Balance loaded by admin')");
                        $stmt->bind_param("idddi", $card_id, $load_amount, $old_balance, $new_balance, $user_id);
                        $stmt->execute();
                        
                        $conn->commit();
                        $success_message = "Balance loaded successfully!";
                    } else {
                        $error_message = "RFID card not found.";
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Error loading balance: " . $e->getMessage();
                }
                break;
                
            case 'update_status':
                $card_id = intval($_POST['card_id']);
                $status = $_POST['status'];
                
                try {
                    $stmt = $conn->prepare("UPDATE rfid_cards SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $status, $card_id);
                    $stmt->execute();
                    $success_message = "Card status updated successfully!";
                } catch (Exception $e) {
                    $error_message = "Error updating status: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all RFID cards
$cards_query = "SELECT r.*, u.username as created_by_name FROM rfid_cards r LEFT JOIN users u ON r.created_by = u.id ORDER BY r.created_at DESC";
$cards_result = $conn->query($cards_query);

// Get recent transactions
$transactions_query = "SELECT rt.*, rc.card_number, rc.card_name, u.username as processed_by_name FROM rfid_transactions rt LEFT JOIN rfid_cards rc ON rt.rfid_card_id = rc.id LEFT JOIN users u ON rt.processed_by = u.id ORDER BY rt.transaction_date DESC LIMIT 20";
$transactions_result = $conn->query($transactions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Card Management - Torres Farm Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --purple-color: #6f42c1;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
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
        
        .main-content {
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--info-color), #5dade2);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            border: none;
        }
        
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--light-color);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .table td {
            border: none;
            vertical-align: middle;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="logo.png" alt="Logo" class="me-2" style="height: 40px;">
                        <h5 class="mb-0">POS Admin</h5>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box me-2"></i>Products
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link" href="sales.php">
                            <i class="bi bi-graph-up me-2"></i>Sales
                        </a>
                        <a class="nav-link" href="inventory.php">
                            <i class="bi bi-boxes me-2"></i>Inventory
                        </a>
                        <a class="nav-link active" href="rfid_cards.php">
                            <i class="bi bi-credit-card me-2"></i>RFID Cards
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                        <hr>
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1><i class="bi bi-credit-card me-3"></i>RFID Card Management</h1>
                            <p class="mb-0">Manage RFID cards for POS payment system</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#createCardModal">
                                <i class="bi bi-plus-circle me-2"></i>Create New Card
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- RFID Cards Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>RFID Cards</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Card Number</th>
                                        <th>Card Name</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($card = $cards_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($card['card_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($card['card_name']); ?></td>
                                            <td><span class="badge bg-success">₱<?php echo number_format($card['balance'], 2); ?></span></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($card['status']) {
                                                    case 'active': $status_class = 'bg-success'; break;
                                                    case 'inactive': $status_class = 'bg-secondary'; break;
                                                    case 'blocked': $status_class = 'bg-danger'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($card['status']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($card['created_by_name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($card['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="loadBalance(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['card_name']); ?>')">
                                                    <i class="bi bi-plus-circle"></i> Load
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $card['id']; ?>, '<?php echo $card['status']; ?>')">
                                                    <i class="bi bi-gear"></i> Status
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Card</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Balance After</th>
                                        <th>Processed By</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($transaction['card_number']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($transaction['card_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $type_class = '';
                                                switch ($transaction['transaction_type']) {
                                                    case 'load': $type_class = 'bg-success'; break;
                                                    case 'payment': $type_class = 'bg-primary'; break;
                                                    case 'refund': $type_class = 'bg-info'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $type_class; ?>"><?php echo ucfirst($transaction['transaction_type']); ?></span>
                                            </td>
                                            <td>₱<?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td>₱<?php echo number_format($transaction['balance_after'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['processed_by_name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Card Modal -->
    <div class="modal fade" id="createCardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New RFID Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_card">
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="card_name" class="form-label">Card Name/Description</label>
                            <input type="text" class="form-control" id="card_name" name="card_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="initial_balance" class="form-label">Initial Balance</label>
                            <input type="number" class="form-control" id="initial_balance" name="initial_balance" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Card</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Load Balance Modal -->
    <div class="modal fade" id="loadBalanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Load Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="load_balance">
                        <input type="hidden" name="card_id" id="load_card_id">
                        <div class="mb-3">
                            <label class="form-label">Card Name</label>
                            <input type="text" class="form-control" id="load_card_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="load_amount" class="form-label">Amount to Load</label>
                            <input type="number" class="form-control" id="load_amount" name="load_amount" min="1" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Load Balance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-gear me-2"></i>Update Card Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="card_id" id="status_card_id">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadBalance(cardId, cardName) {
            document.getElementById('load_card_id').value = cardId;
            document.getElementById('load_card_name').value = cardName;
            new bootstrap.Modal(document.getElementById('loadBalanceModal')).show();
        }
        
        function updateStatus(cardId, currentStatus) {
            document.getElementById('status_card_id').value = cardId;
            document.getElementById('status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
    </script>
</body>
</html>