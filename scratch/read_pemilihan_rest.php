<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'public/Indikator Mutu (IRJ)/Pemilihan.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('PEMILIHAN');
$rows = $sheet->toArray(null, true, true, true);

foreach (array_slice($rows, 27, 40, true) as $rowIndex => $row) {
    $nonEmpty = array_filter($row, fn($v) => !is_null($v) && trim($v) !== '');
    if (!empty($nonEmpty)) {
        echo "Row $rowIndex: ";
        foreach ($nonEmpty as $col => $val) {
            echo "[$col] => " . mb_substr(str_replace("\n", " ", $val), 0, 80) . " | ";
        }
        echo "\n";
    }
}
