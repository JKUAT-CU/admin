<?php
include('db.php'); // Database connection
include('../session.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Retrieve the department ID from the session
$department_id = $_SESSION['department_id'];

// Check if an open timeline exists
$timelineQuery = $mysqli->prepare("SELECT id FROM activity_timelines WHERE NOW() BETWEEN start_date AND end_date LIMIT 1");
if (!$timelineQuery) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare timeline query: ' . $mysqli->error]);
    exit();
}

$timelineQuery->execute();
$timelineQuery->bind_result($timeline_id);

if ($timelineQuery->fetch()) {
    $timelineQuery->close();
} else {
    $timelineQuery->close();
    http_response_code(400);
    echo json_encode(['error' => 'No open timeline available']);
    exit();
}

// Check if the department already has a budget in the open timeline
$existingBudgetQuery = $mysqli->prepare("SELECT id FROM budgets WHERE department_id = ? AND timeline_id = ?");
if (!$existingBudgetQuery) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare budget check query: ' . $mysqli->error]);
    exit();
}

$existingBudgetQuery->bind_param("ii", $department_id, $timeline_id);
$existingBudgetQuery->execute();
$existingBudgetQuery->bind_result($budget_id);

if ($existingBudgetQuery->fetch()) {
    $existingBudgetQuery->close();
    http_response_code(400);
    echo json_encode(['error' => 'A budget already exists for this department during the current timeline']);
    exit();
}
$existingBudgetQuery->close();

// Process the incoming JSON payload
$inputData = file_get_contents('php://input');
if (!$inputData) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit();
}

$payload = json_decode($inputData, true);

// Validate required fields
if (
    !isset($payload['department_name'], $payload['date'], $payload['events'], $payload['assets'], $payload['grand_total'])
    || !is_array($payload['events'])
    || !is_array($payload['assets'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data structure']);
    exit();
}

// Prepare data for saving
$department_name = htmlspecialchars($payload['department_name']);
$date = htmlspecialchars($payload['date']);
$grand_total = floatval($payload['grand_total']);

// Begin database transaction
$mysqli->begin_transaction();
try {
    // Save the main budget record
    $stmt = $mysqli->prepare("INSERT INTO budgets (department_id, timeline_id, total_amount) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Failed to prepare budget insert query: ' . $mysqli->error);
    }
    $stmt->bind_param("iid", $department_id, $timeline_id, $grand_total);
    $stmt->execute();
    $budget_id = $stmt->insert_id;
    $stmt->close();

    // Save event details
    $stmtEvent = $mysqli->prepare("INSERT INTO events (budget_id, event_name, attendees, subtotal) VALUES (?, ?, ?, ?)");
    $stmtItem = $mysqli->prepare("INSERT INTO event_items (event_id, item_name, quantity, cost_per_item, total_cost) VALUES (?, ?, ?, ?, ?)");

    foreach ($payload['events'] as $event) {
        $event_name = htmlspecialchars($event['event_name']);
        $attendees = intval($event['attendees']);
        $event_subtotal = floatval($event['subtotal']);

        $stmtEvent->bind_param("isid", $budget_id, $event_name, $attendees, $event_subtotal);
        $stmtEvent->execute();
        $event_id = $stmtEvent->insert_id;

        foreach ($event['items'] as $item) {
            $item_name = htmlspecialchars($item['item_name']);
            $quantity = intval($item['quantity']);
            $cost_per_item = floatval($item['cost_per_item']);
            $total_cost = floatval($item['total_cost']);

            $stmtItem->bind_param("isidd", $event_id, $item_name, $quantity, $cost_per_item, $total_cost);
            $stmtItem->execute();
        }
    }
    $stmtEvent->close();
    $stmtItem->close();

    // Save asset details
    $stmtAsset = $mysqli->prepare("INSERT INTO assets (budget_id, item_name, quantity, cost_per_item, total_cost) VALUES (?, ?, ?, ?, ?)");
    foreach ($payload['assets'] as $asset) {
        $item_name = htmlspecialchars($asset['item_name']);
        $quantity = intval($asset['quantity']);
        $cost_per_item = floatval($asset['cost_per_item']);
        $total_cost = floatval($asset['total_cost']);

        $stmtAsset->bind_param("isidd", $budget_id, $item_name, $quantity, $cost_per_item, $total_cost);
        $stmtAsset->execute();
    }
    $stmtAsset->close();

    // Commit the transaction
    $mysqli->commit();

    echo json_encode(['message' => 'Budget submitted successfully', 'budget_id' => $budget_id]);
} catch (Exception $e) {
    // Roll back transaction on error
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save budget: ' . $e->getMessage()]);
}

$mysqli->close();
?>
