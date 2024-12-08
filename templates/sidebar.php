

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/css/styles.css" rel="stylesheet">

<!-- Main container -->
<div class="d-flex">
    <!-- Sidebar -->
<div class="sidebar">
    <h2 class="text-center">
        <?php 
        // Check if the session variable 'department' is set and display it
        echo isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : "Department";
        ?>
    </h2>
    <a href="index">Dashboard</a>
    <!-- <a href="./finance">Finance</a>
    <a href="#">Projects</a>
    <a href="#">Staff</a> -->
    <a href="#">Reports</a>
</div>
