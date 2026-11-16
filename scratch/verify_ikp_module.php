<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PatientSafetyIncident;
use Illuminate\Http\Request;

echo "=== VERIFY IKP CONTROLLERS & VIEWS DATA ===\n";

$admin = User::first();
auth()->login($admin);

// 1. Test IkpIncidentController@index
$indexController = app(\App\Http\Controllers\Ikp\IkpIncidentController::class);
$reqIndex = Request::create('/ikp', 'GET', ['tahun' => 2026]);
$reqIndex->setUserResolver(fn() => $admin);
try {
    $resIndex = $indexController->index($reqIndex);
    echo "IkpIncidentController@index: SUCCESS (Inertia Component: " . $resIndex->toResponse($reqIndex)->original->getName() . ")\n";
} catch (\Throwable $e) {
    echo "IkpIncidentController@index: ERROR - " . $e->getMessage() . "\n";
}

// 2. Test IkpIncidentController@show
$sample = PatientSafetyIncident::first();
if ($sample) {
    try {
        $resShow = $indexController->show($sample);
        echo "IkpIncidentController@show: SUCCESS (Inertia Component: " . $resShow->toResponse($reqIndex)->original->getName() . ")\n";
    } catch (\Throwable $e) {
        echo "IkpIncidentController@show: ERROR - " . $e->getMessage() . "\n";
    }
}

// 3. Test IkpMappingController@index
$mappingController = app(\App\Http\Controllers\Ikp\IkpMappingController::class);
$reqMap = Request::create('/ikp/pemetaan', 'GET', ['tahun' => 2026, 'periode_type' => 'tahunan']);
$reqMap->setUserResolver(fn() => $admin);
try {
    $resMap = $mappingController->index($reqMap);
    echo "IkpMappingController@index: SUCCESS (Inertia Component: " . $resMap->toResponse($reqMap)->original->getName() . ")\n";
} catch (\Throwable $e) {
    echo "IkpMappingController@index: ERROR - " . $e->getMessage() . "\n";
}

echo "\nSummary in Database:\n";
echo "- Total Insiden : " . PatientSafetyIncident::count() . "\n";
echo "- Sentinel      : " . PatientSafetyIncident::where('jenis_insiden', 'Sentinel')->count() . "\n";
echo "- KTD           : " . PatientSafetyIncident::where('jenis_insiden', 'KTD')->count() . "\n";
echo "- KTC           : " . PatientSafetyIncident::where('jenis_insiden', 'KTC')->count() . "\n";
echo "- KNC           : " . PatientSafetyIncident::where('jenis_insiden', 'KNC')->count() . "\n";
echo "- KPC           : " . PatientSafetyIncident::where('jenis_insiden', 'KPC')->count() . "\n";
echo "=== SELESAI VERIFIKASI ===\n";

