<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Unit;
use App\Models\Indicator;
use Illuminate\Http\Request;

echo "=== TEST READINESS CONTROLLER & ROUTING ===\n";

$admin = User::first();
auth()->login($admin);

// Test DataEntryController
$controller = app(\App\Http\Controllers\DataEntryController::class);
$req = Request::create('/data-entry', 'GET', ['tahun' => 2026, 'bulan' => 9]);
$req->setUserResolver(fn() => $admin);
try {
    $res = $controller->index($req);
    echo "DataEntryController@index: SUCCESS (Inertia Component: " . $res->toResponse($req)->original->getName() . ")\n";
} catch (\Throwable $e) {
    echo "DataEntryController@index: ERROR - " . $e->getMessage() . "\n";
}

// Test DashboardController
$dashController = app(\App\Http\Controllers\DashboardController::class);
$dashReq = Request::create('/dashboard', 'GET', ['tahun' => 2026]);
$dashReq->setUserResolver(fn() => $admin);
try {
    $dashRes = $dashController->index($dashReq);
    echo "DashboardController@index: SUCCESS\n";
} catch (\Throwable $e) {
    echo "DashboardController@index: ERROR - " . $e->getMessage() . "\n";
}

// Test LaporanMutuController if exists
if (class_exists(\App\Http\Controllers\LaporanMutuController::class)) {
    $lapController = app(\App\Http\Controllers\LaporanMutuController::class);
    $lapReq = Request::create('/laporan-mutu', 'GET', ['tahun' => 2026]);
    try {
        $lapRes = $lapController->index($lapReq);
        echo "LaporanMutuController@index: SUCCESS\n";
    } catch (\Throwable $e) {
        echo "LaporanMutuController@index: ERROR - " . $e->getMessage() . "\n";
    }
}
