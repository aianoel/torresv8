<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pos_cashier') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['id'] ?? 0;

if (!$transaction_id) {
    echo "Transaction ID required";
    exit();
}

// Get transaction details - ensure it belongs to the current cashier
$transaction_query = "SELECT 
    s.id,
    s.sale_number,
    s.subtotal,
    s.tax_amount,
    s.discount_amount,
    s.final_amount,
    s.payment_method,
    s.sale_date,
    u.username as cashier_name
    FROM sales s
    JOIN users u ON s.cashier_id = u.id
    WHERE s.id = ? AND s.cashier_id = ?";

$stmt = $conn->prepare($transaction_query);
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$transaction_result = $stmt->get_result();

if ($transaction_result->num_rows === 0) {
    echo "Transaction not found";
    exit();
}

$transaction = $transaction_result->fetch_assoc();

// Get transaction items
$items_query = "SELECT 
    si.quantity,
    si.unit_price,
    si.total_price,
    p.name as product_name
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
    ORDER BY p.name";

$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($transaction['sale_number']); ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }
        .receipt {
            border: 1px solid #000;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .address {
            font-size: 10px;
            margin-bottom: 5px;
        }
        .transaction-info {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .items {
            margin-bottom: 10px;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-left: 10px;
            margin-bottom: 3px;
        }
        .totals {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .final-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="print-button no-print">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <button class="btn" onclick="window.close()" style="background-color: #6c757d; margin-left: 10px;">Close</button>
    </div>

    <div class="receipt">
        <div class="header">
            <div class="company-name">TORRES FARM HOTEL</div>
            <div class="address">Point of Sale System</div>
            <div class="address">Thank you for your business!</div>
        </div>

        <div class="transaction-info">
            <div><strong>Receipt #:</strong> <?php echo htmlspecialchars($transaction['sale_number']); ?></div>
            <div><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($transaction['sale_date'])); ?></div>
            <div><strong>Cashier:</strong> <?php echo htmlspecialchars($transaction['cashier_name']); ?></div>
            <div><strong>Payment:</strong> <?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?></div>
        </div>

        <div class="items">
            <div style="font-weight: bold; margin-bottom: 5px;">ITEMS PURCHASED:</div>
            <?php while ($item = $items_result->fetch_assoc()): ?>
                <div class="item">
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span>₱<?php echo number_format($item['total_price'], 2); ?></span>
                </div>
                <div class="item-details">
                    <span><?php echo $item['quantity']; ?> x ₱<?php echo number_format($item['unit_price'], 2); ?></span>
                    <span></span>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="totals">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($transaction['subtotal'], 2); ?></span>
            </div>
            <?php if ($transaction['discount_amount'] > 0): ?>
                <div class="total-line">
                    <span>Discount:</span>
                    <span>-₱<?php echo number_format($transaction['discount_amount'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-line">
                <span>Tax (12%):</span>
                <span>₱<?php echo number_format($transaction['tax_amount'], 2); ?></span>
            </div>
            <div class="total-line final-total">
                <span>TOTAL:</span>
                <span>₱<?php echo number_format($transaction['final_amount'], 2); ?></span>
            </div>
        </div>

        <div class="footer">
            <div>Thank you for choosing Torres Farm Hotel!</div>
            <div>Please come again!</div>
            <div style="margin-top: 10px;">---</div>
            <div>This serves as your official receipt</div>
            <div>Printed on: <?php echo date('M j, Y g:i A'); ?></div>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>