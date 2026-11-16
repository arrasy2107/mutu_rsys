<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomFormFormatController;
use App\Http\Controllers\DataAnalysisController;
use App\Http\Controllers\DataEntryController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorSelectionController;
use App\Http\Controllers\SupervisiController;
use App\Http\Controllers\PdsaController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\Auth\ForceChangePasswordController;
use App\Http\Controllers\Hospital\HospitalAggregationController;
use App\Http\Controllers\Hospital\HospitalAnalysisController;
use App\Http\Controllers\Hospital\HospitalPdsaController;
use App\Http\Controllers\Hospital\HospitalDataCapaianController;
use App\Http\Controllers\Hospital\HospitalRtlController;
use App\Http\Controllers\Hospital\HospitalBenchmarkController;
use App\Http\Controllers\Unit\UnitRtlController;
use App\Http\Controllers\Ikp\IkpIncidentController;
use App\Http\Controllers\Ikp\IkpMappingController;
use App\Http\Controllers\Manrisk\ManriskController;
use App\Http\Controllers\Hva\HvaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\ForcePasswordChange::class])->group(function () {

    // Force Change Password (wajib sebelum akses modul lain)
    Route::get('/ganti-password', [ForceChangePasswordController::class, 'show'])->name('password.force-change');
    Route::post('/ganti-password', [ForceChangePasswordController::class, 'update'])->name('password.force-change.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/supervisi', [SupervisiController::class, 'index'])->name('supervisi.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Pemilihan Indikator Mutu ─────────────────────────────────────────────
    Route::get('indicator-selections/export-rekap', [IndicatorSelectionController::class, 'exportRekapExcel'])
        ->name('indicator-selections.export-rekap');
    Route::get('indicator-selections/{indicatorSelection}/export-pdf', [IndicatorSelectionController::class, 'exportPdf'])
        ->name('indicator-selections.export-pdf');
    Route::get('indicator-selections/{indicatorSelection}/export-excel', [IndicatorSelectionController::class, 'exportExcel'])
        ->name('indicator-selections.export-excel');
    Route::resource('indicator-selections', IndicatorSelectionController::class);
    Route::get('indicator-selections/item/{item}/create-profile', [IndicatorSelectionController::class, 'createProfile'])
        ->name('indicator-selections.create-profile');

    // ─── Manajemen Indikator (Kamus Mutu) ─────────────────────────────────────
    Route::match(['get', 'post'], 'indicators/export-pdf', [IndicatorController::class, 'exportPdf'])->name('indicators.export-pdf');
    Route::match(['get', 'post'], 'indicators/export-excel', [IndicatorController::class, 'exportExcel'])->name('indicators.export-excel');
    Route::resource('indicators', IndicatorController::class);
    Route::post('indicators/{indicator}/toggle-active', [IndicatorController::class, 'toggleActive'])->name('indicators.toggle-active');

    // ─── Manajemen Unit ────────────────────────────────────────────────────────
    Route::resource('units', UnitController::class);

    // ─── Manajemen User ────────────────────────────────────────────────────────
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // ─── Entri Data (Form Pengumpul Data / Tally Sheet) ───────────────────────
    Route::prefix('data-entry')->name('data-entry.')->group(function () {
        Route::get('/', [DataEntryController::class, 'index'])->name('index');
        Route::post('/daily-records', [DataEntryController::class, 'saveDailyRecords'])->name('save-daily-records');
        Route::post('/fields/{indicator}', [DataEntryController::class, 'saveFormFields'])->name('save-form-fields');
        Route::get('/export-excel', [DataEntryController::class, 'exportExcel'])->name('export-excel');
        Route::post('/submit-period', [DataEntryController::class, 'submitPeriod'])->name('submit-period');
        Route::get('/create/{indicator}/{tahun}/{bulan}', [DataEntryController::class, 'create'])->name('create');
        Route::post('/', [DataEntryController::class, 'store'])->name('store');
        Route::get('/{dataEntry}', [DataEntryController::class, 'show'])->name('show');
        Route::get('/{dataEntry}/edit', [DataEntryController::class, 'edit'])->name('edit');
        Route::put('/{dataEntry}', [DataEntryController::class, 'update'])->name('update');
        Route::post('/{dataEntry}/submit', [DataEntryController::class, 'submit'])->name('submit');
        Route::delete('/{dataEntry}', [DataEntryController::class, 'destroy'])->name('destroy');
    });

    // ─── Custom Form Formats (Format Form Pengumpul Data Kustom) ─────────────
    Route::post('/custom-form-formats', [CustomFormFormatController::class, 'store'])->name('custom-form-formats.store');
    Route::delete('/custom-form-formats/{customFormFormat}', [CustomFormFormatController::class, 'destroy'])->name('custom-form-formats.destroy');
    Route::get('/custom-form-formats/schema/{indicator}', [CustomFormFormatController::class, 'getSchema'])->name('custom-form-formats.schema');

    // ─── Validasi & Verifikasi ─────────────────────────────────────────────────
    Route::prefix('validation')->name('validation.')->group(function () {
        Route::get('/', [ValidationController::class, 'index'])->name('index');
        Route::get('/export-report', [ValidationController::class, 'exportReport'])->name('export-report');
        Route::get('/export-pdf', [ValidationController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/indicator-periods', [ValidationController::class, 'getIndicatorPeriods'])->name('indicator-periods');
        Route::post('/submit-entry', [ValidationController::class, 'submitEntry'])->name('submit-entry');
        Route::get('/{dataEntry}', [ValidationController::class, 'show'])->name('show');
        Route::get('/{dataEntry}/export-excel', [ValidationController::class, 'exportExcel'])->name('export-excel');
        Route::get('/{dataEntry}/export-pdf', [ValidationController::class, 'exportSinglePdf'])->name('export-single-pdf');
        Route::post('/{dataEntry}/approve', [ValidationController::class, 'approve'])->name('approve');
        Route::post('/{dataEntry}/reject', [ValidationController::class, 'reject'])->name('reject');
        Route::post('/{dataEntry}/revision', [ValidationController::class, 'revision'])->name('revision');
    });

    // ─── Data & Analisa Mutu ──────────────────────────────────────────────────
    Route::prefix('data-analysis')->name('data-analysis.')->group(function () {
        Route::get('/', [DataAnalysisController::class, 'index'])->name('index');
        Route::get('/export-excel', [DataAnalysisController::class, 'exportExcel'])->name('export-excel');
    });

    // ─── Sesi Pemilihan Upload Berkas Pendukung (Butir B.1.a) ─────────────────
    Route::post('/indicator-selections/{indicatorSelection}/upload-berkas', [IndicatorSelectionController::class, 'uploadBerkas'])->name('indicator-selections.upload-berkas');
    Route::delete('/indicator-selections/{indicatorSelection}/delete-berkas', [IndicatorSelectionController::class, 'deleteBerkas'])->name('indicator-selections.delete-berkas');

    // ─── Unit RTL (Rencana Tindak Lanjut Unit - Butir A.6) ─────────────────────
    Route::prefix('unit/rtl')->name('unit.rtl.')->group(function () {
        Route::get('/', [UnitRtlController::class, 'index'])->name('index');
        Route::post('/{pdsa}/upload-bukti', [UnitRtlController::class, 'uploadBukti'])->name('upload-bukti');
        Route::post('/{pdsa}/update-status', [UnitRtlController::class, 'updateStatus'])->name('update-status');
    });

    // ─── Siklus PDCA / PDSA Unit (Butir A.7) ──────────────────────────────────
    Route::get('/pdsa/export-rtl', [PdsaController::class, 'exportRtl'])->name('pdsa.export-rtl');
    Route::resource('pdsa', PdsaController::class);
    Route::post('/pdsa/{pdsa}/submit', [PdsaController::class, 'submit'])->name('pdsa.submit');
    Route::post('/pdsa/{pdsa}/review', [PdsaController::class, 'review'])->name('pdsa.review');

    // ─── Indikator Mutu Tingkat Rumah Sakit (Butir B.1 s.d B.7) ────────────────
    Route::prefix('hospital')->name('hospital.')->group(function () {
        Route::get('/data-capaian', [HospitalDataCapaianController::class, 'index'])->name('data-capaian.index');
        Route::get('/rtl', [HospitalRtlController::class, 'index'])->name('rtl.index');
        Route::post('/rtl/{pdsa}/verify', [HospitalRtlController::class, 'verify'])->name('rtl.verify');
        Route::get('/aggregation', [HospitalAggregationController::class, 'index'])->name('aggregation.index');
        Route::get('/analysis', [HospitalAnalysisController::class, 'index'])->name('analysis.index');
        Route::get('/pdsa', [HospitalPdsaController::class, 'index'])->name('pdsa.index');
        Route::get('/benchmark', [HospitalBenchmarkController::class, 'index'])->name('benchmark.index');
    });

    // ─── Report Generator ──────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
        Route::get('/download/{log}', [ReportController::class, 'download'])->name('download');
    });

    // ─── Modul II: Insiden Keselamatan Pasien (IKP - Butir II.A & II.B) ───────
    Route::get('/ikp/pemetaan', [IkpMappingController::class, 'index'])->name('ikp.mapping');
    Route::post('/ikp/{incident}/upload-bukti', [IkpIncidentController::class, 'uploadBukti'])->name('ikp.upload-bukti');
    Route::resource('ikp', IkpIncidentController::class);

    // ─── Modul III: Manajemen Risiko (Manrisk - Unit, RS, dan MFK) ───────────
    Route::get('/manrisk/matrix', [ManriskController::class, 'matrix'])->name('manrisk.matrix');
    Route::post('/manrisk/{risk}/toggle-rs', [ManriskController::class, 'toggleRsPriority'])->name('manrisk.toggle-rs');
    Route::resource('manrisk', ManriskController::class);

    // ─── Modul IV: Hazard Vulnerability Analysis (HVA - Unit & RS) ───────────
    Route::prefix('hva')->name('hva.')->group(function () {
        Route::get('/', [HvaController::class, 'index'])->name('index');
        Route::get('/summary', [HvaController::class, 'summary'])->name('summary');
        Route::get('/rtl', [HvaController::class, 'rtl'])->name('rtl');
        Route::get('/category/{kategori}', [HvaController::class, 'category'])->name('category');
        Route::put('/row/{assessment}', [HvaController::class, 'updateRow'])->name('update-row');
        Route::put('/rtl/{assessment}', [HvaController::class, 'updateRtl'])->name('update-rtl');
    });

    // ─── Pengaturan Periode ────────────────────────────────────────────────────
    Route::prefix('periods')->name('periods.')->group(function () {
        Route::get('/', [PeriodController::class, 'index'])->name('index');
        Route::post('/open', [PeriodController::class, 'openPeriod'])->name('open');
        Route::post('/{period}/close', [PeriodController::class, 'closePeriod'])->name('close');
        Route::post('/bulk-open', [PeriodController::class, 'bulkOpen'])->name('bulk-open');
    });

});

require __DIR__.'/auth.php';
