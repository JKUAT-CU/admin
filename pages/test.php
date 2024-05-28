<?php
// Database configuration
$servername = "localhost";
$username = "test1";
$password = "qKJM82Hqxa2m(ESd";
$dbname = "jkuatcu_daraja";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
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
