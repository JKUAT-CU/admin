<?php
// Start session and include database connection
session_start();
require 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate input data
    if (empty($email) || empty($password)) {
        echo "<script>alert('Error: Please fill in both email and password fields.'); window.location.href='../login';</script>";
        exit();
    }

    // Query to get the user accounts by email
    $query = "SELECT u.id, u.email, u.password, u.role_id, u.department_id, d.name AS department_name, r.name AS role_name
              FROM users u
              JOIN departments d ON u.department_id = d.id
              JOIN roles r ON u.role_id = r.id
              WHERE u.email = ?";

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            echo "<script>alert('Error: Could not execute the query.'); window.location.href='../login';</script>";
            exit();
        }

        // Bind result variables
        $stmt->bind_result($id, $emailResult, $hashedPassword, $roleId, $departmentId, $departmentName, $roleName);

        $accounts = [];
        while ($stmt->fetch()) {
            // Verify password
            if (password_verify($password, $hashedPassword)) {
                // Collect valid accounts
                $accounts[] = [
                    'id' => $id,
                    'email' => $emailResult,
                    'role_id' => $roleId,
                    'department_id' => $departmentId,
                    'department_name' => $departmentName,
                    'role_name' => $roleName
                ];
            }
        }

        if (!empty($accounts)) {
            // Save accounts in session and redirect to the selection page
            $_SESSION['accounts'] = $accounts;
            header("Location: ../accountselection");
            exit();
        } else {
            echo "<script>alert('Error: Invalid email or password. Please try again.'); window.location.href='../login';</script>";
            exit();
        }

        $stmt->close();
    } else {
        echo "<script>alert('Error: Database query preparation failed.'); window.location.href='../login';</script>";
        exit();
    }
}
?>
