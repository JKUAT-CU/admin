<?php
require_once 'db.php';

function handleBudgetSubmission($input)
{
    global $mysqli;

    header('Content-Type: application/json'); // Ensure JSON response for all cases

    // Validate required fields
    if (!isset($input['department_id'], $input['semester'], $input['grandTotal'], $input['assets'], $input['events'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float)$input['grandTotal'];
    $assets = $input['assets'];
    $events = $input['events'];

    // Begin transaction
    $mysqli->begin_transaction();

    try {
        // Insert budget into `budgets` table
        $query = "INSERT INTO budgets (department_id, semester, grand_total, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare budget insert query');
        }

        $stmt->bind_param('ssd',$department_id, $semester, $grandTotal);
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
