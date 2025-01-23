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

// Fetch full budgets including events and assets
function fetchBudgetsWithDetails($departmentId, $semester, $conn) {
    $query = "
        WITH LatestBudgets AS (
            SELECT 
                b.id AS budget_id, 
                b.semester, 
                b.grand_total, 
                b.created_at, 
                b.status,
                b.department_id
            FROM 
                budgets b
            WHERE 
                b.department_id = ?
                " . ($semester ? "AND b.semester = ?" : "") . "
                AND b.created_at = (
                    SELECT MAX(created_at)
                    FROM budgets b2
                    WHERE b2.semester = b.semester AND b2.department_id = b.department_id
                )
        )
        SELECT 
            lb.budget_id, 
            lb.semester, 
            lb.grand_total, 
            lb.created_at, 
            lb.status, 
            lb.department_id,
            COALESCE(JSON_ARRAYAGG(
                JSON_OBJECT(
                    'event_id', e.id, 
                    'event_name', e.name, 
                    'expected_attendees', e.expected_attendees, 
                    'event_total_cost', e.total_cost
                )
            ) FILTER (WHERE e.id IS NOT NULL), '[]') AS events,
            COALESCE(JSON_ARRAYAGG(
                JSON_OBJECT(
                    'asset_id', a.id, 
                    'asset_name', a.name, 
                    'quantity', a.quantity, 
                    'cost_per_item', a.cost_per_item, 
                    'total_cost', a.total_cost
                )
            ) FILTER (WHERE a.id IS NOT NULL), '[]') AS assets
        FROM 
            LatestBudgets lb
        LEFT JOIN events e ON e.budget_id = lb.budget_id
        LEFT JOIN assets a ON a.budget_id = lb.budget_id
        GROUP BY 
            lb.budget_id, lb.semester, lb.grand_total, lb.created_at, lb.status, lb.department_id
        ORDER BY 
            lb.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare query']);
        exit;
    }

    if ($semester) {
        $stmt->bind_param('is', $departmentId, $semester);
    } else {
        $stmt->bind_param('i', $departmentId);
    }

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
    if (isset($_GET['department_id'])) {
        $departmentId = intval($_GET['department_id']);
        $semester = isset($_GET['semester']) ? $_GET['semester'] : null;

        // Fetch budgets including events and assets
        fetchBudgetsWithDetails($departmentId, $semester, $mysqli);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid request. department_id is required.']);
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Invalid endpoint or method.']);
}
?>
