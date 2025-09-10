<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Sales Management';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'process_sale':
                $customer_name = trim($_POST['customer_name']);
                $customer_phone = trim($_POST['customer_phone']);
                $payment_method = $_POST['payment_method'];
                $items = json_decode($_POST['items'], true);
                $total_amount = floatval($_POST['total_amount']);
                $discount = floatval($_POST['discount']);
                $tax = floatval($_POST['tax']);
                $final_amount = floatval($_POST['final_amount']);
                
                if (!empty($items)) {
                    $conn->begin_transaction();
                    
                    try {
                        // Insert sale record
                        $stmt = $conn->prepare("INSERT INTO sales (customer_name, customer_phone, payment_method, total_amount, discount, tax, final_amount, sale_date, cashier_id) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
                        $stmt->bind_param("sssddddi", $customer_name, $customer_phone, $payment_method, $total_amount, $discount, $tax, $final_amount, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $sale_id = $conn->insert_id;
                        
                        // Insert sale items and update stock
                        foreach ($items as $item) {
                            // Insert sale item
                            $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("iiidd", $sale_id, $item['product_id'], $item['quantity'], $item['price'], $item['total']);
                            $stmt->execute();
                            
                            // Update product stock
                            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                            $stmt->execute();
                        }
                        
                        $conn->commit();
                        $message = '<div class="alert alert-success">Sale processed successfully! Sale ID: ' . $sale_id . '</div>';
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = '<div class="alert alert-danger">Error processing sale: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    $message = '<div class="alert alert-warning">No items in cart!</div>';
                }
                break;
        }
    }
}

// Get recent sales
$recent_sales_query = "SELECT s.*, u.username as cashier_name FROM sales s LEFT JOIN users u ON s.cashier_id = u.id ORDER BY s.sale_date DESC LIMIT 10";
$recent_sales_result = $conn->query($recent_sales_query);

// Get products for POS
$products_query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity > 0 ORDER BY p.name";
$products_result = $conn->query($products_query);

// Get categories for filtering
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Get today's sales summary
$today_sales_query = "SELECT COUNT(*) as total_sales, SUM(final_amount) as total_revenue FROM sales WHERE DATE(sale_date) = CURDATE()";
$today_sales_result = $conn->query($today_sales_query);
$today_stats = $today_sales_result->fetch_assoc();
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
        .product-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-2px);
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .pos-section {
            height: calc(100vh - 140px);
            overflow-y: auto;
        }
        .cart-section {
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
        }
        .low-stock {
            border-left: 4px solid #ffc107;
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
                        <a class="nav-link active" href="sales.php">
                            <i class="bi bi-graph-up me-2"></i>Sales
                        </a>
                        <a class="nav-link" href="inventory.php">
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
            <div class="col-md-10 main-content p-0">
                <!-- Header -->
                <div class="bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1>Sales Management & POS</h1>
                        <div class="d-flex gap-3">
                            <div class="text-center">
                                <div class="h4 mb-0 text-primary"><?php echo $today_stats['total_sales'] ?: 0; ?></div>
                                <small class="text-muted">Today's Sales</small>
                            </div>
                            <div class="text-center">
                                <div class="h4 mb-0 text-success">₱<?php echo number_format($today_stats['total_revenue'] ?: 0, 2); ?></div>
                                <small class="text-muted">Today's Revenue</small>
                            </div>
                        </div>
                    </div>
                </div>

                <?php echo $message; ?>

                <div class="row g-0">
                    <!-- POS Section -->
                    <div class="col-md-8 p-3">
                        <div class="pos-section">
                            <!-- Category Filter -->
                            <div class="mb-3">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-outline-primary btn-sm category-filter active" data-category="all">All</button>
                                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <button class="btn btn-outline-primary btn-sm category-filter" data-category="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </button>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Search -->
                            <div class="mb-3">
                                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                            </div>

                            <!-- Products Grid -->
                            <div class="row" id="productsGrid">
                                <?php if ($products_result && $products_result->num_rows > 0): ?>
                                    <?php while ($product = $products_result->fetch_assoc()): ?>
                                        <div class="col-md-4 col-lg-3 mb-3 product-item" data-category="<?php echo $product['category_id']; ?>" data-name="<?php echo strtolower($product['name']); ?>">
                                            <div class="card product-card <?php echo $product['stock_quantity'] <= $product['min_stock'] ? 'low-stock' : ''; ?>" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                <div class="card-body p-2">
                                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <p class="card-text small text-muted mb-1"><?php echo htmlspecialchars($product['category_name'] ?: 'No Category'); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-primary">₱<?php echo number_format($product['price'], 2); ?></span>
                                                        <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                                                    </div>
                                                    <?php if ($product['stock_quantity'] <= $product['min_stock']): ?>
                                                        <small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Low Stock</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-5">
                                        <i class="bi bi-box display-1 text-muted"></i>
                                        <p class="text-muted mt-2">No products available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Section -->
                    <div class="col-md-4 cart-section">
                        <div class="p-3 h-100 d-flex flex-column">
                            <h5 class="mb-3">Current Sale</h5>
                            
                            <!-- Customer Info -->
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-sm mb-2" id="customerName" placeholder="Customer Name (Optional)">
                                <input type="text" class="form-control form-control-sm" id="customerPhone" placeholder="Customer Phone (Optional)">
                            </div>

                            <!-- Cart Items -->
                            <div class="flex-grow-1" style="min-height: 200px; max-height: 300px; overflow-y: auto;">
                                <div id="cartItems">
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-cart display-4"></i>
                                        <p class="mt-2">Cart is empty</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Summary -->
                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount:</span>
                                    <input type="number" class="form-control form-control-sm w-50" id="discount" value="0" min="0" step="0.01">
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (12%):</span>
                                    <span id="tax">₱0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong id="total">₱0.00</strong>
                                </div>
                                
                                <div class="mb-3">
                                    <select class="form-select" id="paymentMethod">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="gcash">GCash</option>
                                        <option value="paymaya">PayMaya</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" id="processPayment" disabled>
                                        <i class="bi bi-credit-card me-2"></i>Process Payment
                                    </button>
                                    <button class="btn btn-outline-secondary" id="clearCart">
                                        <i class="bi bi-trash me-2"></i>Clear Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales -->
                <div class="p-3 border-top">
                    <h5 class="mb-3">Recent Sales</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Cashier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_sales_result && $recent_sales_result->num_rows > 0): ?>
                                    <?php while ($sale = $recent_sales_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $sale['id']; ?></td>
                                            <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in'); ?></td>
                                            <td>₱<?php echo number_format($sale['final_amount'], 2); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($sale['payment_method']); ?></span></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No recent sales</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        const TAX_RATE = 0.12;

        // Category filtering
        document.querySelectorAll('.category-filter').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-filter').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                document.querySelectorAll('.product-item').forEach(item => {
                    if (category === 'all' || item.dataset.category === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Product search
        document.getElementById('productSearch').addEventListener('input', function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll('.product-item').forEach(item => {
                if (item.dataset.name.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Add to cart
        function addToCart(product) {
            const existingItem = cart.find(item => item.product_id === product.id);
            
            if (existingItem) {
                if (existingItem.quantity < product.stock_quantity) {
                    existingItem.quantity++;
                    existingItem.total = existingItem.quantity * existingItem.price;
                } else {
                    alert('Not enough stock available!');
                    return;
                }
            } else {
                cart.push({
                    product_id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    total: parseFloat(product.price),
                    max_stock: product.stock_quantity
                });
            }
            
            updateCartDisplay();
        }

        // Update cart display
        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-cart display-4"></i>
                        <p class="mt-2">Cart is empty</p>
                    </div>
                `;
                document.getElementById('processPayment').disabled = true;
            } else {
                let html = '';
                cart.forEach((item, index) => {
                    html += `
                        <div class="cart-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${item.name}</h6>
                                    <small class="text-muted">₱${item.price.toFixed(2)} each</small>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, -1)">-</button>
                                    <span class="btn btn-outline-secondary disabled">${item.quantity}</span>
                                    <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, 1)">+</button>
                                </div>
                                <strong>₱${item.total.toFixed(2)}</strong>
                            </div>
                        </div>
                    `;
                });
                cartItems.innerHTML = html;
                document.getElementById('processPayment').disabled = false;
            }
            
            updateTotals();
        }

        // Update quantity
        function updateQuantity(index, change) {
            const item = cart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeFromCart(index);
            } else if (newQuantity <= item.max_stock) {
                item.quantity = newQuantity;
                item.total = item.quantity * item.price;
                updateCartDisplay();
            } else {
                alert('Not enough stock available!');
            }
        }

        // Remove from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        // Update totals
        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const discountedAmount = subtotal - discount;
            const tax = discountedAmount * TAX_RATE;
            const total = discountedAmount + tax;
            
            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
            document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
        }

        // Clear cart
        document.getElementById('clearCart').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear the cart?')) {
                cart = [];
                updateCartDisplay();
            }
        });

        // Process payment
        document.getElementById('processPayment').addEventListener('click', function() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }
            
            const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const discountedAmount = subtotal - discount;
            const tax = discountedAmount * TAX_RATE;
            const total = discountedAmount + tax;
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="process_sale">
                <input type="hidden" name="customer_name" value="${document.getElementById('customerName').value}">
                <input type="hidden" name="customer_phone" value="${document.getElementById('customerPhone').value}">
                <input type="hidden" name="payment_method" value="${document.getElementById('paymentMethod').value}">
                <input type="hidden" name="items" value='${JSON.stringify(cart)}'>
                <input type="hidden" name="total_amount" value="${subtotal}">
                <input type="hidden" name="discount" value="${discount}">
                <input type="hidden" name="tax" value="${tax}">
                <input type="hidden" name="final_amount" value="${total}">
            `;
            
            document.body.appendChild(form);
            form.submit();
        });

        // Update totals when discount changes
        document.getElementById('discount').addEventListener('input', updateTotals);

        // Initialize
        updateCartDisplay();
    </script>
</body>
</html>