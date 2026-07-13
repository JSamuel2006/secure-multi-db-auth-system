<?php
// php/register.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mongo.php';

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

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// Server-side validation
if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
    sendJSONResponse('error', 'All fields are required.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse('error', 'Invalid email format.');
}

if ($password !== $confirmPassword) {
    sendJSONResponse('error', 'Passwords do not match.');
}

if (strlen($password) < 8) {
    sendJSONResponse('error', 'Password must be at least 8 characters long.');
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    sendJSONResponse('error', 'Username must be 3-30 characters, containing only letters, numbers, and underscores.');
}

$mysql = getMySQLConnection();

// Check unique username or email
try {
    $stmt = $mysql->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        if (strcasecmp($existing['username'], $username) === 0) {
            sendJSONResponse('error', 'Username is already taken.');
        } else {
            sendJSONResponse('error', 'Email is already registered.');
        }
    }
} catch (\PDOException $e) {
    sendJSONResponse('error', 'Database query execution failed.', [], 500);
}

// Hash password with secure bcrypt
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    // Start transaction
    $mysql->beginTransaction();

    // MySQL Insert
    $insertUser = $mysql->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $insertUser->execute([$username, $email, $hashedPassword]);
    $userId = (int)$mysql->lastInsertId();

    // MongoDB Profile Insert
    $mongo = getMongoConnection();
    $profiles = $mongo->selectCollection('profiles');
    
    $profiles->insertOne([
        'user_id' => $userId,
        'name' => '',
        'age' => '',
        'bio' => '',
        'interests' => []
    ]);

    // Commit MySQL transaction if MongoDB insert succeeds
    $mysql->commit();

    sendJSONResponse('success', 'Registration Successful. You can now login.');
} catch (\Exception $e) {
    if ($mysql->inTransaction()) {
        $mysql->rollBack();
    }
    
    $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
    $errorDetails = ($appDebug === 'true' || $appDebug === '1') ? $e->getMessage() : 'Transaction processing failed';
    sendJSONResponse('error', 'Registration failed: ' . $errorDetails, [], 500);
}
