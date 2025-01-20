<?php
header('Content-Type: application/json');

// CORS configuration
$allowed_origins = [
    'https://admin.jkuatcu.org',
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403);
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include PHPMailer and DB files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';
include 'db.php';

// Check database connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $department_id = $_POST['department_id'] ?? null;
    $semester = $_POST['semester'] ?? '2025';
    $finance_email = 'finance@jkuatcu.org';
    $department_name = "Department {$department_id}"; // You can pull this info from your DB if needed

    // Validate the PDF file upload
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['message' => 'Invalid PDF upload.']);
        http_response_code(400);
        exit;
    }

    // Retrieve the uploaded PDF content
    $pdfPath = $_FILES['pdf']['tmp_name'];
    $pdfContent = file_get_contents($pdfPath);

    // Create a PHPMailer instance to send email
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'mail.jkuatcu.org'; // SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'reset@jkuatcu.org'; // SMTP username
        $mail->Password = '8&+cqTnOa!A5'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Sender and recipient
        $mail->setFrom('sender@jkuatcu.org', 'JKUATCU System');
        $mail->addAddress($finance_email, 'Finance Department'); // Finance department

        // Attach the PDF file
        $mail->addStringAttachment($pdfContent, "Budget_{$department_name}_{$semester}.pdf");

        // Email subject and body
        $mail->Subject = "Budget Submission - {$department_name} ({$semester})";
        $mail->Body = "Dear Finance,\n\nThe budget for {$department_name} for the semester {$semester} has been submitted.\n\nBest Regards,\nJKUATCU System";

        // Send the email
        $mail->send();

        // Respond with success
        echo json_encode(['message' => 'Budget submitted and emailed successfully.']);
        http_response_code(200);
    } catch (Exception $e) {
        // Respond with error if email fails
        echo json_encode(['message' => "Failed to send email. Error: {$mail->ErrorInfo}"]);
        http_response_code(500);
    }
} else {
    echo json_encode(['message' => 'Invalid request method']);
    http_response_code(405);
}
?>
