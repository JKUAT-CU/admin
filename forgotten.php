<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Start the session

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';
include 'backend/db.php';

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from the request body
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: forgot.php");
        exit();
    }

    // Generate a unique token
    $token = bin2hex(random_bytes(32)); // Generate a random string (token)

    // Store the token in the database along with the email and timestamp
    $timestamp = date('Y-m-d H:i:s');
    $insertTokenQuery = "INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, ?)";
    $stmtInsertToken = $mysqli->prepare($insertTokenQuery);
    if ($stmtInsertToken) {
        $stmtInsertToken->bind_param("sss", $email, $token, $timestamp);
        $stmtInsertToken->execute();
        $stmtInsertToken->close();
    } else {
        $_SESSION['error'] = "Failed to store token in the database.";
        header("Location: forgot.php");
        exit();
    }

    // Create a PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'mail.jkuatcu.org';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'reset@jkuatcu.org';                    // SMTP username
        $mail->Password   = '8&+cqTnOa!A5';                         // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
        $mail->Port       = 465;                                    // TCP port to connect to

        // Sender and recipient
        $mail->setFrom('reset@jkuatcu.org', 'JKUATCU');
        $mail->addAddress($email);

        // Email subject and body
        $mail->Subject = "Password Reset";
        $resetPasswordLink = "https://api.jkuatcu.org/reset.php?token=$token";
        $mail->Body = "Click the following link to reset your password: $resetPasswordLink";

        // Send email
        $mail->send();

        $_SESSION['success'] = "Instructions to reset your password have been sent to your email";
        header("Location: forgot.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
        header("Location: forgot.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: forgot.php");
    exit();
}
?>
