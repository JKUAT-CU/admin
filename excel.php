<?php
header('Content-Type: application/json');

require 'db.php';

// Fetch budget data from the database
function fetch_budget_data($mysqli) {
    $query = "
        SELECT 
            b.id AS budget_id, 
            d.name AS department_name, 
            b.semester, 
            b.grand_total, 
            COALESCE(fb.grand_total, 0) AS finance_approved_total,
            COALESCE(a.name, 'N/A') AS asset_name, 
            COALESCE(a.quantity, 0) AS asset_quantity, 
            COALESCE(a.price, 0) AS asset_price, 
            COALESCE(e.name, 'N/A') AS event_name, 
            COALESCE(e.attendance, 0) AS attendance, 
            COALESCE(ei.name, 'N/A') AS event_item_name, 
            COALESCE(ei.quantity, 0) AS event_item_quantity, 
            COALESCE(ei.price, 0) AS event_item_price
        FROM budgets b
        JOIN departments d ON b.department_id = d.id
        LEFT JOIN finance_budgets fb ON b.id = fb.id
        LEFT JOIN assets a ON b.id = a.budget_id
        LEFT JOIN events e ON b.id = e.budget_id
        LEFT JOIN event_items ei ON e.id = ei.event_id
        ORDER BY b.semester ASC, b.created_at DESC;
    ";

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare statement: ' . $mysqli->error]);
        exit;
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
        exit;
    }

    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($data);
    exit;
}

// Execute function and return JSON
fetch_budget_data($mysqli);
?>
