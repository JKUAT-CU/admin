<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'jkuatcu_devs';
$password = '#God@isAble!#';  // Ensure this is the correct password
$database = 'jkuatcu_data';

// // Create connection
// $mysqli = new mysqli($host, $user, $password, $database);

// // // Database connection
// $host = 'localhost';
// $user = 'portals';
// $password = 'I&Y*U&^(JN&Y Kjbkjn'; // Ensure this is the correct password
// $database = 'admin';

// Create connection
$mysqli = new mysqli($host, $user, $password, $database);

// Check database connection
if (!$mysqli) {
    die(json_encode(['message' => 'Database connection failed.']));
}

header('Content-Type: application/json');

// Set CORS policy for missions.jkuatcu.org
// Uncomment the following lines to allow CORS for missions.jkuatcu.org
// header('Access-Control-Allow-Origin: https://missions.jkuatcu.org');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Origin: no-cors');

// Fetch data from makueni table
$sqlMakueni = "SELECT member_id, account_number FROM makueni";
$resultMakueni = $mysqli->query($sqlMakueni);

if (!$resultMakueni) {
    die(json_encode(['message' => 'Query failed: ' . $mysqli->error]));
}

if ($resultMakueni->num_rows > 0) {
    $response = [];

    while ($makueniRow = $resultMakueni->fetch_assoc()) {
        $memberId = $makueniRow['member_id'];
        $accountNumber = $makueniRow['account_number'];
        $accountNumberLower = strtolower($accountNumber);

        // Fetch user details from cu_members table
        $sqlUser = "SELECT first_name, surname FROM cu_members WHERE id = $memberId";
        $resultUser = $mysqli->query($sqlUser);

        if ($resultUser && $resultUser->num_rows > 0) {
            $userRow = $resultUser->fetch_assoc();
            $firstName = $userRow['first_name'];
            $lastName = $userRow['surname'];

            // Fetch transaction data via API endpoint
            $apiUrl = "https://admin.jkuatcu.org/api1.php?account_number=" . urlencode($accountNumberLower);
            $transactionData = @file_get_contents($apiUrl);

            $totalAmount = 0;
            if ($transactionData !== FALSE) {
                $transactionArray = json_decode($transactionData, true);
                foreach ($transactionArray as $transaction) {
                    if (strtolower($transaction['BillRefNumber']) === $accountNumberLower) {
                        $totalAmount += (float)$transaction['TransAmount'];
                    }
                }
            }

            $response[] = [
                'member_id' => $memberId,
                'account_number' => $accountNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'total_amount' => $totalAmount
            ];
        }
    }

    echo json_encode($response);
} else {
    echo json_encode(['message' => 'No data found in makueni table']);
}

$mysqli->close();
?>
