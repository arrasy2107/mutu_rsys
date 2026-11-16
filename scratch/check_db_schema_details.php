<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "DATA_ENTRIES COLUMNS:\n";
print_r(Schema::getColumnListing('data_entries'));

echo "\nINDICATOR_DAILY_RECORDS COLUMNS:\n";
print_r(Schema::getColumnListing('indicator_daily_records'));

echo "\nCUSTOM_INDICATOR_FORMATS TABLE EXISTS?\n";
echo Schema::hasTable('custom_indicator_formats') ? "YES\n" : "NO\n";
if (Schema::hasTable('custom_indicator_formats')) {
    print_r(Schema::getColumnListing('custom_indicator_formats'));
}

$sampleEntry = DB::table('data_entries')->where('unit_id', 12)->first();
echo "\nSAMPLE DATA ENTRY FOR FARMASI:\n";
print_r($sampleEntry);
