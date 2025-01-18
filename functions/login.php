<?php
require_once 'db.php';

header('Content-Type: application/json');

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

    // Validate action
    if (!isset($input['action']) || $input['action'] !== 'login') {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Invalid action']);
        exit;
    }

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

    // Login successful: return user ID
    echo json_encode(['message' => 'Login successful', 'user_id' => $user['id']]);
    exit;
}

// Call the function to handle login
handleLogin($input);
?>
