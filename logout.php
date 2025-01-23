<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Destroy the session
    session_unset();
    session_destroy();

    // Return a success response
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
} else {
    // Handle invalid request methods
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>
