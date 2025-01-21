<?php
header('Content-Type: application/json');

// Allowed origins for CORS
$allowedOrigins = [
    'https://admin.jkuatcu.org',
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Parse the input data
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['email'], $input['department_id'], $input['added_by_id'])) {
    $email = $input['email'];
    $departmentId = $input['department_id'];
    $addedById = $input['added_by_id']; // ID of the user adding this new user

    // Generate a random password
    $password = bin2hex(random_bytes(4)); // Generates an 8-character random password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Fetch department_id of the user adding this one
    $query = "SELECT department_id FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $addedById);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $addedByDepartmentId = $result->fetch_assoc()['department_id'];
        $stmt->close();

        // Check if the added_by department matches the new department
        if ($addedByDepartmentId == $departmentId) {
            // Insert the new user into the database
            $query = "INSERT INTO users (department_id, email, password, role_id) VALUES (?, ?, ?, 2)";
            $stmt = $db->prepare($query);
            $stmt->bind_param('iss', $departmentId, $email, $hashedPassword);

            if ($stmt->execute()) {
                $stmt->close();

                // Send email notification
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'mail.jkuatcu.org';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'reset@jkuatcu.org';
                    $mail->Password = '8&+cqTnOa!A5';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    // Sender and recipient
                    $mail->setFrom('sender@jkuatcu.org', 'JKUATCU System');
                    $mail->addAddress($email);

                    // Email subject and body
                    $mail->Subject = 'Account Creation - JKUATCU System';
                    $mail->Body = "Dear User,\n\nYour account has been created on the JKUATCU System.\n\nYour login details are as follows:\nEmail: $email\nPassword: $password\n\nPlease log in and change your password.\n\nBest Regards,\nJKUATCU System";

                    // Send the email
                    $mail->send();

                    http_response_code(201); // Created
                    echo json_encode(['message' => 'User added and email sent successfully']);
                } catch (Exception $e) {
                    http_response_code(500); // Internal Server Error
                    echo json_encode(['message' => 'Failed to send email', 'error' => $mail->ErrorInfo]);
                }
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode(['message' => 'Failed to add user', 'error' => $db->error]);
            }
        } else {
            http_response_code(403); // Forbidden
            echo json_encode(['message' => 'You can only add users to your department']);
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to verify department', 'error' => $stmt->error]);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid input data']);
}
?>
