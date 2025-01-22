<?php
// header('Content-Type: application/json');

// // Allowed origins for CORS
// $allowedOrigins = [
//     'https://admin.jkuatcu.org',
// ];

// if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
//     header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
//     header('Access-Control-Allow-Credentials: true');
// } else {
//     http_response_code(403); // Forbidden
//     echo json_encode(['message' => 'Origin not allowed']);
//     exit;
// }

// header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

// Include the database connection
require 'missionsdb.php'; // Ensure this points to your db.php file where you defined the $mysqli connection

// Fetch data from makueni table
$sqlMakueni = "SELECT member_id, account_number, amount FROM makueni";
$resultMakueni = $mysqli->query($sqlMakueni);

if ($resultMakueni->num_rows > 0) {
    $response = [];

    while ($makueniRow = $resultMakueni->fetch_assoc()) {
        $memberId = $makueniRow['member_id'];
        $accountNumber = $makueniRow['account_number'];

        // Convert account_number to lowercase for case-insensitive comparison
        $accountNumberLower = strtolower($accountNumber);

        // Default first_name and surname values
        $firstName = "Unknown";
        $lastName = "Unknown";

        // Check for specific account numbers and override names
        if (strtoupper($accountNumber) === "MM001") {
            $firstName = "Missions";
            $lastName = "Carwash";
        } elseif (strtoupper($accountNumber) === "MM002") {
            $firstName = "Missions";
            $lastName = "Sales";
        } elseif (strtoupper($accountNumber) === "MM003") {
            $firstName = "Missions";
            $lastName = "Fundraiser";
        } elseif (stripos($accountNumberLower, 'makueni') !== false) {
            // Handle all variations of 'makueni'
            $firstName = "Missions";
            $lastName = "Organisations";
        } elseif (stripos($accountNumberLower, 'associates') !== false) {
            // Handle all variations of 'associates'
            $firstName = "Missions";
            $lastName = "Associates";
        } else {
            // Fetch user details from cu_members table for other accounts
            $sqlUser = "SELECT first_name, surname FROM cu_members WHERE id = $memberId";
            $resultUser = $mysqli->query($sqlUser);

            if ($resultUser->num_rows > 0) {
                $userRow = $resultUser->fetch_assoc();
                $firstName = $userRow['first_name'];
                $lastName = $userRow['surname'];
            }
        }

        // Fetch transaction data via API endpoint
        $apiUrl = "https://portal.jkuatcu.org/missions/pages/api1.php?account_number=" . urlencode($accountNumberLower);
        $transactionData = @file_get_contents($apiUrl);

        // If file_get_contents() fails, handle error
        $totalAmount = 0;
        $transactions = []; // To store detailed transaction info

        if ($transactionData !== FALSE) {
            $transactionArray = json_decode($transactionData, true);

            if ($transactionArray && is_array($transactionArray)) {
                // Loop through the API response and sum up TransAmount where BillRefNumber matches account_number
                foreach ($transactionArray as $transaction) {
                    if (strtolower($transaction['BillRefNumber']) === $accountNumberLower) {
                        // Sum the TransAmount
                        $totalAmount += (float) $transaction['TransAmount'];

                        // Add detailed transaction info to the transactions array
                        $transactions[] = [
                            'trans_time' => $transaction['TransTime'],
                            'trans_amount' => $transaction['TransAmount'],
                            'bill_ref_number' => $transaction['BillRefNumber']
                        ];
                    }
                }
            }
        }

        // Add both summed total and detailed transactions to the response
        $response[] = [
            'member_id' => $memberId,
            'account_number' => $accountNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'total_amount' => $totalAmount,
            'transactions' => $transactions // Add detailed transaction info
        ];
    }

    echo json_encode($response);
} else {
    echo json_encode(['message' => 'No data found in makueni table']);
}

// Close the database connection
$mysqli->close();
?>
