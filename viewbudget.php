<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('./backend/db.php'); // Database connection
include('session.php'); // Include session management

// Check if the user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department_id'])) {
    http_response_code(401);
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit();
}

$department_id = $_SESSION['department_id']; // Get the department ID from the session

// Query to fetch events and related items
$events_query = "
    SELECT e.event_name, e.attendees, ei.item_name, ei.quantity, ei.cost_per_item, ei.total_cost, e.id AS event_id
    FROM events e
    LEFT JOIN event_items ei ON e.id = ei.event_id
    LEFT JOIN budgets b ON e.budget_id = b.id
    WHERE b.department_id = ?
    ORDER BY e.id
";

$assets_query = "
    SELECT a.item_name, a.quantity, a.cost_per_item, a.total_cost
    FROM assets a
    LEFT JOIN budgets b ON a.budget_id = b.id
    WHERE b.department_id = ?
";

// Prepare and execute the events query
$events_stmt = $mysqli->prepare($events_query);
if (!$events_stmt) {
    die("Events query preparation failed: " . $mysqli->error);
}
$events_stmt->bind_param("i", $department_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

// Process event results
$events = [];
while ($row = $events_result->fetch_assoc()) {
    $events[$row['event_id']]['event_name'] = $row['event_name'];
    $events[$row['event_id']]['attendees'] = $row['attendees'];
    $events[$row['event_id']]['items'][] = [
        'item_name' => $row['item_name'],
        'quantity' => $row['quantity'],
        'cost_per_item' => $row['cost_per_item'],
        'total_cost' => $row['total_cost']
    ];
}
$events_stmt->close(); // Close the events statement

// Prepare and execute the assets query
$assets_stmt = $mysqli->prepare($assets_query);
if (!$assets_stmt) {
    die("Assets query preparation failed: " . $mysqli->error);
}
$assets_stmt->bind_param("i", $department_id);
$assets_stmt->execute();
$assets_result = $assets_stmt->get_result();

// Process asset results
$assets = [];
while ($row = $assets_result->fetch_assoc()) {
    $assets[] = $row;
}
$assets_stmt->close(); // Close the assets statement
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Event and Asset Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

<?php include('templates/sidebar.php');?>
    <style>
        .section-title {
            font-weight: bold;
            font-size: 1.5rem;
            margin-top: 2rem;
        }
        .table-subtotal {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .grand-total {
            font-weight: bold;
            font-size: 1.25rem;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center">Budget</h1>

        <!-- Events Section -->
        <div class="section-title">Events</div>
        <?php
        $eventGrandTotal = 0;
        foreach ($events as $event) {
            $eventSubtotal = 0;
            echo "<div class='mt-4'><strong>Event:</strong> " . htmlspecialchars($event['event_name']) . "</div>";
            echo "<p><strong>Attendees:</strong> " . htmlspecialchars($event['attendees']) . "</p>";
            echo "<table class='table table-bordered table-striped'>";
            echo "<thead class='table-dark'>
                    <tr>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Cost per Item</th>
                        <th>Total Cost</th>
                    </tr>
                  </thead>";
            echo "<tbody>";
            foreach ($event['items'] as $item) {
                echo "<tr>
                        <td>" . htmlspecialchars($item['item_name']) . "</td>
                        <td>" . htmlspecialchars($item['quantity']) . "</td>
                        <td>" . number_format($item['cost_per_item'], 2) . "</td>
                        <td>" . number_format($item['total_cost'], 2) . "</td>
                      </tr>";
                $eventSubtotal += $item['total_cost'];
            }
            echo "<tr class='table-subtotal'>
                    <td colspan='3'>Subtotal</td>
                    <td>" . number_format($eventSubtotal, 2) . "</td>
                  </tr>";
            echo "</tbody>";
            echo "</table>";
            $eventGrandTotal += $eventSubtotal;
        }
        ?>

        <!-- Assets Section -->
        <div class="section-title">Assets</div>
        <?php
        $assetTotal = 0;
        if (count($assets) > 0) {
            echo "<table class='table table-bordered table-striped'>";
            echo "<thead class='table-dark'>
                    <tr>
                        <th>Asset Name</th>
                        <th>Quantity</th>
                        <th>Cost per Item</th>
                        <th>Total Cost</th>
                    </tr>
                  </thead>";
            echo "<tbody>";
            foreach ($assets as $asset) {
                echo "<tr>
                        <td>" . htmlspecialchars($asset['item_name']) . "</td>
                        <td>" . htmlspecialchars($asset['quantity']) . "</td>
                        <td>" . number_format($asset['cost_per_item'], 2) . "</td>
                        <td>" . number_format($asset['total_cost'], 2) . "</td>
                      </tr>";
                $assetTotal += $asset['total_cost'];
            }
            echo "<tr class='table-subtotal'>
                    <td colspan='3'>Subtotal</td>
                    <td>" . number_format($assetTotal, 2) . "</td>
                  </tr>";
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No assets found for this department.</p>";
        }
        ?>

        <!-- Grand Total -->
        <div class="grand-total">
            Grand Total: <?php echo number_format($eventGrandTotal + $assetTotal, 2); ?>
        </div>
    </div>
</body>
</html>
