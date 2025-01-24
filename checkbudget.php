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

header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';
require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function checkBudget($department_id, $semester)
{
    global $mysqli;

    // Validate inputs
    if (empty($department_id) || empty($semester)) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid department_id or semester']);
        exit;
    }

    // Check if a budget already exists for the department and semester
    $query = "SELECT id FROM budgets WHERE department_id = ? AND semester = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Database query preparation failed']);
        exit;
    }

    $stmt->bind_param('is', $department_id, $semester);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    http_response_code(200); // OK
    echo json_encode(['exists' => $exists]);
}

// Main logic
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid JSON input']);
    exit;
}

// Check action
$action = $input['action'] ?? null;

if ($action === 'check-budget-exists') {
    $department_id = $input['department_id'] ?? null;
    $semester = $input['semester'] ?? null;

    // Validate required fields
    if (!$department_id || !$semester) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Missing department_id or semester']);
        exit;
    }

    // Execute the budget check
    checkBudget((int)$department_id, $semester);
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid action']);
}
