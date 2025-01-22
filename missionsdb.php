<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Retrieve the database credentials from environment variables
$host = $_ENV['DB_HOST'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$password = $_ENV['DB_PASS'] ?? null;
$database = 'jkuatcu_data';

// // Debugging: Print database connection details to ensure correct values
// echo "DB_HOST: " . $host . "<br>";
// echo "DB_USER: " . $user . "<br>";
// echo "DB_NAME: " . $database . "<br>";

// Validate that all required variables are loaded
if (!$host || !$user || !$database) {
    die("Error: Missing environment variables. Please check your .env file.");
}

// Establish the MySQL connection
$mysqli = new mysqli($host, $user, $password, $database);

// Check for any connection errors
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Explicitly select the database
if (!$mysqli->select_db($database)) {
    die("Database selection failed: " . $mysqli->error);
}



// Return the $mysqli object for other scripts to use
return $mysqli;
?>
