<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$allJenis = App\Models\Indicator::pluck('jenis')->flatten()->unique()->values();
echo "Distinct jenis:\n" . json_encode($allJenis) . "\n";

$prioritasIndicators = App\Models\Indicator::where('jenis', 'like', '%IMP%')
    ->orWhere('jenis', 'like', '%Prioritas%')
    ->orWhere('kode', 'like', '%IMP%')
    ->select('id', 'kode', 'judul', 'jenis', 'unit_id')
    ->get();
echo "Found " . $prioritasIndicators->count() . " IMP indicators:\n";
foreach ($prioritasIndicators->take(5) as $pi) {
    echo "  {$pi->kode} - {$pi->judul} (Unit: {$pi->unit_id}) - " . json_encode($pi->jenis) . "\n";
}

