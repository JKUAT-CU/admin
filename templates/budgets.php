<div class="table-responsive mb-5">
    <!-- Events Table -->
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
        <tbody id="eventsTableBody<?php echo ucfirst($semesterId); ?>"></tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-center">
                    <button class="btn btn-add-event" onclick="addEventRow('eventsTableBody<?php echo ucfirst($semesterId); ?>')">+ Add Another Event</button>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="table-responsive mb-5">
    <!-- Assets Table -->
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
        <tbody id="assetsTableBody<?php echo ucfirst($semesterId); ?>"></tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-center">
                    <button class="btn btn-add-asset" onclick="addAssetRow('assetsTableBody<?php echo ucfirst($semesterId); ?>')">+ Add Another Asset</button>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
