<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;

foreach (Unit::orderBy('id')->get() as $u) {
    echo sprintf("ID: %2d | Kode: %-14s | Nama: %s\n", $u->id, $u->kode, $u->nama);
}
