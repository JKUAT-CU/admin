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

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$mysqli = require_once 'db.php';

// Fetch budgets by department_id only
function fetchBudgetsByDepartment($departmentId, $conn) {
    $query = "
        SELECT 
            id AS budget_id, 
            semester, 
            grand_total, 
            created_at, 
            status
        FROM 
            budgets
        WHERE 
            department_id = ?
        ORDER BY 
            created_at DESC
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare query']);
        exit;
    }

    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $budgets = [];
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }

    echo json_encode(['budgets' => $budgets]);
}

// Fetch budgets by department_id and semester
function fetchBudgetsByDepartmentAndSemester($departmentId, $semester, $conn) {
    $query = "
        SELECT 
            id AS budget_id, 
            semester, 
            grand_total, 
            created_at, 
            status
        FROM 
            budgets
        WHERE 
            department_id = ? AND semester = ?
        ORDER BY 
            created_at DESC
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare query']);
        exit;
    }

    $stmt->bind_param('is', $departmentId, $semester);
    $stmt->execute();
    $result = $stmt->get_result();

    $budgets = [];
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }

    echo json_encode(['budgets' => $budgets]);
}

// Route handling
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['department_id']) && isset($_GET['semester'])) {
        // Fetch budgets by department_id and semester
        $departmentId = intval($_GET['department_id']);
        $semester = $_GET['semester'];
        fetchBudgetsByDepartmentAndSemester($departmentId, $semester, $mysqli);
    } elseif (isset($_GET['department_id'])) {
        // Fetch budgets by department_id only
        $departmentId = intval($_GET['department_id']);
        fetchBudgetsByDepartment($departmentId, $mysqli);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid request. department_id is required.']);
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Invalid endpoint or method.']);
}
?>
