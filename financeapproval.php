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
        // Create a new budget record (instead of updating the existing one)
        $query = "INSERT INTO finance_budgets (department_id, semester, grand_total, created_at) 
                  SELECT department_id, ?, ?, NOW() FROM budgets WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare budget insert query');
        }
    
        $stmt->bind_param('sdi', $semester, $grandTotal, $budget_id);
        $stmt->execute();
    
        // Get the ID of the newly inserted budget
        $newBudgetId = $stmt->insert_id;
        $stmt->close();
    
        // Update the status column of the provided budget_id to 'Finance Approved'
        $updateStatusQuery = "UPDATE budgets SET status = 'Finance Approved' WHERE id = ?";
        $updateStatusStmt = $mysqli->prepare($updateStatusQuery);
        if (!$updateStatusStmt) {
            throw new Exception('Failed to prepare status update query');
        }
    
        $updateStatusStmt->bind_param('i', $budget_id); // Use budget_id from the frontend
        $updateStatusStmt->execute();
        $updateStatusStmt->close();
    
        // Continue with inserting assets and events
        $assetQuery = "INSERT INTO finance_assets (budget_id, name, quantity, price) VALUES (?, ?, ?, ?)";
        $assetStmt = $mysqli->prepare($assetQuery);
        if (!$assetStmt) {
            throw new Exception('Failed to prepare asset insert query');
        }
    
        foreach ($assets as $asset) {
            $name = $mysqli->real_escape_string($asset['name']);
            $quantity = (int)$asset['quantity'];
            $price = (float)$asset['price'];
            $assetStmt->bind_param('isid', $newBudgetId, $name, $quantity, $price);
            $assetStmt->execute();
        }
        $assetStmt->close();
    
        $eventQuery = "INSERT INTO finance_events (budget_id, name, attendance) VALUES (?, ?, ?)";
        $eventStmt = $mysqli->prepare($eventQuery);
        if (!$eventStmt) {
            throw new Exception('Failed to prepare event insert query');
        }
    
        $eventItemQuery = "INSERT INTO finance_event_items (event_id, name, quantity, price) VALUES (?, ?, ?, ?)";
        $eventItemStmt = $mysqli->prepare($eventItemQuery);
        if (!$eventItemStmt) {
            throw new Exception('Failed to prepare event item insert query');
        }
    
        foreach ($events as $event) {
            $eventName = $mysqli->real_escape_string($event['name']);
            $attendance = (int)$event['attendance'];
            $eventStmt->bind_param('isi', $newBudgetId, $eventName, $attendance);
            $eventStmt->execute();
        
            // Get the ID of the newly inserted event
            $eventId = $eventStmt->insert_id;
        
            // Check if 'items' exists and is an array before iterating
            if (isset($event['items']) && is_array($event['items'])) {
                foreach ($event['items'] as $item) {
                    $itemName = $mysqli->real_escape_string($item['name']);
                    $itemQuantity = (int)$item['quantity'];
                    $itemPrice = (float)$item['price'];
                    $eventItemStmt->bind_param('isid', $eventId, $itemName, $itemQuantity, $itemPrice);
                    $eventItemStmt->execute();
                }
            }
        }
        
    
        $eventStmt->close();
        $eventItemStmt->close();
    
        // Commit the transaction
        $mysqli->commit();
    
        echo json_encode(['message' => 'Finance Approval Data Saved']);
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
