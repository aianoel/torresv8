<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

// Destroy all session data
logout_user();

// Redirect to login page
header('Location: login.php');
exit;
?>