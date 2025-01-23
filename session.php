<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the action is to verify authentication
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data['action'] === 'check_auth') {
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            echo json_encode([
                'isAuthenticated' => true,
                'accounts' => $_SESSION['accounts'] ?? [],
                'currentAccount' => $_SESSION['currentAccount'] ?? null
            ]);
        } else {
            echo json_encode([
                'isAuthenticated' => false,
                'accounts' => [],
                'currentAccount' => null
            ]);
        }
        exit;
    }
} else {
    // Handle invalid request methods
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>
