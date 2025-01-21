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

require_once 'db.php';
require 'vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'GET' && strpos($requestUri, '/api/budgets') === 0) {
    fetchAllBudgets();
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Endpoint not found']);
}

// Function to fetch all budgets and their data
function fetchAllBudgets() {
    global $db; // Use the global database connection

    $query = "SELECT id, department_id, name, total, details FROM budgets";
    $result = $db->query($query);

    if ($result) {
        $budgets = [];
        while ($row = $result->fetch_assoc()) {
            // Decode JSON details if they exist
            $row['details'] = $row['details'] ? json_decode($row['details'], true) : null;
            $budgets[] = $row;
        }

        http_response_code(200); // OK
        echo json_encode(['budgets' => $budgets]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to fetch budgets', 'error' => $db->error]);
    }
}
?>
