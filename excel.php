<?php

require 'db.php'; 
require 'vendor/autoload.php'; // This is enough to load PhpSpreadsheet

// Manually load PhpSpreadsheet core files
require 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
require 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php';

// Use necessary PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// Set headers for Excel download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Budgets.xlsx"');
header('Cache-Control: max-age=0');

// Fetch budget data from the database
function fetch_budget_data($mysqli) {
    $query = "
        SELECT 
            b.id AS budget_id, 
            d.name AS department_name, 
            b.semester, 
            b.grand_total, 
            COALESCE(fb.grand_total, 0) AS finance_approved_total,
            COALESCE(a.name, 'N/A') AS asset_name, 
            COALESCE(a.quantity, 0) AS asset_quantity, 
            COALESCE(a.price, 0) AS asset_price, 
            COALESCE(e.name, 'N/A') AS event_name, 
            COALESCE(e.attendance, 0) AS attendance, 
            COALESCE(ei.name, 'N/A') AS event_item_name, 
            COALESCE(ei.quantity, 0) AS event_item_quantity, 
            COALESCE(ei.price, 0) AS event_item_price
        FROM budgets b
        JOIN departments d ON b.department_id = d.id
        LEFT JOIN finance_budgets fb ON b.id = fb.id
        LEFT JOIN assets a ON b.id = a.budget_id
        LEFT JOIN events e ON b.id = e.budget_id
        LEFT JOIN event_items ei ON e.id = ei.event_id
        ORDER BY b.semester ASC, b.created_at DESC;
    ";

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die(json_encode(['error' => 'Failed to prepare statement: ' . $mysqli->error]));
    }

    if (!$stmt->execute()) {
        die(json_encode(['error' => 'Query execution failed: ' . $stmt->error]));
    }

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

$budgets = fetch_budget_data($mysqli);

// Create an Excel spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Budgets");

// Headers
$headers = [
    "Department Name", "Semester", "Grand Total", "Finance Approved Total", 
    "Asset Name", "Quantity", "Price", 
    "Event Name", "Attendance", "Event Item", "Item Quantity", "Item Price"
];
$sheet->fromArray([$headers], NULL, 'A1');

// Style Headers (Bold + Borders)
$styleArray = [
    'font' => ['bold' => true],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
];
$sheet->getStyle('A1:L1')->applyFromArray($styleArray);

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

// Auto-size columns for better readability
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Write to output
try {
    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
} catch (Exception $e) {
    die(json_encode(['error' => 'Excel generation failed: ' . $e->getMessage()]));
}

exit;
