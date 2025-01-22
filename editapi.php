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

function handleEditSubmission($input)
{
    global $mysqli;

    header('Content-Type: application/json'); // Ensure JSON response for all cases

    // Validate required fields
    if (!isset($input['budget_id'], $input['semester'], $input['grandTotal'], $input['assets'], $input['events'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $budget_id = (int)$input['budget_id']; // Ensure budget_id is extracted and cast to an integer
    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float)$input['grandTotal'];
    $assets = $input['assets'];
    $events = $input['events'];

    // Validate if the budget exists
    $checkQuery = "SELECT id FROM budgets WHERE id = ?";
    $checkStmt = $mysqli->prepare($checkQuery);

    if (!$checkStmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to prepare budget existence check query']);
        exit;
    }

    $checkStmt->bind_param('i', $budget_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows == 0) {
        $checkStmt->close();
        http_response_code(400);
        echo json_encode(['message' => 'Budget does not exist']);
        exit;
    }

    $checkStmt->close();

    // Begin transaction
    $mysqli->begin_transaction();

    try {
        // Update budget in `budgets` table
        $query = "UPDATE budgets SET semester = ?, grand_total = ?, created_at = NOW() WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare budget update query');
        }

        $stmt->bind_param('sdi', $semester, $grandTotal, $budget_id);
        $stmt->execute();
        $stmt->close();

        // Update assets in `assets` table (delete old, add new)
        $deleteAssetQuery = "DELETE FROM assets WHERE budget_id = ?";
        $deleteAssetStmt = $mysqli->prepare($deleteAssetQuery);
        if (!$deleteAssetStmt) {
            throw new Exception('Failed to prepare asset delete query');
        }
        $deleteAssetStmt->bind_param('i', $budget_id);
        $deleteAssetStmt->execute();
        $deleteAssetStmt->close();

        $assetQuery = "INSERT INTO assets (budget_id, name, quantity, price) VALUES (?, ?, ?, ?)";
        $assetStmt = $mysqli->prepare($assetQuery);
        if (!$assetStmt) {
            throw new Exception('Failed to prepare asset insert query');
        }

        foreach ($assets as $asset) {
            $name = $mysqli->real_escape_string($asset['name']);
            $quantity = (int)$asset['quantity'];
            $price = (float)$asset['price'];
            $assetStmt->bind_param('isid', $budget_id, $name, $quantity, $price);
            $assetStmt->execute();
        }
        $assetStmt->close();

        // Update events in `events` table (delete old, add new)
        $deleteEventQuery = "DELETE FROM events WHERE budget_id = ?";
        $deleteEventStmt = $mysqli->prepare($deleteEventQuery);
        if (!$deleteEventStmt) {
            throw new Exception('Failed to prepare event delete query');
        }
        $deleteEventStmt->bind_param('i', $budget_id);
        $deleteEventStmt->execute();
        $deleteEventStmt->close();

        // Insert new events and event items
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
            $eventStmt->bind_param('isi', $budget_id, $eventName, $attendance);
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

        echo json_encode(['message' => 'Budget updated successfully']);
    } catch (Exception $e) {
        // Rollback transaction on failure
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update budget', 'error' => $e->getMessage()]);
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

    handleEditSubmission($input);
} else {
    http_response_code(405); // Method not allowed
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}
?>
