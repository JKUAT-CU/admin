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

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';
require 'vendor/autoload.php';
require 'functions/editbudget.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($requestMethod === 'GET' && strpos($requestUri, '/api/budgets') === 0) {
    $departmentId = isset($_GET['department_id']) ? $_GET['department_id'] : null;
    fetchBudgetsByDepartment($departmentId);
} elseif ($requestMethod === 'POST' && strpos($requestUri, '/api/budgets') === 0) {
    updateSpecificBudgets($input);
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Endpoint not found']);
}

?>
