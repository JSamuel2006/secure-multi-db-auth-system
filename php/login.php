<?php
// php/login.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/redis.php';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse('error', 'Method not allowed', [], 405);
}

// Retrieve post parameters
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
} else {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$rememberMe = isset($input['remember_me']) && ($input['remember_me'] === true || $input['remember_me'] === 'true' || $input['remember_me'] === '1' || $input['remember_me'] === 'on');

if (empty($email) || empty($password)) {
    sendJSONResponse('error', 'Email and password are required.');
}

$mysql = getMySQLConnection();

try {
    // Prepared statement to retrieve user
    $stmt = $mysql->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        sendJSONResponse('error', 'Invalid credentials.');
    }

    // Generate secure session token (64 hex characters)
    $token = bin2hex(random_bytes(32));

    // Connect to Redis
    $redis = getRedisConnection();
    
    $userId = (int)$user['id'];
    $loginTime = time();
    $sessionKey = "session:" . $token;

    // Store in Redis
    $redis->hmset($sessionKey, [
        'user_id' => $userId,
        'token' => $token,
        'login_time' => $loginTime
    ]);

    // TTL: 30 days if remember me, 2 hours if not
    $ttl = $rememberMe ? 2592000 : 7200;
    $redis->expire($sessionKey, $ttl);

    // Set HTTPOnly Cookie
    $cookieExpire = $rememberMe ? (time() + $ttl) : 0;
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    setcookie('session_token', $token, [
        'expires' => $cookieExpire,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    sendJSONResponse('success', 'Login Successful', [
        'user' => [
            'id' => $userId,
            'username' => $user['username']
        ]
    ]);
} catch (\Exception $e) {
    $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
    $errorDetails = ($appDebug === 'true' || $appDebug === '1') ? $e->getMessage() : 'An error occurred during authentication';
    sendJSONResponse('error', 'Login failed: ' . $errorDetails, [], 500);
}
