<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('session.php');
include('backend/db.php'); // Database connection

// Function to get the state of the timeline
function getTimelineState($mysqli) {
    $query = "SELECT id, name, start_date, end_date 
              FROM activity_timelines 
              WHERE NOW() BETWEEN start_date AND end_date 
              LIMIT 1";
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

// Function to restrict access based on user role
function restrictAccessToRoles($allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: no_access.php"); // Redirect to a "No Access" page
        exit();
    }
}

// Check for required roles (e.g., superadmin or admin)
$allowedRoles = ['superadmin', 'admin'];
restrictAccessToRoles($allowedRoles);

// Get the timeline state
$timelineState = getTimelineState($mysqli);

// Restrict access if the timeline is not open
if ($timelineState['status'] !== 'open') {
    // Clear timeline session variable if the timeline is closed
    $_SESSION['timeline'] = null;

    // Redirect to a styled "Budgeting Closed" page
    header("Location: closed"); // Adjust the path as needed
    exit();
}
// If everything is valid, store the open timeline info in the session
$_SESSION['timeline'] = $timelineState['timeline'];

// Validate department_id and timeline_id from the session
if (!isset($_SESSION['department_id']) || !isset($_SESSION['timeline']['id'])) {
    die("Required parameters are missing.");
}
$department_id = (int)$_SESSION['department_id'];
$timeline_id = (int)$_SESSION['timeline']['id'];

$mysqli->close();
?>
