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

// SMTP Configuration
define('SMTP_HOST', 'mail.jkuatcu.org');
define('SMTP_USERNAME', 'reset@jkuatcu.org');
define('SMTP_PASSWORD', '8&+cqTnOa!A5');
define('SMTP_PORT', 465);

/**
 * Generate a secure random token
 * @return string
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Store the reset token in the database
 * @param mysqli $mysqli
 * @param string $email
 * @return string
 * @throws Exception
 */
function storeToken($mysqli, $email) {
    $token = generateToken();
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $query = "INSERT INTO password_resets (email, token, created_at, expiry) VALUES (?, ?, NOW(), ?)";
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param('sss', $email, $token, $expiry);
        $stmt->execute();
        $stmt->close();
        return $token;
    }
    throw new Exception('Failed to store token.');
}

/**
 * Send a reset email with the token
 * @param string $email
 * @param string $token
 * @throws Exception
 */
function sendResetEmail($email, $token) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $resetLink = "https://admin.jkuatcu.org/reset-password?token=$token";

        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;

        // Email settings
        $mail->setFrom(SMTP_USERNAME, 'JKUATCU');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Password Reset";
        $mail->Body = "Click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a>";

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Failed to send email: " . $mail->ErrorInfo);
    }
}

/**
 * Handle Forgot Password
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    try {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Check if email exists in the database
        $query = "SELECT email FROM users WHERE email = ?";
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                throw new Exception('No user found with that email.');
            }
            $stmt->close();

            // Generate and store token, then send the email
            $token = storeToken($mysqli, $email);
            sendResetEmail($email, $token);

            echo json_encode(['message' => 'Password reset instructions sent.']);
        } else {
            throw new Exception('Database query failed.');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
?>
