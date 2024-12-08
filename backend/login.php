<?php
// Start session and include database connection
session_start();
require 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate input data
    if (empty($email) || empty($password)) {
        echo "<script>alert('Please fill in both fields');</script>";
    } else {
        // Query to get the user accounts by email
        $query = "SELECT u.id, u.email, u.password, u.role_id, u.department_id, d.name AS department_name, r.name AS role_name
                  FROM users u
                  JOIN departments d ON u.department_id = d.id
                  JOIN roles r ON u.role_id = r.id
                  WHERE u.email = ?";

        if ($stmt = $mysqli->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $accounts = [];
                while ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $accounts[] = $row; // Collect valid accounts
                    }
                }
                
                if (!empty($accounts)) {
                    // Save accounts in session and redirect to the selection page
                    $_SESSION['accounts'] = $accounts;
                    header("Location: ../accountselection");
                    exit();
                } else {
                    echo "<script>alert('Invalid password');</script>";
                }
            } else {
                echo "<script>alert('No user found with that email');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Database error');</script>";
        }
    }
}
?>
