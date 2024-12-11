<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Bootstrap Icons for the eye icon -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Custom gradient background */
        .gradient-custom-2 {
            background: #6a11cb;
            background: -webkit-linear-gradient(to left, #2575fc, #6a11cb);
            background: linear-gradient(to left, #2575fc, #6a11cb);
        }

        /* Button color and hover */
        .btn-custom {
            background-color: #2575fc;
            color: white;
            border-radius: 30px;
        }
        .btn-custom:hover {
            background-color: #6a11cb;
            color: white;
        }

        /* Password toggle icon positioning */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10; /* Ensure it's on top of the input */
        }

        /* Form outline style */
        .form-outline {
            position: relative;
        }

        /* Input and label styles */
        .form-control-lg {
            border-radius: 25px;
        }

        .form-label {
            font-weight: bold;
        }

        /* Card design */
        .card {
            position:center;

        }

        /* Centering content */
        .d-flex-center {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Responsive design */
        @media (max-width: 576px) {
            .card-body {
                padding: 2rem;
            }
        }
    </style>
</head>
<body class="gradient-custom-2">
    <section class="d-flex-center">
        <div class="container">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-lg p-4">
                    <div class="card-body">
                        <h3 class="fw-bold text-center mb-4">Reset Password</h3>

                        <!-- Status Alerts -->
                        <?php if (isset($_SESSION['toast_message'])): ?>
                            <div class="alert alert-info" role="alert">
                                <?= $_SESSION['toast_message']; unset($_SESSION['toast_message']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="resetpassword.php" method="POST" id="resetPasswordForm">
                            <input type="hidden" name="token" value="<?= $_GET['token'] ?>" />

                            <!-- New Password Field -->
                            <div class="mb-4 pb-2">
                                <div class="form-outline">
                                    <input type="password" id="new_password" name="new_password" class="form-control form-control-lg" required />
                                    <label class="form-label" for="new_password">New Password</label>
                                    <span id="toggle_new_password" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="bi bi-eye-slash"></i> <!-- Bootstrap Icons Eye Icon -->
                                    </span>
                                </div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="mb-4 pb-2">
                                <div class="form-outline">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control form-control-lg" required />
                                    <label class="form-label" for="confirm_password">Confirm Password</label>
                                    <span id="toggle_confirm_password" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye-slash"></i> <!-- Bootstrap Icons Eye Icon -->
                                    </span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom btn-lg btn-block">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById("toggle_" + inputId);
            
            // Toggle password field type
            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = "<i class='bi bi-eye'></i>";  // Eye open icon
            } else {
                input.type = "password";
                icon.innerHTML = "<i class='bi bi-eye-slash'></i>";  // Eye closed icon
            }
        }
    </script>
</body>
</html>
