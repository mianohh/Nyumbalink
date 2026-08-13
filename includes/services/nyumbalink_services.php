<?php
/**
 * Security Service
 * Enhanced CSRF protection, rate limiting, and input sanitization
 */
class SecurityService {
    private $sessionKey;
    private $rateLimitStore;
    private $ip;
    
    public function __construct() {
        $this->sessionKey = 'csrf_token';
        $this->rateLimitStore = 'rate_limit_' . md5($this->getRemoteAddress());
        $this->ip = $this->getRemoteAddress();
    }
    
    private function getRemoteAddress(): string {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function getSessionKey(): string {
        return $this->sessionKey;
    }
    
    public function generateCSRFToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey] = $token;
        return $token;
    }
    
    public function validateCSRFToken(string $token): bool {
        return isset($_SESSION[$this->sessionKey]) && hash_equals($_SESSION[$this->sessionKey], $token);
    }
    
    public function checkRateLimit(string $key, int $maxAttempts = 5, int $window = 300): bool {
        $key = $this->rateLimitStore . '_' . $key;
        $attempts = $this->getSessionValue($key, 0);
        $now = time();
        
        $cleanedAttempts = [];
        foreach ($attempts as $timestamp => $count) {
            if ($now - $timestamp < $window) {
                $cleanedAttempts[$timestamp] = $count;
            }
        }
        
        $currentCount = isset($cleanedAttempts[$now]) ? $cleanedAttempts[$now] : 0;
        
        if ($currentCount >= $maxAttempts) {
            return false;
        }
        
        $cleanedAttempts[$now] = $currentCount + 1;
        $this->setSessionValue($key, $cleanedAttempts);
        return true;
    }
    
    public function sanitizeInput(array $data, array $rules = []): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                $value = $this->applyInputRules($value, $rules, $key);
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    private function applyInputRules(string $value, array $rules, string $field): string {
        foreach ($rules as $rule => $config) {
            switch ($rule) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new InvalidArgumentException("Invalid email format for {$field}");
                    }
                    break;
                case 'phone':
                    if (!preg_match('/^(\+254|0)[17]\d{8}$/', $value)) {
                        throw new InvalidArgumentException("Invalid phone format for {$field}");
                    }
                    break;
                case 'alphanumeric':
                    if (!preg_match('/^[A-Za-z0-9]+$/', $value)) {
                        throw new InvalidArgumentException("Invalid alphanumeric format for {$field}");
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        throw new InvalidArgumentException("Invalid numeric format for {$field}");
                    }
                    break;
            }
        }
        return $value;
    }
    
    public function escapeOutput(string $text, string $context = 'html'): string {
        switch ($context) {
            case 'json':
                return json_encode($text);
            case 'html':
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            case 'url':
                return urlencode($text);
            default:
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    
    public function sanitizeInputWithRules(string $input, array $rules = [], string $context = 'html'): string {
        $input = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        
        foreach ($rules as $rule => $config) {
            switch ($rule) {
                case 'email':
                    if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                        return 'Invalid email format';
                    }
                    break;
                case 'phone':
                    if (!preg_match('/^(\+254|0)[17]\d{8}$/', $input)) {
                        return 'Invalid phone format';
                    }
                    break;
                case 'alphanumeric':
                    if (!preg_match('/^[A-Za-z0-9]+$/', $input)) {
                        return 'Invalid alphanumeric format';
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($input)) {
                        return 'Invalid numeric format';
                    }
                    break;
            }
        }
        
        return $input;
    }
    
    public function getSessionValue(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function setSessionValue(string $key, $value): void {
        $_SESSION[$key] = $value;
    }
    
    public function clearSession(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}

class ErrorHandler {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/error.log';
        
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function handleException(Exception $e): void {
        $this->log($e->getMessage(), $e->getCode());
        
        if (APP_ENV === 'development') {
            echo "<div class='alert alert-danger'>";
            echo "<h4>Application Error</h4>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><small>File: " . $e->getFile() . " Line: " . $e->getLine() . "</small></p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>An error occurred. Please try again later.</div>";
        }
    }
    
    public function handleError(int $code, string $message, string $file = '', int $line = 0): void {
        $error = ["message" => $message, "file" => $file, "line" => $line];
        $this->log("Error {$code}: " . $message, $code, $error);
        
        if (APP_ENV === 'development') {
            echo "<div class='alert alert-danger'>";
            echo "<h4>Application Error</h4>";
            echo "<p>{$message} (File: {$file} Line: {$line})</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>An error occurred. Please try again later.</div>";
        }
        exit(1);
    }
    
    public function log(string $message, int $level = 0, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [Level: {$level}] {$message}";
        if (!empty($context)) {
            $logEntry .= " | Context: " . json_encode($context);
        }
        $logEntry .= "\n";
        
        error_log($logEntry, 3, $this->logFile);
    }
}

class ValidationService {
    private $errorMessages = [
        'required' => '{field} is required',
        'email' => 'Invalid email format for {field}',
        'phone' => 'Invalid phone format for {field}',
        'alphanumeric' => 'Invalid alphanumeric format for {field}',
        'numeric' => 'Invalid numeric format for {field}',
        'minlength' => '{field} must be at least {length} characters',
        'maxlength' => '{field} cannot exceed {length} characters',
        'unique' => '{field} is already taken',
    ];
    
    public function validate(array $data, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($errors, $field, $value, $fieldRules);
        }
        
        return $errors;
    }
    
    private function validateField(array &$errors, string $field, $value, array $fieldRules): void {
        foreach ($fieldRules as $rule => $param) {
            if (!$this->validateRule($value, $rule, $param, $field)) {
                $errors[$field] = $this->getErrorMessage($rule, $field, $param);
                return;
            }
        }
    }
    
    private function validateRule($value, string $rule, $param, string $field): bool {
        if ($rule === 'required') {
            return !empty($value);
        }
        
        if (empty($value) && $rule !== 'required') {
            return true;
        }
        
        switch ($rule) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'phone':
                return preg_match('/^(\+254|0)[17]\d{8}$/', $value) === 1;
            case 'alphanumeric':
                return preg_match('/^[A-Za-z0-9]+$/', $value) === 1;
            case 'numeric':
                return is_numeric($value);
            case 'minlength':
                return strlen($value) >= $param;
            case 'maxlength':
                return strlen($value) <= $param;
            case 'min':
                return $value >= $param;
            case 'max':
                return $value <= $param;
        }
        return true;
    }
    
    private function getErrorMessage(string $rule, string $field, $param = null): string {
        $message = $this->errorMessages[$rule] ?? $rule;
        $placeholders = ['{field}' => $field, '{length}' => $param];
        return str_replace(array_keys($placeholders), array_values($placeholders), $message);
    }
}