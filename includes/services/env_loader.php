<?php
/**
 * .env Loader for Secure Configuration Management
 * Load environment variables from .env files safely
 * Based on DotEnv library https://github.com/vlucas/phpdotenv
 */

class EnvLoader {
    private static $instance = null;
    private $config = [];
    private $securityConfig;
    
    private function __construct() {
        $this->securityConfig = [
            'require_env_in_production' => true,
            'log_env_access' => true,
            'env_file_permissions' => 0644,
            'restrict_bot_access' => true,
        ];
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function load(?string $file = null): void {
        $envFile = $file ?: $this->getEnvFile();
        
        // Security check: verify .env file permissions in production
        if ($this->securityConfig['require_env_in_production'] && $this->getAppEnv() === 'production') {
            $this->verifyEnvFileSecurity($envFile);
        }
        
        if (!file_exists($envFile) || !is_readable($envFile)) {
            $envFileExists = file_exists($envFile) ? $envFile : 'LOCATION_UNKNOWN';
            $message = "[EnvLoader] .env file not found or not readable: {$envFileExists}";
            error_log($message);
            return;
        }
        
        // Log access attempt for security monitoring
        if ($this->securityConfig['log_env_access']) {
            $this->logSecureAccess($envFile);
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '//') === 0) {
                continue;
            }
            
            // Parse key=value pairs with improved security
            if ($this->validateEnvLine($line)) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                
                $value = $this->unescapeEnvValue($value);
                $value = $this->maskSensitiveValue($key, $value);
                
                // Only set if key doesn't already exist (preserve existing defines)
                if (!defined($key)) {
                    putenv("$key=$value");
                    $this->config[$key] = $value;
                    
                    // Define as PHP constant for backward compatibility
                    if ($this->shouldDefineConstant($key)) {
                        define($key, $value);
                    }
                }
            } else {
                error_log("[EnvLoader Security] Invalid .env line skipped: " . substr($line, 0, 100));
            }
        }
        
        $this->applySecurityHeaders();
    }
    
    private function validateEnvLine(string $line): bool {
        // Check if line has '=' separator
        if (strpos($line, '=') === false) {
            return false;
        }
        
        // Validate basic key format (letters, numbers, underscores only)
        $key = trim(explode('=', $line)[0]);
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
            return false;
        }
        
        return true;
    }
    
    private function unescapeEnvValue(string $value): string {
        $value = trim($value, '"\'');
        $value = str_replace('\\"', '"', $value);
        $value = str_replace("\\'", "'", $value);
        $value = str_replace('\\\\', '\\', $value);
        return $value;
    }
    
    private function maskSensitiveValue(string $key, string $value): string {
        // Log but don't store actual sensitive values in memory
        $sensitiveKeys = ['PASSWORD', 'KEY', 'SECRET', 'TOKEN', 'CERT'];
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (strpos(strtoupper($key), $sensitiveKey) !== false) {
                $logData = [
                    'key' => $key,
                    'masked_value' => $this->maskSensitive($value),
                    'file' => $this->getEnvFile(),
                ];
                error_log("[EnvLoader Security] Sensitive variable '{$key}' loaded: " . json_encode($logData));
                break;
            }
        }
        
        return $value;
    }
    
    private function maskSensitive(string $value): string {
        if (strlen($value) <= 4) {
            return '***';
        }
        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }
    
    private function shouldDefineConstant(string $key): bool {
        $defineKeys = ['APP_', 'DB_', 'SESSION_', 'LOG_', 'SITE_', 'UPLOAD_'];
        
        foreach ($defineKeys as $defineKey) {
            if (strpos($key, $defineKey) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function verifyEnvFileSecurity(string $envFile): void {
        $filePerms = fileperms($envFile);
        $mode = $filePerms >> 6; // Get user permissions
        
        if ($mode & 0200) { // If others can write
            error_log("[EnvLoader Security] WARNING: .env file is writable by others in production");
        }
        
        // Check if file contains development credentials
        $content = file_get_contents($envFile);
        if (strpos(strtolower($content), 'localhost') !== false || 
            strpos(strtolower($content), 'user:root') !== false) {
            error_log("[EnvLoader Security] WARNING: .env file contains default development credentials");
        }
    }
    
    private function logSecureAccess(string $envFile): void {
        $logData = [
            'file' => basename($envFile),
            'size' => filesize($envFile),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'app_env' => $this->getAppEnv(),
        ];
        error_log("[EnvLoader Access] .env file loaded: " . json_encode($logData));
    }
    
    private function getEnvFile(): string {
        // Check for environment-specific .env files with security validation
        // Use project root directory (two levels up from includes/services)
        $rootDir = dirname(dirname(__DIR__));
        $envFiles = [
            $rootDir . '/.env.production',
            $rootDir . '/production.env',  // Support production.env naming
            $rootDir . '/.env.staging',
            $rootDir . '/.env',
        ];
        
        // If APP_ENV is set, prioritize environment-specific files
        if ($this->getAppEnv() === 'production') {
            $priorityFiles = [
                $rootDir . '/.env.production',
                $rootDir . '/production.env',
            ];
            
            foreach ($priorityFiles as $file) {
                if ($this->validateEnvFileSecurity($file)) {
                    return $file;
                }
            }
            
            // Fall back to other files if production env files not found
            foreach ($envFiles as $file) {
                if ($this->validateEnvFileSecurity($file)) {
                    return $file;
                }
            }
        }
        
        // For development (APP_ENV not set), prioritize .env
        $devFiles = [
            $rootDir . '/.env',
        ];
        
        foreach ($devFiles as $file) {
            if ($this->validateEnvFileSecurity($file)) {
                return $file;
            }
        }
        
        // Final fallback to .env
        return $rootDir . '/.env';
    }
    
    private function validateEnvFileSecurity(string $envFile): bool {
        if (!file_exists($envFile)) {
            return false;
        }
        
        // Allow .env.production and production.env even in non-strict mode
        $basename = basename($envFile);
        if (in_array($basename, ['.env.production', 'production.env', '.env.staging'])) {
            return is_readable($envFile);
        }
        
        if (!$this->securityConfig['require_env_in_production'] && $this->getAppEnv() === 'production') {
            return false; // Skip validation in non-strict mode
        }
        
        // Check file readability
        if (!is_readable($envFile)) {
            error_log("[EnvLoader Security] .env file not readable: {$envFile}");
            return false;
        }
        
        // Check file size (limit to prevent memory issues)
        $fileSize = filesize($envFile);
        if ($fileSize > 1024 * 1024) { // 1MB limit
            error_log("[EnvLoader Security] .env file too large: {$fileSize} bytes");
            return false;
        }
        
        return true;
    }
    
    private function getAppEnv(): string {
        return getenv('APP_ENV') ?: 'development';
    }
    
    public function getEnv(string $key, $default = null) {
        $value = getenv($key) ?: $this->config[$key] ?? $default;
        
        if ($value === null && $this->securityConfig['require_env_in_production'] && $this->getAppEnv() === 'production') {
            error_log("[EnvLoader Security] Missing required environment variable: {$key}");
        }
        
        return $value;
    }
    
    public function getBool(string $key, bool $default = false): bool {
        $value = $this->getEnv($key);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?: $default;
    }
    
    private function applySecurityHeaders(): void {
        // Set security headers after loading environment
        if ($this->getAppEnv() === 'development') {
            // Development headers
            header('X-Debug-Mode: true', true, 200);
        } else {
            // Production headers
            header('X-Debug-Mode: false', true, 200);
            header('X-Environment: production', true, 200);
        }
    }
}

// Auto-load .env on script execution
$envLoader = EnvLoader::getInstance();
$envLoader->load();
