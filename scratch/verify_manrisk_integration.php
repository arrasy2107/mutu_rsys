<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RiskRegister;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

echo "=== VERIFYING MANRISK INTEGRATION IN MYSQL ===" . PHP_EOL;

$total = RiskRegister::count();
echo "Total Risk Registers: {$total}" . PHP_EOL;
if ($total === 0) {
    die("ERROR: Table risk_registers is empty!\n");
}

$rsPriority = RiskRegister::where('is_rs_priority', true)->count();
echo "RS Priority Risks: {$rsPriority}" . PHP_EOL;

$karsCount = RiskRegister::whereNotNull('kategori_kars')->count();
echo "KARS 8 Pillars Risks: {$karsCount}" . PHP_EOL;

$controllableCount = RiskRegister::where('kontrolabilitas', 'C')->count();
$uncontrollableCount = RiskRegister::where('kontrolabilitas', 'UC')->count();
echo "Controllability: C={$controllableCount}, UC={$uncontrollableCount}" . PHP_EOL;

// Check distribution by band
$bandDist = RiskRegister::select('inherent_band', DB::raw('count(*) as cnt'))
    ->groupBy('inherent_band')
    ->pluck('cnt', 'inherent_band');

echo "Distribution by Inherent Band:" . PHP_EOL;
foreach ($bandDist as $b => $cnt) {
    echo "  - {$b}: {$cnt}" . PHP_EOL;
}

// Check sample KARS priority records
echo PHP_EOL . "=== SAMPLE 8 PILAR KARS 2026 RECORDS ===" . PHP_EOL;
$karsRecords = RiskRegister::whereNotNull('kategori_kars')->with('unit')->get();
foreach ($karsRecords as $kr) {
    echo sprintf(
        "[%s] KARS: %-18s | Unit: %-25s | PxDxB: %dx%dx%.2f = %.2f | Keg: %s\n",
        $kr->nomor_register,
        $kr->kategori_kars,
        $kr->unit?->nama ?? $kr->pemilik_risiko,
        $kr->inherent_probabilitas,
        $kr->inherent_dampak,
        $kr->bobot,
        $kr->skoring_akhir,
        substr($kr->kegiatan, 0, 45)
    );
}

echo PHP_EOL . "=== VERIFICATION PASSED SUCCESSFULLY! ===" . PHP_EOL;

