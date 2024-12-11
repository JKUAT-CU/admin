<?php
include('accesscontrol.php'); // Check for access control
include('backend/db.php');    // Database connection

// Validate and store the open timeline info in the session
if (isset($timelineState['timeline'])) {
    $_SESSION['timeline'] = $timelineState['timeline'];
}

// Validate `department_id` and `timeline_id` from the session
if (!isset($_SESSION['department_id']) || !isset($_SESSION['timeline']['id'])) {
    die("<script>alert('Error: Required parameters are missing.'); window.location.href='dashboard';</script>");
}

$department_id = (int)$_SESSION['department_id'];
$timeline_id = (int)$_SESSION['timeline']['id'];

// Prepare the query to check if an existing budget exists
$existingBudgetQuery = $mysqli->prepare("SELECT id FROM budgets WHERE department_id = ? AND timeline_id = ?");
if (!$existingBudgetQuery) {
    die("<script>alert('Error: Failed to prepare the database query.'); window.location.href='dashboard';</script>");
}

// Bind parameters and execute the query
$existingBudgetQuery->bind_param("ii", $department_id, $timeline_id);
if (!$existingBudgetQuery->execute()) {
    die("<script>alert('Error: Query execution failed.'); window.location.href='dashboard';</script>");
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

<?php include('templates/sidebar.php');?>
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
            <tbody id="eventsTableBody"></tbody>
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
            <tbody id="assetsTableBody"></tbody>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<script>
    // Add Event Row
    function addEventRow() {
        const eventId = `event-${Date.now()}`;
        const newRow = `
        <!-- Event Header Row -->
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
        <!-- Initial Item Row -->
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
        <!-- Event Subtotal Row -->
        <tr class="event-subtotal-row" data-event-id="${eventId}">
            <td colspan="5" class="text-right"><strong>Event Subtotal:</strong></td>
            <td><span class="event-subtotal">0.00</span></td>
            <td></td>
        </tr>`;
        $('#eventsTableBody').append(newRow);
    }

    // Add Additional Item Row for Event
    function addEventItemRow(eventId) {
        const newItemRow = `
        <tr class="event-item-row" data-event-id="${eventId}">
            <td colspan="2"></td>
            <td><input type="text" class="form-control item-name" placeholder="Item Name" required></td>
            <td><input type="number" class="form-control item-quantity" placeholder="Quantity" min="1" required></td>
            <td><input type="number" class="form-control item-cost" placeholder="Cost per Item" min="0.01" step="0.01" required></td>
            <td><span class="item-total">0.00</span></td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-item-btn" onclick="removeRow(this)">Remove</button>
            </td>
        </tr>`;
        $(`tr[data-event-id="${eventId}"].event-subtotal-row`).before(newItemRow);
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
        const subtotalRow = $('#assetsTableBody .asset-subtotal-row');
        if (subtotalRow.length) {
            subtotalRow.before(newRow);
        } else {
            const subtotalRowHtml = `
            <tr class="asset-subtotal-row">
                <td colspan="3" class="text-right"><strong>Assets Subtotal:</strong></td>
                <td class="assets-subtotal">0.00</td>
                <td></td>
            </tr>`;
            $('#assetsTableBody').append(newRow).append(subtotalRowHtml);
        }
        updateTotals();
    }

    // Update Asset Subtotal
    function updateAssetSubtotal() {
        let assetsSubtotal = 0;
        $('#assetsTableBody .asset-item-row').each(function () {
            const qty = parseFloat($(this).find('.asset-quantity').val()) || 0;
            const cost = parseFloat($(this).find('.asset-cost').val()) || 0;
            const total = qty * cost;
            $(this).find('.asset-total').text(total.toFixed(2));
            assetsSubtotal += total;
        });
        $('#assetsTableBody .assets-subtotal').text(assetsSubtotal.toFixed(2));
        return assetsSubtotal;
    }

    // Update Totals
    let debounceTimer;
    function updateTotals() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
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
            const assetsSubtotal = updateAssetSubtotal();
            $('#grandTotal').text((eventsSubtotal + assetsSubtotal).toFixed(2));
        }, 300);
    }

    // Remove Row
    function removeRow(button) {
        $(button).closest('tr').remove();
        updateTotals();
    }

    // Real-Time Update
    $(document).on('input', '.item-quantity, .item-cost, .asset-quantity, .asset-cost', updateTotals);

    // Submit Budget
    const payload = {
            department_name: departmentName,
            date,
            events: eventGroups,
            assets: assetsData,
            grand_total: grandTotal.toFixed(2)
        };

    // Submit Budget with Email and Backend Submission
    async function submitBudget() {
    try {
        // Fetch letterhead image
        const letterheadImage = await fetch("assets/images/letterhead.gif")
            .then(res => res.ok ? res.blob() : null)
            .then(blob => {
                if (blob) {
                    return new Promise(resolve => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result);
                        reader.readAsDataURL(blob);
                    });
                }
                return null;
            })
            .catch(() => null);

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();

        // Fetch department name from session
        const departmentName = "<?php echo isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'Department'; ?>";

        // Validate department name
        if (!departmentName || departmentName.trim() === "") {
            alert("Invalid department name. Please check your session settings.");
            return;
        }

        const currentYear = new Date().getFullYear();
        const date = new Date().toLocaleDateString();
        const eventGroups = [];
        let validationError = false;

        // Collect events data
        $('#eventsTableBody .event-header-row').each(function () {
            const eventId = $(this).data('event-id');
            const eventName = $(this).find('.event-name').val() || "Unnamed Event";
            const attendees = $(this).find('.event-attendees').val();

            if (isNaN(attendees) || parseInt(attendees) < 0) {
                alert(`Invalid attendee count for event: ${eventName}`);
                validationError = true;
                return false;
            }

            const items = [];
            $(`.event-item-row[data-event-id="${eventId}"]`).each(function () {
                const itemName = $(this).find('.item-name').val() || "Unnamed Item";
                const quantity = $(this).find('.item-quantity').val();
                const cost = $(this).find('.item-cost').val();

                if (isNaN(quantity) || parseInt(quantity) < 0) {
                    alert(`Invalid quantity for item: ${itemName} in event: ${eventName}`);
                    validationError = true;
                    return false;
                }
                if (isNaN(cost) || parseFloat(cost) < 0) {
                    alert(`Invalid cost for item: ${itemName} in event: ${eventName}`);
                    validationError = true;
                    return false;
                }

                const total = parseFloat($(this).find('.item-total').text()) || 0.0;
                items.push({ item_name: itemName, quantity: parseInt(quantity), cost_per_item: parseFloat(cost), total_cost: total });
            });

            const subtotal = items.reduce((sum, item) => sum + item.total_cost, 0).toFixed(2);

            eventGroups.push({
                event_name: eventName,
                attendees: parseInt(attendees),
                items,
                subtotal: parseFloat(subtotal)
            });
        });

        if (validationError) return;

        const assetsData = [];
        $('#assetsTableBody .asset-item-row').each(function () {
            const itemName = $(this).find('input').eq(0).val() || "Unnamed Asset";
            const quantity = $(this).find('.asset-quantity').val();
            const cost = $(this).find('.asset-cost').val();

            if (isNaN(quantity) || parseInt(quantity) < 0) {
                alert(`Invalid quantity for asset: ${itemName}`);
                validationError = true;
                return false;
            }
            if (isNaN(cost) || parseFloat(cost) < 0) {
                alert(`Invalid cost for asset: ${itemName}`);
                validationError = true;
                return false;
            }

            const total = parseFloat($(this).find('.asset-total').text()) || 0.0;
            assetsData.push({ item_name: itemName, quantity: parseInt(quantity), cost_per_item: parseFloat(cost), total_cost: total });
        });

        if (validationError) return;

        const assetSubtotal = assetsData.reduce((sum, item) => sum + item.total_cost, 0).toFixed(2);
        let grandTotal = parseFloat(assetSubtotal);

        // Calculate Grand Total from Events
        eventGroups.forEach(event => {
            grandTotal += parseFloat(event.subtotal);
        });

        // Define payload
        const payload = {
            department_name: departmentName,
            date,
            events: eventGroups,
            assets: assetsData,
            grand_total: grandTotal.toFixed(2)
        };

        // Save PDF
        pdf.save(`${departmentName}_Budget_for_${currentYear}.pdf`);

        // Submit data to backend
        const response = await fetch("backend/budget_submission.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        if (response.ok) {
            await sendEmail(payload); // Email the budget data
            alert("Budget submitted successfully!");
        } else {
            alert("Failed to submit budget. Please try again.");
        }

    } catch (error) {
        console.error("Error submitting budget:", error);
        alert("An unexpected error occurred. Please try again.");
    }
}

   </script>

</body>
</html>
