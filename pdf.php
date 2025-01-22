<?php
header('Content-Type: application/json');

// Configuration
$allowed_origins = ['https://admin.jkuatcu.org'];
define('FINANCE_EMAIL', 'finance@jkuatcu.org');
define('SMTP_USER', getenv('SMTP_USER')); // Load from environment
define('SMTP_PASS', getenv('SMTP_PASS')); // Load from environment
define('SMTP_HOST', 'mail.jkuatcu.org');
define('SMTP_PORT', 465);

// CORS Setup
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403);
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validate request
$department_id = $_POST['department_id'] ?? null;
if (!$department_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Department ID is required']);
    exit;
}

// Database Connection
require 'db.php';
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed']);
    exit;
}

// Fetch Department Name and Emails
try {
    $stmtDept = $mysqli->prepare("SELECT name FROM departments WHERE id = ?");
    $stmtDept->bind_param('i', $department_id);
    $stmtDept->execute();
    $stmtDept->bind_result($departmentName);
    if (!$stmtDept->fetch()) {
        throw new Exception('Department not found');
    }
    $stmtDept->close();

    $stmtUsers = $mysqli->prepare("SELECT email FROM users WHERE department_id = ?");
    $stmtUsers->bind_param('i', $department_id);
    $stmtUsers->execute();
    $result = $stmtUsers->get_result();
    $userEmails = array_column($result->fetch_all(MYSQLI_ASSOC), 'email');
    if (empty($userEmails)) {
        throw new Exception('No users found in the department');
    }
    $stmtUsers->close();
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['message' => $e->getMessage()]);
    exit;
}

// Validate and Process PDF
if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid PDF upload']);
    exit;
}

// Email Setup and Sending
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';

$pdfPath = $_FILES['pdf']['tmp_name'];
$pdfContent = file_get_contents($pdfPath);
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = SMTP_PORT;

    $mail->setFrom('no-reply@jkuatcu.org', 'JKUATCU System');
    $mail->addAddress(FINANCE_EMAIL, 'Finance Department');
    $mail->addStringAttachment($pdfContent, "Budget_{$departmentName}_2025.pdf");
    $mail->Subject = "Budget Submission - {$departmentName} (2025)";
    $mail->Body = "Dear Finance,\n\nThe budget for {$departmentName} for the semester 2025 has been submitted.\n\nBest Regards,\nJKUATCU System";

    $mail->send();
    http_response_code(200);
    echo json_encode(['message' => 'Budget submitted and emailed successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Email failed: ' . $mail->ErrorInfo]);
}
?>
