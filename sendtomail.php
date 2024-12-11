<?php
session_start(); // Start the session

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';
include 'backend/db.php';

// Check database connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from the request
    $department_name = $_POST['department_name'] ?? 'Department';
    $year = $_POST['year'] ?? date('Y');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $finance_email = 'finance@jkuatcu.org';

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: submit_budget.php");
        exit();
    }

    // Validate and process uploaded PDF
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Invalid PDF upload.";
        header("Location: submit_budget.php");
        exit();
    }

    $pdfPath = $_FILES['pdf']['tmp_name'];
    $pdfContent = file_get_contents($pdfPath);

    // Create a PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'mail.jkuatcu.org';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'sender@jkuatcu.org';                   // SMTP username
        $mail->Password   = '8&+cqTnOa!A5';                         // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
        $mail->Port       = 465;                                    // TCP port to connect to

        // Sender and recipient
        $mail->setFrom('sender@jkuatcu.org', 'JKUATCU System');
        $mail->addAddress($email, 'Department Representative');
        $mail->addAddress($finance_email, 'Finance Department');

        // Attach the PDF
        $mail->addStringAttachment($pdfContent, "Budget_{$department_name}_{$year}.pdf");

        // Email subject and body
        $mail->Subject = "Budget Submission - {$department_name} ({$year})";
        $mail->Body = "Dear Finance,\n\nThe budget for {$department_name} for the year {$year} has been submitted.\n\nBest Regards,\nJKUATCU System";

        // Send email
        $mail->send();

        $_SESSION['success'] = "Budget submitted and emailed successfully.";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
        header("Location: budget.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: budget.php");
    exit();
}
?>
