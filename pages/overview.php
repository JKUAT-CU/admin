<?php

// Include the database connection file
include "db.php";
include "header.php";
include "image.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Data</title>
<!-- Bootstrap CSS -->
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<!-- Tailwind CSS -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.0.7/af-2.7.0/datatables.min.css" rel="stylesheet">

<script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.0.7/af-2.7.0/datatables.min.js"></script>
<style>
    .table-container {
        overflow-x: auto;
    }
</style>
</head>

<div class="col-md-6 grid-margin transparent">
<div class="row">
<div class="col-md-6 mb-4 stretch-card transparent">
    <div class="card card-tale">
        <div class="card-body">
            <p class="mb-4">Yearly Collection</p>
            <?php

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
// Function to retrieve yearly collection for an account number
function getYearlyCollection($conn, $accountNumber) {
    $currentYear = date('Y');
    $currentMonth = date('m');

    // Determine the financial year based on the current date
    if ($currentMonth >= 9) {
        $startYear = $currentYear;
        $endYear = $currentYear + 1;
    } else {
        $startYear = $currentYear - 1;
        $endYear = $currentYear;
    }

    $start_date = $startYear . '0901';
    $end_date = $endYear . '0831';

    $sql = "SELECT SUM(TransAmount) AS yearlyCollection 
            FROM accounts 
            WHERE TransTime BETWEEN '$start_date' AND '$end_date' 
            AND BillRefNumber = '$accountNumber'";
    $result = $conn->query($sql);

    $yearlyCollection = 0;

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $yearlyCollection = $row["yearlyCollection"];
    }

    return $yearlyCollection;
}

// Function to retrieve semester collection for an account number
function getSemesterCollection($conn, $accountNumber) {
    $currentMonth = date('m');
    $currentYear = date('Y');

    if ($currentMonth >= 9 && $currentMonth <= 12) {
        $start_date = $currentYear . '0901';
        $end_date = $currentYear . '1231';
    } elseif ($currentMonth >= 1 && $currentMonth <= 4) {
        $start_date = $currentYear . '0101';
        $end_date = $currentYear . '0430';
    } else {
        $start_date = $currentYear . '0501';
        $end_date = $currentYear . '0831';
    }

    $sql = "SELECT SUM(TransAmount) AS semesterCollection 
            FROM accounts 
            WHERE TransTime BETWEEN '$start_date' AND '$end_date' 
            AND BillRefNumber = '$accountNumber'";
    $result = $conn->query($sql);

    $semesterCollection = 0;

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $semesterCollection = $row["semesterCollection"];
    }

    return $semesterCollection;
}

// Function to retrieve monthly collection for an account number
function getMonthlyCollection($conn, $accountNumber) {
    $sql = "SELECT SUM(TransAmount) AS monthlyCollection, MONTH(TransTime) AS month 
            FROM accounts 
            WHERE YEAR(TransTime) = YEAR(CURDATE()) 
            AND BillRefNumber = '$accountNumber' 
            GROUP BY MONTH(TransTime)";
    $result = $conn->query($sql);

    $monthlyCollection = array_fill(1, 12, 0);

    while ($row = $result->fetch_assoc()) {
        $monthlyCollection[(int)$row["month"]] = $row["monthlyCollection"];
    }

    return $monthlyCollection;
}

// Function to retrieve weekly collection for an account number
function getWeeklyCollection($conn, $accountNumber) {
    $sql = "SELECT SUM(TransAmount) AS weeklyCollection, YEARWEEK(TransTime, 0) AS week 
            FROM accounts 
            WHERE YEAR(TransTime) = YEAR(CURDATE()) 
            AND BillRefNumber = '$accountNumber' 
            GROUP BY YEARWEEK(TransTime, 0)";
    $result = $conn->query($sql);

    $weeklyCollection = [];

    while ($row = $result->fetch_assoc()) {
        $weekNumber = (int)$row["week"];
        $firstDayOfWeek = date('Y-m-d', strtotime("Sunday +".($weekNumber-1)." weeks", strtotime(date('Y')."-01-01")));
        $weeklyCollection[$firstDayOfWeek] = $row["weeklyCollection"];
    }

    return $weeklyCollection;
}

// File path for storing account numbers JSON
$filePath = 'accounts.json';

// Function to load account numbers from JSON file
function loadAccountNumbers($filePath) {
    if (file_exists($filePath)) {
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    } else {
        return array();
    }
}


// Load account numbers from JSON file
$validAccountNumbers = loadAccountNumbers($filePath);

$yearlyCollections = [];
$semesterCollections = [];
$monthlyCollections = [];
$weeklyCollections = [];

// Fetch data for each account number
foreach ($validAccountNumbers as $accountNumber) {
    $yearlyCollections[$accountNumber] = getYearlyCollection($conn, $accountNumber);
    $semesterCollections[$accountNumber] = getSemesterCollection($conn, $accountNumber);
    $monthlyCollections[$accountNumber] = getMonthlyCollection($conn, $accountNumber);
    $weeklyCollections[$accountNumber] = getWeeklyCollection($conn, $accountNumber);
}

?>

<div class="container mt-5">
    <div class="mb-4">
        <button class="btn btn-primary" onclick="showView('weekly')">Weekly</button>
        <button class="btn btn-secondary" onclick="showView('monthly')">Monthly</button>
        <button class="btn btn-success" onclick="showView('semester')">Semester</button>
    </div>

    <div class="table-container">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <?php foreach ($validAccountNumbers as $accountNumber) : ?>
                        <th><?php echo ucfirst($accountNumber); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="dataBody">
                <!-- Rows will be dynamically added here based on selected view -->
            </tbody>
        </table>
    </div>
</div>

<script>
    const yearlyCollections = <?php echo json_encode($yearlyCollections); ?>;
    const semesterCollections = <?php echo json_encode($semesterCollections); ?>;
    const monthlyCollections = <?php echo json_encode($monthlyCollections); ?>;
    const weeklyCollections = <?php echo json_encode($weeklyCollections); ?>;

    function showView(view) {
        const dataBody = document.getElementById('dataBody');
        dataBody.innerHTML = '';

        let data;
        let dates;

        switch (view) {
            case 'weekly':
                data = weeklyCollections;
                dates = Array.from({ length: 52 }, (_, i) => 'Week ' + (i + 1));
                break;
            case 'monthly':
                data = monthlyCollections;
                dates = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                break;
            case 'semester':
                data = semesterCollections;
                dates = ['Semester 1', 'Semester 2', 'Semester 3'];
                break;
        }

        dates.forEach((date, index) => {
            const row = document.createElement('tr');
            const dateCell = document.createElement('td');
            dateCell.textContent = date;
            row.appendChild(dateCell);

            <?php foreach ($validAccountNumbers as $accountNumber) : ?>
                const cell = document.createElement('td');
                cell.textContent = data['<?php echo $accountNumber; ?>'][index + 1] || 0;
                row.appendChild(cell);
            <?php endforeach; ?>

            dataBody.appendChild(row);
        });
    }

    // Default view
    showView('weekly');
</script>

<?php
// Close the database connection
$conn->close();
?>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>



<?php include "footer.php" ?>
