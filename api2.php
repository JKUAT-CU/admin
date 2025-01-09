<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'backend/db.php';

header('Content-Type: application/json');

// Fetch data from makueni table
$sqlMakueni = "SELECT member_id, account_number FROM makueni";
$resultMakueni = $mysqli->query($sqlMakueni); // Use $mysqli here

if ($resultMakueni->num_rows > 0) {
    $response = [];

    while ($makueniRow = $resultMakueni->fetch_assoc()) {
        $memberId = $makueniRow['member_id'];
        $accountNumber = $makueniRow['account_number'];

        $accountNumberLower = strtolower($accountNumber);

        // Fetch user details from users table
        $sqlUser = "SELECT first_name, surname FROM cu_members WHERE id = $memberId";
        $resultUser = $mysqli->query($sqlUser); // Use $mysqli here

        if ($resultUser->num_rows > 0) {
            $userRow = $resultUser->fetch_assoc();
            $firstName = $userRow['first_name'];
            $lastName = $userRow['surname'];

            // Fetch transaction data via API endpoint
            $apiUrl = "http://localhost/admin/api?account_number=" . urlencode($accountNumberLower);
            $transactionData = file_get_contents($apiUrl);

            $totalAmount = 0;
            if ($transactionData !== FALSE) {
                $transactionArray = json_decode($transactionData, true);
                foreach ($transactionArray as $transaction) {
                    if (strtolower($transaction['BillRefNumber']) === $accountNumberLower) {
                        $totalAmount += (float) $transaction['TransAmount'];
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

$mysqli->close(); // Use $mysqli here
?>
