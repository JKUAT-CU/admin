<?php include 'templates/header.php';
include 'session.php'; ?>
<div class="content p-4">
    <h1>Department Dashboard</h1>
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-header">Finance</div>
                <div class="card-body">
                    <p>Manage budgets, requisitions, and view spending.</p>
                    <a href="./finance" class="btn btn-primary btn-sm">Go to Finance</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-header">Projects</div>
                <div class="card-body">
                    <p>Placeholder for project management.</p>
                    <button class="btn btn-primary btn-sm">View Projects</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
