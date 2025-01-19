<?php
header('Content-Type: application/json');

$allowed_origins = [
    'https://9tt8scax6ffpqqi9.vercel.app',
    'http://localhost:3000'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403);
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
require_once 'functions/login.php';
require_once 'functions/budget.php';
require 'vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($requestMethod === 'POST' && strpos($requestUri, '/api/login') !== false) {
    handleLogin($input);
} elseif ($requestMethod === 'POST' && strpos($requestUri, '/api/budget') !== false) {
    handleBudgetSubmission($input);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint not found']);
}
?>
