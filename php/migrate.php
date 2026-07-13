<?php
// php/migrate.php
require_once __DIR__ . '/bootstrap.php';

echo "=== Starting Database Migrations ===\n";

$host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: '3306';
$db   = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: 'auth_system';
$user = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'root';
$pass = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: '';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$maxRetries = 15;
$retryDelay = 3; // seconds
$pdo = null;

// Loop to wait for MySQL service to become ready
for ($i = 0; $i < $maxRetries; $i++) {
    try {
        echo "Attempting to connect to database (Attempt " . ($i + 1) . "/" . $maxRetries . ")...\n";
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "Connected successfully to MySQL.\n";
        break;
    } catch (\PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        if ($i < $maxRetries - 1) {
            echo "Retrying in {$retryDelay} seconds...\n";
            sleep($retryDelay);
        }
    }
}

if (!$pdo) {
    echo "CRITICAL ERROR: Failed to connect to MySQL database after {$maxRetries} attempts.\n";
    exit(1);
}

try {
    $sqlPath = __DIR__ . '/../sql/init.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("Migration file sql/init.sql not found.");
    }
    
    $sql = file_get_contents($sqlPath);
    if ($sql === false) {
        throw new Exception("Unable to read sql/init.sql.");
    }
    
    echo "Executing migration schema...\n";
    $pdo->exec($sql);
    echo "=== Migrations Completed Successfully ===\n";
    exit(0);
} catch (Exception $e) {
    echo "CRITICAL ERROR: Migration execution failed: " . $e->getMessage() . "\n";
    exit(1);
}
