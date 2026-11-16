<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IndicatorDailyRecord;
use App\Models\Unit;

$unitsToCheck = ['IGD', 'IRNA-AI', 'GIZI', 'AKUNTANSI'];

foreach ($unitsToCheck as $code) {
    $u = Unit::where('kode', $code)->first();
    if (!$u) continue;
    echo "========================================\n";
    echo "Unit: {$u->nama} ({$u->kode})\n";
    echo "========================================\n";
    $records = IndicatorDailyRecord::where('unit_id', $u->id)->take(5)->get();
    foreach ($records as $r) {
        echo "Ind: {$r->indicator_id} | Thn: {$r->tahun} | Bln: {$r->bulan} | Hari: {$r->hari} | Tgl: {$r->tanggal}\n";
        echo "Data: " . json_encode($r->data) . "\n";
    }
}

