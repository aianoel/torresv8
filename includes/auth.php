<?php
// Authentication functions

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect_to_dashboard() {
    if (!isset($_SESSION['role'])) {
        header('Location: ../login.php');
        exit;
    }

    $base_url = dirname($_SERVER['PHP_SELF']) === '/includes' ? '../' : '';
    
    switch($_SESSION['role']) {
        case 'admin':
            header('Location: ' . $base_url . 'admin/dashboard.php');
            break;
        case 'pos_admin':
            header('Location: ' . $base_url . 'pos_admin/dashboard.php');
            break;
        case 'pos_cashier':
            header('Location: ' . $base_url . 'pos_cashier/dashboard.php');
            break;
        case 'frontdesk':
            header('Location: ' . $base_url . 'frontdesk/dashboard.php');
            break;
        case 'housekeeping':
            header('Location: ' . $base_url . 'housekeeping/dashboard.php');
            break;
        case 'accounting':
            header('Location: ' . $base_url . 'accounting/dashboard.php');
            break;
        case 'hr':
            header('Location: ' . $base_url . 'hr/dashboard.php');
            break;
        default:
            header('Location: ' . $base_url . 'dashboard.php');
    }
    exit;
}

function login_user($user_id, $role) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
}

function logout_user() {
    session_unset();
    session_destroy();
}
?>