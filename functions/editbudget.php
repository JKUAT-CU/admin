
<?php
// Function to fetch budgets by department_id
function fetchBudgetsByDepartment($departmentId) {
    global $db; // Use the global database connection

    if (!$departmentId) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'department_id is required']);
        return;
    }

    $query = "SELECT id, name, total, details FROM budgets WHERE department_id = ?";
    $stmt = $db->prepare($query);

    if ($stmt) {
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        $budgets = [];
        while ($row = $result->fetch_assoc()) {
            $budgets[] = $row;
        }

        http_response_code(200); // OK
        echo json_encode(['budgets' => $budgets]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to fetch budgets', 'error' => $db->error]);
    }
}

// Function to update specific budgets by budget_id
function updateSpecificBudgets($input) {
    global $db; // Use the global database connection

    if (!isset($input['budgets']) || !is_array($input['budgets'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid input data']);
        return;
    }

    foreach ($input['budgets'] as $budget) {
        if (empty($budget['id']) || empty($budget['name']) || empty($budget['total']) || !is_numeric($budget['total'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Invalid budget data']);
            return;
        }

        $id = $budget['id'];
        $name = $budget['name'];
        $total = $budget['total'];
        $details = isset($budget['details']) ? json_encode($budget['details']) : null;

        $query = "UPDATE budgets SET name = ?, total = ?, details = ? WHERE id = ?";
        $stmt = $db->prepare($query);

        if ($stmt) {
            $stmt->bind_param('sdsi', $name, $total, $details, $id);

            if (!$stmt->execute()) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['message' => 'Failed to update budget', 'error' => $stmt->error]);
                return;
            }

            $stmt->close();
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['message' => 'Failed to prepare statement', 'error' => $db->error]);
            return;
        }
    }

    http_response_code(200); // OK
    echo json_encode(['message' => 'Budgets updated successfully']);
}
?>