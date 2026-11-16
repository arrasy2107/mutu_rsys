<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CustomFormFormat;

$cff = CustomFormFormat::find(1);
echo "ID: " . $cff->id . "\n";
echo "Name: " . $cff->name . "\n";
echo "Layout: " . $cff->layout_type . "\n";
echo "Schema: " . json_encode($cff->schema, JSON_PRETTY_PRINT) . "\n";
