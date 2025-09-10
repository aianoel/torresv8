<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Inventory Management';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'adjust_stock':
                $product_id = $_POST['product_id'];
                $adjustment_type = $_POST['adjustment_type'];
                $quantity = intval($_POST['quantity']);
                $reason = trim($_POST['reason']);
                
                if ($quantity > 0) {
                    $conn->begin_transaction();
                    
                    try {
                        // Get current stock
                        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $product = $result->fetch_assoc();
                        
                        $old_quantity = $product['stock_quantity'];
                        $new_quantity = $adjustment_type === 'increase' ? $old_quantity + $quantity : $old_quantity - $quantity;
                        
                        if ($new_quantity < 0) {
                            throw new Exception('Cannot reduce stock below zero');
                        }
                        
                        // Update product stock
                        $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                        $stmt->bind_param("ii", $new_quantity, $product_id);
                        $stmt->execute();
                        
                        // Log the adjustment
                        $stmt = $conn->prepare("INSERT INTO inventory_adjustments (product_id, adjustment_type, quantity, old_quantity, new_quantity, reason, adjusted_by, adjustment_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("isiiiis", $product_id, $adjustment_type, $quantity, $old_quantity, $new_quantity, $reason, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $conn->commit();
                        $message = '<div class="alert alert-success">Stock adjustment completed successfully!</div>';
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = '<div class="alert alert-danger">Error adjusting stock: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    $message = '<div class="alert alert-warning">Please enter a valid quantity!</div>';
                }
                break;
                
            case 'reorder_stock':
                $product_id = $_POST['product_id'];
                $reorder_quantity = intval($_POST['reorder_quantity']);
                $supplier = trim($_POST['supplier']);
                $cost_per_unit = floatval($_POST['cost_per_unit']);
                $notes = trim($_POST['notes']);
                
                $stmt = $conn->prepare("INSERT INTO reorder_requests (product_id, quantity, supplier, cost_per_unit, notes, requested_by, request_date, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')");
                $stmt->bind_param("iisdsi", $product_id, $reorder_quantity, $supplier, $cost_per_unit, $notes, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Reorder request submitted successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error submitting reorder request: ' . $conn->error . '</div>';
                }
                break;
        }
    }
}

// Get inventory overview
$inventory_query = "SELECT p.*, c.name as category_name, 
    CASE 
        WHEN p.stock_quantity = 0 THEN 'out_of_stock'
        WHEN p.stock_quantity <= p.min_stock THEN 'low_stock'
        ELSE 'in_stock'
    END as stock_status
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY 
        CASE 
            WHEN p.stock_quantity = 0 THEN 1
            WHEN p.stock_quantity <= p.min_stock THEN 2
            ELSE 3
        END, p.name";
$inventory_result = $conn->query($inventory_query);

// Get recent adjustments
$adjustments_query = "SELECT ia.*, p.name as product_name, u.username as adjusted_by_name 
    FROM inventory_adjustments ia 
    LEFT JOIN products p ON ia.product_id = p.id 
    LEFT JOIN users u ON ia.adjusted_by = u.id 
    ORDER BY ia.adjustment_date DESC LIMIT 10";
$adjustments_result = $conn->query($adjustments_query);

// Get pending reorders
$reorders_query = "SELECT rr.*, p.name as product_name, u.username as requested_by_name 
    FROM reorder_requests rr 
    LEFT JOIN products p ON rr.product_id = p.id 
    LEFT JOIN users u ON rr.requested_by = u.id 
    WHERE rr.status = 'pending' 
    ORDER BY rr.request_date DESC";
$reorders_result = $conn->query($reorders_query);

// Get inventory statistics
$stats_query = "SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(CASE WHEN stock_quantity <= min_stock AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
    SUM(stock_quantity * cost) as total_inventory_value
    FROM products";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
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
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link:hover {
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            color: var(--primary-color);
            font-weight: bold;
        }
        .main-content {
            padding: 20px;
        }
        .stock-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .status-in-stock {
            background-color: #d1edff;
            color: #0969da;
        }
        .status-low-stock {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
        .stat-card {
            border-left: 4px solid var(--primary-color);
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
                        <a class="nav-link active" href="inventory.php">
                            <i class="bi bi-boxes me-2"></i>Inventory
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Inventory Management</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                            <i class="bi bi-arrow-up-down me-2"></i>Adjust Stock
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reorderModal">
                            <i class="bi bi-cart-plus me-2"></i>Reorder Stock
                        </button>
                    </div>
                </div>

                <?php echo $message; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Total Products</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_products']; ?></h3>
                                    </div>
                                    <i class="bi bi-box-seam display-6 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-left: 4px solid #dc3545;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Out of Stock</h6>
                                        <h3 class="mb-0 text-danger"><?php echo $stats['out_of_stock']; ?></h3>
                                    </div>
                                    <i class="bi bi-exclamation-triangle display-6 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Low Stock</h6>
                                        <h3 class="mb-0 text-warning"><?php echo $stats['low_stock']; ?></h3>
                                    </div>
                                    <i class="bi bi-exclamation-circle display-6 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-1">Inventory Value</h6>
                                        <h3 class="mb-0 text-success">₱<?php echo number_format($stats['total_inventory_value'], 2); ?></h3>
                                    </div>
                                    <i class="bi bi-currency-dollar display-6 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Current Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Min Stock</th>
                                        <th>Status</th>
                                        <th>Value</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($inventory_result && $inventory_result->num_rows > 0): ?>
                                        <?php while ($product = $inventory_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <?php if ($product['barcode']): ?>
                                                        <br><small class="text-muted">Barcode: <?php echo htmlspecialchars($product['barcode']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?: 'No Category'); ?></td>
                                                <td><?php echo $product['stock_quantity']; ?></td>
                                                <td><?php echo $product['min_stock']; ?></td>
                                                <td>
                                                    <span class="stock-status status-<?php echo str_replace('_', '-', $product['stock_status']); ?>">
                                                        <?php 
                                                        switch($product['stock_status']) {
                                                            case 'out_of_stock': echo 'Out of Stock'; break;
                                                            case 'low_stock': echo 'Low Stock'; break;
                                                            default: echo 'In Stock'; break;
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>₱<?php echo number_format($product['stock_quantity'] * $product['cost'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adjustStock(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                        <i class="bi bi-arrow-up-down"></i>
                                                    </button>
                                                    <?php if ($product['stock_status'] !== 'in_stock'): ?>
                                                        <button class="btn btn-sm btn-outline-success" onclick="reorderProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                            <i class="bi bi-cart-plus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bi bi-boxes display-1"></i>
                                                <p class="mt-2">No products found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Adjustments -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Stock Adjustments</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($adjustments_result && $adjustments_result->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($adjustment = $adjustments_result->fetch_assoc()): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($adjustment['product_name']); ?></h6>
                                                        <p class="mb-1 small">
                                                            <span class="badge bg-<?php echo $adjustment['adjustment_type'] === 'increase' ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($adjustment['adjustment_type']); ?> <?php echo $adjustment['quantity']; ?>
                                                            </span>
                                                            <?php echo $adjustment['old_quantity']; ?> → <?php echo $adjustment['new_quantity']; ?>
                                                        </p>
                                                        <small class="text-muted"><?php echo htmlspecialchars($adjustment['reason']); ?></small>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, g:i A', strtotime($adjustment['adjustment_date'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No recent adjustments</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Reorders -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Pending Reorder Requests</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($reorders_result && $reorders_result->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($reorder = $reorders_result->fetch_assoc()): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($reorder['product_name']); ?></h6>
                                                        <p class="mb-1 small">
                                                            Quantity: <strong><?php echo $reorder['quantity']; ?></strong> | 
                                                            Cost: <strong>₱<?php echo number_format($reorder['cost_per_unit'], 2); ?></strong>
                                                        </p>
                                                        <small class="text-muted">Supplier: <?php echo htmlspecialchars($reorder['supplier']); ?></small>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j', strtotime($reorder['request_date'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No pending reorder requests</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="adjust_stock">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select class="form-select" name="product_id" id="adjust_product_id" required>
                                <option value="">Select Product</option>
                                <?php 
                                if ($inventory_result) {
                                    $inventory_result->data_seek(0);
                                    while ($product = $inventory_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['id']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> (Current: <?php echo $product['stock_quantity']; ?>)
                                    </option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adjustment Type</label>
                            <select class="form-select" name="adjustment_type" required>
                                <option value="increase">Increase Stock</option>
                                <option value="decrease">Decrease Stock</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Reason for adjustment" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Adjust Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reorder Modal -->
    <div class="modal fade" id="reorderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reorder Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reorder_stock">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select class="form-select" name="product_id" id="reorder_product_id" required>
                                <option value="">Select Product</option>
                                <?php 
                                if ($inventory_result) {
                                    $inventory_result->data_seek(0);
                                    while ($product = $inventory_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo $product['stock_quantity']; ?>)
                                    </option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reorder Quantity</label>
                            <input type="number" class="form-control" name="reorder_quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" name="supplier" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cost per Unit</label>
                            <input type="number" class="form-control" name="cost_per_unit" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Reorder Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function adjustStock(product) {
            document.getElementById('adjust_product_id').value = product.id;
            new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
        }
        
        function reorderProduct(product) {
            document.getElementById('reorder_product_id').value = product.id;
            new bootstrap.Modal(document.getElementById('reorderModal')).show();
        }
    </script>
</body>
</html>