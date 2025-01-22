<?php
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Allowed origins for CORS
$allowedOrigins = ['https://admin.jkuatcu.org'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403);
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$mysqli = require_once 'db.php';

function getUserEmail($departmentId) {
    global $mysqli;

    $query = "SELECT email FROM departments WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare email query");
    }

    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $email = $result->fetch_assoc()['email'];
    $stmt->close();

    if (!$email) {
        throw new Exception("Email not found for department ID: $departmentId");
    }

    return $email;
}

function sendEmailWithPDF($pdfContent, $fileName, $recipientEmail, $departmentName) {
    require 'vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'mail.jkuatcu.org';
        $mail->SMTPAuth = true;
        $mail->Username = 'reset@jkuatcu.org';
        $mail->Password = '8&+cqTnOa!A5';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Sender and recipients
        $mail->setFrom('sender@jkuatcu.org', 'JKUATCU System');
        $mail->addAddress($recipientEmail, 'Department Representative');

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Updated Budget for Department: $departmentName";
        $mail->Body = "Dear Representative,<br>The budget for <strong>$departmentName</strong> has been updated. Please find the attached PDF for details.";
        $mail->AltBody = "Dear Representative, The budget for $departmentName has been updated. Please find the attached PDF for details.";

        // Attach PDF file
        $mail->addStringAttachment($pdfContent, $fileName);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null || !isset($input['department_id'], $input['semester'], $input['grandTotal'], $input['assets'], $input['events'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid or missing input data']);
        exit;
    }

    $departmentId = (int) $input['department_id'];
    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float) $input['grandTotal'];
    $assets = $input['assets'];
    $events = $input['events'];

    try {
        $recipientEmail = getUserEmail($departmentId);

        // Generate PDF
        $dompdf = new Dompdf();
        $html = "<h1>Budget Report</h1><p>Department: $departmentId</p><p>Semester: $semester</p>";
        $html .= "<p>Grand Total: $grandTotal</p><h2>Assets</h2><ul>";
        foreach ($assets as $asset) {
            $html .= "<li>{$asset['name']} - Quantity: {$asset['quantity']}, Price: {$asset['price']}</li>";
        }
        $html .= "</ul><h2>Events</h2>";
        foreach ($events as $event) {
            $html .= "<h3>{$event['name']} (Attendance: {$event['attendance']})</h3><ul>";
            foreach ($event['items'] as $item) {
                $html .= "<li>{$item['name']} - Quantity: {$item['quantity']}, Price: {$item['price']}</li>";
            }
            $html .= "</ul>";
        }
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $fileName = "Budget_Report_Department_{$departmentId}_{$semester}.pdf";

        // Send email with PDF
        sendEmailWithPDF($pdfContent, $fileName, $recipientEmail, "Department $departmentId");

        echo json_encode(['message' => 'Budget updated and email sent successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method']);
}
?>
