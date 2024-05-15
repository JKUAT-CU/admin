
<?php include "header.php";
include "image.php";?>  
    
<div class="col-md-6 grid-margin transparent">
<div class="row">
<div class="col-md-6 mb-4 stretch-card transparent">
    <div class="card card-tale">
        <div class="card-body">
            <p class="mb-4">Yearly Collection</p>
            <?php
            // Include the database connection file
            include "db.php";

            // Retrieve yearly collection from the accounts table
            $sql = "SELECT SUM(TransAmount) AS yearlyCollection FROM accounts WHERE YEAR(TransTime) = YEAR(CURDATE())";
            $result = $conn->query($sql);

            $yearlyCollection = 0; // Initialize yearlyCollection variable

            if ($result->num_rows > 0) {
                // Fetch yearlyCollection value
                $row = $result->fetch_assoc();
                $yearlyCollection = $row["yearlyCollection"];
            }

            echo "<p class='fs-30 mb-2'>$yearlyCollection</p>"; // Output yearlyCollection
            ?>
            <p>10.00% (30 days)</p>
        </div>
    </div>
</div>
<!-- Yearly Expense Card -->
<div class="col-md-6 mb-4 stretch-card transparent">
    <div class="card card-dark-blue">
        <div class="card-body">
            <p class="mb-4">Yearly Expense</p>
            <?php
            // Retrieve expenses for the current semester from the expenses table
            $sql = "SELECT SUM(CASE WHEN Amount < 0 THEN Amount ELSE 0 END) AS yearlyExpense
                    FROM Expenses";
            $result = $conn->query($sql);

            $semesterExpense = 0; // Initialize semesterExpense variable
            if ($result->num_rows > 0) {
                // Fetch yearlyExpense value
                $row = $result->fetch_assoc();
                $yearlyExpense = abs($row["yearlyExpense"]);
            }

            echo "<p class='fs-30 mb-2'>$yearlyExpense</p>"; // Output yearlyExpense
            ?>
            <p>22.00% (30 days)</p>
        </div>
    </div>
</div>
</div>
<div class="row">
<div class="col-md-6 mb-4 mb-lg-0 stretch-card transparent">
    <div class="card card-light-blue">
        <div class="card-body">
            <p class="mb-4">Semester Collection</p>
            <?php
            // Get the current month
            $current_month = date('m');

            // Determine the current semester based on the current month
            if ($current_month >= 9 && $current_month <= 12) {
                // First semester (1st September to 31st December)
                $start_date = date('Y') . '0901';
                $end_date = date('Y') . '1231';
                $semester_name = "First";
            } elseif ($current_month >= 1 && $current_month <= 4) {
                // Second semester (1st January to 30th April)
                $start_date = date('Y') . '0101';
                $end_date = date('Y') . '0430';
                $semester_name = "Second";
            } else {
                // Third semester (1st May to 31st August)
                $start_date = date('Y') . '0501';
                $end_date = date('Y') . '0831';
                $semester_name = "Third";
            }

            // Retrieve collection for the current semester from the accounts table
            $sql = "SELECT SUM(TransAmount) AS semesterCollection FROM accounts WHERE TransTime BETWEEN '$start_date' AND '$end_date'";
            $result = $conn->query($sql);

            $semesterCollection = 0; // Initialize semesterCollection variable

            if ($result->num_rows > 0) {
                // Fetch semester collection value
                $row = $result->fetch_assoc();
                $semesterCollection = $row["semesterCollection"];
            }

            echo "<p class='fs-30 mb-2'>$semesterCollection</p>"; // Output semesterCollection
            ?>
            
        </div>
    </div>
</div>
<!-- Semester Expense Card -->
<div class="col-md-6 stretch-card transparent">
    <div class="card card-light-danger">
        <div class="card-body">
            <p class="mb-4">Semester Expense</p>
            <?php
            
            // Retrieve collection for the current semester from the accounts table
            $sql = "SELECT SUM(Amount) AS semesterExpense FROM Expenses WHERE TransactionDate BETWEEN '$start_date' AND '$end_date'";
            $result = $conn->query($sql);

            $semesterExpense = 0; // Initialize semesterCollection variable

            if ($result->num_rows > 0) {
                // Fetch semester collection value
                $row = $result->fetch_assoc();
                $semesterExpense = $row["semesterExpense"];
            }

            echo "<p class='fs-30 mb-2'>" . ($semesterExpense != "" ? $semesterExpense : 0) . "</p>";

            ?>
            <p>0.22% (30 days)</p>
        </div>
    </div>
</div>
</div>
</div>
    </div>



    <div class="row">
      <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <p class="card-title">Order Details</p>
            <p class="font-weight-500">The total number of sessions within the date range. It is the period time a user is actively engaged with your website, page or app, etc</p>
            <div class="d-flex flex-wrap mb-5">
              <div class="me-5 mt-3">
                <p class="text-muted">Order value</p>
                <h3 class="text-primary fs-30 font-weight-medium">12.3k</h3>
              </div>
              <div class="me-5 mt-3">
                <p class="text-muted">Orders</p>
                <h3 class="text-primary fs-30 font-weight-medium">14k</h3>
              </div>
              <div class="me-5 mt-3">
                <p class="text-muted">Users</p>
                <h3 class="text-primary fs-30 font-weight-medium">71.56%</h3>
              </div>
              <div class="mt-3">
                <p class="text-muted">Downloads</p>
                <h3 class="text-primary fs-30 font-weight-medium">34040</h3>
              </div>
            </div>
            <canvas id="order-chart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <p class="card-title">Financial Report</p>
              <a href="#" class="text-info">View all</a>
            </div>
            <p class="font-weight-500">The total number of sessions within the date range. It is the period time a user is actively engaged with your website, page or app, etc</p>
            <div id="sales-chart-legend" class="chartjs-legend mt-4 mb-2"></div>
            <canvas id="sales-chart"></canvas>
          </div>
        </div>
      </div>
    </div>