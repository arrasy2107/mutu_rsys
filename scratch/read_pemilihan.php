<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'public/Indikator Mutu (IRJ)/Pemilihan.xlsx';

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

$spreadsheet = IOFactory::load($filePath);
$sheetNames = $spreadsheet->getSheetNames();
echo "Sheet names: " . implode(', ', $sheetNames) . "\n\n";

foreach ($sheetNames as $sheetName) {
    echo "=== Sheet: $sheetName ===\n";
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $rows = $sheet->toArray(null, true, true, true);
    
    $rowCount = 0;
    foreach ($rows as $rowIndex => $row) {
        // filter empty rows
        $nonEmpty = array_filter($row, fn($v) => !is_null($v) && trim($v) !== '');
        if (!empty($nonEmpty)) {
            echo "Row $rowIndex: ";
            foreach ($nonEmpty as $col => $val) {
                echo "[$col] => " . mb_substr(str_replace("\n", " ", $val), 0, 80) . " | ";
            }
            echo "\n";
            $rowCount++;
            if ($rowCount > 60) {
                echo "... (truncated)\n";
                break;
            }
        }
    }
    echo "\n";
}
