<?php
session_start();
include 'backend/db.php'; // Ensure this file correctly initializes $mysqli

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';

    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: reset.php?token=$token");
        exit();
    }

    // Validate token
    $checkTokenQuery = "SELECT email, used, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS minutes_passed FROM password_resets WHERE token = ?";
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

        if ($used || $minutesPassed > 60) {
            $_SESSION['error'] = "Token has expired or already been used";
            header("Location: reset.php?token=$token");
            exit();
        }

        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updatePasswordQuery = "UPDATE users SET password = ? WHERE email = ?";
        $stmtUpdatePassword = $mysqli->prepare($updatePasswordQuery);
        if ($stmtUpdatePassword) {
            $stmtUpdatePassword->bind_param("ss", $hashedPassword, $email);
            $stmtUpdatePassword->execute();

            if ($stmtUpdatePassword->affected_rows > 0) {
                $markTokenUsedQuery = "UPDATE password_resets SET used = TRUE WHERE token = ?";
                $stmtMarkTokenUsed = $mysqli->prepare($markTokenUsedQuery);
                if ($stmtMarkTokenUsed) {
                    $stmtMarkTokenUsed->bind_param("s", $token);
                    $stmtMarkTokenUsed->execute();
                }
                $_SESSION['success'] = "Password reset successful. Log in with your new password.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Password update failed.";
            }
        }
    } else {
        $_SESSION['error'] = "Token verification failed: " . $mysqli->error;
    }
}
?>
