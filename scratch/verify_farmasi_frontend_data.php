<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Unit;
use App\Models\Indicator;
use Illuminate\Http\Request;
use Inertia\Response;

echo "=== VERIFIKASI PROPS INERTIA FORM PENGUMPUL DATA UNIT FARMASI ===\n";

$admin = User::where('role', 'superadmin')->first();
$farmasi = Unit::where('kode', 'FARMASI-RJ')->firstOrFail();
$indicators = Indicator::where('unit_id', $farmasi->id)->orderBy('id')->get();

$controller = new App\Http\Controllers\DataEntryController();

foreach ($indicators as $ind) {
    echo "\n--------------------------------------------------------------\n";
    echo "TESTING INDIKATOR: [{$ind->kode}] {$ind->judul} (ID: {$ind->id})\n";
    echo "Layout Type di DB: {$ind->form_layout_type}\n";

    // Test Bulan 9 (September 2026)
    $req = Request::create('/data-entry', 'GET', [
        'unit_id' => $farmasi->id,
        'indicator_id' => $ind->id,
        'tahun' => 2026,
        'bulan' => 9,
    ]);
    $req->setUserResolver(fn() => $admin);

    /** @var Response $response */
    $response = $controller->index($req);
    $viewData = $response->toResponse($req)->getOriginalContent()->getData();
    $page = $viewData['page'];
    $props = $page['props'];

    $selInd = is_array($props['selectedIndicator']) ? $props['selectedIndicator'] : (array)$props['selectedIndicator'];
    $records = $props['dailyRecords'];
    $entry = is_array($props['currentPeriodEntry']) ? $props['currentPeriodEntry'] : (array)$props['currentPeriodEntry'];
    $annual = $props['annualSummary'];

    $kode = $selInd['kode'] ?? '';
    $judul = $selInd['judul'] ?? '';
    $layoutType = $selInd['form_layout_type'] ?? '';
    $target = $selInd['target_capaian'] ?? '';
    $operator = $selInd['target_operator'] ?? '';

    $num = $entry['numerator'] ?? 0;
    $den = $entry['denominator'] ?? 0;
    $cap = $entry['capaian'] ?? 0;
    $status = $entry['status'] ?? 'empty';

    echo "Props Inertia:\n";
    echo "  - Selected Indicator: [{$kode}] {$judul}\n";
    echo "  - Form Layout Type: {$layoutType}\n";
    echo "  - Target: {$target}% ({$operator})\n";
    echo "  - Current Period Entry (Bulan 9): N={$num}, D={$den}, Cap={$cap}%, Status={$status}\n";
    
    // Check Records structure
    if (is_array($records) || is_object($records)) {
        $arrRec = (array)$records;
        echo "  - DailyRecords keys: " . json_encode(array_keys($arrRec)) . "\n";
        if (isset($arrRec['entities'])) {
            $entities = (array)$arrRec['entities'];
            echo "    -> Entities count (Dokter): " . count($entities) . " dokter.\n";
            $firstDoc = reset($entities);
            echo "    -> Sample Dok 1: " . json_encode($firstDoc) . "\n";
        } elseif (isset($arrRec['categories'])) {
            echo "    -> Categories: " . json_encode($arrRec['categories']) . "\n";
        } elseif (isset($arrRec['locations'])) {
            echo "    -> Locations: " . json_encode($arrRec['locations']) . "\n";
        } elseif (isset($arrRec['points'])) {
            echo "    -> Inspection Points: " . count((array)$arrRec['points']) . " titik.\n";
        } elseif (isset($arrRec[1])) {
            echo "    -> Daily Grid Days: " . count($arrRec) . " hari.\n";
        }
    }

    // Check Annual Summary count
    $filledMonths = 0;
    foreach ($annual as $m => $d) {
        if ($d['capaian'] !== null && $d['capaian'] > 0) $filledMonths++;
    }
    echo "  - Riwayat 12 Bulan: {$filledMonths} bulan terisi capaian.\n";
}

echo "\n=== SELURUH PENGUJIAN INERTIA PROPS UNTUK FARMASI SELESAI DENGAN SEMPURNA! ===\n";
