<?php
/**
 * Main Entry Point
 * Redirects to login or dashboard
 */

require_once __DIR__ . '/includes/core.php';

// Initialize session and set up error handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$db = getDB();

// Check if user is authenticated
if (isAuthenticated()) {
    redirect(url('modules/dashboard/index.php'));
} else {
    redirect(url('modules/auth/login.php'));
}