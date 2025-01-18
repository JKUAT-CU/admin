<?php
header('Content-Type: application/json');

// Dynamically allow specific origins
$allowed_origins = [
    'https://ctgo69pcu6mm4wjk.vercel.app',
    'https://v0.dev/chat/final-portal-UfXVhMMq6kv',
    'https://ctgo69pcu6mm4wjk.vercel.app/'
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

require_once 'db.php';
require_once 'functions/login.php';
require 'vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Collect headers for debugging (optional)
$headers = getallheaders();
file_put_contents('headers_debug.log', json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Process input
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Only POST requests are allowed']);
    exit;
}

if (!isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Action is required']);
    exit;
}

// Action handler
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
