<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'units',
    'users',
    'periods',
    'indicators',
    'indicator_form_fields',
    'data_entries',
    'indicator_daily_records',
    'indicator_selections',
    'indicator_selection_items',
    'indicator_profiles',
    'custom_form_formats',
    'validations',
    'pdsas',
    'rtls',
    'reports',
];

echo "=== TABLE COUNTS IN MUTU_RSYS ===\n";
foreach ($tables as $t) {
    if (Schema::hasTable($t)) {
        $count = DB::table($t)->count();
        echo str_pad($t, 28) . ": {$count} rows\n";
    } else {
        echo str_pad($t, 28) . ": [DOES NOT EXIST]\n";
    }
}
