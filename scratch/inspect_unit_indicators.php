<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\Indicator;
use App\Models\CustomFormFormat;

echo "=== DAFTAR UNIT & INDIKATOR MUTU ===\n";
$units = Unit::with('indicators')->orderBy('nama')->get();
foreach ($units as $u) {
    echo sprintf("[%s] %s (Indikator: %d)\n", $u->kode, $u->nama, $u->indicators->count());
    foreach ($u->indicators as $ind) {
        $fmt = CustomFormFormat::where('indicator_id', $ind->id)->first();
        echo sprintf("   - [%s] %s (Layout: %s, CustomFmt: %s)\n", 
            $ind->kode, 
            substr($ind->judul, 0, 45), 
            $ind->form_layout_type ?: 'daily_grid',
            $fmt ? $fmt->nama_format : 'none'
        );
    }
}
