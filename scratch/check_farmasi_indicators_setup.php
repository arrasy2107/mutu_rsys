<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\Indicator;
use App\Models\CustomFormFormat;
use App\Models\DataEntry;
use App\Models\IndicatorDailyRecord;

$unit = Unit::where('kode', 'FARMASI-RJ')->first();
$indicators = Indicator::where('unit_id', $unit->id)->get();

echo "INDICATORS FOR FARMASI (Unit ID: {$unit->id}):\n";
foreach ($indicators as $ind) {
    echo "\n=== ID: {$ind->id} | [{$ind->kode}] {$ind->judul} ===\n";
    echo "form_layout_type: " . ($ind->form_layout_type ?: '(null)') . "\n";
    echo "form_schema: " . json_encode($ind->form_schema) . "\n";
    $cff = CustomFormFormat::where('indicator_id', $ind->id)->first();
    echo "custom_form_format: " . ($cff ? "ID {$cff->id}, Name: {$cff->name}, Layout: {$cff->layout_type}" : "NONE") . "\n";
    
    // Check data entries count per month
    $entries = DataEntry::where('indicator_id', $ind->id)
        ->where('unit_id', $unit->id)
        ->whereHas('period', fn($q) => $q->where('tahun', 2026))
        ->with('period')
        ->get();
    echo "DataEntries in 2026: " . $entries->count() . " months.\n";
    foreach ($entries as $e) {
        echo "  Bln {$e->period->bulan}: N={$e->numerator}, D={$e->denominator}, Cap={$e->capaian}%, Status={$e->status}\n";
    }

    // Check daily records
    $dailyCount = IndicatorDailyRecord::where('indicator_id', $ind->id)
        ->where('unit_id', $unit->id)
        ->where('tahun', 2026)
        ->count();
    echo "DailyRecords in 2026: {$dailyCount} records.\n";
}
