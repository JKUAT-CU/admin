<?php
header('Content-Type: application/json');

// Dynamically allow specific origins
$allowed_origins = [
    'https://8acx7krguqhmyqvw.vercel.app'
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload dependencies and initialize environment
require_once 'db.php';
require_once 'functions/login.php';
require 'vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Collect and decode input
$input = json_decode(file_get_contents('php://input'), true);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Only POST requests are allowed']);
    exit;
}

// Validate and handle the action
if (!isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Action is required']);
    exit;
}

$action = $input['action'];
switch ($action) {
    case 'login':
        handleLogin($input);
        break;

    default:
        http_response_code(400);
        echo json_encode(['message' => 'Invalid action']);
        break;
}
?>
