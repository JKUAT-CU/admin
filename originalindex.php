<?php
include('session.php');
include('templates/header.php');
include('templates/footer.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JKUATCU Department Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">

<?php include('templates/sidebar.php');
?>


        <!-- Dashboard Cards -->
        <div class="row g-4">
            <!-- Finance -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Finance</div>
                    <div class="card-body">
                        <p>Manage budgets, requisitions, and spending.</p>
                        <a href="#" class="btn btn-primary btn-sm">Go to Finance</a>
                    </div>
                </div>
            </div>

            <!-- Projects -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Projects</div>
                    <div class="card-body">
                        <p>Monitor ongoing department projects.</p>
                        <a href="#" class="btn btn-primary btn-sm">View Projects</a>
                    </div>
                </div>
            </div>

            <!-- Staff -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Staff</div>
                    <div class="card-body">
                        <p>Manage department staff records and assignments.</p>
                        <a href="#" class="btn btn-primary btn-sm">View Staff</a>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Reports</div>
                    <div class="card-body">
                        <p>Access financial and performance reports.</p>
                        <a href="#" class="btn btn-primary btn-sm">View Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
