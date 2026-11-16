<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$units = App\Models\Unit::select('id', 'nama', 'kode')->orderBy('id')->get();
foreach ($units as $u) {
    echo $u->id . " | " . $u->kode . " | " . $u->nama . PHP_EOL;
}

