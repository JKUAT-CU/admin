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

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

function fetchLatestBudgets()
{
    global $mysqli; // Use the global database connection

    $query = "
        SELECT 
            b.id AS budget_id,
            b.department_id,
            b.semester,
            b.grand_total,
            b.created_at,
            d.name AS department_name
        FROM budgets b
        LEFT JOIN departments d ON b.department_id = d.id
        WHERE b.created_at = (
            SELECT MAX(created_at) 
            FROM budgets 
            WHERE department_id = b.department_id
        )
    ";

    $result = $mysqli->query($query);

    if ($result) {
        $budgets = [];

        while ($row = $result->fetch_assoc()) {
            $budgetId = (int)$row['budget_id'];

            // Fetch associated assets for this budget
            $assetQuery = "
                SELECT name, quantity, price
                FROM assets
                WHERE budget_id = $budgetId
            ";
            $assetResult = $mysqli->query($assetQuery);
            $assets = [];
            while ($assetRow = $assetResult->fetch_assoc()) {
                $assets[] = [
                    'name' => $assetRow['name'],
                    'quantity' => (int)$assetRow['quantity'],
                    'price' => (float)$assetRow['price']
                ];
            }

            // Fetch associated events for this budget
            $eventQuery = "
                SELECT id AS event_id, name, attendance
                FROM events
                WHERE budget_id = $budgetId
            ";
            $eventResult = $mysqli->query($eventQuery);
            $events = [];
            while ($eventRow = $eventResult->fetch_assoc()) {
                $eventId = (int)$eventRow['event_id'];

                // Fetch items for this event
                $itemQuery = "
                    SELECT name, quantity, price
                    FROM event_items
                    WHERE event_id = $eventId
                ";
                $itemResult = $mysqli->query($itemQuery);
                $items = [];
                while ($itemRow = $itemResult->fetch_assoc()) {
                    $items[] = [
                        'name' => $itemRow['name'],
                        'quantity' => (int)$itemRow['quantity'],
                        'price' => (float)$itemRow['price']
                    ];
                }

                $events[] = [
                    'name' => $eventRow['name'],
                    'attendance' => (int)$eventRow['attendance'],
                    'items' => $items
                ];
            }

            // Construct the budget structure
            $budgets[] = [
                'budget_id' => $budgetId,
                'department_id' => (int)$row['department_id'],
                'department_name' => $row['department_name'],
                'semester' => $row['semester'],
                'grand_total' => (float)$row['grand_total'],
                'created_at' => $row['created_at'],
                'assets' => $assets,
                'events' => $events
            ];
        }

        http_response_code(200); // OK
        echo json_encode(['budgets' => $budgets], JSON_PRETTY_PRINT);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to fetch budgets', 'error' => $mysqli->error]);
    }
}

// Process GET request to fetch the latest semester budgets
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    fetchLatestBudgets();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Invalid request method']);
}
?>
