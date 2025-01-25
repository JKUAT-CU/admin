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
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$mysqli = require_once 'db.php';

// Fetch budgets by department_id only
function fetchBudgetsByDepartment($departmentId, $conn) {
    $query = "
        WITH LatestBudgets AS (
            SELECT 
                id AS budget_id, 
                semester, 
                grand_total, 
                created_at, 
                status,
                department_id
            FROM 
                budgets
            WHERE 
                department_id = ?
            AND created_at = (
                SELECT MAX(created_at)
                FROM budgets AS b2
                WHERE b2.semester = budgets.semester AND b2.department_id = budgets.department_id
            )
        )
        SELECT * FROM LatestBudgets
        ORDER BY created_at DESC
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

function fetchBudgetsByDepartmentAndSemester($departmentId, $semester, $conn) {
    $query = "
WITH LatestBudgets AS (
    SELECT 
        id AS budget_id, 
        semester, 
        grand_total, 
        created_at, 
        status,
        department_id
    FROM 
        budgets
    WHERE 
        department_id = ? AND semester = ?
    AND created_at = (
        SELECT MAX(created_at)
        FROM budgets AS b2
        WHERE b2.semester = budgets.semester AND b2.department_id = budgets.department_id
    )
)
SELECT 
    lb.budget_id, 
    lb.semester, 
    lb.grand_total, 
    lb.created_at, 
    lb.status, 
    lb.department_id,
    d.name AS department_name,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'name', a.name, 
            'quantity', a.quantity, 
            'price', a.price
        )
    ) AS assets,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'name', e.name, 
            'attendance', e.attendance, 
            'total_cost', IFNULL(e.total_cost, 0),
            'event_items', (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'item_name', ei.name,
                        'item_quantity', ei.quantity,
                        'item_price', ei.price,
                        'item_total_cost', IFNULL(ei.total_cost, 0)
                    )
                )
                FROM event_items ei
                WHERE ei.event_id = e.id
            )
        )
    ) AS events
FROM 
    LatestBudgets lb
JOIN departments d ON lb.department_id = d.id
LEFT JOIN assets a ON a.budget_id = lb.budget_id
LEFT JOIN events e ON e.budget_id = lb.budget_id
GROUP BY 
    lb.budget_id, lb.semester, lb.grand_total, lb.created_at, lb.status, lb.department_id, d.name
ORDER BY 
    lb.created_at DESC;

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
        $row['assets'] = json_decode($row['assets'], true);
        $row['events'] = json_decode($row['events'], true);
        $budgets[] = $row;
    }

    echo json_encode(['budgets' => $budgets]);
}

// Route handling
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['department_id']) && isset($_GET['semester'])) {
        $departmentId = intval($_GET['department_id']);
        $semester = $_GET['semester'];
        fetchBudgetsByDepartmentAndSemester($departmentId, $semester, $mysqli);
    } elseif (isset($_GET['department_id'])) {
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
