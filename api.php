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
    http_response_code(403);
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

// CORS Headers
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload dependencies and initialize environment
require_once 'db.php';
require_once 'functions/login.php';
require_once 'functions/budget.php';
require 'vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Collect and decode input
$input = json_decode(file_get_contents('php://input'), true);

// Validate request method
$allowed_methods = ['POST', 'GET', 'PUT', 'DELETE'];
if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

// Validate and route the action
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!isset($input['action']) || empty($input['action'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Action is required']);
        exit;
    }

    $action = $input['action'];
    switch ($action) {
        case 'login':
            handleLogin($input);
            break;
        case 'submit-budget':
            handleBudgetSubmission($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['message' => "Invalid action: {$action}"]);
            break;
    }
}
?>
