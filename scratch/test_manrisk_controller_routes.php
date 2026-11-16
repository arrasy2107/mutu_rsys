<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\RiskRegister;
use Illuminate\Http\Request;
use App\Http\Controllers\Manrisk\ManriskController;

echo "=== TESTING MANRISK CONTROLLER ENDPOINTS ===" . PHP_EOL;

$user = User::first();
if (!$user) {
    die("No user found in database!\n");
}

$controller = new ManriskController();

// 1. Index
$reqIndex = Request::create('/manrisk', 'GET', ['tahun' => 2026]);
$reqIndex->setUserResolver(fn() => $user);
$resIndex = $controller->index($reqIndex);
echo "[OK] GET /manrisk returned response with " . count($resIndex->toResponse($reqIndex)->getOriginalContent()['page']['props']['risks']['data']) . " risks." . PHP_EOL;

// 2. Index Scope RS
$reqRs = Request::create('/manrisk', 'GET', ['tahun' => 2026, 'scope' => 'rs']);
$reqRs->setUserResolver(fn() => $user);
$resRs = $controller->index($reqRs);
echo "[OK] GET /manrisk?scope=rs returned " . count($resRs->toResponse($reqRs)->getOriginalContent()['page']['props']['risks']['data']) . " priority risks." . PHP_EOL;

// 3. Matrix
$reqMatrix = Request::create('/manrisk/matrix', 'GET', ['tahun' => 2026]);
$reqMatrix->setUserResolver(fn() => $user);
$resMatrix = $controller->matrix($reqMatrix);
echo "[OK] GET /manrisk/matrix returned total: " . $resMatrix->toResponse($reqMatrix)->getOriginalContent()['page']['props']['total'] . PHP_EOL;

// 4. Show
$firstRisk = RiskRegister::first();
$resShow = $controller->show($firstRisk);
echo "[OK] GET /manrisk/{$firstRisk->id} returned show response for {$firstRisk->nomor_register}." . PHP_EOL;

echo "=== ALL CONTROLLER ENDPOINTS OPERATIONAL! ===" . PHP_EOL;

