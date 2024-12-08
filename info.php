<?php
session_start();

// Debug: Print the entire session data
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo bin2hex(random_bytes(32)); // Generates a secure random 64-character key
?>


