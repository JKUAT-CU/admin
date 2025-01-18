<?php


include('db.php'); // Database connection

function getTimelineState($mysqli) {
    $query = "SELECT id, name, start_date, end_date FROM activity_timelines WHERE NOW() BETWEEN start_date AND end_date LIMIT 1";
    $result = $mysqli->query($query);

    if ($result && $result->num_rows > 0) {
        $timeline = $result->fetch_assoc();
        return [
            'status' => 'open',
            'timeline' => [
                'id' => $timeline['id'],
                'name' => $timeline['name'],
                'start_date' => $timeline['start_date'],
                'end_date' => $timeline['end_date'],
            ]
        ];
    } else {
        return [
            'status' => 'closed',
            'message' => 'No open activity timeline currently exists.'
        ];
    }
}

// Example usage
$response = getTimelineState($mysqli);
echo json_encode($response);

$mysqli->close();
?>
