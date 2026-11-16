<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\Indicator;
use App\Models\DataEntry;
use App\Models\IndicatorDailyRecord;
use App\Models\IndicatorPeriod;

echo "=== VERIFIKASI DATA IMUT 35 UNIT DI DATABASE ===\n";

$totalUnitsWithInds = Unit::has('indicators')->count();
$totalInds = Indicator::count();
$totalEntries = DataEntry::count();
$totalDaily = IndicatorDailyRecord::count();
$totalPeriods = IndicatorPeriod::count();

echo "Total Unit dengan Indikator: {$totalUnitsWithInds} unit\n";
echo "Total Indikator Terdaftar   : {$totalInds} indikator\n";
echo "Total Indicator Periods    : {$totalPeriods} periode\n";
echo "Total Data Entries (Bulanan): {$totalEntries} entri\n";
echo "Total Indicator Daily Records: {$totalDaily} records\n\n";

echo "SAMPLE REKAP PER UNIT:\n";
$units = Unit::has('indicators')->withCount(['indicators', 'dataEntries'])->orderBy('nama')->take(10)->get();
foreach ($units as $u) {
    echo sprintf("- %-30s | %2d Indikator | %3d Data Entri\n", $u->nama, $u->indicators_count, $u->data_entries_count);
}

// Cek sampel perhitungan capaian
$sampleEntry = DataEntry::where('capaian', '>', 0)->with(['indicator', 'unit'])->first();
if ($sampleEntry) {
    echo "\nSample Entry:\n";
    echo "Unit     : {$sampleEntry->unit->nama}\n";
    echo "Indikator: {$sampleEntry->indicator->judul}\n";
    echo "Num/Den  : {$sampleEntry->numerator} / {$sampleEntry->denominator}\n";
    echo "Capaian  : {$sampleEntry->capaian}%\n";
    echo "Status   : {$sampleEntry->status}\n";
}
