<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$units = App\Models\Unit::select('id', 'nama', 'kode')->get();
$map = [];
foreach ($units as $u) {
    $map[] = ['id' => $u->id, 'kode' => $u->kode, 'nama' => $u->nama];
}
file_put_contents(__DIR__ . '/db_units.json', json_encode($map, JSON_PRETTY_PRINT));
echo "Saved " . count($map) . " units.\n";

