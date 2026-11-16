<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CustomFormFormat;
use App\Models\Indicator;

$formats = CustomFormFormat::all();
echo "Total CustomFormFormats in DB: " . $formats->count() . "\n";
foreach ($formats as $f) {
    $ind = Indicator::find($f->indicator_id);
    echo "ID: {$f->id} | Ind: [{$ind?->kode}] {$ind?->judul} | Layout: {$f->layout_type} | Name: {$f->name}\n";
    echo "  Schema summary: " . json_encode(array_keys($f->schema ?? [])) . "\n";
}
