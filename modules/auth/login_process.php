<?php
require_once __DIR__ . '/../../includes/core.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (!isset($_SESSION['user_id'])) {
    // Handle login
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect(url('modules/auth/login.php'));
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            setFlash('error', 'Please enter both email and password.');
            redirect(url('modules/auth/login.php'));
        }
        
        if (login($username, $password)) {
            redirect(url('modules/dashboard/index.php'));
        } else {
            setFlash('error', 'Invalid email or password.');
            redirect(url('modules/auth/login.php'));
        }
    }
}

// If logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    redirect(url('modules/dashboard/index.php'));
}