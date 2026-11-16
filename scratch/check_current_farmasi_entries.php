<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\Indicator;
use App\Models\DataEntry;
use App\Models\IndicatorDailyRecord;
use App\Models\CustomIndicatorFormat;

$unit = Unit::where('kode', 'FARMASI-RJ')->first();
echo "UNIT: " . $unit->nama . " (ID: " . $unit->id . ")\n";

$entries = DataEntry::where('unit_id', $unit->id)->get();
echo "Total DataEntries: " . $entries->count() . "\n";
foreach ($entries as $e) {
    $ind = Indicator::find($e->indicator_id);
    echo "- Ind: [" . ($ind ? $ind->kode : '?') . "] " . ($ind ? $ind->judul : '?') . " | Thn: " . $e->tahun . " Bln: " . $e->bulan . " | N=" . $e->numerator . " D=" . $e->denominator . " Cap=" . $e->capaian . "%\n";
}

$daily = IndicatorDailyRecord::where('unit_id', $unit->id)->get();
echo "Total DailyRecords: " . $daily->count() . "\n";

$formats = CustomIndicatorFormat::where('unit_id', $unit->id)->get();
echo "Total Custom Formats: " . $formats->count() . "\n";
foreach ($formats as $f) {
    echo "- Format ID: " . $f->id . " | IndID: " . $f->indicator_id . " | Layout: " . $f->layout_type . " | Name: " . $f->name . "\n";
}
