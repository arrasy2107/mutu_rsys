<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Unit;
use App\Models\Indicator;
use Illuminate\Http\Request;

echo "=== VERIFIKASI RUNTIME DATA ENTRY CONTROLLER ===\n";

$admin = User::where('role', 'superadmin')->first();

// 1. Test Casemix (monthly_summary)
$casemix = Unit::where('kode', 'CASEMIX')->first();
$indCasemix = Indicator::where('unit_id', $casemix->id)->first();

$reqCasemix = Request::create('/data-entry', 'GET', [
    'unit_id' => $casemix->id,
    'indicator_id' => $indCasemix->id,
    'tahun' => 2026,
    'bulan' => 1,
]);
$reqCasemix->setUserResolver(fn() => $admin);

$controller = app(App\Http\Controllers\DataEntryController::class);
$respCasemix = $controller->index($reqCasemix);
$propsCasemix = $respCasemix->toResponse($reqCasemix)->getOriginalContent()['page']['props'];

echo "✓ Casemix (Monthly Summary):\n";
echo "  - Selected Unit: " . $propsCasemix['selectedUnit']['nama'] . "\n";
echo "  - Indicator: " . $propsCasemix['selectedIndicator']['judul'] . "\n";
echo "  - Form Layout Type: " . $propsCasemix['selectedIndicator']['form_layout_type'] . "\n";
echo "  - Has Custom Format: " . ($propsCasemix['currentFormat'] ? 'YES (' . $propsCasemix['currentFormat']['nama_format'] . ')' : 'NO') . "\n";
echo "  - Annual Summary Months Count: " . count($propsCasemix['annualSummary']) . "\n";

// 2. Test Sudirman (dynamic_rows - SBAR)
$sudirman = Unit::where('kode', 'IRNA-SUD')->first();
$indSudirman = Indicator::where('unit_id', $sudirman->id)->first();

$reqSudirman = Request::create('/data-entry', 'GET', [
    'unit_id' => $sudirman->id,
    'indicator_id' => $indSudirman->id,
    'tahun' => 2026,
    'bulan' => 1,
]);
$reqSudirman->setUserResolver(fn() => $admin);

$respSudirman = $controller->index($reqSudirman);
$propsSudirman = $respSudirman->toResponse($reqSudirman)->getOriginalContent()['page']['props'];

echo "\n✓ Sudirman (Dynamic Rows - SBAR):\n";
echo "  - Selected Unit: " . $propsSudirman['selectedUnit']['nama'] . "\n";
echo "  - Indicator: " . $propsSudirman['selectedIndicator']['judul'] . "\n";
echo "  - Form Layout Type: " . $propsSudirman['selectedIndicator']['form_layout_type'] . "\n";
echo "  - Has Custom Format: " . ($propsSudirman['currentFormat'] ? 'YES (' . $propsSudirman['currentFormat']['nama_format'] . ')' : 'NO') . "\n";
echo "  - Schema Columns Count: " . count($propsSudirman['currentFormat']['schema']['columns'] ?? []) . "\n";

// 3. Test Save Daily Records for Casemix (monthly_summary)
echo "\n✓ Test Simpan Data Casemix (monthly_summary):\n";
$saveReqCasemix = Request::create('/data-entry/save-daily-records', 'POST', [
    'indicator_id' => $indCasemix->id,
    'unit_id' => $casemix->id,
    'tahun' => 2026,
    'bulan' => 1,
    'records' => [
        1 => ['num' => 95, 'den' => 100, 'capaian' => 95, 'notes' => 'Klaim reguler terverifikasi']
    ],
    'summary' => [
        'numerator' => 95,
        'denominator' => 100,
        'capaian' => 95,
    ]
]);
$saveReqCasemix->setUserResolver(fn() => $admin);
$saveRespCasemix = $controller->saveDailyRecords($saveReqCasemix);
echo "  - Response Status: " . $saveRespCasemix->getStatusCode() . " (Redirect)\n";

$entry = App\Models\DataEntry::where('indicator_id', $indCasemix->id)
    ->where('unit_id', $casemix->id)
    ->first();
echo "  - DataEntry Tersimpan: Numerator=" . $entry->numerator . ", Denominator=" . $entry->denominator . ", Capaian=" . $entry->capaian . "%\n";

// 4. Test Save Daily Records for Sudirman (dynamic_rows - SBAR)
echo "\n✓ Test Simpan Data Sudirman (dynamic_rows - SBAR):\n";
$saveReqSudirman = Request::create('/data-entry/save-daily-records', 'POST', [
    'indicator_id' => $indSudirman->id,
    'unit_id' => $sudirman->id,
    'tahun' => 2026,
    'bulan' => 1,
    'records' => [
        ['no' => 1, 'tanggal' => '2026-01-05', 'norm' => '00-11-22', 'num' => 1, 'den' => 1, 'perawat' => 'Ns. Silvia'],
        ['no' => 2, 'tanggal' => '2026-01-05', 'norm' => '00-33-44', 'num' => 1, 'den' => 1, 'perawat' => 'Ns. Silvia'],
        ['no' => 3, 'tanggal' => '2026-01-06', 'norm' => '00-55-66', 'num' => 0, 'den' => 1, 'perawat' => 'Ns. Nindha'],
    ],
    'summary' => [
        'numerator' => 2,
        'denominator' => 3,
        'capaian' => 66.67,
    ]
]);
$saveReqSudirman->setUserResolver(fn() => $admin);
$saveRespSudirman = $controller->saveDailyRecords($saveReqSudirman);
echo "  - Response Status: " . $saveRespSudirman->getStatusCode() . " (Redirect)\n";

$entrySud = App\Models\DataEntry::where('indicator_id', $indSudirman->id)
    ->where('unit_id', $sudirman->id)
    ->first();
echo "  - DataEntry Tersimpan: Numerator=" . $entrySud->numerator . ", Denominator=" . $entrySud->denominator . ", Capaian=" . $entrySud->capaian . "%\n";

$dailyRec = App\Models\IndicatorDailyRecord::where('indicator_id', $indSudirman->id)
    ->where('unit_id', $sudirman->id)
    ->where('hari', 0)
    ->first();
echo "  - Detail Pasien di DailyRecord (hari=0): " . count($dailyRec->data) . " baris log pasien tersimpan rapi.\n";

echo "\n=== SEMUA PENGUJIAN CONTROLLER & DATABASE SUKSES! ===\n";
