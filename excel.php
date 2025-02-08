<?php

require 'db.php'; // Include your database connection
require 'vendor/autoload.php'; // Include PHPSpreadsheet

use PhpSpreadsheet\Spreadsheet;
use PhpSpreadsheet\Writer\Xlsx;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Budgets.xlsx"');

// Fetch budget data from database
function fetch_budget_data($conn) {
    $query = "
        SELECT 
            b.id AS budget_id, d.name AS department_name, b.semester, b.grand_total, 
            f.approved_total AS finance_approved_total, 
            a.name AS asset_name, a.quantity AS asset_quantity, a.price AS asset_price, 
            e.name AS event_name, e.attendance, ei.name AS event_item_name, 
            ei.quantity AS event_item_quantity, ei.price AS event_item_price
        FROM budgets b
        JOIN departments d ON b.department_id = d.id
        LEFT JOIN finance_approvals f ON b.id = f.budget_id
        LEFT JOIN assets a ON b.id = a.budget_id
        LEFT JOIN events e ON b.id = e.budget_id
        LEFT JOIN event_items ei ON e.id = ei.event_id
        ORDER BY b.semester ASC, b.created_at DESC;";
    
    $result = $conn->query($query);
    $budgets = [];
    
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }
    
    return $budgets;
}

$mysqli = new mysqli("localhost", "username", "password", "database");
$budgets = fetch_budget_data($mysqli);

// Create an Excel file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Budgets");

// Headers
$headers = [
    "Department Name", "Semester", "Grand Total", "Finance Approved Total", 
    "Asset Name", "Quantity", "Price", 
    "Event Name", "Attendance", "Event Item", "Item Quantity", "Item Price"
];
$sheet->fromArray($headers, NULL, 'A1');

// Populate data
$rowIndex = 2;
foreach ($budgets as $budget) {
    $sheet->fromArray([
        $budget['department_name'], $budget['semester'], $budget['grand_total'], $budget['finance_approved_total'],
        $budget['asset_name'], $budget['asset_quantity'], $budget['asset_price'],
        $budget['event_name'], $budget['attendance'],
        $budget['event_item_name'], $budget['event_item_quantity'], $budget['event_item_price']
    ], NULL, "A$rowIndex");
    $rowIndex++;
}

// Write to output
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
