<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'check_updates') {
        // Get the last update timestamp from request
        $lastUpdate = $_GET['last_update'] ?? '1970-01-01 00:00:00';
        
        // Check for RFID cards updated after the last check
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as update_count
            FROM rfid_cards 
            WHERE updated_at > ?
        ");
        $stmt->execute([$lastUpdate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'has_updates' => $result['update_count'] > 0,
            'current_time' => date('Y-m-d H:i:s')
        ]);
        
    } elseif ($action === 'get_card_info') {
        // Get specific card information (for refreshing displayed card info)
        $cardNumber = $_GET['card_number'] ?? '';
        
        if (empty($cardNumber)) {
            echo json_encode([
                'success' => false,
                'message' => 'Card number is required'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT card_number, card_name, balance, status 
            FROM rfid_cards 
            WHERE card_number = ? AND status = 'active'
        ");
        $stmt->execute([$cardNumber]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($card) {
            echo json_encode([
                'success' => true,
                'card' => $card
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Card not found or inactive'
            ]);
        }
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>