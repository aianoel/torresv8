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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax_action'] === 'verify_rfid') {
        $card_number = trim($_POST['card_number']);
        
        $stmt = $conn->prepare("SELECT id, card_name, balance, status FROM rfid_cards WHERE card_number = ?");
        $stmt->bind_param("s", $card_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($card = $result->fetch_assoc()) {
            if ($card['status'] === 'active') {
                echo json_encode([
                    'success' => true,
                    'card' => $card
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Card is ' . $card['status']
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Card not found'
            ]);
        }
        exit();
    }
}

// Handle new sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sale') {
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $payment_method = $_POST['payment_method'];
    $subtotal = floatval($_POST['subtotal']);
    $discount = floatval($_POST['discount']);
    $tax = floatval($_POST['tax']);
    $final_amount = floatval($_POST['final_amount']);
    $cart_items = json_decode($_POST['cart_items'], true);
    $rfid_card_id = null;
    
    // Handle RFID payment
    if ($payment_method === 'rfid') {
        $rfid_card_number = trim($_POST['rfid_card_number']);
        
        // Verify RFID card and check balance
        $stmt = $conn->prepare("SELECT id, balance, status FROM rfid_cards WHERE card_number = ?");
        $stmt->bind_param("s", $rfid_card_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $rfid_card = $result->fetch_assoc();
        
        if (!$rfid_card) {
            $error_message = "RFID card not found.";
        } elseif ($rfid_card['status'] !== 'active') {
            $error_message = "RFID card is " . $rfid_card['status'] . ".";
        } elseif ($rfid_card['balance'] < $final_amount) {
            $error_message = "Insufficient balance. Available: ₱" . number_format($rfid_card['balance'], 2);
        } else {
            $rfid_card_id = $rfid_card['id'];
        }
    }
    
    if (!isset($error_message)) {
        // Generate sale number
        $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            $conn->begin_transaction();
            
            // Insert sale record
            $stmt = $conn->prepare("INSERT INTO sales (sale_number, subtotal, tax_amount, discount_amount, final_amount, payment_method, rfid_card_id, cashier_id, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sdddsiii", $sale_number, $subtotal, $tax, $discount, $final_amount, $payment_method, $rfid_card_id, $user_id);
            $stmt->execute();
            
            $sale_id = $conn->insert_id;
            
            // Process RFID payment
            if ($payment_method === 'rfid' && $rfid_card_id) {
                $old_balance = $rfid_card['balance'];
                $new_balance = $old_balance - $final_amount;
                
                // Update RFID card balance
                $stmt = $conn->prepare("UPDATE rfid_cards SET balance = ? WHERE id = ?");
                $stmt->bind_param("di", $new_balance, $rfid_card_id);
                $stmt->execute();
                
                // Log RFID transaction
                $stmt = $conn->prepare("INSERT INTO rfid_transactions (rfid_card_id, transaction_type, amount, balance_before, balance_after, sale_id, processed_by, notes) VALUES (?, 'payment', ?, ?, ?, ?, ?, ?)");
                $notes = "Payment for sale " . $sale_number;
                $stmt->bind_param("idddiis", $rfid_card_id, $final_amount, $old_balance, $new_balance, $sale_id, $user_id, $notes);
                $stmt->execute();
            }
            
            // Insert sale items and update stock
            foreach ($cart_items as $item) {
                // Insert sale item
                $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidd", $sale_id, $item['id'], $item['quantity'], $item['price'], $item['total']);
                $stmt->execute();
                
                // Update product stock
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['id']);
                $stmt->execute();
            }
            
            $conn->commit();
            $success_message = "Sale completed successfully! Sale Number: " . $sale_number;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error processing sale: " . $e->getMessage();
        }
    }
}

// Get all products for POS
$products_query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity > 0 ORDER BY p.name";
$products_result = $conn->query($products_query);

// Get categories for filtering
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Torres Farm Hotel</title>
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
        .product-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .product-card:hover {
            transform: translateY(-2px);
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .cart-section {
            background: #f8f9fa;
            border-radius: 15px;
            min-height: 600px;
        }
        .cart-item {
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .logo {
            max-height: 40px;
        }
        .product-grid {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar collapse">
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
                            <a class="nav-link active" href="sales.php">
                                <i class="fas fa-cash-register me-2"></i>
                                Point of Sale
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="transactions.php">
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
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Point of Sale</h1>
                    <div class="text-muted">
                        Cashier: <?php echo htmlspecialchars($user_name); ?> | <?php echo date('F j, Y g:i A'); ?>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Products Section -->
                    <div class="col-md-8">
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <select class="form-select" id="categoryFilter">
                                        <option value="">All Categories</option>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                                </div>
                            </div>
                        </div>

                        <!-- Products Grid -->
                        <div class="product-grid">
                            <div class="row" id="productsContainer">
                                <?php while ($product = $products_result->fetch_assoc()): ?>
                                    <div class="col-md-4 col-lg-3 mb-3 product-item" 
                                         data-category="<?php echo $product['category_id']; ?>"
                                         data-name="<?php echo strtolower($product['name']); ?>">
                                        <div class="card product-card h-100" 
                                             onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock_quantity']; ?>)">
                                            <div class="card-body text-center">
                                                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></p>
                                                <h5 class="text-primary">₱<?php echo number_format($product['price'], 2); ?></h5>
                                                <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Section -->
                    <div class="col-md-4">
                        <div class="cart-section p-3">
                            <h5 class="mb-3">Shopping Cart</h5>
                            
                            <div id="cartItems" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                                <p class="text-muted text-center">Cart is empty</p>
                            </div>

                            <!-- Customer Info -->
                            <div class="mb-3">
                                <input type="text" class="form-control mb-2" id="customerName" placeholder="Customer Name (Optional)">
                                <input type="text" class="form-control" id="customerPhone" placeholder="Customer Phone (Optional)">
                            </div>

                            <!-- Totals -->
                            <div class="total-section p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount:</span>
                                    <input type="number" class="form-control form-control-sm" id="discount" value="0" min="0" step="0.01" style="width: 80px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (12%):</span>
                                    <span id="tax">₱0.00</span>
                                </div>
                                <hr style="border-color: rgba(255,255,255,0.3);">
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong id="total">₱0.00</strong>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" id="paymentMethod" required onchange="toggleRfidSection()">
                                    <option value="cash">Cash</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="rfid">RFID Card</option>
                                </select>
                            </div>

                            <!-- RFID Card Section -->
                            <div class="mb-3" id="rfidSection" style="display: none;">
                                <label class="form-label">RFID Card Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="rfidCardNumber" placeholder="Scan or enter card number">
                                    <button class="btn btn-outline-secondary" type="button" onclick="verifyRfidCard()">
                                        <i class="bi bi-search"></i> Verify
                                    </button>
                                </div>
                                <div id="rfidCardInfo" class="mt-2" style="display: none;">
                                    <div class="alert alert-info">
                                        <strong id="rfidCardName"></strong><br>
                                        <small>Balance: ₱<span id="rfidCardBalance">0.00</span></small>
                                    </div>
                                </div>
                                <div id="rfidError" class="mt-2" style="display: none;">
                                    <div class="alert alert-danger">
                                        <span id="rfidErrorMessage"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button class="btn btn-success btn-lg" onclick="processSale()" id="processSaleBtn" disabled>
                                    <i class="fas fa-credit-card me-2"></i>
                                    Process Sale
                                </button>
                                <button class="btn btn-outline-secondary" onclick="clearCart()">
                                    <i class="fas fa-trash me-2"></i>
                                    Clear Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Sale Form (Hidden) -->
    <form id="saleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="create_sale">
        <input type="hidden" name="customer_name" id="formCustomerName">
        <input type="hidden" name="customer_phone" id="formCustomerPhone">
        <input type="hidden" name="payment_method" id="formPaymentMethod">
        <input type="hidden" name="subtotal" id="formSubtotal">
        <input type="hidden" name="discount" id="formDiscount">
        <input type="hidden" name="tax" id="formTax">
        <input type="hidden" name="final_amount" id="formFinalAmount">
        <input type="hidden" name="cart_items" id="formCartItems">
        <input type="hidden" name="rfid_card_number" id="formRfidCardNumber">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];

        function addToCart(id, name, price, stock) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                if (existingItem.quantity < stock) {
                    existingItem.quantity++;
                    existingItem.total = existingItem.quantity * existingItem.price;
                } else {
                    alert('Not enough stock available!');
                    return;
                }
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1,
                    total: price,
                    stock: stock
                });
            }
            
            updateCartDisplay();
            updateTotals();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCartDisplay();
            updateTotals();
        }

        function updateQuantity(id, quantity) {
            const item = cart.find(item => item.id === id);
            if (item) {
                if (quantity > 0 && quantity <= item.stock) {
                    item.quantity = quantity;
                    item.total = item.quantity * item.price;
                } else if (quantity > item.stock) {
                    alert('Not enough stock available!');
                    return;
                } else {
                    removeFromCart(id);
                    return;
                }
            }
            updateCartDisplay();
            updateTotals();
        }

        function updateCartDisplay() {
            const cartContainer = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartContainer.innerHTML = '<p class="text-muted text-center">Cart is empty</p>';
                document.getElementById('processSaleBtn').disabled = true;
                return;
            }
            
            document.getElementById('processSaleBtn').disabled = false;
            
            let html = '';
            cart.forEach(item => {
                html += `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">${item.name}</h6>
                                <small class="text-muted">₱${item.price.toFixed(2)} each</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="input-group" style="width: 120px;">
                                <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control form-control-sm text-center" value="${item.quantity}" 
                                       onchange="updateQuantity(${item.id}, parseInt(this.value))" min="1" max="${item.stock}">
                                <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <strong>₱${item.total.toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            });
            
            cartContainer.innerHTML = html;
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const discountedAmount = subtotal - discount;
            const tax = discountedAmount * 0.12;
            const total = discountedAmount + tax;
            
            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
            document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
            updateTotals();
        }

        function processSale() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }
            
            const paymentMethod = document.getElementById('paymentMethod').value;
            
            // Validate RFID payment
            if (paymentMethod === 'rfid') {
                const rfidCardNumber = document.getElementById('rfidCardNumber').value.trim();
                if (!rfidCardNumber) {
                    alert('Please enter RFID card number!');
                    return;
                }
                
                const rfidCardInfo = document.getElementById('rfidCardInfo');
                if (rfidCardInfo.style.display === 'none') {
                    alert('Please verify RFID card first!');
                    return;
                }
                
                const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
                const discount = parseFloat(document.getElementById('discount').value) || 0;
                const discountedAmount = subtotal - discount;
                const tax = discountedAmount * 0.12;
                const total = discountedAmount + tax;
                
                const cardBalance = parseFloat(document.getElementById('rfidCardBalance').textContent);
                if (cardBalance < total) {
                    alert(`Insufficient balance! Available: ₱${cardBalance.toFixed(2)}, Required: ₱${total.toFixed(2)}`);
                    return;
                }
            }
            
            const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const discountedAmount = subtotal - discount;
            const tax = discountedAmount * 0.12;
            const total = discountedAmount + tax;
            
            // Fill form data
            document.getElementById('formCustomerName').value = document.getElementById('customerName').value;
            document.getElementById('formCustomerPhone').value = document.getElementById('customerPhone').value;
            document.getElementById('formPaymentMethod').value = paymentMethod;
            document.getElementById('formSubtotal').value = subtotal;
            document.getElementById('formDiscount').value = discount;
            document.getElementById('formTax').value = tax;
            document.getElementById('formFinalAmount').value = total;
            document.getElementById('formCartItems').value = JSON.stringify(cart);
            
            if (paymentMethod === 'rfid') {
                document.getElementById('formRfidCardNumber').value = document.getElementById('rfidCardNumber').value;
            }
            
            // Submit form
            document.getElementById('saleForm').submit();
        }
        
        function toggleRfidSection() {
            const paymentMethod = document.getElementById('paymentMethod').value;
            const rfidSection = document.getElementById('rfidSection');
            
            if (paymentMethod === 'rfid') {
                rfidSection.style.display = 'block';
            } else {
                rfidSection.style.display = 'none';
                // Reset RFID fields
                document.getElementById('rfidCardNumber').value = '';
                document.getElementById('rfidCardInfo').style.display = 'none';
                document.getElementById('rfidError').style.display = 'none';
            }
        }
        
        function verifyRfidCard() {
            const cardNumber = document.getElementById('rfidCardNumber').value.trim();
            
            if (!cardNumber) {
                alert('Please enter card number!');
                return;
            }
            
            // Show loading state
            const verifyBtn = event.target;
            const originalText = verifyBtn.innerHTML;
            verifyBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Verifying...';
            verifyBtn.disabled = true;
            
            // Hide previous results
            document.getElementById('rfidCardInfo').style.display = 'none';
            document.getElementById('rfidError').style.display = 'none';
            
            // Make AJAX request
            const formData = new FormData();
            formData.append('ajax_action', 'verify_rfid');
            formData.append('card_number', cardNumber);
            
            fetch('sales.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('rfidCardName').textContent = data.card.card_name;
                    document.getElementById('rfidCardBalance').textContent = parseFloat(data.card.balance).toFixed(2);
                    document.getElementById('rfidCardInfo').style.display = 'block';
                } else {
                    document.getElementById('rfidErrorMessage').textContent = data.message;
                    document.getElementById('rfidError').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('rfidErrorMessage').textContent = 'Error verifying card. Please try again.';
                document.getElementById('rfidError').style.display = 'block';
            })
            .finally(() => {
                // Restore button state
                verifyBtn.innerHTML = originalText;
                verifyBtn.disabled = false;
            });
        }
        
        // Allow Enter key to verify RFID card
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('rfidCardNumber').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    verifyRfidCard();
                }
            });
            
            // Start real-time synchronization
            startRfidSync();
        });
        
        // Real-time synchronization variables
        let lastUpdateTime = '1970-01-01 00:00:00';
        let syncInterval;
        let currentRfidCard = null;
        
        function startRfidSync() {
            // Check for updates every 5 seconds
            syncInterval = setInterval(checkForRfidUpdates, 5000);
        }
        
        function stopRfidSync() {
            if (syncInterval) {
                clearInterval(syncInterval);
            }
        }
        
        function checkForRfidUpdates() {
            // Only check if we have a currently displayed RFID card
            const rfidCardInfo = document.getElementById('rfidCardInfo');
            if (rfidCardInfo.style.display === 'none') {
                return;
            }
            
            const cardNumber = document.getElementById('rfidCardNumber').value.trim();
            if (!cardNumber) {
                return;
            }
            
            // Check if there are any updates
            fetch(`ajax_rfid_sync.php?action=check_updates&last_update=${encodeURIComponent(lastUpdateTime)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.has_updates) {
                        // Refresh the current card information
                        refreshCurrentRfidCard(cardNumber);
                        lastUpdateTime = data.current_time;
                    }
                })
                .catch(error => {
                    console.error('Sync error:', error);
                });
        }
        
        function refreshCurrentRfidCard(cardNumber) {
            fetch(`ajax_rfid_sync.php?action=get_card_info&card_number=${encodeURIComponent(cardNumber)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the displayed card information
                        document.getElementById('rfidCardName').textContent = data.card.card_name;
                        document.getElementById('rfidCardBalance').textContent = parseFloat(data.card.balance).toFixed(2);
                        
                        // Show a subtle notification that the card was updated
                        showUpdateNotification('Card information updated');
                    } else {
                        // Card might have been deactivated or deleted
                        document.getElementById('rfidCardInfo').style.display = 'none';
                        document.getElementById('rfidErrorMessage').textContent = 'Card is no longer available or has been deactivated';
                        document.getElementById('rfidError').style.display = 'block';
                        showUpdateNotification('Card status changed', 'warning');
                    }
                })
                .catch(error => {
                    console.error('Refresh error:', error);
                });
        }
        
        function showUpdateNotification(message, type = 'info') {
            // Create a temporary notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'warning' ? 'warning' : 'info'} alert-dismissible fade show`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                <i class="bi bi-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }

        // Search and filter functionality
        document.getElementById('productSearch').addEventListener('input', filterProducts);
        document.getElementById('categoryFilter').addEventListener('change', filterProducts);
        document.getElementById('discount').addEventListener('input', updateTotals);

        function filterProducts() {
            const searchTerm = document.getElementById('productSearch').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const products = document.querySelectorAll('.product-item');
            
            products.forEach(product => {
                const name = product.dataset.name;
                const category = product.dataset.category;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesCategory = !categoryFilter || category === categoryFilter;
                
                if (matchesSearch && matchesCategory) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>