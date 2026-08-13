<?php
/**
 * Application Configuration
 * Environment-aware configuration with secure defaults
 */

// Load environment configuration helper
$env = new class {
    public static function get($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            $value = $default;
        }
        return $value;
    }
    
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
};

// App Configuration
if (!defined('APP_NAME')) define('APP_NAME', $env->get('APP_NAME', "Nyumbalink"));
if (!defined('APP_VERSION')) define('APP_VERSION', $env->get('APP_VERSION', '1.0.0'));
if (!defined('BASE_URL')) define('BASE_URL', $env->get('BASE_URL', ''));
if (!defined('ASSETS_URL')) define('ASSETS_URL', $env->get('BASE_URL', '') . '/assets');
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', __DIR__ . '/../uploads');
if (!defined('REPORTS_DIR')) define('REPORTS_DIR', __DIR__ . '/../reports');

// Pagination with validation
if (!defined('PER_PAGE')) define('PER_PAGE', max(1, (int)($env->get('PER_PAGE', 20))));

// Session timeout with validation
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', max(300, (int)($env->get('SESSION_TIMEOUT', 1800))));

// Environment
if (!defined('APP_ENV')) define('APP_ENV', $env->get('APP_ENV', 'development'));

// Timezone with validation
date_default_timezone_set($env->get('timezone', 'Africa/Nairobi'));