<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .gradient-custom-2 {
            background: linear-gradient(to left, #2575fc, #6a11cb);
        }
        .btn-custom {
            background-color: #2575fc; color: white; border-radius: 30px;
        }
        .btn-custom:hover {
            background-color: #6a11cb;
        }
        .password-toggle {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; z-index: 10;
        }
        .form-control-lg { border-radius: 25px; }
        .card { border-radius: 20px; }
    </style>
</head>
<body class="gradient-custom-2">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg p-4">
                <div class="card-body">
                    <h3 class="text-center fw-bold mb-4">Reset Password</h3>

                    <!-- Status Alerts -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>

                    <form action="reset.php" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>" />
                        <div class="mb-4 position-relative">
                            <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="New Password" required>
                            <span onclick="togglePassword('password')" class="password-toggle">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                        <div class="mb-4 position-relative">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control form-control-lg" placeholder="Confirm Password" required>
                            <span onclick="togglePassword('confirm_password')" class="password-toggle">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                        <button type="submit" class="btn btn-custom w-100 btn-lg">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector("i");
            if (field.type === "password") {
                field.type = "text";
                icon.classList.replace("bi-eye-slash", "bi-eye");
            } else {
                field.type = "password";
                icon.classList.replace("bi-eye", "bi-eye-slash");
            }
        }
    </script>
</body>
</html>
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
