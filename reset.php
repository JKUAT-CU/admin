<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Include styles and scripts as in the sign-in page -->
</head>
<body>
    <section class="h-100 h-custom gradient-custom-2 d-flex-center">
        <div class="container py-5 h-100 d-flex-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card bg-indigo text-white" style="border-radius: 15px;">
                    <div class="card-body p-5 text-center">
                        <h3 class="fw-bold mb-5">Reset Password</h3>

                        <!-- Status Alerts -->
                        <?php if (isset($_SESSION['toast_message'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $_SESSION['toast_message']; unset($_SESSION['toast_message']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="reset_password.php" method="POST" id="resetPasswordForm">
                            <input type="hidden" name="token" value="<?= $_GET['token'] ?>" />
                            <div class="mb-4 pb-2">
                                <div class="form-outline form-white">
                                    <input type="password" id="new_password" name="new_password" class="form-control form-control-lg" required />
                                    <label class="form-label" for="new_password">New Password</label>
                                </div>
                            </div>
                            <div class="mb-4 pb-2">
                                <div class="form-outline form-white">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control form-control-lg" required />
                                    <label class="form-label" for="confirm_password">Confirm Password</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom btn-lg btn-block">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
