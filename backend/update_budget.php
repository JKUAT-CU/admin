<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('db.php'); // Database connection
include('../session.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    error_log("Unauthorized access attempt.");
    exit();
}

// Retrieve the department ID from the session
$department_id = $_SESSION['department_id'];

// Process the incoming JSON payload
$inputData = file_get_contents('php://input');
if (!$inputData) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    error_log("No data received in POST request.");
    exit();
}

$payload = json_decode($inputData, true);

// Validate required fields
if (
    !isset($payload['budget_id'], $payload['events'], $payload['assets'], $payload['grand_total'])
    || !is_array($payload['events'])
    || !is_array($payload['assets'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data structure']);
    error_log("Invalid data structure received. Payload: " . print_r($payload, true));
    exit();
}

// Prepare data for saving
$budget_id = intval($payload['budget_id']);
$grand_total = floatval($payload['grand_total']);
error_log("Updating budget ID: $budget_id, Grand Total: $grand_total");

// Begin database transaction
$mysqli->begin_transaction();
try {
    // Update the main budget record
    $stmt = $mysqli->prepare("UPDATE budgets SET total_amount = ? WHERE id = ? AND department_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed for main budget update: " . $mysqli->error);
    }
    $stmt->bind_param("dii", $grand_total, $budget_id, $department_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed for main budget update: " . $stmt->error);
    }
    $stmt->close();
    error_log("Main budget record updated successfully.");

    // Update event details
    $stmtEvent = $mysqli->prepare("UPDATE events SET event_name = ?, attendees = ?, subtotal = ? WHERE id = ? AND budget_id = ?");
    if (!$stmtEvent) {
        throw new Exception("Prepare failed for event update: " . $mysqli->error);
    }
    $stmtItem = $mysqli->prepare("UPDATE event_items SET item_name = ?, quantity = ?, cost_per_item = ?, total_cost = ? WHERE id = ? AND event_id = ?");
    if (!$stmtItem) {
        throw new Exception("Prepare failed for event item update: " . $mysqli->error);
    }

    foreach ($payload['events'] as $event) {
        $event_id = intval($event['event_id']);
        $event_name = htmlspecialchars($event['event_name']);
        $attendees = intval($event['attendees']);
        $event_subtotal = floatval($event['subtotal']);
        error_log("Updating event ID: $event_id, Name: $event_name, Attendees: $attendees, Subtotal: $event_subtotal");

        // Update event data
        $stmtEvent->bind_param("sidii", $event_name, $attendees, $event_subtotal, $event_id, $budget_id);
        if (!$stmtEvent->execute()) {
            throw new Exception("Event update failed: " . $stmtEvent->error);
        }

        // Update associated event items
        foreach ($event['items'] as $item) {
            $item_id = intval($item['item_id']);
            $item_name = htmlspecialchars($item['item_name']);
            $quantity = intval($item['quantity']);
            $cost_per_item = floatval($item['cost_per_item']);
            $total_cost = floatval($item['total_cost']);
            error_log("Updating item ID: $item_id, Name: $item_name, Quantity: $quantity, Cost: $cost_per_item, Total: $total_cost");

            $stmtItem->bind_param("siddi", $item_name, $quantity, $cost_per_item, $total_cost, $item_id, $event_id);
            if (!$stmtItem->execute()) {
                throw new Exception("Event item update failed: " . $stmtItem->error);
            }
        }
    }
    $stmtEvent->close();
    $stmtItem->close();

    // Update asset details
    $stmtAsset = $mysqli->prepare("UPDATE assets SET item_name = ?, quantity = ?, cost_per_item = ?, total_cost = ? WHERE id = ? AND budget_id = ?");
    if (!$stmtAsset) {
        throw new Exception("Prepare failed for asset update: " . $mysqli->error);
    }
    foreach ($payload['assets'] as $asset) {
        $asset_id = intval($asset['asset_id']);
        $item_name = htmlspecialchars($asset['item_name']);
        $quantity = intval($asset['quantity']);
        $cost_per_item = floatval($asset['cost_per_item']);
        $total_cost = floatval($asset['total_cost']);
        error_log("Updating asset ID: $asset_id, Name: $item_name, Quantity: $quantity, Cost: $cost_per_item, Total: $total_cost");

        $stmtAsset->bind_param("siddi", $item_name, $quantity, $cost_per_item, $total_cost, $asset_id, $budget_id);
        if (!$stmtAsset->execute()) {
            throw new Exception("Asset update failed: " . $stmtAsset->error);
        }
    }
    $stmtAsset->close();

    // Commit the transaction
    $mysqli->commit();
    error_log("Transaction committed successfully.");

    // Respond with success
    echo json_encode(['message' => 'Budget updated successfully', 'budget_id' => $budget_id]);
} catch (Exception $e) {
    // Roll back transaction on error
    $mysqli->rollback();
    http_response_code(500);
    error_log("Error during transaction: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to update budget: ' . $e->getMessage()]);
}

// Close the database connection
$mysqli->close();
?>
