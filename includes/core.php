<?php
/**
 * Fixed Application Core - version 2.0
 * Correct file paths and proper initialization
 */

if (defined('APP_CORE_LOADED')) {
    return;
}
define('APP_CORE_LOADED', true);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Core autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Kevo\\';
    $baseDir = __DIR__ . '/';
    
    $classParts = explode('\\', $class);
    if ($classParts[0] !== 'Kevo') {
        return;
    }
    
    $classParts = array_slice($classParts, 1);
    $path = $baseDir . implode('/', $classParts) . '.php';
    
    if (file_exists($path)) {
        require_once $path;
    }
});

// Load environment variables and configuration
require_once __DIR__ . '/services/env_loader.php';

// Load configuration
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Start session early before anything else
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load system components
require_once __DIR__ . '/functions.php';

// Services directory - correct path
$servicesDir = __DIR__ . '/services';
if (is_dir($servicesDir)) {
    $services = scandir($servicesDir);
    foreach ($services as $service) {
        if (pathinfo($service, PATHINFO_EXTENSION) === 'php' && $service !== 'index.php' && $service !== 'env_loader.php') {
            require_once $servicesDir . '/' . $service;
        }
    }
}
