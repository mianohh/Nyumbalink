<?php
/**
 * Global Helper Functions
 */
if (defined('FUNCTIONS_LOADED')) return;
define('FUNCTIONS_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===========================================
// Application Services
// ===========================================
require_once __DIR__ . '/services/nyumbalink_services.php';

$securityService = new SecurityService();
$validationService = new ValidationService();
$errorHandler = new ErrorHandler();

// ===========================================
// Authentication Functions
// ===========================================

function login(string $username, string $password): bool {
    global $securityService;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $securityService->verifyPassword($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['email'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

function logout(): void {
    global $securityService;
    $securityService->clearSession();
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

function isAuthenticated(): bool {
    return isset($_SESSION['user_id']);
}

function requireAuth(): void {
    global $securityService;
    
    if (!isAuthenticated()) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $securityService->clearSession();
        header('Location: ' . BASE_URL . '/modules/auth/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireAdmin(): void {
    requireAuth();
    if ($_SESSION['role'] !== 'admin') {
        setFlash('error', 'Access denied. Admin privileges required.');
        header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ===========================================
// Flash Messages
// ===========================================

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ===========================================
// CSRF Protection (Standardized)
// ===========================================

function generateCSRFToken(): string {
    global $securityService;
    if (!isset($securityService) || !is_object($securityService)) {
        $securityService = new SecurityService();
    }
    return $securityService->generateCSRFToken();
}

function csrfField(): string {
    global $securityService;
    if (!isset($securityService) || !is_object($securityService)) {
        $securityService = new SecurityService();
    }
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function validateCSRFToken(string $token): bool {
    global $securityService;
    if (!isset($securityService) || !is_object($securityService)) {
        $securityService = new SecurityService();
    }
    $valid = $securityService->validateCSRFToken($token);
    if (!$valid) {
        error_log("CSRF FAIL: token='" . substr($token,0,8) . "...' session_key='" . $securityService->getSessionKey() . "' session_has_key=" . (isset($_SESSION[$securityService->getSessionKey()]) ? 'YES' : 'NO') . " session_id=" . session_id());
    }
    return $valid;
}

// ===========================================
// Input Handling
// ===========================================

function sanitize(string $input, array $rules = [], string $context = 'html'): string {
    global $securityService;
    if (!isset($securityService)) {
        $securityService = new SecurityService();
    }
    return $securityService->sanitizeInputWithRules($input, $rules, $context);
}

function getInput(string $key, string $default = ''): string {
    global $securityService;
    if (isset($_POST[$key])) {
        return sanitize($_POST[$key], [], 'html');
    }
    if (isset($_GET[$key])) {
        return sanitize($_GET[$key], [], 'html');
    }
    return $default;
}

function getPostData(): array {
    global $securityService;
    $data = [];
    foreach ($_POST as $key => $value) {
        if ($key !== 'csrf_token') {
            $data[$key] = sanitize($value, [], 'html');
        }
    }
    return $data;
}

function escapeOutput(string $text, string $context = 'html'): string {
    global $securityService;
    return $securityService->escapeOutput($text, $context);
}

// ===========================================
// Validation Functions
// ===========================================

function validate(array $data, array $rules): array {
    global $validationService;
    return $validationService->validate($data, $rules);
}

function validateRequired(array $fields, array $data): array {
    global $validationService;
    $rules = array_fill_keys(array_keys($fields), ['required' => true]);
    $errors = $validationService->validate($data, $rules);
    
    $requiredErrors = [];
    foreach ($errors as $field => $error) {
        if (str_contains($error, 'required')) {
            $requiredErrors[$field] = $error;
        }
    }
    
    return $requiredErrors;
}

// Legacy validation functions for backward compatibility
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone(string $phone): bool {
    return preg_match('/^(\+254|0)[17]\d{8}$/', $phone) === 1;
}

function validateIDNumber(string $id): bool {
    return preg_match('/^[A-Za-z0-9]{6,15}$/', $id) === 1;
}

function validateAmount($amount): bool {
    return is_numeric($amount) && $amount > 0;
}

function validateDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ===========================================
// Database Helpers
// ===========================================

function generateReceiptNumber(): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 3; $i++) {
        $code .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return 'RCP-' . date('Ymd') . '-' . strtoupper($code);
}

function paginate(string $table, int $page = 1, int $perPage = PER_PAGE): array {
    $db = getDB();
    $offset = ($page - 1) * $perPage;
    
    $total = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    $page = min(max(1, $page), $totalPages);
    
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage
    ];
}

// ===========================================
// Formatting
// ===========================================

function formatCurrency(float $amount): string {
    return 'KES ' . number_format($amount, 2);
}

function formatDate(string $date): string {
    return date('d M Y', strtotime($date));
}

function formatDateTime(string $datetime): string {
    return date('d M Y H:i', strtotime($datetime));
}

// ===========================================
// URL Helpers
// ===========================================

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function url(string $path): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

// ===========================================
// Response Helpers
// ===========================================

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ===========================================
// File Upload Helpers
// ===========================================

function uploadImage(array $file, string $subdirectory, array $options = []): ?string {
    $allowedTypes = $options['allowed_types'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }
    
    if ($file['size'] > $maxSize) {
        return null;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $subdirectory . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadPath = UPLOADS_DIR . '/' . $subdirectory . '/';
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
        return $filename;
    }
    
    return null;
}

function deleteImage(string $subdirectory, string $filename): bool {
    $filepath = UPLOADS_DIR . '/' . $subdirectory . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

function getImageUrl(string $subdirectory, ?string $filename): string {
    if ($filename) {
        return url('uploads/' . $subdirectory . '/' . $filename);
    }
    return '';
}

function logError(string $message, int $code = 0): void {
    $errorHandler->log($message, $code);
}

function handleException(Exception $e): void {
    $errorHandler->handleException($e);
}

function handleError(int $code, string $message, string $file = '', int $line = 0): void {
    $errorHandler->handleError($code, $message, $file, $line);
}