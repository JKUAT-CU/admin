<?php

include('session.php');
include('./backend/db.php'); // Include database connection

// Restrict access to superadmin or admin roles
$allowedRoles = ['superadmin', 'admin'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    header("Location: no_access.php");
    exit();
}

// Fetch department-specific events and assets
$department_id = $_SESSION['department_id'];

// Events Query
$events_query = "
    SELECT e.id AS event_id, e.event_name, e.attendees, ei.id AS item_id, ei.item_name, ei.quantity, ei.cost_per_item, ei.total_cost
    FROM events e
    LEFT JOIN event_items ei ON e.id = ei.event_id
    LEFT JOIN budgets b ON e.budget_id = b.id
    WHERE b.department_id = ?
    ORDER BY e.id";
$events_stmt = $mysqli->prepare($events_query);
$events_stmt->bind_param("i", $department_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

// Group event data
$events = [];
while ($row = $events_result->fetch_assoc()) {
    $events[$row['event_id']]['event_name'] = $row['event_name'];
    $events[$row['event_id']]['attendees'] = $row['attendees'];
    $events[$row['event_id']]['items'][] = $row;
}

// Assets Query
$assets_query = "
    SELECT a.id AS asset_id, a.item_name, a.quantity, a.cost_per_item, a.total_cost
    FROM assets a
    LEFT JOIN budgets b ON a.budget_id = b.id
    WHERE b.department_id = ?";
$assets_stmt = $mysqli->prepare($assets_query);
$assets_stmt->bind_param("i", $department_id);
$assets_stmt->execute();
$assets_result = $assets_stmt->get_result();
$assets = $assets_result->fetch_all(MYSQLI_ASSOC);

// Close Statements
$events_stmt->close();
$assets_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Budget</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <link href="assets/css/styles.css" rel="stylesheet">

<?php include('templates/sidebar.php');?>
    <style>
        :root {
            --primary-color: #800000;
            --secondary-color: #089000;
        }

        body {
            background-color: #f8f9fa;
            margin-bottom:4vh;
        }

        .btn-primary, .btn-add-event, .btn-add-asset {
            background-color: var(--primary-color);
            color: white;
        }

        .table thead {
            background-color: var(--secondary-color);
            color: white;
        }

        .total-row {
            font-weight: bold;
        }

        .grand-total {
            width: fit-content;
            background-color: var(--primary-color);
            color: white;
            padding: 10px;
            margin-top: 20px;
            font-size: 1.25rem;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Edit Budget</h2>

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
            </tr>
        </thead>
        <tbody id="eventsTableBody">
            <?php foreach ($events as $event_id => $event): ?>
                <!-- Event Name and Attendees Row -->
                <tr>
                    <td colspan="1"><?php echo htmlspecialchars($event['event_name']); ?></td>
                    <td colspan="1">
                        <input type="number" class="form-control" name="attendees[<?php echo $event_id; ?>]" value="<?php echo htmlspecialchars($event['attendees']); ?>" >
                    </td>
                    <td></td> <!-- Empty cell for event name -->
                    <td></td> <!-- Empty cell for attendees -->
                    <td></td> <!-- Empty cell for event name -->
                    <td></td> <!-- Empty cell for attendees -->
                </tr>
                <!-- Item Rows -->
                <?php foreach ($event['items'] as $item): ?>
                    <tr data-item-id="<?php echo $item['item_id']; ?>">
                        <td></td> <!-- Empty cell for event name -->
                        <td></td> <!-- Empty cell for attendees -->
                        <td>
                            <input type="text" class="form-control" name="item_name[<?php echo $item['item_id']; ?>]" value="<?php echo htmlspecialchars($item['item_name']); ?>">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="quantity[<?php echo $item['item_id']; ?>]" value="<?php echo htmlspecialchars($item['quantity']); ?>" onchange="updateTotals()">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="cost_per_item[<?php echo $item['item_id']; ?>]" value="<?php echo htmlspecialchars($item['cost_per_item']); ?>" onchange="updateTotals()">
                        </td>
                        <td class="item-total"><?php echo htmlspecialchars($item['total_cost']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <!-- Subtotal Row -->
                <tr class="total-row">
                    <td colspan="5">Subtotal</td>
                    <td class="event-subtotal">0.00</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <div class="table-responsive mb-5">
        <h3>Assets</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Cost per Item</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody id="assetsTableBody">
                <?php foreach ($assets as $asset): ?>
                    <tr data-asset-id="<?php echo $asset['asset_id']; ?>">
                        <td>
                            <input type="text" class="form-control" name="asset_name[<?php echo $asset['asset_id']; ?>]" value="<?php echo htmlspecialchars($asset['item_name']); ?>">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="asset_quantity[<?php echo $asset['asset_id']; ?>]" value="<?php echo htmlspecialchars($asset['quantity']); ?>" onchange="updateTotals()">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="asset_cost_per_item[<?php echo $asset['asset_id']; ?>]" value="<?php echo htmlspecialchars($asset['cost_per_item']); ?>" onchange="updateTotals()">
                        </td>
                        <td class="asset-total"><?php echo htmlspecialchars($asset['total_cost']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Grand Total -->
    <div colspan="1" class="grand-total" id="grandTotal">Grand Total: 0.00</div>

    <!-- Submit Button -->
    <div class="text-right">
        <button class="btn btn-success" onclick="submitBudget()">Save Changes and Generate PDF</button>
    </div>
</div>

<script>
    function updateTotals() {
        let grandTotal = 0;

        // Update event subtotals
        $('#eventsTableBody').children('tr').each(function () {
            if ($(this).hasClass('total-row')) {
                // Reset the subtotal before recalculating
                const eventSubtotalRow = $(this);
                let eventSubtotal = 0;

                // Calculate the subtotal for items above this row
                eventSubtotalRow.prevUntil('.total-row').each(function () {
                    const quantity = parseFloat($(this).find('[name^="quantity"]').val()) || 0;
                    const costPerItem = parseFloat($(this).find('[name^="cost_per_item"]').val()) || 0;
                    const totalCost = quantity * costPerItem;
                    $(this).find('.item-total').text(totalCost.toFixed(2));
                    eventSubtotal += totalCost;
                });

                // Update the subtotal for the event
                eventSubtotalRow.find('.event-subtotal').text(eventSubtotal.toFixed(2));
                grandTotal += eventSubtotal;
            }
        });

        // Update asset totals
        $('#assetsTableBody').children('tr').each(function () {
            const quantity = parseFloat($(this).find('[name^="asset_quantity"]').val()) || 0;
            const costPerItem = parseFloat($(this).find('[name^="asset_cost_per_item"]').val()) || 0;
            const totalCost = quantity * costPerItem;
            $(this).find('.asset-total').text(totalCost.toFixed(2));
            grandTotal += totalCost;
        });

        // Update grand total
        $('#grandTotal').text('Grand Total: ' + grandTotal.toFixed(2));
    }

    $(document).ready(function () {
        updateTotals();
    });

    async function submitBudget() {
    try {
        const events = [];
        const assets = [];
        let grandTotal = 0;

        // Collect event data
        $('#eventsTableBody').find('tr.event-header-row').each(function () {
            const eventId = $(this).data('event-id');
            const eventName = $(this).find('.event-name').val() || "Unnamed Event";
            const attendees = parseInt($(this).find('.event-attendees').val()) || 0;

            const items = [];
            let eventSubtotal = 0;

            $(`tr[data-event-id="${eventId}"].event-item-row`).each(function () {
                const itemId = $(this).data('item-id');
                const itemName = $(this).find('.item-name').val() || "Unnamed Item";
                const quantity = parseInt($(this).find('.item-quantity').val()) || 0;
                const costPerItem = parseFloat($(this).find('.item-cost').val()) || 0;
                const totalCost = quantity * costPerItem;

                items.push({ item_id: itemId, item_name: itemName, quantity, cost_per_item: costPerItem, total_cost: totalCost });
                eventSubtotal += totalCost;
            });

            events.push({ event_id: eventId, event_name: eventName, attendees, subtotal: eventSubtotal, items });
            grandTotal += eventSubtotal;
        });

        // Collect asset data
        $('#assetsTableBody').find('tr.asset-item-row').each(function () {
            const assetId = $(this).data('asset-id');
            const itemName = $(this).find('.asset-name').val() || "Unnamed Asset";
            const quantity = parseInt($(this).find('.asset-quantity').val()) || 0;
            const costPerItem = parseFloat($(this).find('.asset-cost').val()) || 0;
            const totalCost = quantity * costPerItem;

            assets.push({ asset_id: assetId, item_name: itemName, quantity, cost_per_item: costPerItem, total_cost: totalCost });
            grandTotal += totalCost;
        });

        // Prepare payload
        const payload = {
            budget_id: "<?php echo $_SESSION['budget_id']; ?>", // Ensure this is set in the session
            department_id: "<?php echo $_SESSION['department_id']; ?>",
            events,
            assets,
            grand_total: grandTotal
        };

        // Send payload to backend
        const response = await fetch('backend/update_budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (response.ok) {
            const result = await response.json();
            alert(result.message || "Budget updated successfully!");
            // Optionally regenerate the PDF here
            generatePdf(payload);
        } else {
            const error = await response.json();
            alert(error.error || "Failed to update budget.");
        }
    } catch (error) {
        console.error("Error submitting budget:", error);
        alert("An unexpected error occurred. Please try again.");
    }
}

function generatePdf(payload) {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF();

    // Add PDF content based on payload (use existing logic from previous implementation)
    pdf.text("Edited Budget", 10, 10);
    pdf.save("Edited_Budget.pdf");
}


    $(document).ready(function() {
        // Initialize totals
        updateTotals();
    });
    console.log(JSON.stringify(payload, null, 2));

</script>
</body>
</html>
