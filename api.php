<?php
header('Content-Type: application/json');

$allowed_origins = [
    'https://ctgo69pcu6mm4wjk.vercel.app',
    'https://v0.dev/chat/final-portal-UfXVhMMq6kv'
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
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

// Collect headers for debugging
$headers = getallheaders();
file_put_contents('headers_debug.log', json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Extract Authorization header
$providedApiKey = $headers['Authorization'] ?? null;
$validApiKey = getenv('API_KEY'); // Ensure this is set in your .env file

// Debug log: Compare keys
file_put_contents('auth_debug.log', json_encode([
    'received_key' => $providedApiKey,
    'expected_key' => $validApiKey,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if ($providedApiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode([
        'message' => 'Invalid API key',
        'received_key' => $providedApiKey,
        'expected_key' => $validApiKey,
    ]);
    exit;
}

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
