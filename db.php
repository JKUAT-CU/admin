<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'portals';
$password = 'I&Y*U&^(JN&Y Kjbkjn'; 
$database = 'admin';

// Create connection
$mysqli = new mysqli($host, $user, $password, $database, 3306); // Explicitly specify port 3306

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} else {
    echo "Connected successfully";
}
?>