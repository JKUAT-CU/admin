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

// Fetch budgets with all related data
function fetchDetailedBudgets($departmentId, $conn) {
    $query = "
        SELECT 
            b.id AS budget_id, 
            b.semester, 
            b.grand_total, 
            b.created_at,
            a.id AS asset_id,
            a.name AS asset_name,
            a.quantity AS asset_quantity,
            a.price AS asset_price,
            e.id AS event_id,
            e.name AS event_name,
            e.attendance AS event_attendance,
            ei.id AS item_id,
            ei.name AS item_name,
            ei.quantity AS item_quantity,
            ei.price AS item_price
        FROM 
            budgets b
        LEFT JOIN assets a ON b.id = a.budget_id
        LEFT JOIN events e ON b.id = e.budget_id
        LEFT JOIN event_items ei ON e.id = ei.event_id
        WHERE 
            b.department_id = ?
        ORDER BY b.id, e.id, ei.id
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
        $budgetId = $row['budget_id'];

        if (!isset($budgets[$budgetId])) {
            $budgets[$budgetId] = [
                'budget_id' => $row['budget_id'],
                'semester' => $row['semester'],
                'grand_total' => $row['grand_total'],
                'created_at' => $row['created_at'],
                'assets' => [],
                'events' => [],
            ];
        }

        // Add assets
        if ($row['asset_id']) {
            $budgets[$budgetId]['assets'][] = [
                'id' => $row['asset_id'],
                'name' => $row['asset_name'],
                'quantity' => $row['asset_quantity'],
                'price' => $row['asset_price'],
            ];
        }

        // Add events
        if ($row['event_id']) {
            if (!isset($budgets[$budgetId]['events'][$row['event_id']])) {
                $budgets[$budgetId]['events'][$row['event_id']] = [
                    'id' => $row['event_id'],
                    'name' => $row['event_name'],
                    'attendance' => $row['event_attendance'],
                    'items' => [],
                ];
            }

            // Add event items
            if ($row['item_id']) {
                $budgets[$budgetId]['events'][$row['event_id']]['items'][] = [
                    'id' => $row['item_id'],
                    'name' => $row['item_name'],
                    'quantity' => $row['item_quantity'],
                    'price' => $row['item_price'],
                ];
            }
        }
    }

    // Normalize the structure
    $budgets = array_values($budgets);
    foreach ($budgets as &$budget) {
        $budget['events'] = array_values($budget['events']);
    }

    echo json_encode(['budgets' => $budgets]);
}

// Route handling
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['department_id'])) {
    $departmentId = intval($_GET['department_id']);
    fetchDetailedBudgets($departmentId, $mysqli);
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Endpoint not found']);
}


?>
