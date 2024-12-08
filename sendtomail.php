<?php
session_start();

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Database connection details
$host = 'localhost';
$username = 'jkuatcu_devs';
$password = '#God@isAble!#';
$database = 'jkuatcu_data';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve PDF and email address
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $pdfData = base64_decode($_POST['pdfData'] ?? ''); // Assume PDF is sent as a base64 string
    $fileName = $_POST['fileName'] ?? 'budget.pdf';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: budget.php");
        exit();
    }

    // Save the PDF temporarily
    $pdfPath = sys_get_temp_dir() . '/' . $fileName;
    file_put_contents($pdfPath, $pdfData);

    // Create PHPMailer instance
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

        // Sender and recipients
        $mail->setFrom('reset@jkuatcu.org', 'JKUATCU Treasury');
        $mail->addAddress($email); // User's email
        $mail->addAddress('treasury@jkuatcu.org'); // Treasury email

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Budget Submission Confirmation";
        $mail->Body = "<p>Dear User,</p>
                       <p>Your budget has been successfully submitted. Please find the attached PDF for your records.</p>
                       <p>Best regards,<br>JKUATCU Treasury</p>";

        // Attach PDF
        $mail->addAttachment($pdfPath, $fileName);

        // Send email
        $mail->send();

        // Cleanup temporary file
        unlink($pdfPath);

        $_SESSION['success'] = "Budget submitted and email sent successfully!";
        header("Location: budget.php");
        exit();
    } catch (Exception $e) {
        // Handle errors
        $_SESSION['error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
        unlink($pdfPath); // Cleanup even on error
        header("Location: budget.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: budget.php");
    exit();
}

// Close database connection
$conn->close();
?>
