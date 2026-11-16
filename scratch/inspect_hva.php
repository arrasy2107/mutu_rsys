<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== INSPECTING MASTER HVA RSYS 2026.XLSX ===" . PHP_EOL;

$masterPath = realpath(__DIR__ . '/../public/Sample Data/IV. HVA/HVA RSYS 2026.xlsx');
echo "Realpath Master: " . ($masterPath ?: 'NOT FOUND') . PHP_EOL;

if ($masterPath && file_exists($masterPath)) {
    $reader = IOFactory::createReaderForFile($masterPath);
    $reader->setReadDataOnly(true);
    $wb = $reader->load($masterPath);
    $sheets = $wb->getSheetNames();
    echo "Master sheets (" . count($sheets) . "): " . implode(', ', $sheets) . PHP_EOL;
    
    foreach ($sheets as $sName) {
        $sheet = $wb->getSheetByName($sName);
        $maxRow = $sheet->getHighestRow();
        $maxCol = $sheet->getHighestColumn();
        echo "- Sheet [{$sName}]: maxRow={$maxRow}, maxCol={$maxCol}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== INSPECTING UNIT FILE: ADE IRMA 2026.XLS ===" . PHP_EOL;
$sampleUnit = realpath(__DIR__ . '/../public/Sample Data/IV. HVA/ALL UNIT (35)/ADE IRMA 2026.xls');
echo "Realpath Unit: " . ($sampleUnit ?: 'NOT FOUND') . PHP_EOL;

if ($sampleUnit && file_exists($sampleUnit)) {
    $uReader = IOFactory::createReaderForFile($sampleUnit);
    $uReader->setReadDataOnly(true);
    $uWb = $uReader->load($sampleUnit);
    $uSheets = $uWb->getSheetNames();
    echo "Unit sheets (" . count($uSheets) . "): " . implode(', ', $uSheets) . PHP_EOL;
    foreach ($uSheets as $usName) {
        $uSheet = $uWb->getSheetByName($usName);
        echo "- Unit Sheet [{$usName}]: maxRow=" . $uSheet->getHighestRow() . ", maxCol=" . $uSheet->getHighestColumn() . PHP_EOL;
    }
}

