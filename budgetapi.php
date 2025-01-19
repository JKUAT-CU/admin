<?php
header('Content-Type: application/json');

// Allowed origins for CORS
$allowed_origins = [
    'https://9tt8scax6ffpqqi9.vercel.app',
    'http://localhost:3000'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
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

// Route handling
if ($requestMethod === 'POST' && preg_match('/\/api\/budget\/(\w+)$/', $requestUri, $matches)) {
    $semester = $matches[1];
    handleBudgetSubmission($input, $semester); // Directly submit the budget
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Endpoint not found']);
}
?>
