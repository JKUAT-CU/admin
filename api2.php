<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'backend/db.php';

header('Content-Type: application/json');

// Fetch data from makueni table
$sqlMakueni = "SELECT member_id, account_number FROM makueni";
$resultMakueni = $conn->query($sqlMakueni);

if ($resultMakueni->num_rows > 0) {
    $response = [];

    while ($makueniRow = $resultMakueni->fetch_assoc()) {
        $memberId = $makueniRow['member_id'];
        $accountNumber = $makueniRow['account_number'];

        // Convert account_number to lowercase (or uppercase) for case-insensitive comparison
        $accountNumberLower = strtolower($accountNumber);

        // Fetch user details from users table
        $sqlUser = "SELECT first_name, surname FROM cu_members WHERE id = $memberId";
        $resultUser = $conn->query($sqlUser);

        if ($resultUser->num_rows > 0) {
            $userRow = $resultUser->fetch_assoc();
            $firstName = $userRow['first_name'];
            $lastName = $userRow['surname'];

            // Fetch transaction data via API endpoint
            $apiUrl = "http://localhost/admin/api?account_number=" . urlencode($accountNumberLower);
            $transactionData = file_get_contents($apiUrl);

            // If file_get_contents() fails, handle error
            if ($transactionData === FALSE) {
                $totalAmount = 0;
            } else {
                $transactionArray = json_decode($transactionData, true);
                $totalAmount = 0;

                // Loop through the API response and sum up TransAmount where BillRefNumber matches account_number
                foreach ($transactionArray as $transaction) {
                    // Convert BillRefNumber to lowercase for case-insensitive comparison
                    if (strtolower($transaction['BillRefNumber']) === $accountNumberLower) {
                        // Sum the TransAmount values
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

$conn->close();
?>
