<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$sub = glob(__DIR__ . '/public/Sample Data/I. Indikator Mutu/IMUT ALL UNIT (35)/*.xlsx');
echo "Found " . count($sub) . " unit excel files.\n\n";

$targets = ['IRJ', 'FARMASI', 'IGD', 'LABORATORIUM', 'REKAM MEDIS', 'GIZI', 'IBS', 'K3LH'];

foreach ($sub as $filePath) {
    $base = pathinfo($filePath, PATHINFO_FILENAME);
    $matched = false;
    foreach ($targets as $t) {
        if (strcasecmp($base, $t) === 0) {
            $matched = true;
            break;
        }
    }
    if (!$matched) continue;

    echo "========================================================\n";
    echo "FILE: $base.xlsx\n";
    echo "========================================================\n";
    try {
        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();
        echo "Total Sheets: " . count($sheetNames) . "\n";
        echo "Sheets: " . implode(" | ", $sheetNames) . "\n\n";

        // Inspect non-profil sheets
        $inspectedCount = 0;
        foreach ($sheetNames as $sName) {
            if (stripos($sName, 'profil') !== false || stripos($sName, 'pemilihan') !== false) {
                continue;
            }
            $inspectedCount++;
            if ($inspectedCount > 3) break;

            $sheet = $spreadsheet->getSheetByName($sName);
            $highestRow = min($sheet->getHighestRow(), 30);
            $highestColumn = min($sheet->getHighestColumn(), 'O');
            echo "--- [Sheet: $sName] (Rows: 1..$highestRow, Cols: A..$highestColumn) ---\n";
            
            // Check merged cells in this sheet
            $mergeCells = $sheet->getMergeCells();
            if (!empty($mergeCells)) {
                echo "Merged Cells Sample: " . implode(', ', array_slice($mergeCells, 0, 8)) . "\n";
            }

            for ($r = 1; $r <= min($highestRow, 18); $r++) {
                $rowVals = [];
                for ($c = 'A'; $c <= $highestColumn; $c++) {
                    $val = $sheet->getCell($c . $r)->getValue();
                    if ($val !== null && trim((string)$val) !== '') {
                        $rowVals[] = "$c$r: " . trim((string)$val);
                    }
                }
                if (!empty($rowVals)) {
                    echo "Row $r: " . implode(" | ", array_slice($rowVals, 0, 6)) . "\n";
                }
            }
            echo "\n";
        }
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

