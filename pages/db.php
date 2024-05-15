
<?php

// Database configuration
$servername = "localhost";
$username = "test1";
$password = "qKJM82Hqxa2m(ESd";
$dbname = "jkuatcu_daraja";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

