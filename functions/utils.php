<?php
// Helper function to send JSON responses
function sendResponse($success, $data = null, $message = "") {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data,
    ]);
    exit();
}
?>
