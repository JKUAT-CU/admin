<?php
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$mysqli = require_once 'db.php';

function sendEmailWithPDF($departmentName, $pdfPath, $recipientEmail)
{

    
    // Load Composer's autoloader
    require 'vendor/autoload.php';
    include 'backend/db.php';
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'mail.jkuatcu.org';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reset@jkuatcu.org';
        $mail->Password   = '8&+cqTnOa!A5';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Sender and recipients
        $mail->setFrom('sender@jkuatcu.org', 'JKUATCU System');
        $mail->addAddress($recipientEmail, 'Department Representative');

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Updated Budget for Department: $departmentName";
        $mail->Body    = "The budget for <strong>$departmentName</strong> has been updated. Please find the attached PDF for detailed information.";
        $mail->AltBody = "The budget for $departmentName has been updated. Please find the attached PDF for detailed information.";

        // Attach PDF file
        $mail->addAttachment($pdfPath, 'budget_report.pdf');

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

function generatePDF($data, $departmentName)
{
    $dompdf = new Dompdf();

    // HTML content for the PDF
    $html = "<h1>Budget Report for $departmentName</h1>";
    $html .= "<p>Semester: {$data['semester']}</p>";
    $html .= "<p>Grand Total: {$data['grandTotal']}</p>";

    // Assets
    $html .= "<h2>Assets</h2><ul>";
    foreach ($data['assets'] as $asset) {
        $html .= "<li>{$asset['name']} - Quantity: {$asset['quantity']}, Price: {$asset['price']}</li>";
    }
    $html .= "</ul>";

    // Events
    $html .= "<h2>Events</h2>";
    foreach ($data['events'] as $event) {
        $html .= "<h3>{$event['name']} (Attendance: {$event['attendance']})</h3><ul>";
        foreach ($event['items'] as $item) {
            $html .= "<li>{$item['name']} - Quantity: {$item['quantity']}, Price: {$item['price']}</li>";
        }
        $html .= "</ul>";
    }

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfPath = sys_get_temp_dir() . "/budget_report.pdf";
    file_put_contents($pdfPath, $dompdf->output());

    return $pdfPath;
}

function handleEditSubmission($input)
{
    global $mysqli;

    if (!isset($input['budget_id'], $input['semester'], $input['grandTotal'], $input['assets'], $input['events'], $input['department_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $budget_id = (int)$input['budget_id'];
    $department_id = (int)$input['department_id'];
    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float)$input['grandTotal'];
    $assets = $input['assets'];
    $events = $input['events'];

    // Get department name
    $query = "SELECT name FROM departments WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to prepare department query']);
        exit;
    }
    $stmt->bind_param('i', $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $departmentName = $result->fetch_assoc()['name'];
    $stmt->close();

    if (!$departmentName) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid department ID']);
        exit;
    }

    $mysqli->begin_transaction();

    try {
        // Omitted budget creation logic for brevity

        $mysqli->commit();

        // Generate and email the PDF
        $pdfPath = generatePDF($input, $departmentName);
        $recipientEmail = "admin@jkuatcu.org"; // Update with recipient's email
        $emailSent = sendEmailWithPDF($departmentName, $pdfPath, $recipientEmail);

        if ($emailSent) {
            echo json_encode(['message' => 'Budget updated and email sent successfully']);
        } else {
            throw new Exception('Failed to send email');
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update budget', 'error' => $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON input']);
        exit;
    }

    handleEditSubmission($input);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}
