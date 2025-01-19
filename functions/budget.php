<?php
require_once 'db.php';

function handleBudgetSubmission($input)
{
    global $mysqli;

    header('Content-Type: application/json'); // Ensure JSON response for all cases

    // Validate required fields
    if (!isset($input['semester'], $input['grandTotal'], $input['assets'], $input['events'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $semester = $mysqli->real_escape_string($input['semester']);
    $grandTotal = (float)$input['grandTotal'];

    // Validate assets and events JSON encoding
    $assets = json_encode($input['assets']);
    $events = json_encode($input['events']);

    if ($assets === false || $events === false) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid data format for assets or events']);
        exit;
    }

    // Prepare and execute SQL
    $query = "INSERT INTO budgets (semester, grand_total, assets, events) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to prepare database query']);
        exit;
    }

    $stmt->bind_param('sdss', $semester, $grandTotal, $assets, $events);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Budget submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to submit budget. Error: ' . $stmt->error]);
    }

    $stmt->close();
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
