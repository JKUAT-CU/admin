<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Budget Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
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

    <div id="semesters-container"></div>

    <div class="text-right mb-5">
        <h4>Grand Total: <span id="grandTotal">0.00</span></h4>
        <button class="btn btn-success" onclick="submitBudget()">Submit Budget</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    // Semesters Data
    const semesters = ["January-April", "May-August", "September-December"];

    $(document).ready(() => {
        renderSemesters();
    });

    function renderSemesters() {
        const container = $('#semesters-container');
        container.empty();

        semesters.forEach(semester => {
            const semesterId = semester.replace(/\s+/g, '-');

            const semesterHtml = `
                <div class="semester-budget mb-5" id="${semesterId}">
                    <h3>Budget for ${semester}</h3>

                    <!-- Events Table -->
                    <div class="table-responsive mb-5">
                        <h4>Events</h4>
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
                            <tbody id="eventsTableBody-${semesterId}"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <button class="btn btn-add-event" onclick="addEvent('${semesterId}')">+ Add Another Event</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Assets Table -->
                    <div class="table-responsive mb-5">
                        <h4>Assets</h4>
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
                            <tbody id="assetsTableBody-${semesterId}"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <button class="btn btn-add-asset" onclick="addAsset('${semesterId}')">+ Add Another Asset</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>`;

            container.append(semesterHtml);
        });
    }

    function addEvent(semesterId) {
        const eventId = `event-${Date.now()}`;
        const newRow = `
            <tr class="event-header-row" data-event-id="${eventId}">
                <td><input type="text" class="form-control event-name" placeholder="Event Name" required></td>
                <td><input type="number" class="form-control event-attendees" placeholder="Attendees" min="1" required></td>
                <td><input type="text" class="form-control item-name" placeholder="Item Name" required></td>
                <td><input type="number" class="form-control item-quantity" placeholder="Quantity" min="1" required></td>
                <td><input type="number" class="form-control item-cost" placeholder="Cost per Item" min="0.01" step="0.01" required></td>
                <td><span class="item-total">0.00</span></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button></td>
            </tr>`;

        $(`#eventsTableBody-${semesterId}`).append(newRow);
    }

    function addAsset(semesterId) {
        const newRow = `
            <tr class="asset-item-row">
                <td><input type="text" class="form-control asset-name" placeholder="Item Name" required></td>
                <td><input type="number" class="form-control asset-quantity" placeholder="Quantity" min="1" required></td>
                <td><input type="number" class="form-control asset-cost" placeholder="Cost per Item" min="0.01" step="0.01" required></td>
                <td><span class="asset-total">0.00</span></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button></td>
            </tr>`;

        $(`#assetsTableBody-${semesterId}`).append(newRow);
    }

    function removeRow(button) {
        $(button).closest('tr').remove();
        updateTotals();
    }

    function updateTotals() {
        let grandTotal = 0;

        // Calculate Events Total
        semesters.forEach(semester => {
            const semesterId = semester.replace(/\s+/g, '-');
            let semesterTotal = 0;

            $(`#eventsTableBody-${semesterId} .event-header-row`).each(function () {
                const qty = parseFloat($(this).find('.item-quantity').val()) || 0;
                const cost = parseFloat($(this).find('.item-cost').val()) || 0;
                const total = qty * cost;

                $(this).find('.item-total').text(total.toFixed(2));
                semesterTotal += total;
            });

            grandTotal += semesterTotal;
        });

        // Calculate Assets Total
        semesters.forEach(semester => {
            const semesterId = semester.replace(/\s+/g, '-');
            let semesterAssetTotal = 0;

            $(`#assetsTableBody-${semesterId} .asset-item-row`).each(function () {
                const qty = parseFloat($(this).find('.asset-quantity').val()) || 0;
                const cost = parseFloat($(this).find('.asset-cost').val()) || 0;
                const total = qty * cost;

                $(this).find('.asset-total').text(total.toFixed(2));
                semesterAssetTotal += total;
            });

            grandTotal += semesterAssetTotal;
        });

        $('#grandTotal').text(grandTotal.toFixed(2));
    }

    $(document).on('input', '.item-quantity, .item-cost, .asset-quantity, .asset-cost', updateTotals);

    function submitBudget() {
        alert('Budget submission feature is under development.');
    }
</script>
</body>
</html>
