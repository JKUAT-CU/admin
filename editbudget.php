<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('accesscontrol.php'); // Validate user access
include('backend/db.php');    // Include database connection

// Function to fetch budget data (events and assets)
function getBudgetData($mysqli, $department_id) {
    $response = [
        'events' => [],
        'assets' => [],
    ];

    try {
        // **Events Query**
        $events_query = "
            SELECT 
                e.id AS event_id, e.event_name, e.attendees, 
                ei.id AS item_id, ei.item_name, ei.quantity, ei.cost_per_item, ei.total_cost
            FROM 
                events e
            LEFT JOIN 
                event_items ei ON e.id = ei.event_id
            LEFT JOIN 
                budgets b ON e.budget_id = b.id
            WHERE 
                b.department_id = ?
            ORDER BY 
                e.id";
        $events_stmt = $mysqli->prepare($events_query);
        $events_stmt->bind_param("i", $department_id);
        $events_stmt->execute();
        $events_stmt->bind_result(
            $event_id, $event_name, $attendees,
            $item_id, $item_name, $quantity, $cost_per_item, $total_cost
        );

        $events = [];
        while ($events_stmt->fetch()) {
            if (!isset($events[$event_id])) {
                $events[$event_id] = [
                    'event_name' => $event_name,
                    'attendees' => $attendees,
                    'items' => [],
                ];
            }
            if ($item_id) {
                $events[$event_id]['items'][] = [
                    'item_id' => $item_id,
                    'item_name' => $item_name,
                    'quantity' => $quantity,
                    'cost_per_item' => $cost_per_item,
                    'total_cost' => $total_cost,
                ];
            }
        }
        $response['events'] = $events;
        $events_stmt->close();

        // **Assets Query**
        $assets_query = "
            SELECT 
                a.id AS asset_id, a.item_name, a.quantity, a.cost_per_item, a.total_cost
            FROM 
                assets a
            LEFT JOIN 
                budgets b ON a.budget_id = b.id
            WHERE 
                b.department_id = ?";
        $assets_stmt = $mysqli->prepare($assets_query);
        $assets_stmt->bind_param("i", $department_id);
        $assets_stmt->execute();
        $assets_stmt->bind_result($asset_id, $item_name, $quantity, $cost_per_item, $total_cost);

        $assets = [];
        while ($assets_stmt->fetch()) {
            $assets[] = [
                'asset_id' => $asset_id,
                'item_name' => $item_name,
                'quantity' => $quantity,
                'cost_per_item' => $cost_per_item,
                'total_cost' => $total_cost,
            ];
        }
        $response['assets'] = $assets;
        $assets_stmt->close();

    } catch (Exception $e) {
        error_log("Error fetching budget data: " . $e->getMessage());
    }

    return $response;
}

// If JSON data is requested
if (isset($_GET['fetch']) && $_GET['fetch'] === 'true') {
    if (!isset($_SESSION['department_id']) || !isset($_SESSION['timeline']['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Required parameters are missing.']));
    }

    $department_id = (int)$_SESSION['department_id'];
    $response = getBudgetData($mysqli, $department_id);

    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // Prevent further output
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Budget Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <?php include('templates/sidebar.php'); ?>
    <style>
        :root {
            --primary-color: #800000;
            --secondary-color: #089000;
        }

        body {
            background-color: #f8f9fa;
        }

        .btn-primary, .btn-add-event, .btn-add-asset {
            background-color: var(--primary-color);
            color: white;
        }

        .table thead {
            background-color: var(--secondary-color);
            color: white;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Budget Management</h2>
    
    <!-- Events Table -->
    <div class="table-responsive mb-5">
        <h3>Events</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Attendees</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Cost per Item</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="eventsTableBody">
                <!-- Existing event rows will be populated here -->
            </tbody>
        </table>
    </div>

    <!-- Assets Table -->
    <div class="table-responsive mb-5">
        <h3>Assets</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Cost per Item</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="assetsTableBody">
                <!-- Existing asset rows will be populated here -->
            </tbody>
        </table>
    </div>

    <!-- Grand Total -->
    <div class="text-right mb-5">
        <h4>Grand Total: <span id="grandTotal">0.00</span></h4>
        <button class="btn btn-success" onclick="submitBudget()">Submit Budget</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script>
    $(document).ready(function () {
        // Fetch and populate budget data on page load
        fetchBudgetData();
    });

    function fetchBudgetData() {
        $.ajax({
            url: '?fetch=true',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                populateTables(response);
            },
            error: function (error) {
                console.error('Error fetching budget data:', error);
                alert('Failed to load budget data.');
            }
        });
    }

    function populateTables(data) {
        const events = data.events || [];
        const assets = data.assets || [];

        // Populate events table
        const eventsTableBody = $('#eventsTableBody');
        eventsTableBody.empty(); // Clear existing rows
        events.forEach(event => {
            event.items.forEach(item => {
                const row = `
                    <tr>
                        <td>${event.event_name}</td>
                        <td>${event.attendees}</td>
                        <td>${item.item_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.cost_per_item.toFixed(2)}</td>
                        <td>${item.total_cost.toFixed(2)}</td>
                        <td><button class="btn btn-danger btn-sm">Delete</button></td>
                    </tr>`;
                eventsTableBody.append(row);
            });
        });

        // Populate assets table
        const assetsTableBody = $('#assetsTableBody');
        assetsTableBody.empty(); // Clear existing rows
        assets.forEach(asset => {
            const row = `
                <tr>
                    <td>${asset.item_name}</td>
                    <td>${asset.quantity}</td>
                    <td>${asset.cost_per_item.toFixed(2)}</td>
                    <td>${asset.total_cost.toFixed(2)}</td>
                    <td><button class="btn btn-danger btn-sm">Delete</button></td>
                </tr>`;
            assetsTableBody.append(row);
        });

        updateGrandTotal(data);
    }

    function updateGrandTotal(data) {
        let total = 0;

        // Calculate events total
        Object.values(data.events).forEach(event => {
            event.items.forEach(item => {
                total += parseFloat(item.total_cost);
            });
        });

        // Calculate assets total
        data.assets.forEach(asset => {
            total += parseFloat(asset.total_cost);
        });

        $('#grandTotal').text(total.toFixed(2));
    }

    function submitBudget() {
        alert('Budget submitted successfully!');
    }
</script>

</body>
</html>
