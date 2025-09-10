<?php
// Start session for potential future use
session_start();
require_once '../includes/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get and validate input data
    $checkin = $_POST['checkin'] ?? '';
    $checkout = $_POST['checkout'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $room_type = $_POST['room_type'] ?? '';
    
    // Validation
    $errors = [];
    
    // Validate dates
    if (empty($checkin)) {
        $errors[] = 'Check-in date is required';
    } elseif (strtotime($checkin) < strtotime('today')) {
        $errors[] = 'Check-in date cannot be in the past';
    }
    
    if (empty($checkout)) {
        $errors[] = 'Check-out date is required';
    } elseif (strtotime($checkout) <= strtotime($checkin)) {
        $errors[] = 'Check-out date must be after check-in date';
    }
    
    // Validate guests
    if ($guests < 1 || $guests > 10) {
        $errors[] = 'Number of guests must be between 1 and 10';
    }
    
    // Validate room type
    $valid_room_types = ['all', 'standard', 'deluxe', 'suite', 'family'];
    if (empty($room_type) || !in_array($room_type, $valid_room_types)) {
        $errors[] = 'Please select a valid room type';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
        exit;
    }
    
    // Calculate stay duration
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $nights = $checkin_date->diff($checkout_date)->days;
    
    // For now, simulate room availability check
    // In a real system, you would query the database for available rooms
    $available_rooms = [];
    
    // Sample room data (replace with actual database query)
    $room_types_data = [
        'standard' => [
            'name' => 'Standard Room',
            'price_per_night' => 2500,
            'max_guests' => 2,
            'available_count' => 5
        ],
        'deluxe' => [
            'name' => 'Deluxe Room',
            'price_per_night' => 3500,
            'max_guests' => 3,
            'available_count' => 3
        ],
        'suite' => [
            'name' => 'Suite',
            'price_per_night' => 5000,
            'max_guests' => 4,
            'available_count' => 2
        ],
        'family' => [
            'name' => 'Family Room',
            'price_per_night' => 4000,
            'max_guests' => 6,
            'available_count' => 2
        ]
    ];
    
    // Filter rooms based on guest count and room type preference
    foreach ($room_types_data as $type => $room_data) {
        if ($room_type === 'all' || $room_type === $type) {
            if ($room_data['max_guests'] >= $guests && $room_data['available_count'] > 0) {
                $total_price = $room_data['price_per_night'] * $nights;
                $available_rooms[] = [
                    'type' => $type,
                    'name' => $room_data['name'],
                    'price_per_night' => $room_data['price_per_night'],
                    'total_price' => $total_price,
                    'max_guests' => $room_data['max_guests'],
                    'available_count' => $room_data['available_count']
                ];
            }
        }
    }
    
    // Log the booking inquiry (optional)
    $log_data = [
        'checkin' => $checkin,
        'checkout' => $checkout,
        'guests' => $guests,
        'room_type' => $room_type,
        'nights' => $nights,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // You could save this to a booking_inquiries table
    // $stmt = $pdo->prepare("INSERT INTO booking_inquiries (checkin, checkout, guests, room_type, nights, created_at, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    // $stmt->execute([$checkin, $checkout, $guests, $room_type, $nights, $log_data['timestamp'], $log_data['ip_address']]);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => 'Availability checked successfully',
        'data' => [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'guests' => $guests,
            'nights' => $nights,
            'available_rooms' => $available_rooms,
            'total_available' => count($available_rooms)
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Booking API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Please try again later'
    ]);
}
?>