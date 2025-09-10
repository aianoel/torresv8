<?php
// Initialize session and database connection
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/content_helper.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect to appropriate dashboard if already logged in
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Include landing page
include 'public/landing.php';
?>