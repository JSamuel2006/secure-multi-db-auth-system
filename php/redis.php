<?php
// php/redis.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Returns a Predis Client instance.
 */
function getRedisConnection(): \Predis\Client {
    $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: $_ENV['REDISHOST'] ?? getenv('REDISHOST') ?: '127.0.0.1';
    $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: $_ENV['REDISPORT'] ?? getenv('REDISPORT') ?: '6379';
    $password = $_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD') ?: $_ENV['REDISPASSWORD'] ?? getenv('REDISPASSWORD') ?: null;

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
        // Throw so callers can decide whether Redis is required or optional
        throw new \RuntimeException('Redis connection failed: ' . $e->getMessage(), 0, $e);
    }
}
