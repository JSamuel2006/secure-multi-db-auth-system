<?php
// php/mongo.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Returns a MongoDB Database connection.
 */
function getMongoConnection(): \MongoDB\Database {
    $uri = $_ENV['MONGO_URI'] ?? getenv('MONGO_URI') ?: 'mongodb://127.0.0.1:27017/auth_system';

    // Parse database name from URI if it exists
    $dbName = 'auth_system';
    $parsed = parse_url($uri);
    if (isset($parsed['path'])) {
        $dbName = ltrim($parsed['path'], '/');
    }
    if (empty($dbName)) {
        $dbName = 'auth_system';
    }

    try {
        $client = new MongoDB\Client($uri);
        return $client->selectDatabase($dbName);
    } catch (\Exception $e) {
        $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        $errorMsg = ($appDebug === 'true' || $appDebug === '1') ? $e->getMessage() : 'MongoDB connection failed';
        sendJSONResponse('error', 'MongoDB connection failed: ' . $errorMsg, [], 500);
    }
}
