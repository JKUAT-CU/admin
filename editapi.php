<?php

header('Content-Type: application/json');

// Allow all origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function fetchBudgetsByDepartment($departmentId = 1) {
    $url = 'https://yourdomain.com/api/budgets'; // Replace with your actual domain

    $ch = curl_init();

    // Query parameters
    $queryParams = http_build_query([
        'department_id' => $departmentId,
    ]);

    curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        // No specific origin header as this is open
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'CURL Error: ' . curl_error($ch);
    } else {
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpStatus === 200) {
            echo "Response: " . $response;
        } else {
            echo "HTTP Error: $httpStatus\nResponse: " . $response;
        }
    }

    curl_close($ch);
}

// Call the function to fetch budgets for department_id 1
fetchBudgetsByDepartment(1);

?>
