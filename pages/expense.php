<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Upload</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.0.7/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-4">

<div class="container mx-auto">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-md shadow-md">
        <h2 class="text-2xl mb-4">Upload CSV File</h2>
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="csvFile" class="block text-sm font-medium text-gray-700">Select CSV File:</label>
                <input type="file" id="csvFile" name="csvFile" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">Upload</button>
        </form>
        <div id="responseMessage" class="mt-4 text-sm text-gray-500"></div>
    </div>
</div>

<script>
    document.getElementById('uploadForm').addEventListener('submit', async function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        try {
            const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            document.getElementById('responseMessage').textContent = data.message;
        } catch (error) {
            console.error('Error:', error);
        }
    });
</script>

<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile'];

    // Check if file is CSV
    $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($fileType !== 'csv') {
        echo json_encode(['error' => 'Only CSV files are allowed.']);
        exit;
    }

    // Move uploaded file to a temporary location
    $tmpName = $file['tmp_name'];
    $csvData = file_get_contents($tmpName);

    // Parse CSV data
    $lines = explode(PHP_EOL, $csvData);
    $data = [];
    foreach ($lines as $line) {
        $data[] = str_getcsv($line);
    }

    // Insert data into database
    foreach ($data as $row) {
        $serialNo = $conn->real_escape_string($row[0]);
        $transactionDate = $conn->real_escape_string($row[1]);
        $category = $conn->real_escape_string($row[2]);
        $amount = $conn->real_escape_string($row[3]);
        $note = $conn->real_escape_string($row[4]);
        
        $sql = "INSERT INTO Expenses (TransactionDate, Category, Amount, Note) VALUES ('$transactionDate', '$category', '$amount', '$note')";
        if ($conn->query($sql) !== TRUE) {
            echo json_encode(['error' => 'Error inserting data: ' . $conn->error]);
            exit;
        }
    }

    echo json_encode(['message' => 'Data uploaded successfully.']);
} else {

}

// Close connection
$conn->close();

?>

</body>
</html>
