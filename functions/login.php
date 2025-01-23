<?php
require_once 'db.php';

header('Content-Type: application/json');
session_start(); // Start the session

// Handle the incoming request data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid JSON payload']);
    exit;
}

function handleLogin($input)
{
    global $mysqli;

    // Check if email and password are provided
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Email and password are required']);
        exit;
    }

    $email = $mysqli->real_escape_string($input['email']);
    $password = $input['password'];

    // Prepare SQL query to fetch user data based on email
    $query = "SELECT id, password FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Database error']);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 0) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password']);
        exit;
    }

    $user = $result->fetch_assoc();

    // Verify the password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid email or password']);
        exit;
    }

    // Fetch all accounts with the same email
    $query = "
        SELECT u.id AS user_id, u.email, r.name AS role_name, r.id AS role_id, 
               d.name AS department_name, d.id AS department_id
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.email = ?
    ";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Database error']);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $accounts = $result->fetch_all(MYSQLI_ASSOC);

    if (!$accounts) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'No accounts found for this email']);
        exit;
    }

    // Update session data
    $_SESSION['authenticated'] = true;
    $_SESSION['accounts'] = $accounts;
    $_SESSION['currentAccount'] = $accounts[0]; // Default to the first account

    // Return all accounts for the email
    echo json_encode([
        'message' => 'Login successful',
        'accounts' => $accounts,
        'currentAccount' => $accounts[0]
    ]);
}

// Call the function to handle login
handleLogin($input);
?>
