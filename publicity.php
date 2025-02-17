<?php
header('Content-Type: application/json');

// Allowed origins for CORS
$allowedOrigins = [
    'https://admin.jkuatcu.org',
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require 'publicitydb.php'; // Include your database connection

// Mapping of account numbers to department names
$accountMapping = [
    'ET1' => 'CET', 'ET2' => 'NAIRET', 'ET3' => 'NET', 'ET4' => 'NORET', 'ET5' => 'NUSETA',
    'ET6' => 'MUBET', 'ET7' => 'MOUT', 'ET8' => 'SORET', 'ET9' => 'TKET', 'ET10' => 'NETWORK',
    'ET11' => 'UET', 'ET12' => 'WESO',
    'MS1' => 'CREAM', 'MS2' => 'Décor', 'MS3' => 'Edit', 'MS4' => 'Hospitality', 'MS5' => 'HCM',
    'MS6' => 'HSM', 'MS7' => 'Music Ministry', 'MS8' => 'Sound', 'MS9' => 'Sunday School',
    'MS10' => 'Ushering',
    'AS1' => 'Associates', 'CH1' => 'Challenges', 'PUBLICITY SALES' => 'Sales',
    'PUBLICITY CW' => 'Car Wash', 'CM1' => 'Committee Members', 'EC1' => 'Executive Committee',
    'AS' => 'Associates', 'WW1' => 'Well Wishers', 'PUBLICITY' => 'Main Account'
];

// Create a case-insensitive mapping by converting keys to uppercase
$caseInsensitiveMapping = [];
foreach ($accountMapping as $key => $value) {
    $caseInsensitiveMapping[strtoupper($key)] = $value;
}

// Extract account numbers for SQL query
$accountNumbers = array_keys($caseInsensitiveMapping);
$placeholders = implode(',', array_fill(0, count($accountNumbers), '?'));

// Prepare the SQL query to fetch data from the accounts table
$query = "SELECT TRIM(`BillRefNumber`) AS `BillRefNumber`, `TransAmount`, `TransTime`, `BusinessShortCode`, `TransID`
          FROM `finance`
          WHERE UPPER(TRIM(`BillRefNumber`)) IN ($placeholders)";
$stmt = $db->prepare($query);

$data = []; // Initialize an array to hold the JSON data
$departmentTotals = []; // Initialize an array to hold totals for each department
$grandTotal = 0; // Variable to hold the grand total

if ($stmt) {
    // Bind the parameters dynamically (convert account numbers to uppercase)
    $stmt->bind_param(str_repeat('s', count($accountNumbers)), ...array_map('strtoupper', $accountNumbers));
    $stmt->execute();

    // Bind the result columns
    $stmt->bind_result($billRefNumber, $transAmount, $transTime, $businessShortCode, $transID);

    // Fetch the results and process each row
    while ($stmt->fetch()) {
        // Trim the `BillRefNumber` and map the department name
        $trimmedBillRefNumber = strtoupper(trim($billRefNumber));
        $departmentName = $caseInsensitiveMapping[$trimmedBillRefNumber] ?? 'Unknown';

        // Add to the JSON data
        $data[] = [
            'DepartmentName' => $departmentName,
            'BillRefNumber' => $billRefNumber,
            'TransAmount' => $transAmount,
            'TransTime' => $transTime,
        ];

        // Calculate totals by department
        if (!isset($departmentTotals[$departmentName])) {
            $departmentTotals[$departmentName] = 0;
        }
        $departmentTotals[$departmentName] += $transAmount;
        $grandTotal += $transAmount;
    }

    // Close the statement
    $stmt->close();
}

// Prepare the totals for JSON output
$totalsData = [];
foreach ($departmentTotals as $department => $total) {
    $totalsData[] = [
        'DepartmentName' => $department,
        'TotalAmount' => $total,
    ];
}

// Combine transactions and totals data for output
$output = [
    'Transactions' => $data,
    'DepartmentTotals' => $totalsData,
    'GrandTotal' => $grandTotal,
];

// Output the JSON data
header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);

$db->close();
?>
