<?php
// php/profile.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mongo.php';
require_once __DIR__ . '/redis.php';

// Retrieve session token from cookie
$token = $_COOKIE['session_token'] ?? '';

if (empty($token)) {
    sendJSONResponse('error', 'Unauthorized. No active session.', [], 401);
}

try {
    $redis = getRedisConnection();
    $sessionKey = "session:" . $token;
    
    // Retrieve session from Redis
    $sessionData = $redis->hgetall($sessionKey);

    if (empty($sessionData) || !isset($sessionData['user_id'])) {
        // Clear invalid cookie
        setcookie('session_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        sendJSONResponse('error', 'Unauthorized. Session has expired.', [], 401);
    }

    $userId = (int)$sessionData['user_id'];
    
    $mysql = getMySQLConnection();
    $mongo = getMongoConnection();
    $profilesCollection = $mongo->selectCollection('profiles');

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch credential metadata from MySQL
        $stmt = $mysql->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJSONResponse('error', 'User authentication profile not found.', [], 404);
        }

        // Fetch rich bio metadata from MongoDB
        $profile = $profilesCollection->findOne(['user_id' => $userId]);

        // Auto-create document if it somehow doesn't exist
        if (!$profile) {
            $profilesCollection->insertOne([
                'user_id' => $userId,
                'name' => '',
                'age' => '',
                'bio' => '',
                'interests' => []
            ]);
            $profile = [
                'user_id' => $userId,
                'name' => '',
                'age' => '',
                'bio' => '',
                'interests' => []
            ];
        }

        $interests = [];
        if (isset($profile['interests'])) {
            if ($profile['interests'] instanceof \MongoDB\Model\BSONArray || is_array($profile['interests'])) {
                $interests = (array)$profile['interests'];
            }
        }

        // Return combined sanitized JSON data
        sendJSONResponse('success', 'Profile loaded', [
            'profile' => [
                'user_id' => $userId,
                'username' => htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                'created_at' => $user['created_at'],
                'name' => htmlspecialchars($profile['name'] ?? '', ENT_QUOTES, 'UTF-8'),
                'age' => htmlspecialchars($profile['age'] ?? '', ENT_QUOTES, 'UTF-8'),
                'bio' => htmlspecialchars($profile['bio'] ?? '', ENT_QUOTES, 'UTF-8'),
                'interests' => array_map(function($item) {
                    return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
                }, $interests)
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve parameters
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];
        } else {
            $input = $_POST;
        }

        $name = trim($input['name'] ?? '');
        $age = trim($input['age'] ?? '');
        $bio = trim($input['bio'] ?? '');
        
        $rawInterests = $input['interests'] ?? '';
        if (is_array($rawInterests)) {
            $interests = array_filter(array_map('trim', $rawInterests));
        } else {
            $interests = array_filter(array_map('trim', explode(',', $rawInterests)));
        }

        // Validate age if set
        if ($age !== '' && (!is_numeric($age) || (int)$age < 0 || (int)$age > 150)) {
            sendJSONResponse('error', 'Age must be a valid number between 0 and 150.');
        }

        // Input Sanitization for storage
        $sanitizedName = htmlspecialchars(strip_tags($name), ENT_QUOTES, 'UTF-8');
        $sanitizedBio = htmlspecialchars(strip_tags($bio), ENT_QUOTES, 'UTF-8');
        $sanitizedInterests = array_values(array_map(function($item) {
            return htmlspecialchars(strip_tags($item), ENT_QUOTES, 'UTF-8');
        }, $interests));

        // Update MongoDB document
        $profilesCollection->updateOne(
            ['user_id' => $userId],
            ['$set' => [
                'name' => $sanitizedName,
                'age' => $age !== '' ? (int)$age : '',
                'bio' => $sanitizedBio,
                'interests' => $sanitizedInterests
            ]],
            ['upsert' => true]
        );

        sendJSONResponse('success', 'Profile updated successfully.');
    } else {
        sendJSONResponse('error', 'Method not allowed.', [], 405);
    }

} catch (\Exception $e) {
    $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
    $errorDetails = ($appDebug === 'true' || $appDebug === '1') ? $e->getMessage() : 'Operations failed';
    sendJSONResponse('error', 'Profile operation failed: ' . $errorDetails, [], 500);
}
