<?php
require_once 'db.php';

function handleBudgetSubmission($input)
{
    global $mysqli;

    // Validate input fields
    if (!isset($input['semester'], $input['events'], $input['assets'], $input['grandTotal'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid budget payload']);
        exit;
    }

    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float) $input['grandTotal'];

    // Insert budget summary
    $query = "INSERT INTO budgets (semester, grand_total) VALUES (?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Database error while saving budget']);
        exit;
    }

    $stmt->bind_param('sd', $semester, $grandTotal);
    $stmt->execute();
    $budgetId = $stmt->insert_id; // Get the inserted budget ID
    $stmt->close();

    // Insert events
    foreach ($input['events'] as $event) {
        $eventName = $mysqli->real_escape_string($event['name']);
        $attendance = (int) $event['attendance'];

        $query = "INSERT INTO budget_events (budget_id, name, attendance) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error while saving events']);
            exit;
        }

        $stmt->bind_param('isi', $budgetId, $eventName, $attendance);
        $stmt->execute();
        $eventId = $stmt->insert_id; // Get the inserted event ID
        $stmt->close();

        // Insert items for each event
        foreach ($event['items'] as $item) {
            $itemName = $mysqli->real_escape_string($item['name']);
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];
            $total = (float) $item['total'];

            $query = "INSERT INTO budget_event_items (event_id, name, quantity, price, total) VALUES (?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error while saving event items']);
                exit;
            }

            $stmt->bind_param('isidd', $eventId, $itemName, $quantity, $price, $total);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Insert assets
    foreach ($input['assets'] as $asset) {
        $assetName = $mysqli->real_escape_string($asset['name']);
        $quantity = (int) $asset['quantity'];
        $price = (float) $asset['price'];
        $total = (float) $asset['total'];

        $query = "INSERT INTO budget_assets (budget_id, name, quantity, price, total) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error while saving assets']);
            exit;
        }

        $stmt->bind_param('isidd', $budgetId, $assetName, $quantity, $price, $total);
        $stmt->execute();
        $stmt->close();
    }

    // Successful submission response
    echo json_encode(['message' => 'Budget submitted successfully', 'budget_id' => $budgetId]);
    exit;
}
?>
