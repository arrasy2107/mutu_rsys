<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$units = App\Models\Unit::where('nama', 'like', '%farmasi%')->orWhere('kode', 'like', '%farmasi%')->get();
echo "UNITS FOUND: " . $units->count() . PHP_EOL;
foreach ($units as $u) {
    echo "ID: " . $u->id . " | Kode: " . $u->kode . " | Nama: " . $u->nama . PHP_EOL;
    $inds = $u->indicators;
    echo "  Indicators count: " . $inds->count() . PHP_EOL;
    foreach ($inds as $ind) {
        $jenisStr = is_array($ind->jenis) ? json_encode($ind->jenis) : (string)$ind->jenis;
        echo "    - ID: " . $ind->id . " | [" . $ind->kode . "] " . $ind->judul . " (target: " . $ind->target . " " . $ind->satuan . ", jenis: " . $jenisStr . ")" . PHP_EOL;
    }
}
