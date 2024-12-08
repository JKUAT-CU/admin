<?php
// Start session
session_start();
if (!isset($_SESSION['accounts'])) {
    header("Location: login"); // Redirect to login if no accounts are found
    exit();
}

// Retrieve the accounts
$accounts = $_SESSION['accounts'];

// Handle account selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_account'])) {
    $selectedAccountId = $_POST['selected_account'];
    foreach ($accounts as $account) {
        if ($account['id'] == $selectedAccountId) {
            // Save the selected account details in the session
            $_SESSION['user_id'] = $account['id'];
            $_SESSION['email'] = $account['email'];
            $_SESSION['role'] = $account['role_name'];
            $_SESSION['role_id'] = $account['role_id'];
            $_SESSION['department'] = $account['department_name'];
            $_SESSION['department_id'] = $account['department_id'];
            
            // Redirect to the appropriate dashboard
            header("Location: index");
            exit();
        }
    }
    echo "<script>alert('Invalid account selected');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        h2 {
            text-align: center;
            margin-top: 20px;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px;
        }
        .card {
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 10px;
            padding: 20px;
            width: 250px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card.selected {
            border: 2px solid #007bff; /* Highlight the selected card */
            background-color: #e9f7ff; /* Light blue background when selected */
        }
        .card h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .card p {
            font-size: 14px;
            color: #555;
            margin: 5px 0;
        }
        .submit-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 20px;
            display: block;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h2>Select an Account</h2>
    <form method="POST" id="accountForm">
        <div class="container">
            <?php foreach ($accounts as $account): ?>
                <div class="card" data-account-id="<?php echo $account['id']; ?>">
                    <h3><?php echo htmlspecialchars($account['department_name']); ?></h3>
                    <p><strong>Role:</strong> <?php echo htmlspecialchars($account['role_name']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="selected_account" id="selected_account">
        <button type="submit" class="submit-btn">Proceed</button>
    </form>

    <script>
        const cards = document.querySelectorAll('.card');
        let selectedCard = null;

        cards.forEach(card => {
            card.addEventListener('click', function() {
                // Deselect the previous card if any
                if (selectedCard) {
                    selectedCard.classList.remove('selected');
                }
                
                // Select the clicked card
                card.classList.add('selected');
                selectedCard = card;

                // Set the selected account id in the hidden input
                document.getElementById('selected_account').value = card.getAttribute('data-account-id');
            });
        });
    </script>

</body>
</html>
