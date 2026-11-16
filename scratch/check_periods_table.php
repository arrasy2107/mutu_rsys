<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "INDICATOR_PERIODS COLUMNS:\n";
print_r(Schema::getColumnListing('indicator_periods'));

$sample = DB::table('indicator_periods')->first();
echo "\nSAMPLE RECORD:\n";
print_r($sample);
