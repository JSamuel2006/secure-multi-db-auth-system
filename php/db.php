<?php
// php/db.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Returns a PDO connection to MySQL database.
 */
function getMySQLConnection(): PDO {
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

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // Log locally if debug is enabled, otherwise output generalized error
        $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        $errorMsg = ($appDebug === 'true' || $appDebug === '1') ? $e->getMessage() : 'Database connection error';
        sendJSONResponse('error', 'Database connection failed: ' . $errorMsg, [], 500);
    }
}
