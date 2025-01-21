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
require_once 'db.php';

// Fetch budgets by department (GET)
function fetchBudgetsByDepartment($departmentId) {
    global $conn;

    $query = "SELECT * FROM budgets WHERE department_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $budgets = [];
        while ($row = $result->fetch_assoc()) {
            $budgets[] = $row;
        }
        echo json_encode(['budgets' => $budgets]);
    } else {
        echo json_encode(['budgets' => []]);
    }
}

// Save or update budgets (POST)
function saveBudgets($budgets) {
    global $conn;

    foreach ($budgets as $budget) {
        // Check if budget already exists
        $query = "SELECT id FROM budgets WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $budget['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing budget
            $query = "UPDATE budgets SET name = ?, semester = ?, total = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssdi",
                $budget['name'],
                $budget['semester'],
                $budget['total'],
                $budget['id']
            );
        } else {
            // Insert new budget
            $query = "INSERT INTO budgets (id, name, semester, total, department_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "issdi",
                $budget['id'],
                $budget['name'],
                $budget['semester'],
                $budget['total'],
                $budget['department_id']
            );
        }
        $stmt->execute();
    }

    echo json_encode(['message' => 'Budgets saved successfully']);
}

// Route handling
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['department_id'])) {
    $departmentId = intval($_GET['department_id']);
    fetchBudgetsByDepartment($departmentId);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['budgets'])) {
        saveBudgets($input['budgets']);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid input data']);
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Endpoint not found']);
}

?>
