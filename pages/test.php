<?php

// Define the list of valid account numbers
$validAccountNumbers = array(
    "offering",
    "thanksgiving",
    "tithe",
    "missions",
    "samburu",
    "sm24***",
    "welfare",
    "carwash",
    "kasiluni",
    "bible",
    "biblestudy",
    "bs guide",
    "publicity",
    "food",
    "RFTB",
    "Kairos",
    "Books",
    "Hatua",
    "Challenge"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valid Account Numbers</title>
</head>
<body>
    <h2>List of Valid Account Numbers</h2>
    <ul>
        <?php foreach ($validAccountNumbers as $account): ?>
            <li><?php echo $account; ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
