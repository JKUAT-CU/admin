<?php
header('Content-Type: application/json');

// Allowed origins for CORS
$allowedOrigins = ['https://admin.jkuatcu.org'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require 'db.php'; // Database connection
require 'vendor/autoload.php'; // For PHPMailer

/**
 * Validate the reset token
 * @param mysqli $mysqli
 * @param string $token
 * @return string
 * @throws Exception
 */
function validateToken($mysqli, $token) {
    $query = "SELECT email, expiry FROM password_resets WHERE token = ?";
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($email, $expiry);
        if ($stmt->fetch()) {
            $stmt->close();
            if (new DateTime() > new DateTime($expiry)) {
                throw new Exception('Token has expired.');
            }
            return $email;
        }
        throw new Exception('Invalid token.');
    }
    throw new Exception('Failed to validate token.');
}

/**
 * Update the user's password in the database
 * @param mysqli $mysqli
 * @param string $email
 * @param string $password
 * @throws Exception
 */
function updatePassword($mysqli, $email, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $query = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ss', $hashedPassword, $email);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception('Password update failed.');
        }
        $stmt->close();
    } else {
        throw new Exception('Database error: ' . $mysqli->error);
    }
}

/**
 * Handle Password Reset
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['token'])) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $token = $_POST['token'];

    try {
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        $email = validateToken($mysqli, $token);
        updatePassword($mysqli, $email, $password);

        echo json_encode(['message' => 'Password reset successful.']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
?>
