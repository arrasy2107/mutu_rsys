<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xls;

$masterPath = realpath(__DIR__ . '/../public/Sample Data/IV. HVA/HVA RSYS 2026.xlsx');
echo "Testing Xls reader on: " . $masterPath . PHP_EOL;

$reader = new Xls();
$reader->setReadDataOnly(true);
$wb = $reader->load($masterPath);

echo "SUCCESS! Loaded master workbook!" . PHP_EOL;
$sheets = $wb->getSheetNames();
echo "Master sheets (" . count($sheets) . "): " . implode(', ', $sheets) . PHP_EOL;

foreach ($sheets as $sName) {
    $sheet = $wb->getSheetByName($sName);
    echo "- Sheet [{$sName}]: maxRow=" . $sheet->getHighestRow() . ", maxCol=" . $sheet->getHighestColumn() . PHP_EOL;
}

