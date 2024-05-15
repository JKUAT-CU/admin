<?php

// Include the database connection file
include "db.php";

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

// Retrieve data from the finance table
$sql = "SELECT finance.*, accounts.BillRefNumber AS existingBillRefNumber FROM finance LEFT JOIN accounts ON finance.id = accounts.id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Iterate over each row in the finance table
    while ($row = $result->fetch_assoc()) {
        $financeAccountNumber = $row["BillRefNumber"];
        // Truncate "RFTB" to just "RFTB" if it appears anywhere in the string
        if (stripos($financeAccountNumber, "rftb") !== false) {
            $closestMatch = "RFTB";
        }
        // Truncate "Samburu" to just "Samburu" if it appears anywhere in the string
        elseif (stripos($financeAccountNumber, "samburu") !== false) {
            $closestMatch = "Samburu";
        }
        // Truncate "missions" to just "missions" if it appears anywhere in the string
        elseif (stripos($financeAccountNumber, "missions") !== false) {
            $closestMatch = "missions";
        }
        // Apply Levenshtein algorithm to find the closest match from your predefined list of valid account numbers
        else {
            $closestMatch = findClosestMatch($financeAccountNumber, $validAccountNumbers);
        }

        // Update or insert data into the accounts table with the matched account number
        $id = $row["id"];
        $transID = $row["TransID"];
        $transTime = $row["TransTime"];
        $transAmount = $row["TransAmount"];
        $businessShortCode = $row["BusinessShortCode"];

        // Use the existing values from the finance table for other columns
        $sql = "INSERT INTO accounts (id, TransID, TransTime, TransAmount, BusinessShortCode, BillRefNumber) VALUES ('$id', '$transID', '$transTime', '$transAmount', '$businessShortCode', '$closestMatch') ON DUPLICATE KEY UPDATE BillRefNumber = '$closestMatch'";
        $conn->query($sql);
    }
} else {
    echo "0 results";
}

// Close the database connection
$conn->close();

// Check if the account number contains any of the predefined strings
function findClosestMatch($input, $validAccountNumbers) {
    $minDistance = PHP_INT_MAX;
    $closestMatch = "";
    $containsValidString = false;

    foreach ($validAccountNumbers as $validString) {
        if (stripos($input, $validString) !== false) {
            $closestMatch = $validString;
            $containsValidString = true;
            break;
        }
    }

    // If no valid string is found in the account number, set it as "others"
    if (!$containsValidString) {
        $closestMatch = "others";
    }

    return $closestMatch;
}

?>
