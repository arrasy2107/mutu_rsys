<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Indicator;

echo "Total Indicators: " . Indicator::count() . "\n";
echo "Null Unit: " . Indicator::whereNull('unit_id')->count() . "\n";
foreach (Indicator::all() as $i) {
    echo sprintf("[%d] %s - %s (unit_id: %s)\n", $i->id, $i->kode, substr($i->judul, 0, 50), $i->unit_id ?? 'NULL');
}
