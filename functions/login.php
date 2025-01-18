<?php
require_once 'db.php';

header('Content-Type: application/json');

function handleLogin($input)
{
    global $mysqli;

    // Validate action
    if (!isset($input['action']) || $input['action'] !== 'login') {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid action']);
        exit;
    }

    // Check if email and password are provided
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Email and password are required']);
        exit;
    }

    $email = $mysqli->real_escape_string($input['email']);
    $password = $input['password'];

    // Prepare SQL query
    $query = "SELECT id, password FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Database error']);
        exit;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 0) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password']);
        exit;
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password']);
        exit;
    }

    // Generate JWT token
    $payload = [
        'id' => $user['id'],
        'email' => $email,
        'iat' => time(),
        'exp' => time() + 3600, // Token valid for 1 hour
    ];
    $key = 'hwhnmf-qxklvm-aj9qmn984'; // Replace with a secure key
    $jwt = generateJWT($payload, $key);

    http_response_code(200); // OK
    echo json_encode([
        'message' => 'Login successful',
        'token' => $jwt,
    ]);
}

function generateJWT($payload, $key)
{
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);

    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payload);

    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $key, true);
    $base64Signature = base64UrlEncode($signature);

    return "$base64Header.$base64Payload.$base64Signature";
}

function base64UrlEncode($data)
{
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

// Handle request
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid JSON payload']);
    exit;
}

handleLogin($input);
?>
