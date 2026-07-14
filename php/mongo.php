<?php
// php/mongo.php
require_once __DIR__ . '/bootstrap.php';

use MongoDB\Client;
use MongoDB\Database;

/**
 * Returns a MongoDB Database connection.
 */
function getMongoConnection(): Database {
    $uri = $_ENV['MONGO_URI'] ?? getenv('MONGO_URI') ?: 
           $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL') ?: 
           $_ENV['MONGO_URL'] ?? getenv('MONGO_URL') ?: 
           'mongodb://127.0.0.1:27017/auth_system';

    // Extract database name from URI safely using regex (supports mongodb+srv://)
    $dbName = 'auth_system';
    if (preg_match('/\/([a-zA-Z0-9_-]+)(?:\?|$)/', $uri, $matches)) {
        $dbName = $matches[1];
    }

    try {
        $client = new Client($uri);
        return $client->selectDatabase($dbName);
    } catch (\Exception $e) {
        // Throw so callers can decide whether MongoDB is required or optional
        throw new \RuntimeException('MongoDB connection failed: ' . $e->getMessage(), 0, $e);
    }
}
