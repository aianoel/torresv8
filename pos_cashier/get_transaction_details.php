<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pos_cashier') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['id'] ?? 0;

if (!$transaction_id) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
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
    s.sale_date
    FROM sales s
    WHERE s.id = ? AND s.cashier_id = ?";

$stmt = $conn->prepare($transaction_query);
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$transaction_result = $stmt->get_result();

if ($transaction_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
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

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'transaction' => $transaction,
    'items' => $items
]);
?>