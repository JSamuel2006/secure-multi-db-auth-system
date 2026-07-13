<?php
// php/redis.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Returns a Predis Client instance.
 */
function getRedisConnection(): \Predis\Client {
    $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '127.0.0.1';
    $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: '6379';
    $password = $_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD') ?: null;

    $parameters = [
        'scheme' => 'tcp',
        'host'   => $host,
        'port'   => (int)$port,
    ];

    if ($password !== null && $password !== '') {
        $parameters['password'] = $password;
    }

    try {
        $client = new Predis\Client($parameters);
        // Connect to verify it works
        $client->connect();
        return $client;
    } catch (\Exception $e) {
        $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        $errorMsg = ($appDebug === 'true' || $appDebug === '1') ? $e->getMessage() : 'Redis connection failed';
        sendJSONResponse('error', 'Redis connection failed: ' . $errorMsg, [], 500);
    }
}
