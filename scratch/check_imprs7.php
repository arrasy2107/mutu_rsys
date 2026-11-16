<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IndicatorDailyRecord;
use App\Models\DataEntry;

echo "=== CHECKING IMP-RS7 (ID 23) ===\n";
for ($m = 1; $m <= 12; $m++) {
    $e = DataEntry::where('indicator_id', 23)->where('unit_id', 12)
        ->whereHas('period', fn($q) => $q->where('tahun', 2026)->where('bulan', $m))
        ->first();
    $rec0 = IndicatorDailyRecord::where('indicator_id', 23)->where('unit_id', 12)
        ->where('tahun', 2026)->where('bulan', $m)->where('hari', 0)->first();
    
    echo "Bulan {$m}: Entry N=" . ($e ? $e->numerator : '-') . " D=" . ($e ? $e->denominator : '-') . " Cap=" . ($e ? $e->capaian : '-') . " | Rec0: " . ($rec0 ? "EXISTS (keys: " . json_encode(array_keys($rec0->data ?? [])) . ")" : "NONE") . "\n";
}
