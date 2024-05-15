<?php

// Include the database connection file
include "db.php";
include "header.php";
include "image.php";
?>
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
        </div>
    </div>
</div>
<!-- Yearly Expense Card -->
<div class="col-md-6 mb-4 stretch-card transparent">
    <div class="card card-dark-blue">
        <div class="card-body">
            <p class="mb-4">Budgeted Value</p>
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

            // echo "<p class='fs-30 mb-2'>$yearlyExpense</p>"; // Output yearlyExpense
            ?>

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
            <p class="mb-4">Budgeted Value</p>
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

            // echo "<p class='fs-30 mb-2'>" . ($semesterExpense != "" ? $semesterExpense : 0) . "</p>";

            ?>

        </div>
    </div>
</div>
</div>
</div>
</div>

<?php

// Include the database connection file
include "db.php";

// Function to retrieve yearly collection for an account number
function getYearlyCollection($conn, $accountNumber) {
    $sql = "SELECT SUM(TransAmount) AS yearlyCollection FROM accounts WHERE YEAR(TransTime) = YEAR(CURDATE()) AND BillRefNumber = '$accountNumber'";
    $result = $conn->query($sql);

    $yearlyCollection = 0; // Initialize yearlyCollection variable

    if ($result->num_rows > 0) {
        // Fetch yearlyCollection value
        $row = $result->fetch_assoc();
        $yearlyCollection = $row["yearlyCollection"];
    }

    return $yearlyCollection;
}

// Function to retrieve semester collection for an account number
function getSemesterCollection($conn, $accountNumber) {
    // Get the current month
    $current_month = date('m');

    // Determine the current semester based on the current month
    if ($current_month >= 9 && $current_month <= 12) {
        // First semester (1st September to 31st December)
        $start_date = date('Y') . '0901';
        $end_date = date('Y') . '1231';
    } elseif ($current_month >= 1 && $current_month <= 4) {
        // Second semester (1st January to 30th April)
        $start_date = date('Y') . '0101';
        $end_date = date('Y') . '0430';
    } else {
        // Third semester (1st May to 31st August)
        $start_date = date('Y') . '0501';
        $end_date = date('Y') . '0831';
    }

    $sql = "SELECT SUM(TransAmount) AS semesterCollection FROM accounts WHERE TransTime BETWEEN '$start_date' AND '$end_date' AND BillRefNumber = '$accountNumber'";
    $result = $conn->query($sql);

    $semesterCollection = 0; // Initialize semesterCollection variable

    if ($result->num_rows > 0) {
        // Fetch semester collection value
        $row = $result->fetch_assoc();
        $semesterCollection = $row["semesterCollection"];
    }

    return $semesterCollection;
}

?>
<?php

// File path for storing account numbers JSON
$filePath = 'accounts.json';

// Function to load account numbers from JSON file
function loadAccountNumbers($filePath) {
    if (file_exists($filePath)) {
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    } else {
        return array(); // Return an empty array if file doesn't exist
    }
}

// Load account numbers from JSON file
$validAccountNumbers = loadAccountNumbers($filePath);
?>
<div class="col-md-6 grid-margin transparent">
    <div class="row">
        <?php foreach ($validAccountNumbers as $accountNumber) : ?>
            <div class="col-md-6 mb-4 stretch-card transparent">
                <div class="card card-tale">
                    <div class="card-body">
                        <h4 class="mb-4"><?php echo ucfirst($accountNumber); ?></h4>
                        <p>Yearly Collection</p>
                        <?php $yearlyCollection = getYearlyCollection($conn, $accountNumber); ?>
                        <p class="fs-30 mb-2"><?php echo $yearlyCollection == 0 ? '0' : $yearlyCollection; ?></p>

                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4 stretch-card transparent">
                <div class="card card-light-blue">
                    <div class="card-body">
                        <h4 class="mb-4"><?php echo ucfirst($accountNumber); ?></h4>
                        <p>Semester Collection</p>
                        <?php $semesterCollection = getSemesterCollection($conn, $accountNumber); ?>
                        <p class="fs-30 mb-2"><?php echo $semesterCollection == 0 ? '0' : $semesterCollection; ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php

// Close the database connection
$conn->close();

?>
<?php include "footer.php" ?>
