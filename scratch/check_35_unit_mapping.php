<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;

$files = [
    "ADE IRMA.xlsx",
    "AKRI.xlsx",
    "AKUTANSI.xlsx",
    "BENDAHARA.xlsx",
    "CASEMIX.xlsx",
    "CUCI JAHIT.xlsx",
    "CUSTOMER CARE.xlsx",
    "DEWI SARTIKA.xlsx",
    "DIKLIT.xlsx",
    "FARMASI.xlsx",
    "GIZI.xlsx",
    "GUDANG UMUM.xlsx",
    "HERLINA.xlsx",
    "HOME CARE.xlsx",
    "IBS.xlsx",
    "IGD.xlsx",
    "INTENSIVE CARE.xlsx",
    "IRJ.xlsx",
    "IT.xlsx",
    "K3LH.xlsx",
    "KEAMANAN & TRANSPORTASI.xlsx",
    "KEPEGAWAIAN.xlsx",
    "LA RUFFA.xlsx",
    "LABORATORIUM.xlsx",
    "LOGISTIK.xlsx",
    "PASTORAL CARE.xlsx",
    "PEMASARAN.xlsx",
    "PEMELIHARAAN SARANA MEDIS.xlsx",
    "PEMELIHARAAN SARANA UMUM.xlsx",
    "RADIOLOGI.xlsx",
    "REHABILITASI MEDIK.xlsx",
    "REKAM MEDIS.xlsx",
    "SUDIRMAN.xlsx",
    "THAMRIN.xlsx",
    "TU.xlsx",
];

$allUnits = Unit::all();

echo "MAPPING 35 EXCEL FILES TO UNITS TABLE:\n";
$matched = 0;
$unmatched = [];

foreach ($files as $f) {
    $cleanName = str_replace('.xlsx', '', $f);
    // Try exact or fuzzy match
    $u = Unit::where('nama', $cleanName)
        ->orWhere('kode', $cleanName)
        ->orWhere('nama', 'like', "%{$cleanName}%")
        ->orWhere('kode', 'like', "%{$cleanName}%")
        ->first();
    
    // Special handlings if needed
    if (!$u) {
        if ($cleanName === 'AKUTANSI') {
            $u = Unit::where('nama', 'like', '%akuntansi%')->orWhere('kode', 'like', '%akt%')->first();
        } elseif ($cleanName === 'FARMASI') {
            $u = Unit::where('kode', 'FARMASI-RJ')->first();
        } elseif ($cleanName === 'PEMASARAN') {
            $u = Unit::where('kode', 'MKT')->orWhere('nama', 'like', '%pemasaran%')->first();
        } elseif ($cleanName === 'TU') {
            $u = Unit::where('kode', 'TU')->orWhere('nama', 'like', '%tata usaha%')->first();
        } elseif ($cleanName === 'K3LH') {
            $u = Unit::where('kode', 'like', '%k3%')->orWhere('nama', 'like', '%k3%')->first();
        }
    }

    if ($u) {
        $matched++;
        echo sprintf("✓ [%-28s] -> ID: %2d | Kode: %-12s | Nama: %s\n", $cleanName, $u->id, $u->kode, $u->nama);
    } else {
        $unmatched[] = $cleanName;
        echo sprintf("✗ [%-28s] -> NOT FOUND!\n", $cleanName);
    }
}

echo "\nSummary: {$matched} matched, " . count($unmatched) . " unmatched.\n";
if (!empty($unmatched)) {
    echo "Unmatched: " . implode(', ', $unmatched) . "\n";
}
