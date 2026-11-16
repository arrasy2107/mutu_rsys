<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\Indicator;
use App\Models\DataEntry;

$units = Unit::withCount('indicators')->get();
echo "Units with Indicators:\n";
$withInd = 0;
$withData = 0;
foreach ($units as $u) {
    $dataEntriesCount = DataEntry::where('unit_id', $u->id)->count();
    echo "{$u->id} | {$u->kode} - {$u->nama}: {$u->indicators_count} Indikator | {$dataEntriesCount} DataEntries\n";
    if ($u->indicators_count > 0) $withInd++;
    if ($dataEntriesCount > 0) $withData++;
}

echo "\nSummary: $withInd / {$units->count()} units have indicators. Only $withData / {$units->count()} units have DataEntries!\n";

