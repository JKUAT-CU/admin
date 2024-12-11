<?php
session_start();
include 'backend/db.php';
// Check database connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';

    // Validate passwords
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: reset.php?token=$token");
        exit();
    }

    // Check token validity
    $checkTokenQuery = "SELECT email, used, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS minutes_passed FROM password_reset WHERE token = ?";
    $stmtCheckToken = $mysqli->prepare($checkTokenQuery);

    if ($stmtCheckToken) {
        $stmtCheckToken->bind_param("s", $token);
        $stmtCheckToken->execute();
        $stmtCheckToken->store_result();

        if ($stmtCheckToken->num_rows === 0) {
            $_SESSION['error'] = "Invalid token";
            header("Location: reset.php?token=$token");
            exit();
        }

        $stmtCheckToken->bind_result($email, $used, $minutesPassed);
        $stmtCheckToken->fetch();
        $stmtCheckToken->close();

        // Check if token is used or expired
        if ($used || $minutesPassed > 60) {
            $_SESSION['error'] = "Token has expired or already been used";
            header("Location: reset.php?token=$token");
            exit();
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update user's password
        $updatePasswordQuery = "UPDATE users SET password = ? WHERE email = ?";
        $stmtUpdatePassword = $mysqli->prepare($updatePasswordQuery);

        if ($stmtUpdatePassword) {
            $stmtUpdatePassword->bind_param("ss", $hashedPassword, $email);
            $stmtUpdatePassword->execute();

            if ($stmtUpdatePassword->affected_rows === 1) {
                // Mark token as used
                $markTokenUsedQuery = "UPDATE password_reset SET used = TRUE WHERE token = ?";
                $stmtMarkTokenUsed = $mysqli->prepare($markTokenUsedQuery);

                if ($stmtMarkTokenUsed) {
                    $stmtMarkTokenUsed->bind_param("s", $token);
                    $stmtMarkTokenUsed->execute();
                    $stmtMarkTokenUsed->close();
                }

                // Password reset successful
                $_SESSION['success'] = "Password reset successful. You can now login with your new password.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to update password.";
                header("Location: reset.php?token=$token");
                exit();
            }
        } else {
            $_SESSION['error'] = "Failed to prepare statement for updating password: " . $mysqli->error;
            header("Location: reset.php?token=$token");
            exit();
        }
    } else {
        $_SESSION['error'] = "Failed to prepare statement for checking token: " . $mysqli->error;
        header("Location: reset.php?token=$token");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: reset.php");
    exit();
}

?>