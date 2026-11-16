<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\IndicatorDailyRecord;
use App\Models\DataEntry;

$units = Unit::all();
echo "Checking IndicatorDailyRecord per Unit:\n";
$hasDaily = 0;
foreach ($units as $u) {
    $dailyCount = IndicatorDailyRecord::where('unit_id', $u->id)->count();
    $dataEntriesCount = DataEntry::where('unit_id', $u->id)->count();
    echo "Unit {$u->id} ({$u->kode}): {$dailyCount} DailyRecords | {$dataEntriesCount} DataEntries\n";
    if ($dailyCount > 0) $hasDaily++;
}

echo "\nSummary: Only $hasDaily / {$units->count()} units have IndicatorDailyRecord!\n";

