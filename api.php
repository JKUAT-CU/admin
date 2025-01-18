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

// URL path parsing
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $path);

// Routing based on URL and HTTP Method
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Handling login action via POST
        if (count($segments) === 1 && $segments[0] === 'login') {
            handleLogin($input);
        } elseif (count($segments) === 2 && $segments[0] === 'budget') {
            handleBudgetSubmission($input);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid endpoint for POST request']);
        }
        break;

    case 'GET':
        // Handle retrieving all budgets or a specific budget by ID
        if (count($segments) === 2 && $segments[0] === 'budgets') {
            if (isset($segments[1])) {
                handleGetBudgetById($segments[1]);
            } else {
                handleGetAllBudgets();
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid endpoint for GET request']);
        }
        break;

    case 'PUT':
        // Handle updating a specific budget
        if (count($segments) === 3 && $segments[0] === 'budgets') {
            handleUpdateBudget($segments[1], $input);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid endpoint for PUT request']);
        }
        break;

    case 'DELETE':
        // Handle deleting a specific budget
        if (count($segments) === 3 && $segments[0] === 'budgets') {
            handleDeleteBudget($segments[1]);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid endpoint for DELETE request']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

// Function to handle login
function handleLogin($input) {
    // Handle the login logic
    echo json_encode(['message' => 'Login successful']);
}

// Function to handle budget submission
function handleBudgetSubmission($input) {
    // Handle the logic for submitting a budget
    echo json_encode(['message' => 'Budget submitted']);
}

// Function to get all budgets
function handleGetAllBudgets() {
    // Handle the logic for retrieving all budgets
    echo json_encode(['message' => 'All budgets retrieved']);
}

// Function to get a specific budget by ID
function handleGetBudgetById($id) {
    // Handle the logic for retrieving a budget by ID
    echo json_encode(['message' => "Budget with ID {$id} retrieved"]);
}

// Function to update a budget
function handleUpdateBudget($id, $input) {
    // Handle the logic for updating the budget
    echo json_encode(['message' => "Budget with ID {$id} updated"]);
}

// Function to delete a budget
function handleDeleteBudget($id) {
    // Handle the logic for deleting the budget
    echo json_encode(['message' => "Budget with ID {$id} deleted"]);
}
?>
