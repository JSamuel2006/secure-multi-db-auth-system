<?php
// php/logout.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/redis.php';

$token = $_COOKIE['session_token'] ?? '';

if (!empty($token)) {
    try {
        $redis = getRedisConnection();
        $sessionKey = "session:" . $token;
        
        // Delete key from Redis
        $redis->del($sessionKey);
    } catch (\Exception $e) {
        // Silently ignore or log connection failure so logout still invalidates client cookie
    }
}

// Delete cookie by setting expiration in the past
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
setcookie('session_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

sendJSONResponse('success', 'Logged out successfully.');
