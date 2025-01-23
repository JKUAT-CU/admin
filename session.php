<?php
header('Content-Type: application/json');

// Allowed origins for CORS
$allowedOrigins = ['https://admin.jkuatcu.org'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
};

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
