<?php
session_start();
include 'backend/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Prevent SQL Injection
    $email = $mysqli->real_escape_string($email);

    // Check if the email exists
    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token expires in 1 hour

        // Store the token in the password_resets table
        $sql = "INSERT INTO password_resets (email, token, expiry) VALUES ('$email', '$token', '$expiry')";
        $mysqli->query($sql);

        // Send reset email (Assuming mail configuration is set)
        $reset_link = "https://admin.jkuatcu.org/reset.php?token=$token";
        mail($email, "Password Reset", "Click the following link to reset your password: $reset_link");

        $_SESSION['toast_message'] = "Password reset link has been sent to your email.";
    } else {
        $_SESSION['toast_message'] = "No user found with that email.";
    }

    header("Location: forgotten.php"); // Redirect to show alert after form submission
    exit();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            margin-top: 20%;
            background: linear-gradient(145deg, #6c757d, #adb5bd);
            color: white;
            border-radius: 1rem;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <section class="h-100">
        <div class="container h-100 d-flex align-items-center justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                        <h3 class="fw-bold text-center mb-4">Forgot Password</h3>

                        <!-- Status Alerts -->
                        <?php if (isset($_SESSION['toast_message'])): ?>
                            <div class="alert alert-info" role="alert">
                                <?= $_SESSION['toast_message']; unset($_SESSION['toast_message']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="forgotten.php" method="POST" id="forgotPasswordForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Your Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-custom w-100">Send Reset Link</button>
                        </form>
                        <div class="text-center mt-3">
                            <button type="button" onclick="location.href='index.php';" class="btn btn-outline-light btn-sm">
                                Back to Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
