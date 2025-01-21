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
$database = $_ENV['DB_NAME'] ?? null;

// Validate that all required variables are loaded
if (!$host || !$user || !$database) {
    die("Error: Missing environment variables. Please check your .env file.");
}

// Establish the MySQL connection
$conn = new mysqli($host, $user, $password, $database);

// Check for any connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Explicitly select the database
if (!$conn->select_db($database)) {
    die("Database selection failed: " . $conn->error);
}
