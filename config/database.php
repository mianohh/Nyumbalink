<?php
/**
 * Database Abstraction Layer
 * Enhanced with ORM-like interface and centralized database operations
 */
class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private array $cache = [];
    private const CACHE_TTL = 300; // 5 minutes
    
    private function __construct() {
        $this->pdo = self::getConnection();
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private static function getConnection(): PDO {
        // Get database configuration from environment variables with local defaults
        $dbConfig = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'name' => getenv('DB_NAME') ?: 'nyumbalink',
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASS') ?: '',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
            'port' => getenv('DB_PORT') ?: '3306',
        ];
        
        // Validate required database configuration
        if (empty($dbConfig['name']) || empty($dbConfig['user'])) {
            error_log("[Database Error] Database configuration missing: DB_NAME or DB_USER not set");
            die("Database configuration error. Please check your .env file.");
        }
        
        // Force TCP connection for localhost to avoid Unix socket issues
        $host = $dbConfig['host'];
        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }
        
        $dsn = "mysql:host=" . $host . ";port=" . $dbConfig['port'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Disable SSL for local development
        ];
        
        try {
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
            
            // Set connection charset
            $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES ' . $dbConfig['charset']);
            
            // Enable query logging for development environment only
            if (getenv('APP_ENV') === 'development') {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            }
            
            return $pdo;
        } catch (PDOException $e) {
            // If SSL error, try again without SSL options
            if (strpos($e->getMessage(), 'SSL') !== false) {
                try {
                    // Remove SSL-related options and retry
                    unset($options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
                    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
                    $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES ' . $dbConfig['charset']);
                    return $pdo;
                } catch (PDOException $e2) {
                    error_log("[Database Error] Database connection failed after SSL retry: " . $e2->getMessage());
                }
            }
            
            error_log("[Database Error] Database connection failed: " . $e->getMessage() . " Host: " . $dbConfig['host'] . " DB: " . $dbConfig['name']);
            die("Database connection failed. Please check your database credentials and network connectivity.");
        }
    }
    
    public function query(string $sql, array $params = [], bool $cache = false, string $cacheKey = ''): PDOStatement {
        $key = $cache ? ($cacheKey ?: md5($sql . json_encode($params))) : '';
        
        if ($cache && isset($this->cache[$key])) {
            $cached = $this->cache[$key];
            if (time() - $cached['time'] < self::CACHE_TTL) {
                return $cached['result'];
            } else {
                unset($this->cache[$key]);
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $errorInfo = $this->pdo->errorInfo();
            error_log("[Database Error] Failed to prepare query: " . $errorInfo[2] . " SQL: " . $sql);
            throw new PDOException("Failed to prepare statement: " . $errorInfo[2]);
        }
        
        $stmt->execute($params);
        
        if ($cache && $key) {
            $this->cache[$key] = [
                'result' => $stmt,
                'time' => time()
            ];
        }
        
        return $stmt;
    }
    
    // CRUD OPERATIONS
    
    public function getAll(string $table, array $where = [], array $params = [], bool $cache = false): array {
        return $this->find($table, $where, $params, $cache);
    }
    
    public function getOne(string $table, array $where = [], array $params = [], bool $cache = false): ?array {
        $results = $this->find($table, $where, $params, $cache, 1);
        return $results ? $results[0] : null;
    }
    
    public function find(string $table, array $where = [], array $params = [], bool $cache = false, int $limit = 0): array {
        $sql = "SELECT * FROM `{$table}`";
        if (!empty($where)) {
            $conditions = [];
            $placeholders = [];
            foreach ($where as $column => $value) {
                $conditions[] = "`$column` = ?";
                $placeholders[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
            $params = array_merge($placeholders, $params);
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->query($sql, $params, $cache);
        return $stmt->fetchAll();
    }
    
    public function create(string $table, array $data): int {
        return $this->insert($table, $data);
    }
    
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        $this->clearCache();
        return (int)$this->pdo->lastInsertId();
    }
    
    public function update(string $table, array $data, array $where = []): int {
        if (empty($where)) {
            throw new Exception("WHERE clause is required for update operations");
        }
        
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "`$column` = :{$column}";
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $set);
        
        $conditions = [];
        foreach ($where as $column => $value) {
            $conditions[] = "`$column` = :where_{$column}";
        }
        $sql .= " WHERE " . implode(' AND ', $conditions);
        
        $params = array_merge($data, array_map(function($k, $v) { return ["where_$k" => $v]; }, array_keys($where), array_values($where)));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $this->clearCache();
        return $stmt->rowCount();
    }
    
    public function delete(string $table, array $where = []): int {
        if (empty($where)) {
            throw new Exception("WHERE clause is required for delete operations");
        }
        
        $conditions = [];
        foreach ($where as $column => $value) {
            $conditions[] = "`$column` = :{$column}";
        }
        $sql = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $conditions);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($where);
        
        $this->clearCache();
        return $stmt->rowCount();
    }
    
    public function count(string $table, array $where = [], array $params = [], bool $cache = false): int {
        $sql = "SELECT COUNT(*) FROM `{$table}`";
        if (!empty($where)) {
            $conditions = [];
            foreach (array_keys($where) as $column) {
                $conditions[] = "`$column` = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
            $params = array_merge($params, array_values($where));
        }
        
        $stmt = $this->query($sql, $params, $cache);
        return (int)$stmt->fetchColumn();
    }
    
    public function exists(string $table, array $where = [], array $params = [], bool $cache = false): bool {
        return $this->count($table, $where, $params, $cache) > 0;
    }
    
    public function paginate(string $table, int $page = 1, int $perPage = PER_PAGE, array $where = [], array $params = [], bool $cache = false): array {
        $total = $this->count($table, $where, $params, $cache);
        $totalPages = max(1, ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM `{$table}`";
        if (!empty($where)) {
            $conditions = [];
            foreach (array_keys($where) as $column) {
                $conditions[] = "`$column` = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
            $params = array_merge($params, array_values($where));
        }
        
        $sql .= " ORDER BY id DESC LIMIT $offset, $perPage";
        $stmt = $this->query($sql, $params, $cache);
        
        return [
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    // TRANSACTION SUPPORT
    public function transaction(callable $callback): bool {
        $this->pdo->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->pdo->commit();
            $this->clearCache();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("Transaction failed: " . $e->getMessage());
            return false;
        }
    }
    
    // CACHE MANAGEMENT
    public function clearCache(): void {
        $this->cache = [];
    }
    
    public function getCacheStats(): array {
        return [
            'entries' => count($this->cache),
            'size' => memory_get_usage(true)
        ];
    }
    
    // UTILITY METHODS
    public function quote(string $string): string {
        return $this->pdo->quote($string);
    }
    
    public function getLastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
    
    public function prepare(string $sql): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $errorInfo = $this->pdo->errorInfo();
            error_log("[Database Error] Failed to prepare statement: " . $errorInfo[2] . " | SQL: " . $sql);
            throw new PDOException("Failed to prepare statement: " . $errorInfo[2]);
        }
        return $stmt;
    }
    
    public function errorInfo(): array {
        return $this->pdo->errorInfo();
    }
    
    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }
    
    // ERROR HANDLING
    private function logError(string $message): void {
        error_log("[Database Error] " . $message);
    }
    
    public function __destruct() {
        $this->pdo = null;
    }
}

// Global functions for backward compatibility
$GLOBALS['db'] = Database::getInstance();

function getDB(): Database {
    return Database::getInstance();
}

function db(): Database {
    return Database::getInstance();
}