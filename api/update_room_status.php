<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'housekeeping'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['room_number']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$roomNumber = $input['room_number'];
$status = $input['status'];

// Validate status
$validStatuses = ['available', 'occupied', 'maintenance'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Update room status
    $updateQuery = $conn->prepare("UPDATE rooms SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE room_number = ?");
    $updateQuery->bind_param("ss", $status, $roomNumber);
    
    if ($updateQuery->execute()) {
        if ($updateQuery->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Room {$roomNumber} status updated to {$status}"
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Room not found']);
        }
    } else {
        throw new Exception('Database update failed');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>