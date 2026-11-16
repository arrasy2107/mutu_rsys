<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IndicatorDailyRecord;

$records = IndicatorDailyRecord::where('unit_id', 12)
    ->where('tahun', 2026)
    ->where('hari', 0)
    ->get();

echo "DailyRecords with hari=0 (Specific Form Data) for Farmasi: " . $records->count() . "\n";
foreach ($records as $r) {
    echo "Ind ID: {$r->indicator_id} | Bln: {$r->bulan} | Data keys: " . json_encode(array_keys($r->data ?? [])) . "\n";
    if ($r->bulan == 9) {
        echo "  Bulan 9 Data preview: " . json_encode($r->data) . "\n";
    }
}
