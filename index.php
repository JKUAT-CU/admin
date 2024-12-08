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

<?php include('templates/sidebar.php');
?>
    <style>
        /* Custom color scheme */
        :root {
            --primary-color: #800000;
            --secondary-color: #089000;
            --accent-color: #f7a306;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        

        /* Main Content */
        .content {
            padding: 20px;
        }

        .content h1 {
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Cards */
        .card {
            border: 1px solid var(--secondary-color);
        }

        .card-header {
            background-color: var(--secondary-color);
            color: white;
            font-weight: bold;
        }

        /* Button Styling */
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            color: white;
        }
    </style>
</head>

<!-- Main container -->
<div class="d-flex">

       <!-- Dashboard Cards -->
        <div class="row g-4">
            <!-- Finance -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Finance</div>
                    <div class="card-body">
                        <p>Create Budgets for the financial Year</p>
                        <a href="budget" class="btn btn-primary btn-sm">Create Budget</a>
                    </div>
                </div>
            </div>

            <!-- Staff -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Staff</div>
                    <div class="card-body">
                        <p>Manage department staff records and assignments.</p>
                        <a href="editbudget" class="btn btn-primary btn-sm">Edit Budget</a>
                    </div>
                </div>
            </div>

            <!-- Projects -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Projects</div>
                    <div class="card-body">
                        <p>View Budgets.</p>
                        <a href="viewbudget" class="btn btn-primary btn-sm">View Budgets</a>
                    </div>
                </div>
            </div>

            <!-- Reports
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Reports</div>
                    <div class="card-body">
                        <p>Access financial and performance reports.</p>
                        <a href="#" class="btn btn-primary btn-sm">View Reports</a>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
