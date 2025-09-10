<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Get the last update timestamp from request
    $lastUpdate = $_GET['last_update'] ?? '1970-01-01 00:00:00';
    
    // Check for RFID cards updated after the last check
    $stmt = $pdo->prepare("
        SELECT id, card_number, card_name, balance, status, updated_at
        FROM rfid_cards 
        WHERE updated_at > ? 
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$lastUpdate]);
    $updatedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current server timestamp
    $currentTime = date('Y-m-d H:i:s');
    
    echo json_encode([
        'success' => true,
        'updated_cards' => $updatedCards,
        'current_time' => $currentTime,
        'has_updates' => count($updatedCards) > 0
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>