<?php
require_once 'db.php';


function handleBudgetSubmission($input)
{
    global $mysqli;

    // Validate required fields
    if (!isset($input['semester'], $input['grandTotal'], $input['assets'], $input['events'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float)$input['grandTotal'];
    $assets = json_encode($input['assets']);
    $events = json_encode($input['events']);

    // Prepare and execute SQL
    $query = "INSERT INTO budgets (semester, grand_total, assets, events) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error']);
        exit;
    }

    $stmt->bind_param('sdss', $semester, $grandTotal, $assets, $events);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Budget submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to submit budget']);
    }

    $stmt->close();
}
?>
