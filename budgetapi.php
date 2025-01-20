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

header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';
require_once 'functions/budget.php';
require 'vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($requestMethod === 'POST' && preg_match('/\/api\/budget\/([\w-]+)/', $requestUri, $matches)) {
    $semester = $matches[1];
    handleBudgetSubmission($input, $semester);
}// elseif ($requestMethod === 'GET' && preg_match('/\/api\/budgets\/([\w-]+)/', $requestUri, $matches)) {
//     $semester = $matches[1];
//     if (isset($_GET['id'])) {
//         $id = $_GET['id'];
//         fetchSpecificBudget($semester, $id);
//     } else {
//         fetchBudgets($semester);
//     }
// } elseif ($requestMethod === 'PUT' && preg_match('/\/api\/budgets\/([\w-]+)\/([\w-]+)/', $requestUri, $matches)) {
//     $semester = $matches[1];
//     $id = $matches[2];
//     updateBudget($input, $semester, $id);
// } elseif ($requestMethod === 'DELETE' && preg_match('/\/api\/budgets\/([\w-]+)\/([\w-]+)/', $requestUri, $matches)) {
//     $semester = $matches[1];
//     $id = $matches[2];
//     deleteBudget($semester, $id);
// } else {
//     http_response_code(404); // Not Found
//     echo json_encode(['message' => 'Endpoint not found']);
// // }
?>
