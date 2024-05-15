<?php
include "header.php";
// File path for storing account numbers JSON
$filePath = 'accounts.json';

// Function to load account numbers from JSON file
function loadAccountNumbers($filePath) {
    if (file_exists($filePath)) {
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    } else {
        return array(); // Return an empty array if file doesn't exist
    }
}

// Function to save account numbers to JSON file
function saveAccountNumbers($accountNumbers, $filePath) {
    $json = json_encode($accountNumbers, JSON_PRETTY_PRINT);
    file_put_contents($filePath, $json);
}

// Load account numbers from JSON file
$validAccountNumbers = loadAccountNumbers($filePath);

// Ensure $validAccountNumbers is initialized properly
if (!is_array($validAccountNumbers)) {
    $validAccountNumbers = array();
}

// Handle form submission to add or remove account numbers
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_account'])) {
        $newAccount = $_POST['new_account'];
        if (!in_array($newAccount, $validAccountNumbers)) {
            $validAccountNumbers[] = $newAccount;
            // Save the updated account numbers to JSON file
            saveAccountNumbers($validAccountNumbers, $filePath);
        }
    } elseif (isset($_POST['remove_account'])) {
        $removeAccount = $_POST['remove_account'];
        if (($key = array_search($removeAccount, $validAccountNumbers)) !== false) {
            unset($validAccountNumbers[$key]);
            // Save the updated account numbers to JSON file
            saveAccountNumbers($validAccountNumbers, $filePath);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<body class="bg-light">
    <div class="container">
        <h2 class="mt-4">Manage Account Numbers</h2>
        <form class="mt-4" method="post">
            <div class="mb-3">
                <label for="new_account" class="form-label">Add Account Number:</label>
                <input type="text" class="form-control" id="new_account" name="new_account" required>
            </div>
            <button type="submit" class="btn btn-primary" name="add_account">Add</button>
        </form>
        <hr class="my-4">
        <h3 class="mt-4">Current Account Numbers:</h3>
        <ul class="list-group">
            <?php foreach ($validAccountNumbers as $account): ?>
                <li class="list-group-item"><?php echo ucfirst($account); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>

