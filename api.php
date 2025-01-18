<?php
header('Content-Type: application/json');

// Allow only requests from localhost:3000
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS'); // Include OPTIONS method
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Return HTTP 200 for preflight
    exit;
}

require_once 'db.php';
require_once 'functions/login.php';
require 'vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Validate API Key
$headers = getallheaders();
$providedApiKey = isset($headers['Authorization']) ? $headers['Authorization'] : null;
$validApiKey = "f6cfe845-ce1f-407e-8eb9-c8ac79894649"; // Fetch API key from the .env file

if ($providedApiKey !== $validApiKey) {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Invalid API key']);
    exit;
}

// Proceed with request handling
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Only POST requests are allowed']);
    exit;
}

if (!isset($input['action'])) {
    http_response_code(400); // Bad Request
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
