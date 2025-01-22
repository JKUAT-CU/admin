<?php
header('Content-Type: application/json');

// Allow only specific origins
$allowedOrigins = ['https://admin.jkuatcu.org'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403);
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

require 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}

// Handle the incoming form data
$department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$budget_id = isset($_POST['budget_id']) ? (int)$_POST['budget_id'] : null;
$semester = isset($_POST['semester']) ? $_POST['semester'] : null;
$grandTotal = isset($_POST['grandTotal']) ? (float)$_POST['grandTotal'] : null;

// Parse assets and events from JSON strings
$assets = isset($_POST['assets']) ? json_decode($_POST['assets'], true) : [];
$events = isset($_POST['events']) ? json_decode($_POST['events'], true) : [];

// Check for uploaded file
if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['message' => 'PDF file is required']);
    exit;
}
$pdfFile = $_FILES['pdf'];
$uploadDir = '/path/to/upload/directory/'; // Update with your actual path
$pdfPath = $uploadDir . basename($pdfFile['name']);

// Save the uploaded PDF file
if (!move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to upload PDF']);
    exit;
}

// Validate department ID
if (!$department_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Department ID is required']);
    exit;
}

// Fetch department name
$queryDept = "SELECT name FROM departments WHERE id = ?";
$stmtDept = $mysqli->prepare($queryDept);
if (!$stmtDept) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmtDept->bind_param('i', $department_id);
$stmtDept->execute();
$stmtDept->bind_result($departmentName);
if (!$stmtDept->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Department not found']);
    exit;
}
$stmtDept->close();

// Fetch user emails
$queryUsers = "SELECT email FROM users WHERE department_id = ?";
$stmtUsers = $mysqli->prepare($queryUsers);
if (!$stmtUsers) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmtUsers->bind_param('i', $department_id);
$stmtUsers->execute();
$result = $stmtUsers->get_result();
$userEmails = [];
while ($row = $result->fetch_assoc()) {
    $userEmails[] = $row['email'];
}
$stmtUsers->close();

if (empty($userEmails)) {
    http_response_code(404);
    echo json_encode(['message' => 'No users found in the department']);
    exit;
}

// Send email with the PDF file as an attachment
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'mail.jkuatcu.org';
    $mail->SMTPAuth = true;
    $mail->Username = 'reset@jkuatcu.org';
    $mail->Password = '8&+cqTnOa!A5';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('sender@jkuatcu.org', 'JKUATCU System');
    foreach ($userEmails as $email) {
        $mail->addAddress($email);
    }

    $mail->Subject = "Important Update for {$departmentName}";
    $mail->Body = "Dear {$departmentName} members,\n\nThis is an important notification.\n\nBest Regards,\nJKUATCU System";

    // Attach the uploaded PDF file
    $mail->addAttachment($pdfPath);

    $mail->send();
    echo json_encode(['message' => 'Email sent successfully']);
    http_response_code(200);
} catch (Exception $e) {
    echo json_encode(['message' => 'Email sending failed', 'error' => $mail->ErrorInfo]);
    http_response_code(500);
}
?>
