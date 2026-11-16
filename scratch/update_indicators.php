<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = \App\Models\Indicator::query()->update([
    'jenis' => json_encode(['IMN']), 
    'jenis_indikator' => 'Proses'
]);
echo "Updated $updated records.\n";
