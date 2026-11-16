<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$prop = "Tables_in_" . $dbName;

echo "Database: {$dbName}\n";
echo "Total Tables: " . count($tables) . "\n\n";

foreach ($tables as $t) {
    $tName = $t->$prop;
    $count = DB::table($tName)->count();
    echo str_pad($tName, 32) . ": {$count} rows\n";
}
