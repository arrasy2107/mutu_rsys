<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\RiskRegister;
use Illuminate\Http\Request;

echo "=== VERIFY MANRISK CONTROLLERS & DATA ===\n";

$admin = User::first();
auth()->login($admin);

$controller = app(\App\Http\Controllers\Manrisk\ManriskController::class);

// 1. Test index
$reqIndex = Request::create('/manrisk', 'GET', ['tahun' => 2026]);
$reqIndex->setUserResolver(fn() => $admin);
try {
    $resIndex = $controller->index($reqIndex);
    echo "ManriskController@index (All): SUCCESS (Inertia Component: " . $resIndex->toResponse($reqIndex)->original->getName() . ")\n";
} catch (\Throwable $e) {
    echo "ManriskController@index (All): ERROR - " . $e->getMessage() . "\n";
}

// 2. Test index with RS scope
$reqRs = Request::create('/manrisk', 'GET', ['tahun' => 2026, 'scope' => 'rs']);
$reqRs->setUserResolver(fn() => $admin);
try {
    $resRs = $controller->index($reqRs);
    echo "ManriskController@index (Scope RS): SUCCESS\n";
} catch (\Throwable $e) {
    echo "ManriskController@index (Scope RS): ERROR - " . $e->getMessage() . "\n";
}

// 3. Test show
$sample = RiskRegister::first();
if ($sample) {
    try {
        $resShow = $controller->show($sample);
        echo "ManriskController@show: SUCCESS (Inertia Component: " . $resShow->toResponse($reqIndex)->original->getName() . ")\n";
    } catch (\Throwable $e) {
        echo "ManriskController@show: ERROR - " . $e->getMessage() . "\n";
    }
}

// 4. Test matrix 5x5
$reqMatrix = Request::create('/manrisk/matrix', 'GET', ['tahun' => 2026]);
$reqMatrix->setUserResolver(fn() => $admin);
try {
    $resMatrix = $controller->matrix($reqMatrix);
    echo "ManriskController@matrix (5x5 Heatmap): SUCCESS (Inertia Component: " . $resMatrix->toResponse($reqMatrix)->original->getName() . ")\n";
} catch (\Throwable $e) {
    echo "ManriskController@matrix: ERROR - " . $e->getMessage() . "\n";
}

echo "\nSummary in Database:\n";
echo "- Total Risiko : " . RiskRegister::count() . "\n";
echo "- Ekstrem (Merah) : " . RiskRegister::where('inherent_band', 'ekstrem')->count() . "\n";
echo "- Tinggi (Oranye) : " . RiskRegister::where('inherent_band', 'tinggi')->count() . "\n";
echo "- Sedang (Kuning) : " . RiskRegister::where('inherent_band', 'sedang')->count() . "\n";
echo "- MFK              : " . RiskRegister::where('ruang_lingkup', 'mfk')->count() . "\n";
echo "- Prioritas RS     : " . RiskRegister::where(fn($q) => $q->where('ruang_lingkup', 'rs')->orWhere('is_rs_priority', true))->count() . "\n";
echo "=== SELESAI VERIFIKASI MANRISK ===\n";

