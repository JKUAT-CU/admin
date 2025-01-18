<?php
require_once 'db.php';

function handleLogin($input)
{
    global $mysqli;

    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Email and password are required']);
        exit;
    }

    $email = $mysqli->real_escape_string($input['email']);
    $password = $input['password'];

    $query = "SELECT id, password FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password']);
        exit;
    }

    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password']);
        exit;
    }

    // Generate JWT Token
    $payload = [
        'id' => $user['id'],
        'email' => $email,
        'iat' => time(),
        'exp' => time() + 3600, // Token valid for 1 hour
    ];
    $key = 'your_secret_key'; // Replace with a secure key
    $jwt = generateJWT($payload, $key);

    http_response_code(200);
    echo json_encode(['token' => $jwt]);
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

?>
