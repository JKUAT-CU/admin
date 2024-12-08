<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('accesscontrol.php'); // Validate user access
include('backend/db.php');    // Include database connection

// Validate session parameters
if (!isset($_SESSION['department_id']) || !isset($_SESSION['timeline']['id'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Required parameters are missing.']));
}

// Extract session variables
$department_id = (int)$_SESSION['department_id'];
$timeline_id = (int)$_SESSION['timeline']['id'];

// Initialize response data
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
    if (!$events_stmt) {
        throw new Exception("Failed to prepare events query: " . $mysqli->error);
    }

    $events_stmt->bind_param("i", $department_id);
    $events_stmt->execute();

    // Bind result columns
    $events_stmt->bind_result(
        $event_id, $event_name, $attendees,
        $item_id, $item_name, $quantity, $cost_per_item, $total_cost
    );

    // Process rows
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
    if (!$assets_stmt) {
        throw new Exception("Failed to prepare assets query: " . $mysqli->error);
    }

    $assets_stmt->bind_param("i", $department_id);
    $assets_stmt->execute();

    // Bind result columns for assets
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

    // Return response data as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500);
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

// Close database connection
$mysqli->close();
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
            <tfoot>
                <tr>
                    <td colspan="7" class="text-center">
                        <button class="btn btn-add-event" onclick="addEventRow()">+ Add Another Event</button>
                    </td>
                </tr>
            </tfoot>
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
            <tfoot>
                <tr>
                    <td colspan="5" class="text-center">
                        <button class="btn btn-add-asset" onclick="addAssetRow()">+ Add Another Asset</button>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Grand Total -->
    <div class="text-right mb-5">
        <h4>Grand Total: <span id="grandTotal">0.00</span></h4>
        <button class="btn btn-success" onclick="submitBudget()">Submit Budget</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<script>
    // Add Event Row
    function addEventRow() {
        const eventId = `event-${Date.now()}`;
        const newRow = `
            <tr class="event-header-row" data-event-id="${eventId}">
                <td colspan="2">
                    <input type="text" class="form-control event-name" placeholder="Event Name" required>
                </td>
                <td>
                    <input type="number" class="form-control event-attendees" placeholder="Attendees" min="1" required>
                </td>
                <td colspan="3" class="text-right">
                    <button type="button" class="btn btn-sm btn-add-item" onclick="addEventItemRow('${eventId}')">+ Add Item</button>
                </td>
            </tr>
            <tr class="event-item-row" data-event-id="${eventId}">
                <td colspan="2"></td>
                <td><input type="text" class="form-control item-name" placeholder="Item Name" required></td>
                <td><input type="number" class="form-control item-quantity" placeholder="Quantity" min="1" required></td>
                <td><input type="number" class="form-control item-cost" placeholder="Cost per Item" min="0.01" step="0.01" required></td>
                <td><span class="item-total">0.00</span></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item-btn" onclick="removeRow(this)">Remove</button>
                </td>
            </tr>
            <tr class="event-subtotal-row" data-event-id="${eventId}">
                <td colspan="5" class="text-right"><strong>Event Subtotal:</strong></td>
                <td><span class="event-subtotal">0.00</span></td>
                <td></td>
            </tr>`;
        $('#eventsTableBody').append(newRow);
    }

    // Add Asset Row
    function addAssetRow() {
        const newRow = `
            <tr class="asset-item-row">
                <td><input type="text" class="form-control" placeholder="Item Name" required></td>
                <td><input type="number" class="form-control asset-quantity" placeholder="Quantity" min="1" required></td>
                <td><input type="number" class="form-control asset-cost" placeholder="Cost per Item" min="0.01" step="0.01" required></td>
                <td><span class="asset-total">0.00</span></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
                </td>
            </tr>`;
        $('#assetsTableBody').append(newRow);
        updateTotals();
    }

    // Update Event and Asset Subtotal
    function updateTotals() {
        let eventsSubtotal = 0;
        $('#eventsTableBody').find('.event-header-row').each(function () {
            const eventId = $(this).data('event-id');
            let eventTotal = 0;
            $(`.event-item-row[data-event-id="${eventId}"]`).each(function () {
                const qty = parseFloat($(this).find('.item-quantity').val()) || 0;
                const cost = parseFloat($(this).find('.item-cost').val()) || 0;
                const total = qty * cost;
                $(this).find('.item-total').text(total.toFixed(2));
                eventTotal += total;
            });
            $(`.event-subtotal-row[data-event-id="${eventId}"] .event-subtotal`).text(eventTotal.toFixed(2));
            eventsSubtotal += eventTotal;
        });

        let assetsSubtotal = 0;
        $('#assetsTableBody .asset-item-row').each(function () {
            const qty = parseFloat($(this).find('.asset-quantity').val()) || 0;
            const cost = parseFloat($(this).find('.asset-cost').val()) || 0;
            const total = qty * cost;
            $(this).find('.asset-total').text(total.toFixed(2));
            assetsSubtotal += total;
        });

        $('#grandTotal').text((eventsSubtotal + assetsSubtotal).toFixed(2));
    }

    // Remove Row (Event Item or Asset)
    function removeRow(button) {
        $(button).closest('tr').remove();
        updateTotals();
    }

    // Submit Budget
    function submitBudget() {
        const grandTotal = $('#grandTotal').text();
        alert('Budget submitted successfully! Grand Total: ' + grandTotal);
    }
</script>

</body>
</html>
