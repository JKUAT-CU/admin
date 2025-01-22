<?php
// header('Content-Type: application/json');

// // Allowed origins for CORS
// $allowedOrigins = [
//     'https://admin.jkuatcu.org',
// ];

// if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
//     header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
//     header('Access-Control-Allow-Credentials: true');
// } else {
//     http_response_code(403); // Forbidden
//     echo json_encode(['message' => 'Origin not allowed']);
//     exit;
// }

// header('Access-Control-Allow-Methods: GET, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

require_once 'db.php';

// Fetch and display all budgets with their associated details
function fetchAllBudgets()
{
    global $mysqli; // Use the global database connection

    $query = "
        SELECT 
            b.id AS budget_id, 
            b.department_id, 
            b.semester, 
            b.grand_total, 
            b.created_at, 
            d.name AS department_name,
            GROUP_CONCAT(DISTINCT CONCAT(a.name, ':', a.quantity, ':', a.price)) AS assets,
            GROUP_CONCAT(DISTINCT CONCAT(e.name, ':', e.attendance)) AS events
        FROM budgets b
        LEFT JOIN departments d ON b.department_id = d.id
        LEFT JOIN assets a ON b.id = a.budget_id
        LEFT JOIN events e ON b.id = e.budget_id
        GROUP BY b.id
    ";

    $result = $mysqli->query($query);

    if ($result) {
        $budgets = [];

        while ($row = $result->fetch_assoc()) {
            // Parse assets and events into structured arrays
            $assets = [];
            if (!empty($row['assets'])) {
                foreach (explode(',', $row['assets']) as $asset) {
                    [$name, $quantity, $price] = explode(':', $asset);
                    $assets[] = [
                        'name' => $name,
                        'quantity' => (int)$quantity,
                        'price' => (float)$price
                    ];
                }
            }

            $events = [];
            if (!empty($row['events'])) {
                foreach (explode(',', $row['events']) as $event) {
                    [$name, $attendance] = explode(':', $event);
                    $events[] = [
                        'name' => $name,
                        'attendance' => (int)$attendance
                    ];
                }
            }

            // Build the response structure
            $budgets[] = [
                'budget_id' => (int)$row['budget_id'],
                'department_id' => (int)$row['department_id'],
                'department_name' => $row['department_name'],
                'semester' => $row['semester'],
                'grand_total' => (float)$row['grand_total'],
                'created_at' => $row['created_at'],
                'assets' => $assets,
                'events' => $events,
            ];
        }

        http_response_code(200); // OK
        echo json_encode(['budgets' => $budgets]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to fetch budgets', 'error' => $mysqli->error]);
    }
}

// Process GET request to fetch budgets
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    fetchAllBudgets();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Invalid request method']);
}
?>
