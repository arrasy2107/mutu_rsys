<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Unit;

$folder = public_path('Sample Data/Form Pengumpul Data');
$files = array_filter(scandir($folder), fn($f) => str_ends_with($f, '.xlsx') && !str_starts_with($f, '~$'));

echo "Total Excel files: " . count($files) . "\n\n";

$allUnits = Unit::all();

$unitMapping = [
    'ADE IRMA' => 'IRNA-AI',
    'AKRI' => 'AKRI',
    'AKUTANSI' => 'AKUNTANSI',
    'BENDAHARA' => 'BENDAHARA',
    'CASEMIX' => 'CASEMIX',
    'CUCI JAHIT' => 'CUCI-JAHIT',
    'CUSTOMER CARE' => 'CUSTOMER-CARE',
    'DEWI SARTIKA' => 'IRNA-DS',
    'DIKLIT' => 'DIKLIT',
    'FARMASI RAJAL' => 'FARMASI-RJ',
    'GIZI' => 'GIZI',
    'GUDANG UMUM' => 'GUDANG-UMUM',
    'HERLINA' => 'IRNA-HERLINA',
    'HOME CARE' => 'HOMECARE',
    'IBS' => 'IBS',
    'IGD' => 'IGD',
    'INTENSIVE CARE' => 'IRNA-IC',
    'IRJ' => 'IRJ',
    'IT' => 'IT',
    'K3LH' => 'K3LH',
    'KEAMANAN & TRANSPORTASI' => 'KEAMANAN',
    'KEPEGAWAIAN' => 'PSDM',
    'LA RUFFA' => 'IRNA-LA',
    'LABORATORIUM' => 'LABOR',
    'LOGISTIK' => 'LOGISTIK',
    'PASTORAL CARE' => 'PASTORAL',
    'PEMASARAN' => 'MARKETING',
    'PEMELIHARAAN SARANA MEDIS' => 'IPS-M',
    'PEMELIHARAAN SARANA UMUM' => 'IPS-U',
    'RADIOLOGI' => 'RADIOLOGI',
    'REHABILITASI MEDIK' => 'REHAB-MED',
    'REKAM MEDIS' => 'REKAM-MED',
    'SUDIRMAN' => 'IRNA-SUD',
    'THAMRIN' => 'IRNA-TH',
    'TU' => 'TU',
];

$matched = 0;
foreach ($files as $f) {
    $base = str_replace('.xlsx', '', $f);
    $mappedCode = $unitMapping[$base] ?? null;
    $unit = null;
    if ($mappedCode) {
        $unit = $allUnits->firstWhere('kode', $mappedCode);
    }
    if (!$unit) {
        $unit = $allUnits->first(fn($u) => stripos($u->nama, $base) !== false || stripos($base, $u->nama) !== false);
    }

    if ($unit) {
        $matched++;
        echo sprintf("✓ MATCH: [%s] -> Unit: [%s] %s (id: %d, indicators: %d)\n", 
            $base, $unit->kode, $unit->nama, $unit->id, $unit->indicators()->count());
    } else {
        echo sprintf("✗ UNMATCHED: [%s]\n", $base);
    }
}

echo "\nMatched: $matched / " . count($files) . "\n";
