<?php
// Utility functions for the hotel management system

if (!defined('CONFIG_LOADED')) {
    die('Direct access not allowed');
}

/**
 * Format currency amount
 */
function format_currency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Format date for display
 */
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function format_datetime($datetime, $format = 'M d, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Check if user has permission
 */
function has_permission($required_role, $user_role = null) {
    if ($user_role === null) {
        $user_role = $_SESSION['role'] ?? '';
    }
    
    $role_hierarchy = [
        'admin' => 10,
        'pos_admin' => 8,
        'accounting' => 7,
        'hr' => 6,
        'frontdesk' => 5,
        'pos_cashier' => 4,
        'housekeeping' => 3
    ];
    
    $user_level = $role_hierarchy[$user_role] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

/**
 * Get user's full name
 */
function get_user_full_name($user_id = null) {
    if ($user_id === null) {
        return ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['first_name'] . ' ' . $user['last_name'];
    }
    
    return 'Unknown User';
}

/**
 * Log activity
 */
function log_activity($action, $description = '', $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    // For now, just return true. Can be extended to log to database
    return true;
}

/**
 * Get system setting
 */
function get_setting($key, $default = '') {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * Set system setting
 */
function set_setting($key, $value, $description = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, description = ?");
    $stmt->bind_param("sssss", $key, $value, $description, $value, $description);
    
    return $stmt->execute();
}
?>