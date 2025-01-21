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
function handleEditSubmission($input)
{
    global $mysqli;

    header('Content-Type: application/json'); // Ensure JSON response for all cases

    // Validate required fields
    if (!isset($input['department_id'], $input['semester'], $input['grandTotal'], $input['assets'], $input['events'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $department_id = (int)$input['department_id']; // Ensure department_id is extracted and cast to an integer
    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float)$input['grandTotal'];
    $assets = $input['assets'];
    $events = $input['events'];

    if (empty($department_id)) { // Additional validation for department_id
        http_response_code(400);
        echo json_encode(['message' => 'Invalid department_id']);
        exit;
    }

    // Check if a budget already exists for the department and semester
    $checkQuery = "SELECT id FROM budgets WHERE department_id = ? AND semester = ?";
    $checkStmt = $mysqli->prepare($checkQuery);

    if (!$checkStmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to prepare budget existence check query']);
        exit;
    }

    $checkStmt->bind_param('is', $department_id, $semester);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        http_response_code(400);
        echo json_encode(['message' => 'Budget for this department and semester already exists']);
        exit;
    }

    $checkStmt->close();

    // Begin transaction
    $mysqli->begin_transaction();

    try {
        // Insert budget into `budgets` table
        $query = "INSERT INTO budgets (department_id, semester, grand_total, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare budget insert query');
        }

        $stmt->bind_param('isd', $department_id, $semester, $grandTotal);
        $stmt->execute();

        // Get the ID of the newly inserted budget
        $budgetId = $stmt->insert_id;
        $stmt->close();

        // Insert assets into `assets` table
        $assetQuery = "INSERT INTO assets (budget_id, name, quantity, price) VALUES (?, ?, ?, ?)";
        $assetStmt = $mysqli->prepare($assetQuery);
        if (!$assetStmt) {
            throw new Exception('Failed to prepare asset insert query');
        }

        foreach ($assets as $asset) {
            $name = $mysqli->real_escape_string($asset['name']);
            $quantity = (int)$asset['quantity'];
            $price = (float)$asset['price'];
            $assetStmt->bind_param('isid', $budgetId, $name, $quantity, $price);
            $assetStmt->execute();
        }
        $assetStmt->close();

        // Insert events into `events` table
        $eventQuery = "INSERT INTO events (budget_id, name, attendance) VALUES (?, ?, ?)";
        $eventStmt = $mysqli->prepare($eventQuery);
        if (!$eventStmt) {
            throw new Exception('Failed to prepare event insert query');
        }

        $eventItemQuery = "INSERT INTO event_items (event_id, name, quantity, price) VALUES (?, ?, ?, ?)";
        $eventItemStmt = $mysqli->prepare($eventItemQuery);
        if (!$eventItemStmt) {
            throw new Exception('Failed to prepare event item insert query');
        }

        foreach ($events as $event) {
            $eventName = $mysqli->real_escape_string($event['name']);
            $attendance = (int)$event['attendance'];
            $eventStmt->bind_param('isi', $budgetId, $eventName, $attendance);
            $eventStmt->execute();

            // Get the ID of the newly inserted event
            $eventId = $eventStmt->insert_id;

            // Insert event items into `event_items` table
            foreach ($event['items'] as $item) {
                $itemName = $mysqli->real_escape_string($item['name']);
                $itemQuantity = (int)$item['quantity'];
                $itemPrice = (float)$item['price'];
                $eventItemStmt->bind_param('isid', $eventId, $itemName, $itemQuantity, $itemPrice);
                $eventItemStmt->execute();
            }
        }

        $eventStmt->close();
        $eventItemStmt->close();

        // Commit the transaction
        $mysqli->commit();

        echo json_encode(['message' => 'Budget submitted successfully']);
    } catch (Exception $e) {
        // Rollback transaction on failure
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['message' => 'Failed to submit budget', 'error' => $e->getMessage()]);
    }
}

// Ensure POST request and decode input JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON input']);
        exit;
    }

    handleBudgetSubmission($input);
} else {
    http_response_code(405); // Method not allowed
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}
?>
