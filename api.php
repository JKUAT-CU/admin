<?php
header('Content-Type: application/json');

// Dynamically allow specific origins
$allowed_origins = [
    'https://5ofpvzmb0s3vpubm.vercel.app'
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
        case 'submit-budget':
            handleBudgetSubmission($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['message' => 'Invalid action']);
            break;
    }
}
/**
 * Fetch all budgets based on optional filters.
 *
 * @param string|null $semester The semester to filter budgets (optional).
 * @param int|null $year The year to filter budgets (optional).
 * @return array List of budgets.
 */
function fetchAllBudgets($semester = null, $year = null)
{
    global $db; // Assume $db is the database connection.
    $query = "SELECT * FROM budgets WHERE 1=1";
    $params = [];

    if ($semester) {
        $query .= " AND semester = ?";
        $params[] = $semester;
    }

    if ($year) {
        $query .= " AND YEAR(created_at) = ?";
        $params[] = $year;
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch a specific budget by ID.
 *
 * @param int $id The ID of the budget to fetch.
 * @return array|null The budget data or null if not found.
 */
function fetchBudgetById($id)
{
    global $db; // Assume $db is the database connection.
    $stmt = $db->prepare("SELECT * FROM budgets WHERE id = ?");
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Delete a budget by ID.
 *
 * @param int $id The ID of the budget to delete.
 * @return bool True if the budget was deleted, false otherwise.
 */
function deleteBudgetById($id)
{
    global $db; // Assume $db is the database connection.
    $stmt = $db->prepare("DELETE FROM budgets WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Save a new budget to the database.
 *
 * @param array $input The budget data to save.
 * @return bool True if the budget was saved, false otherwise.
 */
function saveBudget($input)
{
    global $db; // Assume $db is the database connection.

    $stmt = $db->prepare("
        INSERT INTO budgets (semester, events, assets, grand_total, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    return $stmt->execute([
        $input['semester'],
        json_encode($input['events']),
        json_encode($input['assets']),
        $input['grandTotal']
    ]);
}

/**
 * Validate the budget input for submission.
 *
 * @param array $input The budget data to validate.
 * @return array Validation status and message.
 */
function validateBudgetInput($input)
{
    if (empty($input['semester'])) {
        return ['status' => 'error', 'message' => 'Semester is required'];
    }

    if (!is_array($input['events'])) {
        return ['status' => 'error', 'message' => 'Events must be an array'];
    }

    if (!is_array($input['assets'])) {
        return ['status' => 'error', 'message' => 'Assets must be an array'];
    }

    if (!is_numeric($input['grandTotal'])) {
        return ['status' => 'error', 'message' => 'Grand total must be a number'];
    }

    return ['status' => 'success'];
}
?>
