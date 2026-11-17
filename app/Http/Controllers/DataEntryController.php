<?php

namespace App\Http\Controllers;

use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorDailyRecord;
use App\Models\IndicatorFormField;
use App\Models\IndicatorPeriod;
use App\Models\Unit;
use App\Models\User;
use App\Models\Validation;
use App\Services\PrivateEvidenceStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as SpreadsheetCalculationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataEntryController extends Controller
{
    /**
     * Tampilan Utama: Form Pengumpul Data / Tally Sheet Harian
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isUnitStaff = in_array($user->role, ['kepala_unit', 'pic_unit'], true);
        $request->validate([
            'tahun' => 'nullable|integer|between:1900,2100',
            'bulan' => 'nullable|integer|between:1,12',
        ]);
        $tahun = $request->integer('tahun', 2026);
        $bulan = $request->integer('bulan', 1); // default Januari

        // 1. Tentukan Unit yang dipilih (Admin, Komite Mutu, dan Direksi dapat mengakses seluruh unit)
        if ($user->isSuperAdmin() || $user->isKomiteMutu() || $user->isDireksi()) {
            $selectedUnitId = $request->integer('unit_id');
            if (! $selectedUnitId) {
                $irj = Unit::where('kode', 'IRJ')->first()
                    ?? Unit::where('nama', 'like', '%Rawat Jalan%')->where('kode', 'not like', '%FARMASI%')->first()
                    ?? Unit::aktif()->first();
                $selectedUnitId = $irj ? $irj->id : Unit::aktif()->first()?->id;
            }
        } else {
            $selectedUnitId = $user->unit_id;
        }

        $selectedUnit = Unit::find($selectedUnitId);

        // 2. Daftar Profil Indikator Mutu Aktif untuk Unit ini (Form Pengumpul Data berelasi langsung dengan Profil Indikator Mutu)
        $unitIndicators = Indicator::aktif()
            ->where('unit_id', $selectedUnitId)
            ->with('unit')
            ->orderBy('kode')
            ->get();

        $hospitalIndicators = Indicator::aktif()
            ->whereNull('unit_id')
            ->with('unit')
            ->orderBy('kode')
            ->get();
        $availableIndicators = $unitIndicators
            ->concat($hospitalIndicators)
            ->sortBy('kode')
            ->values();
        if (! $isUnitStaff) {
            $unitIndicators = $availableIndicators;
        }

        // 3. Tentukan Indikator yang dipilih
        $selectedIndicatorId = $request->integer('indicator_id');
        $selectedIndicator = $availableIndicators->firstWhere('id', $selectedIndicatorId)
            ?? $unitIndicators->first();

        // 4. Kolom Instrumen Form (Custom Fields)
        $formFields = [];
        if ($selectedIndicator) {
            $formFields = IndicatorFormField::where('indicator_id', $selectedIndicator->id)
                ->orderBy('order')
                ->get();

            if ($formFields->isEmpty()) {
                $formFields = collect([
                    [
                        'id' => 0,
                        'name' => $selectedIndicator->numerator_keterangan ?: 'Numerator (Pembilang)',
                        'key' => 'num',
                        'type' => 'numerator',
                        'order' => 1,
                    ],
                    [
                        'id' => 0,
                        'name' => $selectedIndicator->denominator_keterangan ?: 'Denominator (Penyebut)',
                        'key' => 'den',
                        'type' => 'denominator',
                        'order' => 2,
                    ],
                ]);
            }
        }

        $currentPeriodEntry = null;
        if ($selectedIndicator && $selectedUnit) {
            $currentPeriodEntry = DataEntry::where('indicator_id', $selectedIndicator->id)
                ->where('unit_id', $selectedUnit->id)
                ->whereHas('period', fn ($query) => $query
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->where('indicator_id', $selectedIndicator->id))
                ->with('latestValidation:id,data_entry_id,status,catatan_validator,tanggal_validasi,validator_id')
                ->first();
        }

        $entrySchema = $selectedIndicator?->form_schema;
        $entrySchemaVersion = $currentPeriodEntry?->form_schema_version;
        if (
            is_array($entrySchema) &&
            $entrySchemaVersion !== null &&
            (int) ($entrySchema['schema_version'] ?? 0) !== $entrySchemaVersion
        ) {
            $entrySchema = $entrySchema['schema_history'][(string) $entrySchemaVersion] ?? null;
            abort_if(
                ! is_array($entrySchema),
                409,
                'Versi format yang digunakan untuk data periode ini tidak tersedia. Data tidak dapat ditampilkan dengan aman; hubungi administrator.'
            );
        }

        // 5. Data Harian untuk Bulan & Tahun terpilih
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $dailyRecordsMap = [];

        if ($selectedIndicator && $selectedUnit) {
            $layoutType = $entrySchema['row_type'] ?? ($selectedIndicator->form_layout_type ?: 'daily_grid');

            if ($layoutType === 'daily_grid') {
                $dailyRecords = IndicatorDailyRecord::where('indicator_id', $selectedIndicator->id)
                    ->where('unit_id', $selectedUnit->id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->where('hari', '>', 0)
                    ->get()
                    ->keyBy('hari');

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $rec = $dailyRecords->get($d);
                    $dailyRecordsMap[$d] = $rec ? $rec->data : ['num' => 0, 'den' => 0];
                }
            } else {
                // Untuk format formulir spesifik / dynamic_rows / matrix
                $entry = DataEntry::where('indicator_id', $selectedIndicator->id)
                    ->where('unit_id', $selectedUnit->id)
                    ->whereHas('period', fn ($q) => $q->where('tahun', $tahun)->where('bulan', $bulan)->where('indicator_id', $selectedIndicator->id))
                    ->first();

                $log = $entry?->log_harian;

                // 1. Jika DataEntry memiliki log_harian (misal: array pasien atau data matriks)
                if (! empty($log) && (is_array($log) || is_object($log))) {
                    $dailyRecordsMap = $log;
                } else {
                    // 2. Cek apakah ada record spesifik dengan hari = 0 di IndicatorDailyRecord
                    $specRecord = IndicatorDailyRecord::where('indicator_id', $selectedIndicator->id)
                        ->where('unit_id', $selectedUnit->id)
                        ->where('tahun', $tahun)
                        ->where('bulan', $bulan)
                        ->where('hari', 0)
                        ->first();

                    if ($specRecord && ! empty($specRecord->data)) {
                        $dailyRecordsMap = $specRecord->data;
                    } else {
                        // 3. Cek apakah ada items pasien di IndicatorDailyRecord hari 1..31
                        $dailyItems = IndicatorDailyRecord::where('indicator_id', $selectedIndicator->id)
                            ->where('unit_id', $selectedUnit->id)
                            ->where('tahun', $tahun)
                            ->where('bulan', $bulan)
                            ->where('hari', '>', 0)
                            ->get();

                        $collected = [];
                        foreach ($dailyItems as $dRec) {
                            if (! empty($dRec->data['items']) && is_array($dRec->data['items'])) {
                                foreach ($dRec->data['items'] as $it) {
                                    $collected[] = $it;
                                }
                            }

                        }

                        if (! empty($collected)) {
                            $dailyRecordsMap = $collected;
                        } else {
                            $dailyRecordsMap = in_array($layoutType, ['dynamic_rows', 'patient_register']) ? [] : (object) [];
                        }
                    }
                }
            }
        }

        // 6. Rekapitulasi Tahunan (Bulan 1 s.d. 12) untuk Indikator ini
        $annualSummary = [];
        if ($selectedIndicator && $selectedUnit) {
            $entriesYear = DataEntry::where('indicator_id', $selectedIndicator->id)
                ->where('unit_id', $selectedUnit->id)
                ->whereHas('period', fn ($q) => $q->where('tahun', $tahun)->where('indicator_id', $selectedIndicator->id))
                ->with('period')
                ->get()
                ->keyBy(fn ($e) => $e->period->bulan);

            for ($m = 1; $m <= 12; $m++) {
                $e = $entriesYear->get($m);
                $annualSummary[$m] = [
                    'bulan' => $m,
                    'numerator' => $e ? (float) $e->numerator : 0,
                    'denominator' => $e ? (float) $e->denominator : 0,
                    'capaian' => $e ? (float) $e->capaian : null,
                    'status' => $e ? $e->status : 'empty',
                ];
            }
        }

        // 8. Otorisasi Peran Pengguna (Admin, Komite Mutu, Direksi, Unit)
        $isExecutive = $user->isDireksi();
        $isQualityCommittee = $user->isKomiteMutu();
        $isAdmin = $user->isSuperAdmin();
        $canEditDaily = ($isAdmin || $isQualityCommittee || ($isUnitStaff && $user->unit_id === $selectedUnitId)) && ! $isExecutive;
        $canTemporarilyCorrectPeriod = $selectedUnit !== null
            && $this->canTemporarilyCorrectPeriod($user, $selectedUnit, (int) $tahun, (int) $bulan);
        $canManageFields = $isAdmin || $isQualityCommittee;
        $canSaveMetricMappingDefault = ($isAdmin || $isQualityCommittee || (
            $isUnitStaff &&
            $user->unit_id === $selectedUnitId &&
            $selectedIndicator?->unit_id !== null
        )) && ! $isExecutive;
        $canSubmitValidation = ($isAdmin || ($isUnitStaff && $user->unit_id === $selectedUnitId)) && ! $isExecutive && ! $isQualityCommittee;

        $roleScope = match (true) {
            $isAdmin => 'admin',
            $isQualityCommittee => 'komite_mutu',
            $isExecutive => 'direksi',
            default => 'unit',
        };

        // 9. Status Kepatuhan Validasi Indikator Terpilih (Siklus Wajib / Lolos / Uji Ulang)
        $validationStatus = null;
        if ($selectedIndicator && $selectedUnit) {
            $validationStatus = Validation::getIndicatorValidationStatus(
                (int) $selectedUnit->id,
                (int) $selectedIndicator->id,
                (int) $tahun,
                (int) $bulan
            );
        }

        return Inertia::render('DataEntry/Index', [
            'units' => Unit::aktif()->get(['id', 'kode', 'nama']),
            'selectedUnit' => $selectedUnit,
            'unitIndicators' => $unitIndicators,
            'hospitalIndicators' => $hospitalIndicators,
            'selectedIndicator' => $selectedIndicator,
            'entrySchema' => $entrySchema,
            'formFields' => $formFields,
            'dailyRecords' => $dailyRecordsMap,
            'daysInMonth' => $daysInMonth,
            'currentPeriodEntry' => $currentPeriodEntry,
            'annualSummary' => $annualSummary,
            'validationStatus' => $validationStatus,
            'roleScope' => $roleScope,
            'permissions' => [
                'canEditDaily' => $canEditDaily,
                'canTemporarilyCorrectPeriod' => $canTemporarilyCorrectPeriod,
                'canManageFields' => $canManageFields,
                'canSaveMetricMappingDefault' => $canSaveMetricMappingDefault,
                'canSubmitValidation' => $canSubmitValidation,
                'isExecutive' => $isExecutive,
                'isQualityCommittee' => $isQualityCommittee,
                'isAdmin' => $isAdmin,
            ],
            'filters' => [
                'unit_id' => $selectedUnitId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'indicator_id' => $selectedIndicator?->id,
            ],
        ]);
    }

    /**
     * Simpan Perubahan Tally Sheet Harian & Sinkronkan ke Data Agregat Bulanan
     */
    public function saveDailyRecords(Request $request)
    {
        $user = $request->user();
        if ($user->isDireksi()) {
            return redirect()->back()->with('error', 'Akses dibatasi: Direksi hanya memiliki hak pemantauan eksekutif (Read-Only).');
        }

        $saveMetricMappingAsDefault = $request->boolean('save_metric_mapping_as_default');
        $validated = $request->validate([
            'indicator_id' => 'required|exists:indicators,id',
            'unit_id' => 'required|exists:units,id',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer|between:1,12',
            'schema_version' => 'nullable|integer|min:1',
            'records' => 'required|array',
            'workbook_snapshot' => 'nullable|array',
            'workbook_snapshot.sheetOrder' => 'required_with:workbook_snapshot|array|size:1',
            'workbook_snapshot.sheetOrder.0' => 'required_with:workbook_snapshot|string|max:255',
            'workbook_snapshot.sheets' => 'required_with:workbook_snapshot|array|size:1',
            'workbook_snapshot.entry_metadata.logical_row_count' => 'nullable|integer|min:0|max:500',
            'workbook_snapshot.entry_metadata.metric_mapping' => 'nullable|array:version,numerator,denominator',
            'workbook_snapshot.entry_metadata.metric_mapping.version' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping|integer|in:1',
            'workbook_snapshot.entry_metadata.metric_mapping.numerator' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping|array:range,aggregation',
            'workbook_snapshot.entry_metadata.metric_mapping.numerator.range' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping.numerator|string|max:40',
            'workbook_snapshot.entry_metadata.metric_mapping.numerator.aggregation' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping.numerator|in:sum,count,counta',
            'workbook_snapshot.entry_metadata.metric_mapping.denominator' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping|array:range,aggregation',
            'workbook_snapshot.entry_metadata.metric_mapping.denominator.range' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping.denominator|string|max:40',
            'workbook_snapshot.entry_metadata.metric_mapping.denominator.aggregation' => 'required_with:workbook_snapshot.entry_metadata.metric_mapping.denominator|in:sum,count,counta',
            'workbook_snapshot.sheets.*.rowCount' => 'required|integer|min:1|max:10000',
            'workbook_snapshot.sheets.*.columnCount' => 'required|integer|min:1|max:500',
            'workbook_snapshot.sheets.*.cellData' => 'nullable|array',
            'workbook_snapshot.sheets.*.mergeData' => 'nullable|array|max:5000',
            'workbook_snapshot.defined_names' => 'nullable|array|max:500',
            'save_metric_mapping_as_default' => 'nullable|boolean',
        ]);

        if (isset($validated['workbook_snapshot'])) {
            $validated['workbook_snapshot'] = $request->input('workbook_snapshot');
            $this->validateWorkbookSnapshot($validated['workbook_snapshot']);
        }

        if (! $user->isSuperAdmin() && ! $user->isKomiteMutu() && $user->unit_id !== (int) $validated['unit_id']) {
            return redirect()->back()->with('error', 'Akses dibatasi: Anda hanya dapat menyimpan data untuk unit Anda sendiri.');
        }

        $indicator = Indicator::with('formFields')
            ->findOrFail($validated['indicator_id']);
        $unit = Unit::findOrFail($validated['unit_id']);
        if ($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id) {
            return redirect()->back()->with('error', 'Indikator tidak terdaftar pada unit yang dipilih.');
        }
        if (
            $saveMetricMappingAsDefault &&
            ! $user->isSuperAdmin() &&
            ! $user->isKomiteMutu() &&
            (int) $indicator->unit_id !== (int) $unit->id
        ) {
            return redirect()->back()->with(
                'error',
                'Anda tidak memiliki izin untuk mengubah pemetaan default indikator ini.'
            );
        }

        $tahun = $validated['tahun'];
        $bulan = $validated['bulan'];
        $canTemporarilyCorrectPeriod = $this->canTemporarilyCorrectPeriod(
            $user,
            $unit,
            $tahun,
            $bulan,
        );
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

        $currentFormSchema = $indicator->form_schema ?? [];
        $currentSchemaVersion = (int) ($currentFormSchema['schema_version'] ?? 0);
        $formSchemaVersion = $validated['schema_version'] ?? $currentSchemaVersion;
        $formSchema = $currentFormSchema;

        if ($formSchemaVersion > 0 && $formSchemaVersion !== $currentSchemaVersion) {
            $historicalSchema = $currentFormSchema['schema_history'][(string) $formSchemaVersion] ?? null;
            if (! is_array($historicalSchema)) {
                throw ValidationException::withMessages([
                    'schema_version' => 'Versi format form ini sudah tidak tersedia. Muat ulang halaman sebelum menyimpan.',
                ]);
            }
            $formSchema = $historicalSchema;
        }

        $layoutType = $formSchema['row_type'] ?? ($indicator->form_layout_type ?: 'daily_grid');
        $legacySpreadsheetMetadata = $validated['records']['__spreadsheet'] ?? null;
        $hasLegacySpreadsheetMetadata = isset($validated['records']['__spreadsheet']);
        $isLegacyDailyGridSpreadsheet = $layoutType === 'daily_grid' &&
            is_array($legacySpreadsheetMetadata) &&
            ($legacySpreadsheetMetadata['layout_type'] ?? null) === 'daily_grid' &&
            ($legacySpreadsheetMetadata['row_type'] ?? null) === 'fixed_rows';
        $metricMappingDefault = $currentFormSchema['metric_mapping_default'] ?? null;
        if (is_array($metricMappingDefault)) {
            $formSchema['metric_mapping_default'] ??= $metricMappingDefault;
        }
        $hasNativeSpreadsheetLayout = in_array($layoutType, ['dynamic_rows', 'fixed_rows'], true);
        $hasWorkbookMetricMapping = is_array(
            $validated['workbook_snapshot']['entry_metadata']['metric_mapping'] ?? null
        );
        $hasSpreadsheetMetricMapping = $hasWorkbookMetricMapping ||
            is_array($formSchema['metric_mapping_default'] ?? null);
        $supportsMetricMappingDefault =
            ($formSchema['renderer'] ?? null) === 'spreadsheet' ||
            $hasLegacySpreadsheetMetadata ||
            (
                $hasNativeSpreadsheetLayout &&
                ! isset($formSchema['renderer']) &&
                $hasSpreadsheetMetricMapping
            );
        if (
            $saveMetricMappingAsDefault &&
            (
                ! isset($validated['workbook_snapshot']['entry_metadata']['metric_mapping']) ||
                ! $supportsMetricMappingDefault
            )
        ) {
            throw ValidationException::withMessages([
                'save_metric_mapping_as_default' => 'Default N/D hanya dapat disimpan untuk form spreadsheet dengan pemetaan Numerator dan Denominator yang lengkap.',
            ]);
        }

        $dailyGridFields = $indicator->formFields->isNotEmpty()
            ? $indicator->formFields->map(fn (IndicatorFormField $field): array => [
                'key' => $field->key,
                'type' => $field->type,
            ])->all()
            : [
                ['key' => 'num', 'type' => 'numerator'],
                ['key' => 'den', 'type' => 'denominator'],
            ];
        $legacySpreadsheetAdditionalKeys = [];
        $legacySpreadsheetRows = null;
        if ($hasLegacySpreadsheetMetadata) {
            $validated['records'] = $this->recalculateLegacySpreadsheetRecords(
                $validated['records'],
                $layoutType,
                $legacySpreadsheetRows
            );
            $legacySpreadsheetAdditionalKeys = array_values(array_filter(
                array_column(
                    $request->input('records.__spreadsheet.columns', []),
                    'key'
                ),
                fn ($key): bool => is_string($key) && preg_match('/^col_\d+$/', $key) === 1
            ));
            $metricMapping = $validated['workbook_snapshot']['entry_metadata']['metric_mapping']
                ?? $formSchema['metric_mapping_default']
                ?? null;
            if (is_array($metricMapping) && $legacySpreadsheetRows !== null) {
                $legacySpreadsheetSchema = [
                    'columns' => $legacySpreadsheetMetadata['columns'],
                    'rows' => $legacySpreadsheetMetadata['rows'],
                    'header_rows' => $legacySpreadsheetMetadata['header_rows'] ?? 2,
                ];
                $this->validateSpreadsheetRecords(
                    $legacySpreadsheetRows,
                    $legacySpreadsheetSchema,
                    $validated['workbook_snapshot'] ?? null
                );
            }
        }

        return DB::transaction(function () use (
            $request,
            $user,
            $indicator,
            $unit,
            $tahun,
            $bulan,
            $daysInMonth,
            $validated,
            $formSchema,
            $layoutType,
            $dailyGridFields,
            $legacySpreadsheetAdditionalKeys,
            $hasLegacySpreadsheetMetadata,
            $legacySpreadsheetMetadata,
            $legacySpreadsheetRows,
            $hasNativeSpreadsheetLayout,
            $hasSpreadsheetMetricMapping,
            $isLegacyDailyGridSpreadsheet,
            $canTemporarilyCorrectPeriod,
            $saveMetricMappingAsDefault
        ) {
            $period = IndicatorPeriod::where([
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ])->lockForUpdate()->first();
            $existingEntry = $period
                ? DataEntry::where('indicator_id', $indicator->id)
                    ->where('unit_id', $unit->id)
                    ->where('period_id', $period->id)
                    ->lockForUpdate()
                    ->first()
                : null;
            $currentMonth = (int) now()->format('Y') * 12 + (int) now()->format('n');
            $selectedMonth = $tahun * 12 + $bulan;
            $isReopeningApprovedEntry = $existingEntry?->status === 'approved';
            $isReopeningSubmittedEntry = $existingEntry?->status === 'submitted'
                && $canTemporarilyCorrectPeriod;
            $isReopeningEntry = $isReopeningApprovedEntry || $isReopeningSubmittedEntry;

            if ($existingEntry?->status === 'submitted' && ! $canTemporarilyCorrectPeriod) {
                return redirect()->back()->with(
                    'error',
                    'Data yang sedang menunggu validasi tidak dapat diubah. Minta Administrator membuka revisi terlebih dahulu.'
                );
            }

            if ($isReopeningApprovedEntry && ! $canTemporarilyCorrectPeriod && ! $user->isSuperAdmin()) {
                return redirect()->back()->with(
                    'error',
                    'Data yang sudah disetujui hanya dapat dibuka kembali oleh Administrator.'
                );
            }

            if ($isReopeningApprovedEntry && ! $canTemporarilyCorrectPeriod && $selectedMonth >= $currentMonth) {
                return redirect()->back()->with('error', 'Data yang sudah disetujui pada periode berjalan atau mendatang tidak dapat diubah.');
            }

            $sumNum = 0;
            $sumDen = 0;
            $logHarian = [];

            if ($layoutType !== 'daily_grid') {
                $usesSpreadsheetRenderer = $hasNativeSpreadsheetLayout &&
                    (
                        ($formSchema['renderer'] ?? null) === 'spreadsheet' ||
                        (
                            ! isset($formSchema['renderer']) &&
                            $hasSpreadsheetMetricMapping
                        )
                    );

                if ($usesSpreadsheetRenderer) {
                    $validated['records'] = $this->removeSpreadsheetSummaryRows(
                        $validated['records'],
                        $layoutType
                    );
                    if (count($validated['records']) > 500) {
                        throw ValidationException::withMessages([
                            'records' => 'Spreadsheet tidak dapat menyimpan lebih dari 500 baris input.',
                        ]);
                    }
                    $this->validateSpreadsheetRecords(
                        $validated['records'],
                        $formSchema,
                        $validated['workbook_snapshot'] ?? null
                    );
                    $validated['records'] = $this->calculateSpreadsheetFormulaValues(
                        $validated['records'],
                        $formSchema,
                        $layoutType,
                        $validated['workbook_snapshot'] ?? null
                    );
                    $this->validateSpreadsheetRecords(
                        $validated['records'],
                        $formSchema,
                        $validated['workbook_snapshot'] ?? null
                    );
                    $metricMapping = $validated['workbook_snapshot']['entry_metadata']['metric_mapping']
                        ?? $formSchema['metric_mapping_default']
                        ?? null;
                    if (is_array($metricMapping)) {
                        [$sumNum, $sumDen] = $this->calculateSpreadsheetSummaryFromMapping(
                            $formSchema,
                            $layoutType,
                            $validated['workbook_snapshot'] ?? null,
                            $metricMapping
                        );
                    } else {
                        [$sumNum, $sumDen] = $this->calculateSpreadsheetSummary(
                            $validated['records'],
                            $formSchema,
                            $layoutType,
                            $validated['workbook_snapshot'] ?? null
                        );
                    }
                } else {
                    $summary = $hasLegacySpreadsheetMetadata
                        ? []
                        : $request->input('summary', []);
                    $sumNum = (float) ($summary['numerator'] ?? 0);
                    $sumDen = (float) ($summary['denominator'] ?? 0);

                    // Fallback kalkulasi otomatis jika summary tidak terisi
                    if ($sumNum === 0.0 && $sumDen === 0.0) {
                        if ($layoutType === 'grouped_category' && isset($validated['records']['categories'])) {
                            foreach ($validated['records']['categories'] as $cat) {
                                $sumNum += (float) ($cat['compliant'] ?? 0);
                                $sumDen += (float) ($cat['sample'] ?? 0);
                            }
                        } elseif ($layoutType === 'entity_checklist' && isset($validated['records']['entities'])) {
                            foreach ($validated['records']['entities'] as $ent) {
                                $sumNum += (float) ($ent['aturan_pakai'] ?? 0);
                                $sumDen += (float) ($ent['sample'] ?? 0);
                            }
                        } elseif ($layoutType === 'location_matrix' && isset($validated['records']['locations'])) {
                            foreach ($validated['records']['locations'] as $loc) {
                                $sumNum += (float) ($loc['fornas'] ?? 0);
                                $sumDen += (float) ($loc['total_resep'] ?? 0);
                            }
                        } elseif ($layoutType === 'inspection_points' && isset($validated['records']['points'])) {
                            foreach ($validated['records']['points'] as $p) {
                                if (! empty($p['checked'])) {
                                    $sumDen++;
                                    if (array_key_exists('compliant', $p)) {
                                        $sumNum += $this->spreadsheetMetricValue($p['compliant'], 'number');
                                    } else {
                                        $allMet = true;
                                        foreach (($p['criteria'] ?? []) as $crit) {
                                            if (! $crit) {
                                                $allMet = false;
                                                break;
                                            }
                                        }
                                        if ($allMet) {
                                            $sumNum++;
                                        }
                                    }
                                }
                            }
                        } elseif ($layoutType === 'monthly_summary') {
                            if (isset($validated['records'][$bulan])) {
                                $sumNum = (float) ($validated['records'][$bulan]['num'] ?? 0);
                                $sumDen = (float) ($validated['records'][$bulan]['den'] ?? 0);
                            } elseif (isset($validated['records']['num']) || isset($validated['records']['numerator'])) {
                                $sumNum = (float) ($validated['records']['num'] ?? $validated['records']['numerator'] ?? 0);
                                $sumDen = (float) ($validated['records']['den'] ?? $validated['records']['denominator'] ?? 0);
                            }
                        }

                        // Generic Custom Form Schema Rollup
                        if ($sumNum === 0.0 && $sumDen === 0.0 && ! empty($indicator->form_schema['columns'])) {
                            $cols = $indicator->form_schema['columns'];
                            $numKeys = collect($cols)->where('role', 'numerator')->pluck('key')->all();
                            $denKeys = collect($cols)->where('role', 'denominator')->pluck('key')->all();

                            $rows = is_array($validated['records']) ? $validated['records'] : [];
                            foreach ($rows as $row) {
                                if (! is_array($row)) {
                                    continue;
                                }
                                foreach ($numKeys as $nk) {
                                    $sumNum += (float) ($row[$nk] ?? 0);
                                }
                                foreach ($denKeys as $dk) {
                                    $sumDen += (float) ($row[$dk] ?? 0);
                                }
                            }

                            // Fallback jika tidak ada kolom Denominator eksplisit pada dynamic_rows (total baris terisi = denominator)
                            if (empty($denKeys) && in_array($layoutType, ['dynamic_rows', 'patient_register'])) {
                                $validRows = 0;
                                foreach ($rows as $row) {
                                    if (is_array($row) && ! empty(array_filter($row))) {
                                        $validRows++;
                                    }
                                }
                                $sumDen = (float) $validRows;
                            }
                        }

                        // Fallback jika form_schema kosong tapi layoutType adalah dynamic_rows
                        if ($sumNum === 0.0 && $sumDen === 0.0 && in_array($layoutType, ['dynamic_rows', 'patient_register'])) {
                            $rows = is_array($validated['records']) ? $validated['records'] : [];
                            $validRows = 0;
                            foreach ($rows as $row) {
                                if (! is_array($row)) {
                                    continue;
                                }
                                $hasData = ! empty(array_filter($row, fn ($v) => $v !== null && $v !== '' && $v !== false));
                                if ($hasData) {
                                    $validRows++;
                                    $nVal = $row['num'] ?? $row['kepatuhan'] ?? 0;
                                    if ($nVal === true || $nVal === 'true' || $nVal === '1' || $nVal === 1 || $nVal === 'v' || $nVal === 'V' || $nVal === 'Sesuai' || $nVal === 'Ya') {
                                        $sumNum += 1;
                                    } else {
                                        $sumNum += (float) $nVal;
                                    }
                                }
                            }
                            $sumDen = (float) $validRows;
                        }
                    }

                    if (isset($validated['workbook_snapshot']) && ! empty($formSchema['columns'])) {
                        [$sumNum, $sumDen] = $this->calculateSpreadsheetSummary(
                            $validated['records'],
                            $formSchema,
                            $layoutType,
                            $validated['workbook_snapshot']
                        );
                    }
                }

                $tanggal = sprintf('%04d-%02d-01', $tahun, $bulan);

                // Simpan detail ke IndicatorDailyRecord dengan hari = 0
                IndicatorDailyRecord::updateOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'unit_id' => $unit->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'hari' => 0,
                    ],
                    [
                        'tanggal' => $tanggal,
                        'data' => $validated['records'],
                        'created_by' => $user->id,
                    ]
                );

                $logHarian = $validated['records'];
            } else {
                foreach ($validated['records'] as $hari => $values) {
                    $hariInt = (int) $hari;
                    if ($hariInt < 1 || $hariInt > $daysInMonth || ! is_array($values)) {
                        throw ValidationException::withMessages([
                            'records' => 'Setiap data harian harus memiliki tanggal dan format baris yang valid.',
                        ]);
                    }

                    $normalizedValues = [];
                    $numVal = 0.0;
                    $denVal = 0.0;
                    foreach ($dailyGridFields as $field) {
                        $key = $field['key'];
                        $value = $values[$key] ?? 0;
                        if ($value === null || $value === '') {
                            $value = 0;
                        }
                        if (! is_numeric($value) || (float) $value < 0) {
                            throw ValidationException::withMessages([
                                'records' => "Kolom {$key} pada tanggal {$hariInt} harus berisi angka nol atau lebih.",
                            ]);
                        }

                        $normalizedValues[$key] = (float) $value;
                        if ($field['type'] === 'numerator') {
                            $numVal += (float) $value;
                        } elseif ($field['type'] === 'denominator') {
                            $denVal += (float) $value;
                        }
                    }
                    foreach ($legacySpreadsheetAdditionalKeys as $key) {
                        if (! array_key_exists($key, $values)) {
                            continue;
                        }

                        if (! is_scalar($values[$key]) && $values[$key] !== null) {
                            throw ValidationException::withMessages([
                                'records' => "Kolom {$key} pada tanggal {$hariInt} harus berisi teks atau angka.",
                            ]);
                        }

                        $normalizedValues[$key] = $values[$key];
                    }

                    $additionalFormulas = array_intersect_key(
                        $values['__formulas'] ?? [],
                        array_fill_keys($legacySpreadsheetAdditionalKeys, true)
                    );
                    if ($additionalFormulas !== []) {
                        $normalizedValues['__formulas'] = $additionalFormulas;
                    }
                    $values = $normalizedValues;
                    $sumNum += $numVal;
                    $sumDen += $denVal;

                    $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hariInt);

                    IndicatorDailyRecord::updateOrCreate(
                        [
                            'indicator_id' => $indicator->id,
                            'unit_id' => $unit->id,
                            'tahun' => $tahun,
                            'bulan' => $bulan,
                            'hari' => $hariInt,
                        ],
                        [
                            'tanggal' => $tanggal,
                            'data' => $values,
                            'created_by' => $user->id,
                        ]
                    );

                    $logHarian[$hariInt] = [
                        'n' => $numVal,
                        'd' => $denVal,
                        'data' => $values,
                    ];
                }

                IndicatorDailyRecord::where('indicator_id', $indicator->id)
                    ->where('unit_id', $unit->id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->where('hari', '>', 0)
                    ->whereNotIn('hari', array_keys($logHarian))
                    ->delete();
            }

            if (
                $hasLegacySpreadsheetMetadata &&
                $legacySpreadsheetMetadata !== null &&
                $legacySpreadsheetRows !== null
            ) {
                $mappingSchema = [
                    'columns' => $legacySpreadsheetMetadata['columns'],
                    'rows' => $legacySpreadsheetMetadata['rows'],
                    'header_rows' => $legacySpreadsheetMetadata['header_rows'] ?? 2,
                    'metric_mapping_default' => $formSchema['metric_mapping_default'] ?? null,
                ];
                $workbookSnapshot = $validated['workbook_snapshot'] ?? null;
                $metricMapping = $workbookSnapshot['entry_metadata']['metric_mapping']
                    ?? $mappingSchema['metric_mapping_default']
                    ?? null;

                if ($metricMapping !== null) {
                    [$sumNum, $sumDen, $rowMetricSummaries] = $this->calculateSpreadsheetSummaryFromMapping(
                        $mappingSchema,
                        $legacySpreadsheetMetadata['row_type'],
                        $workbookSnapshot,
                        $metricMapping
                    );

                    if ($isLegacyDailyGridSpreadsheet) {
                        foreach ($rowMetricSummaries as $rowIndex => $rowSummary) {
                            $day = (string) ($legacySpreadsheetMetadata['rows'][$rowIndex]['key'] ?? '');
                            if ($day === '' || ! isset($logHarian[$day])) {
                                continue;
                            }

                            $logHarian[$day]['n'] = $rowSummary['numerator'] ?? 0;
                            $logHarian[$day]['d'] = $rowSummary['denominator'] ?? 0;
                        }
                    }
                }
            }

            if ($saveMetricMappingAsDefault) {
                $indicatorForUpdate = Indicator::whereKey($indicator->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $metricMapping = $validated['workbook_snapshot']['entry_metadata']['metric_mapping'];
                $indicatorSchema = $indicatorForUpdate->form_schema;
                if (! is_array($indicatorSchema) || $indicatorSchema === []) {
                    $indicatorSchema = $formSchema;
                }
                $indicatorSchema['metric_mapping_default'] = $metricMapping;
                $indicatorForUpdate->form_schema = $indicatorSchema;
                $indicatorForUpdate->save();
            }

            // Cari atau buat periode
            $period = IndicatorPeriod::firstOrCreate(
                ['indicator_id' => $indicator->id, 'unit_id' => $unit->id, 'tahun' => $tahun, 'bulan' => $bulan],
                ['status' => 'open']
            );

            $capaian = $sumDen > 0 ? round(($sumNum / $sumDen) * ($indicator->multiplier ?? 100), 2) : 0;

            // Update ke DataEntry bulanan
            $entryData = [
                'user_id' => $user->id,
                'numerator' => $sumNum,
                'denominator' => $sumDen,
                'capaian' => $capaian,
                'log_harian' => $logHarian,
                'form_schema_version' => $formSchema['schema_version'] ?? null,
            ];
            $annualValidationStatus = Validation::getIndicatorValidationStatus(
                (int) $unit->id,
                (int) $indicator->id,
                $tahun,
                $bulan,
            );
            if ($existingEntry === null || in_array($existingEntry->status, ['draft', 'exempt'], true)) {
                $entryData['status'] = $annualValidationStatus['is_exempt'] && $sumDen > 0
                    ? 'exempt'
                    : 'draft';
            }
            if (array_key_exists('workbook_snapshot', $validated)) {
                $entryData['workbook_snapshot'] = $validated['workbook_snapshot'];
            }

            $entry = DataEntry::updateOrCreate(
                ['indicator_id' => $indicator->id, 'period_id' => $period->id, 'unit_id' => $unit->id],
                $entryData
            );

            if ($isReopeningEntry) {
                $entry->update([
                    'status' => 'revision',
                    'submitted_at' => null,
                ]);
                $period->update([
                    'status' => 'open',
                    'tanggal_validasi' => null,
                    'validated_by' => null,
                ]);
                $revisionNote = $isReopeningSubmittedEntry
                    ? 'Pengajuan validasi dibuka kembali untuk koreksi sementara data IGD. Ajukan kembali setelah koreksi.'
                    : ($canTemporarilyCorrectPeriod
                        ? 'Data disesuaikan untuk koreksi sementara data IGD setelah persetujuan. Ajukan kembali untuk validasi.'
                        : 'Data disesuaikan oleh Administrator setelah persetujuan. Ajukan kembali untuk validasi.');
                Validation::create([
                    'data_entry_id' => $entry->id,
                    'validator_id' => null,
                    'actor_id' => $user->id,
                    'status' => 'revision',
                    'catatan_validator' => $revisionNote,
                    'tanggal_validasi' => now(),
                    'unit_numerator' => $sumNum,
                    'unit_denominator' => $sumDen,
                    'unit_capaian' => $capaian,
                    'is_valid' => false,
                ]);
                $annualValidationStatus = Validation::getIndicatorValidationStatus(
                    (int) $unit->id,
                    (int) $indicator->id,
                    $tahun,
                    $bulan,
                );
                if (! $annualValidationStatus['is_exempt']) {
                    DataEntry::where('indicator_id', $indicator->id)
                        ->where('unit_id', $unit->id)
                        ->where('status', 'exempt')
                        ->whereHas('period', fn ($query) => $query->where('tahun', $tahun))
                        ->update(['status' => 'draft']);
                }
            }

            return redirect()->back()->with('success', 'Data Tally Sheet berhasil disimpan. Total Numerator: '.number_format($sumNum, 0, ',', '.').', Denominator: '.number_format($sumDen, 0, ',', '.')." (Capaian: {$capaian}%)");
        });
    }

    /**
     * Remove dynamic rows that contain worksheet aggregate formulas.
     *
     * @param  array<string|int, mixed>  $records
     * @return array<int, array<string, mixed>>
     */
    private function removeSpreadsheetSummaryRows(array $records, string $layoutType): array
    {
        if ($layoutType !== 'dynamic_rows') {
            return $records;
        }

        return array_values(array_filter($records, function ($row): bool {
            if (! is_array($row)) {
                return true;
            }

            $formulas = $row['__formulas'] ?? [];
            if (is_array($formulas)) {
                foreach ($formulas as $formula) {
                    if ($this->isSpreadsheetAggregateFormula($formula)) {
                        return false;
                    }
                }
            }

            return true;
        }));
    }

    private function isSpreadsheetAggregateFormula(mixed $formula): bool
    {
        if (
            ! is_string($formula) ||
            preg_match('/\b(?:SUM|SUBTOTAL|COUNT|COUNTA|AVERAGE|MIN|MAX)\s*\(/i', $formula) !== 1
        ) {
            return false;
        }

        preg_match_all(
            '/\$?[A-Z]{1,3}\$?(\d+)\s*:\s*\$?[A-Z]{1,3}\$?(\d+)/i',
            $formula,
            $ranges,
            PREG_SET_ORDER
        );

        foreach ($ranges as $range) {
            if ($range[1] !== $range[2]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recalculate spreadsheet-rendered form metrics from submitted cell values.
     *
     * @param  array<string|int, mixed>  $records
     * @param  array<string, mixed>  $schema
     * @return array{0: float, 1: float}
     */
    private function calculateSpreadsheetSummary(
        array $records,
        array $schema,
        string $layoutType,
        ?array $workbookSnapshot = null
    ): array {
        $metricMapping = $workbookSnapshot['entry_metadata']['metric_mapping']
            ?? $schema['metric_mapping_default']
            ?? null;
        if ($metricMapping !== null) {
            [$numerator, $denominator] = $this->calculateSpreadsheetSummaryFromMapping(
                $schema,
                $layoutType,
                $workbookSnapshot,
                $metricMapping
            );

            return [$numerator, $denominator];
        }

        $columns = $schema['columns'] ?? [];
        $rows = array_values(array_filter($records, 'is_array'));
        $numeratorColumns = array_values(array_filter($columns, fn ($column): bool => is_array($column) && ($column['role'] ?? null) === 'numerator'));
        $denominatorColumns = array_values(array_filter($columns, fn ($column): bool => is_array($column) && ($column['role'] ?? null) === 'denominator'));
        $compositeCriteria = $schema['formula_config']['composite_criteria'] ?? [];
        $summaryRanges = [];
        foreach (array_merge($numeratorColumns, $denominatorColumns) as $column) {
            $summaryRanges[$column['key']] = $this->getSpreadsheetSummaryMetricRowRange(
                $schema,
                $workbookSnapshot,
                (string) $column['key']
            );
        }

        $validRows = array_values(array_filter($rows, function (array $row): bool {
            foreach ($row as $key => $value) {
                if ($key !== '_id' && str_starts_with((string) $key, '__') === false && $this->hasSpreadsheetValue($value)) {
                    return true;
                }
            }

            return false;
        }));

        $numerator = 0.0;
        if ($layoutType === 'dynamic_rows' && $compositeCriteria !== []) {
            foreach ($validRows as $row) {
                $isCompliant = true;
                foreach ($compositeCriteria as $key) {
                    if (! $this->isSpreadsheetChecked($row[$key] ?? null)) {
                        $isCompliant = false;
                        break;
                    }
                }
                if ($isCompliant) {
                    $numerator++;
                }
            }
        } else {
            foreach ($rows as $rowIndex => $row) {
                foreach ($numeratorColumns as $column) {
                    $summaryRange = $summaryRanges[$column['key']] ?? null;
                    if (
                        $summaryRange !== null &&
                        ($rowIndex < $summaryRange[0] || $rowIndex > $summaryRange[1])
                    ) {
                        continue;
                    }

                    $numerator += $this->spreadsheetMetricValue(
                        $row[$column['key']] ?? null,
                        $column['type'] ?? 'text'
                    );
                }
            }
        }

        $denominator = 0.0;
        if ($denominatorColumns !== []) {
            foreach ($rows as $rowIndex => $row) {
                foreach ($denominatorColumns as $column) {
                    $summaryRange = $summaryRanges[$column['key']] ?? null;
                    if (
                        $summaryRange !== null &&
                        ($rowIndex < $summaryRange[0] || $rowIndex > $summaryRange[1])
                    ) {
                        continue;
                    }

                    $denominator += $this->spreadsheetMetricValue(
                        $row[$column['key']] ?? null,
                        $column['type'] ?? 'text'
                    );
                }
            }
        } elseif ($layoutType === 'dynamic_rows') {
            $denominator = (float) count($validRows);
        }

        return [$numerator, $denominator];
    }

    /**
     * Calculate spreadsheet metrics from user-selected worksheet ranges.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, mixed>  $mapping
     * @return array{0: float, 1: float, 2: array<int, array{numerator?: float, denominator?: float}>}
     */
    private function calculateSpreadsheetSummaryFromMapping(
        array $schema,
        string $layoutType,
        ?array $snapshot,
        array $mapping
    ): array {
        $sheetId = $snapshot['sheetOrder'][0] ?? null;
        $sheet = is_string($sheetId) ? ($snapshot['sheets'][$sheetId] ?? null) : null;
        if (! is_array($sheet)) {
            throw ValidationException::withMessages([
                'workbook_snapshot.entry_metadata.metric_mapping' => 'Workbook tidak menyediakan worksheet untuk membaca sumber N dan D.',
            ]);
        }

        $headerRowCount = $this->getSpreadsheetHeaderRowCount($schema, $snapshot);
        $summaryRows = [];
        foreach ($sheet['cellData'] ?? [] as $rowIndex => $cells) {
            if ((int) $rowIndex < $headerRowCount || ! is_array($cells)) {
                continue;
            }

            foreach ($cells as $cell) {
                if (is_array($cell) && $this->isSpreadsheetAggregateFormula($cell['f'] ?? null)) {
                    $summaryRows[] = (int) $rowIndex;
                    break;
                }
            }
        }

        $rowCount = (int) ($sheet['rowCount'] ?? 0);
        $columnCount = (int) ($sheet['columnCount'] ?? 0);
        $results = [];
        $rowResults = [];

        foreach (['numerator', 'denominator'] as $metric) {
            $source = $mapping[$metric] ?? null;
            if (
                ! is_array($source) ||
                ! is_string($source['range'] ?? null) ||
                ! in_array($source['aggregation'] ?? null, ['sum', 'count', 'counta'], true)
            ) {
                throw ValidationException::withMessages([
                    'workbook_snapshot.entry_metadata.metric_mapping' => 'Pemetaan sumber Numerator dan Denominator tidak lengkap atau tidak valid.',
                ]);
            }

            $bounds = $this->parseSpreadsheetMetricRange($source['range']);
            if (
                $bounds === null ||
                $bounds['start_row'] <= $headerRowCount ||
                $bounds['end_row'] > $rowCount ||
                $bounds['end_column'] > $columnCount ||
                ($bounds['end_row'] - $bounds['start_row'] + 1) *
                    ($bounds['end_column'] - $bounds['start_column'] + 1) > 10000
            ) {
                throw ValidationException::withMessages([
                    'workbook_snapshot.entry_metadata.metric_mapping' => "Rentang sumber {$metric} harus berupa sel data yang valid dan tidak lebih dari 10.000 sel.",
                ]);
            }

            $total = 0.0;
            for ($sheetRow = $bounds['start_row']; $sheetRow <= $bounds['end_row']; $sheetRow++) {
                $zeroBasedSheetRow = $sheetRow - 1;
                if (in_array($zeroBasedSheetRow, $summaryRows, true)) {
                    throw ValidationException::withMessages([
                        'workbook_snapshot.entry_metadata.metric_mapping' => "Rentang sumber {$metric} tidak boleh mencakup baris ringkasan formula.",
                    ]);
                }

                $recordIndex = $zeroBasedSheetRow - $headerRowCount;
                if ($layoutType === 'dynamic_rows') {
                    foreach ($summaryRows as $summaryRow) {
                        if ($summaryRow < $zeroBasedSheetRow) {
                            $recordIndex--;
                        }
                    }
                }

                for ($columnIndex = $bounds['start_column']; $columnIndex <= $bounds['end_column']; $columnIndex++) {
                    $cell = $sheet['cellData'][$zeroBasedSheetRow][$columnIndex - 1] ?? null;
                    $value = is_array($cell) ? ($cell['v'] ?? null) : null;
                    $valueTotal = $this->aggregateSpreadsheetMetricValue(
                        $value,
                        $source['aggregation']
                    );
                    $total += $valueTotal;
                    $rowResults[$recordIndex][$metric] =
                        ($rowResults[$recordIndex][$metric] ?? 0.0) + $valueTotal;
                }
            }

            $results[$metric] = $total;
        }

        return [$results['numerator'], $results['denominator'], $rowResults];
    }

    /**
     * @return array{start_column: int, start_row: int, end_column: int, end_row: int}|null
     */
    private function parseSpreadsheetMetricRange(string $range): ?array
    {
        if (
            preg_match(
                '/^\$?([A-Z]{1,3})\$?(\d+)(?::\$?([A-Z]{1,3})\$?(\d+))?$/iD',
                trim($range),
                $matches
            ) !== 1
        ) {
            return null;
        }

        $startColumn = Coordinate::columnIndexFromString(strtoupper($matches[1]));
        $startRow = (int) $matches[2];
        $endColumn = isset($matches[3])
            ? Coordinate::columnIndexFromString(strtoupper($matches[3]))
            : $startColumn;
        $endRow = isset($matches[4]) ? (int) $matches[4] : $startRow;

        if (
            $startRow < 1 ||
            $endRow < $startRow ||
            $endColumn < $startColumn
        ) {
            return null;
        }

        return [
            'start_column' => $startColumn,
            'start_row' => $startRow,
            'end_column' => $endColumn,
            'end_row' => $endRow,
        ];
    }

    private function aggregateSpreadsheetMetricValue(mixed $value, string $aggregation): float
    {
        if ($aggregation === 'counta') {
            return $value !== null && $value !== '' ? 1.0 : 0.0;
        }

        if ($aggregation === 'count') {
            return is_numeric($value) ? 1.0 : 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $this->isSpreadsheetChecked($value) ? 1.0 : 0.0;
    }

    /**
     * Resolve the input-row range used by a metric's worksheet SUM formula.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array{0: int, 1: int}|null
     */
    private function getSpreadsheetSummaryMetricRowRange(
        array $schema,
        ?array $snapshot,
        string $columnKey
    ): ?array {
        if ($snapshot === null) {
            return null;
        }

        $columns = $this->getSpreadsheetColumnsForWorkbook($schema, $snapshot);
        $columnIndex = array_search($columnKey, array_column($columns, 'key'), true);
        if ($columnIndex === false) {
            return null;
        }

        $sheetId = $snapshot['sheetOrder'][0] ?? null;
        $sheet = is_string($sheetId) ? ($snapshot['sheets'][$sheetId] ?? null) : null;
        if (! is_array($sheet)) {
            return null;
        }

        $headerRowCount = $this->getSpreadsheetHeaderRowCount($schema, $snapshot);
        $columnName = Coordinate::stringFromColumnIndex($columnIndex + 1);
        $summaryRows = [];
        foreach ($sheet['cellData'] ?? [] as $rowIndex => $cells) {
            if ((int) $rowIndex < $headerRowCount || ! is_array($cells)) {
                continue;
            }

            foreach ($cells as $cell) {
                if (is_array($cell) && $this->isSpreadsheetAggregateFormula($cell['f'] ?? null)) {
                    $summaryRows[] = (int) $rowIndex;
                    break;
                }
            }
        }

        foreach (array_reverse($summaryRows) as $summaryRowIndex) {
            $formula = $sheet['cellData'][(string) $summaryRowIndex][$columnIndex]['f'] ?? null;
            if (
                ! is_string($formula) ||
                preg_match(
                    '/^\s*=?\s*SUM\(\s*\$?'.preg_quote($columnName, '/').'\$?(\d+)\s*:\s*\$?'.preg_quote($columnName, '/').'\$?(\d+)\s*\)\s*$/i',
                    $formula,
                    $matches
                ) !== 1
            ) {
                continue;
            }

            $startRow = (int) $matches[1] - $headerRowCount - 1;
            $endRow = (int) $matches[2] - $headerRowCount - 1;
            if ($endRow < 0 || $endRow < $startRow) {
                continue;
            }

            return [max(0, $startRow), $endRow];
        }

        return null;
    }

    /**
     * Validate the editable workbook snapshot before storing it for the period.
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function validateWorkbookSnapshot(array $snapshot): void
    {
        $encodedSnapshot = json_encode($snapshot);
        if ($encodedSnapshot === false || strlen($encodedSnapshot) > 6 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'workbook_snapshot' => 'Ukuran workbook melebihi batas penyimpanan 6 MB.',
            ]);
        }

        $sheetId = $snapshot['sheetOrder'][0] ?? null;
        $sheet = is_string($sheetId) ? ($snapshot['sheets'][$sheetId] ?? null) : null;
        if (! is_array($sheet)) {
            throw ValidationException::withMessages([
                'workbook_snapshot' => 'Struktur worksheet tidak valid. Muat ulang halaman sebelum menyimpan.',
            ]);
        }

        $rowCount = (int) $sheet['rowCount'];
        $columnCount = (int) $sheet['columnCount'];
        foreach ($sheet['cellData'] ?? [] as $rowIndex => $cells) {
            if (
                filter_var($rowIndex, FILTER_VALIDATE_INT) === false ||
                (int) $rowIndex < 0 ||
                (int) $rowIndex >= $rowCount ||
                ! is_array($cells)
            ) {
                throw ValidationException::withMessages([
                    'workbook_snapshot' => 'Data sel workbook tidak valid. Periksa kembali perubahan spreadsheet.',
                ]);
            }

            foreach ($cells as $columnIndex => $cell) {
                if (
                    filter_var($columnIndex, FILTER_VALIDATE_INT) === false ||
                    (int) $columnIndex < 0 ||
                    (int) $columnIndex >= $columnCount ||
                    ! is_array($cell)
                ) {
                    throw ValidationException::withMessages([
                        'workbook_snapshot' => 'Data sel workbook tidak valid. Periksa kembali perubahan spreadsheet.',
                    ]);
                }
            }
        }

        foreach ($sheet['mergeData'] ?? [] as $merge) {
            if (
                ! is_array($merge) ||
                ! isset($merge['startRow'], $merge['endRow'], $merge['startColumn'], $merge['endColumn']) ||
                ! is_numeric($merge['startRow']) ||
                ! is_numeric($merge['endRow']) ||
                ! is_numeric($merge['startColumn']) ||
                ! is_numeric($merge['endColumn']) ||
                (int) $merge['startRow'] < 0 ||
                (int) $merge['endRow'] < (int) $merge['startRow'] ||
                (int) $merge['endRow'] >= $rowCount ||
                (int) $merge['startColumn'] < 0 ||
                (int) $merge['endColumn'] < (int) $merge['startColumn'] ||
                (int) $merge['endColumn'] >= $columnCount
            ) {
                throw ValidationException::withMessages([
                    'workbook_snapshot' => 'Rentang merge pada workbook tidak valid.',
                ]);
            }
        }
    }

    /**
     * Validate values against the active spreadsheet column definitions.
     *
     * @param  array<string|int, mixed>  $records
     * @param  array<string, mixed>  $schema
     */
    private function validateSpreadsheetRecords(
        array $records,
        array $schema,
        ?array $workbookSnapshot = null
    ): void {
        $columns = array_values(array_filter(
            $schema['columns'] ?? [],
            fn ($column): bool => is_array($column) && isset($column['key'])
        ));
        $workbookColumns = $this->getSpreadsheetColumnsForWorkbook(
            $schema,
            $workbookSnapshot
        );
        $columnKeys = array_column($columns, 'key');
        $additionalColumnKeys = array_values(array_diff(
            array_column($workbookColumns, 'key'),
            $columnKeys
        ));
        foreach ($records as $rowIndex => $row) {
            if (! is_array($row)) {
                throw ValidationException::withMessages([
                    'records' => 'Format baris spreadsheet tidak valid.',
                ]);
            }

            $formulas = $row['__formulas'] ?? [];
            if (! is_array($formulas)) {
                throw ValidationException::withMessages([
                    'records' => 'Format rumus spreadsheet tidak valid.',
                ]);
            }

            $rowAdditionalColumnKeys = [];
            foreach (array_keys($row) as $key) {
                if ($key === '__formulas' || in_array($key, $columnKeys, true)) {
                    continue;
                }

                if ($key === '_id' && is_scalar($row[$key])) {
                    continue;
                }

                if (! is_string($key) || ! in_array($key, $additionalColumnKeys, true)) {
                    throw ValidationException::withMessages([
                        'records' => 'Data berisi kolom spreadsheet yang tidak dikenali: '.(string) $key.'.',
                    ]);
                }

                $rowAdditionalColumnKeys[] = $key;
            }

            foreach ($formulas as $key => $formula) {
                if (
                    (! in_array($key, $columnKeys, true) && ! in_array($key, $rowAdditionalColumnKeys, true)) ||
                    ! is_string($formula)
                ) {
                    throw ValidationException::withMessages([
                        'records' => 'Rumus hanya boleh digunakan pada kolom spreadsheet yang dikenali.',
                    ]);
                }

                $this->validateSpreadsheetFormula($formula);
            }

            foreach ($columns as $column) {
                $value = $row[$column['key']] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                if (! is_scalar($value)) {
                    throw ValidationException::withMessages([
                        'records' => 'Nilai sel spreadsheet harus berupa teks atau angka.',
                    ]);
                }

                if (is_string($value) && str_starts_with(trim($value), '=') && ! isset($formulas[$column['key']])) {
                    throw ValidationException::withMessages([
                        'records' => 'Rumus sel harus dikirim melalui editor spreadsheet.',
                    ]);
                }

                $type = $column['type'] ?? 'text';
                $isMetricColumn = in_array($column['role'] ?? null, ['numerator', 'denominator'], true);
                $isValid = match ($type) {
                    'number', 'decimal' => is_numeric($value) && (float) $value >= 0,
                    'checkbox' => in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false', 'v', 'V', '√', 'ya', 'Ya', 'Tidak', 'tidak', 'Sesuai', 'Tidak sesuai'], true),
                    'date' => is_string($value)
                        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
                        && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)),
                    'time' => is_string($value) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1,
                    'select' => is_array($column['options'] ?? null)
                        && in_array($value, $column['options'], true),
                    default => true,
                };
                if ($isMetricColumn) {
                    $isValid = in_array($type, ['number', 'decimal', 'checkbox', 'text'], true)
                        && ($type === 'checkbox' || (is_numeric($value) && (float) $value >= 0));
                }

                if (! $isValid) {
                    throw ValidationException::withMessages([
                        'records' => $isMetricColumn
                            ? "Kolom Numerator atau Denominator pada baris {$rowIndex} harus berisi angka atau nilai checkbox yang valid."
                            : "Nilai pada baris {$rowIndex}, kolom {$column['key']} tidak sesuai dengan tipe atau pilihan yang ditentukan.",
                    ]);
                }
            }

            foreach ($rowAdditionalColumnKeys as $key) {
                $value = $row[$key] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                if (! is_scalar($value)) {
                    throw ValidationException::withMessages([
                        'records' => 'Nilai sel spreadsheet harus berupa teks atau angka.',
                    ]);
                }

                if (
                    is_string($value) &&
                    str_starts_with(trim($value), '=') &&
                    ! isset($formulas[$key])
                ) {
                    throw ValidationException::withMessages([
                        'records' => 'Rumus sel harus dikirim melalui editor spreadsheet.',
                    ]);
                }
            }
        }
    }

    /**
     * Resolve schema keys in the order of the saved workbook headers.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>|null  $snapshot
     * @return array<int, array<string, mixed>>
     */
    private function getSpreadsheetColumnsForWorkbook(array $schema, ?array $snapshot): array
    {
        $columns = array_values(array_filter(
            $schema['columns'] ?? [],
            fn ($column): bool => is_array($column) && isset($column['key'])
        ));
        if ($snapshot === null || $columns === []) {
            return $columns;
        }

        $sheetId = $snapshot['sheetOrder'][0] ?? null;
        $sheet = is_string($sheetId) ? ($snapshot['sheets'][$sheetId] ?? null) : null;
        if (! is_array($sheet)) {
            return $columns;
        }

        $headerRowCount = $this->getSpreadsheetHeaderRowCount($schema, $snapshot);
        $headerRows = [];
        for ($rowIndex = 0; $rowIndex < $headerRowCount; $rowIndex++) {
            $cells = $sheet['cellData'][(string) $rowIndex] ?? [];
            $headerRows[$rowIndex] = [];
            if (! is_array($cells)) {
                continue;
            }

            foreach ($cells as $columnIndex => $cell) {
                $columnIndex = (int) $columnIndex;
                $title = is_array($cell) ? ($cell['v'] ?? null) : null;
                if (
                    $columnIndex >= 0 &&
                    $columnIndex < 500 &&
                    is_string($title) &&
                    trim($title) !== ''
                ) {
                    $headerRows[$rowIndex][$columnIndex] = trim($title);
                }
            }
        }

        $mergeData = is_array($sheet['mergeData'] ?? null) ? $sheet['mergeData'] : [];
        $lastColumnIndex = -1;
        foreach ($headerRows as $cells) {
            foreach (array_keys($cells) as $columnIndex) {
                $lastColumnIndex = max($lastColumnIndex, $columnIndex);
            }
        }

        foreach ($mergeData as $merge) {
            if (
                is_array($merge) &&
                (int) ($merge['startRow'] ?? $headerRowCount) < $headerRowCount &&
                (int) ($merge['endRow'] ?? -1) >= 0 &&
                (int) ($merge['startColumn'] ?? -1) >= 0 &&
                (int) ($merge['endColumn'] ?? -1) >= (int) $merge['startColumn']
            ) {
                $lastColumnIndex = max(
                    $lastColumnIndex,
                    min(499, (int) $merge['endColumn'])
                );
            }
        }

        $headerTitles = [];
        for ($columnIndex = 0; $columnIndex <= min(499, $lastColumnIndex); $columnIndex++) {
            $headerTitle = null;
            for ($rowIndex = $headerRowCount - 1; $rowIndex >= 0; $rowIndex--) {
                $title = $headerRows[$rowIndex][$columnIndex] ?? null;
                if ($title === null) {
                    continue;
                }

                $containingMerge = null;
                foreach ($mergeData as $merge) {
                    if (
                        is_array($merge) &&
                        (int) ($merge['startRow'] ?? PHP_INT_MAX) <= $rowIndex &&
                        (int) ($merge['endRow'] ?? -1) >= $rowIndex &&
                        (int) ($merge['startColumn'] ?? PHP_INT_MAX) <= $columnIndex &&
                        (int) ($merge['endColumn'] ?? -1) >= $columnIndex
                    ) {
                        $containingMerge = $merge;
                        break;
                    }
                }

                $mergeWidth = $containingMerge
                    ? (int) $containingMerge['endColumn'] - (int) $containingMerge['startColumn'] + 1
                    : 1;
                if ($mergeWidth > 1) {
                    continue;
                }

                $headerTitle = $title;
                break;
            }
            $headerTitles[] = $headerTitle;
        }

        $normalizedTitle = fn ($title): string => is_string($title)
            ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($title)) ?? '')
            : '';
        $matchingColumns = [];
        foreach ($columns as $index => $column) {
            $title = $normalizedTitle($column['title'] ?? null);
            if ($title === '') {
                continue;
            }
            $matchingColumns[$title][] = $index;
        }

        $exactColumnIndexes = [];
        foreach ($headerTitles as $title) {
            $normalized = $normalizedTitle($title);
            $matches = $matchingColumns[$normalized] ?? [];
            $exactColumnIndexes[] = $normalized !== '' && count($matches) === 1
                ? $matches[0]
                : null;
        }

        $reservedExactColumns = array_fill_keys(
            array_filter($exactColumnIndexes, fn ($index): bool => $index !== null),
            true
        );
        $usedColumns = [];
        $assignedKeys = array_fill_keys(array_column($columns, 'key'), true);
        $workbookColumns = [];
        foreach ($headerTitles as $index => $headerTitle) {
            $columnIndex = $exactColumnIndexes[$index] ?? null;
            $isPositionallyMatched = false;
            if (
                ($columnIndex === null || isset($usedColumns[$columnIndex])) &&
                isset($columns[$index]) &&
                ! isset($usedColumns[$index]) &&
                (! isset($reservedExactColumns[$index]) || $exactColumnIndexes[$index] === $index)
            ) {
                $columnIndex = $index;
                $isPositionallyMatched = true;
            }

            if ($columnIndex !== null && isset($usedColumns[$columnIndex])) {
                $columnIndex = null;
            }

            if ($columnIndex === null) {
                $keyNumber = $index + 1;
                while (isset($assignedKeys['col_'.$keyNumber])) {
                    $keyNumber++;
                }
                $key = 'col_'.$keyNumber;
                $assignedKeys[$key] = true;
                $column = [
                    'id' => 'c_'.$key,
                    'key' => $key,
                    'type' => 'text',
                    'role' => 'auxiliary',
                ];
            } else {
                $usedColumns[$columnIndex] = true;
                $column = $columns[$columnIndex];
            }

            if ($headerTitle !== null) {
                if (
                    $normalizedTitle($column['title'] ?? null) !== $normalizedTitle($headerTitle) &&
                    in_array($column['role'] ?? null, ['numerator', 'denominator'], true) &&
                    ! $isPositionallyMatched
                ) {
                    $column['role'] = 'auxiliary';
                }
                $column['title'] = $headerTitle;
            } else {
                if (in_array($column['role'] ?? null, ['numerator', 'denominator'], true)) {
                    $column['role'] = 'auxiliary';
                }
                $column['title'] = 'Kolom '.Coordinate::stringFromColumnIndex($index + 1);
            }

            $workbookColumns[] = $column;
        }

        return $headerTitles === [] ? $columns : $workbookColumns;
    }

    /**
     * Resolve the effective header rows when saved data occupies a configured header row.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>|null  $snapshot
     */
    private function getSpreadsheetHeaderRowCount(array $schema, ?array $snapshot): int
    {
        $configuredHeaderRowCount = max(1, min(10, (int) ($schema['header_rows'] ?? 2)));
        if ($snapshot === null) {
            return $configuredHeaderRowCount;
        }

        $sheetId = $snapshot['sheetOrder'][0] ?? null;
        $sheet = is_string($sheetId) ? ($snapshot['sheets'][$sheetId] ?? null) : null;
        if (! is_array($sheet)) {
            return $configuredHeaderRowCount;
        }

        $metricColumns = [];
        foreach (array_values(array_filter(
            $schema['columns'] ?? [],
            fn ($column): bool => is_array($column)
        )) as $columnIndex => $column) {
            if (in_array($column['role'] ?? null, ['numerator', 'denominator'], true)) {
                $metricColumns[$columnIndex] = $column;
            }
        }

        for ($rowIndex = 1; $rowIndex < $configuredHeaderRowCount; $rowIndex++) {
            $cells = $sheet['cellData'][(string) $rowIndex] ?? [];
            if (! is_array($cells)) {
                $cells = [];
            }

            $hasFormula = false;
            foreach ($cells as $cell) {
                if (
                    is_array($cell) &&
                    is_string($cell['f'] ?? null) &&
                    trim($cell['f']) !== ''
                ) {
                    $hasFormula = true;
                    break;
                }
            }

            $hasMetricInput = false;
            foreach ($metricColumns as $columnIndex => $column) {
                $cell = $cells[$columnIndex] ?? null;
                $value = is_array($cell) ? ($cell['v'] ?? null) : null;
                if ($value === null || $value === '') {
                    continue;
                }

                if (is_numeric($value) || is_bool($value)) {
                    $hasMetricInput = true;
                    break;
                }

                if (! is_string($value)) {
                    continue;
                }

                $normalizedValue = mb_strtolower(trim($value));
                $hasNumericValue = $normalizedValue === '' || is_numeric($normalizedValue);
                $hasCheckboxValue = ($column['type'] ?? null) === 'checkbox' &&
                    in_array($normalizedValue, [
                        'false',
                        'no',
                        'sesuai',
                        'tidak',
                        'tidak sesuai',
                        'true',
                        'v',
                        'x',
                        'ya',
                        'yes',
                        '√',
                    ], true);

                if ($hasNumericValue || $hasCheckboxValue) {
                    $hasMetricInput = true;
                    break;
                }
            }

            if ($hasFormula || $hasMetricInput) {
                return $rowIndex;
            }
        }

        return $configuredHeaderRowCount;
    }

    /**
     * Recalculate formulas for legacy layouts while preserving their stored record shapes.
     *
     * @param  array<string|int, mixed>  $records
     * @return array<string|int, mixed>
     */
    private function recalculateLegacySpreadsheetRecords(
        array $records,
        string $layoutType,
        ?array &$spreadsheetRows = null
    ): array {
        $metadata = $records['__spreadsheet'] ?? null;
        if (
            ! is_array($metadata) ||
            ($metadata['layout_type'] ?? null) !== $layoutType ||
            ! in_array($layoutType, [
                'daily_grid',
                'entity_checklist',
                'grouped_category',
                'inspection_points',
                'location_matrix',
                'monthly_summary',
                'entity_rows',
                'patient_register',
            ], true)
        ) {
            throw ValidationException::withMessages([
                'records' => 'Metadata spreadsheet tidak cocok dengan format indikator.',
            ]);
        }

        $columns = $metadata['columns'] ?? null;
        $fixedRows = $metadata['rows'] ?? null;
        $spreadsheetRows = $metadata['records'] ?? null;
        $spreadsheetRowType = $metadata['row_type'] ?? null;
        if (
            ! is_array($columns) ||
            $columns === [] ||
            count($columns) > 100 ||
            ! is_array($fixedRows) ||
            count($fixedRows) > 500 ||
            ! is_array($spreadsheetRows) ||
            ! in_array($spreadsheetRowType, ['dynamic_rows', 'fixed_rows'], true) ||
            ($spreadsheetRowType === 'fixed_rows' && $fixedRows === []) ||
            ($spreadsheetRowType === 'dynamic_rows' && (count($spreadsheetRows) > 500 || ! array_is_list($spreadsheetRows)))
        ) {
            throw ValidationException::withMessages([
                'records' => 'Struktur spreadsheet tidak valid.',
            ]);
        }

        $columnKeys = [];
        foreach ($columns as $column) {
            $key = is_array($column) ? ($column['key'] ?? null) : null;
            if (! is_string($key) || preg_match('/^[A-Za-z0-9_-]{1,100}$/', $key) !== 1 || in_array($key, $columnKeys, true)) {
                throw ValidationException::withMessages([
                    'records' => 'Kolom spreadsheet tidak valid.',
                ]);
            }

            $columnKeys[] = $key;
        }

        $rowKeys = [];
        if ($spreadsheetRowType === 'fixed_rows') {
            foreach ($fixedRows as $row) {
                $key = is_array($row) ? ($row['key'] ?? null) : null;
                if (! is_string($key) || preg_match('/^[A-Za-z0-9_-]{1,100}$/', $key) !== 1 || in_array($key, $rowKeys, true)) {
                    throw ValidationException::withMessages([
                        'records' => 'Baris spreadsheet tidak valid.',
                    ]);
                }

                $rowKeys[] = $key;
            }
        } else {
            $rowKeys = array_keys($spreadsheetRows);
        }

        $normalizedRows = [];
        foreach ($rowKeys as $rowIndex => $rowKey) {
            $rowKey = (string) ($spreadsheetRowType === 'fixed_rows' ? $rowKey : $rowIndex);
            $normalizedRow = $spreadsheetRowType === 'fixed_rows'
                ? ($spreadsheetRows[$rowKey] ?? [])
                : ($spreadsheetRows[$rowIndex] ?? []);
            if (! is_array($normalizedRow)) {
                throw ValidationException::withMessages([
                    'records' => 'Data baris spreadsheet tidak valid.',
                ]);
            }

            $formulas = $normalizedRow['__formulas'] ?? [];
            if (! is_array($formulas)) {
                throw ValidationException::withMessages([
                    'records' => 'Metadata rumus spreadsheet tidak valid.',
                ]);
            }

            foreach ($formulas as $key => $formula) {
                if (! in_array($key, $columnKeys, true) || ! is_string($formula)) {
                    throw ValidationException::withMessages([
                        'records' => 'Rumus hanya boleh digunakan pada kolom spreadsheet yang terdaftar.',
                    ]);
                }

                $this->validateSpreadsheetFormula($formula);
            }

            $normalizedRows[$rowKey] = $normalizedRow;
        }

        $schema = [
            'columns' => $columns,
            'rows' => $fixedRows,
            'header_rows' => max(1, min(10, (int) ($metadata['header_rows'] ?? 2))),
        ];
        $calculatedRows = $this->calculateSpreadsheetFormulaValues(
            $normalizedRows,
            $schema,
            $spreadsheetRowType
        );
        $spreadsheetRows = array_values($calculatedRows);
        unset($records['__spreadsheet']);

        if (in_array($layoutType, ['entity_rows', 'patient_register'], true)) {
            return $spreadsheetRowType === 'dynamic_rows'
                ? array_values($calculatedRows)
                : $calculatedRows;
        }

        foreach ($rowKeys as $rowKey) {
            $normalizedRowKey = $spreadsheetRowType === 'fixed_rows'
                ? (string) $rowKey
                : (string) array_search($rowKey, $rowKeys, true);
            $formulas = $calculatedRows[$normalizedRowKey]['__formulas'] ?? [];
            foreach ($formulas as $key => $formula) {
                $value = $calculatedRows[$normalizedRowKey][$key] ?? null;

                switch ($layoutType) {
                    case 'daily_grid':
                        if (
                            isset($records[$rowKey]) &&
                            is_array($records[$rowKey]) &&
                            array_key_exists($key, $records[$rowKey])
                        ) {
                            $records[$rowKey][$key] = $value;
                            $records[$rowKey]['__formulas'][$key] = $formula;
                        }
                        break;
                    case 'grouped_category':
                        if (
                            isset($records['categories'][$rowKey]) &&
                            is_array($records['categories'][$rowKey]) &&
                            array_key_exists($key, $records['categories'][$rowKey])
                        ) {
                            $records['categories'][$rowKey][$key] = $value;
                            $records['categories'][$rowKey]['__formulas'][$key] = $formula;
                        }
                        break;
                    case 'entity_checklist':
                        if (
                            isset($records['entities'][$rowKey]) &&
                            is_array($records['entities'][$rowKey]) &&
                            array_key_exists($key, $records['entities'][$rowKey])
                        ) {
                            $records['entities'][$rowKey][$key] = $value;
                            $records['entities'][$rowKey]['__formulas'][$key] = $formula;
                        }
                        break;
                    case 'location_matrix':
                        if (
                            isset($records['locations'][$rowKey]) &&
                            is_array($records['locations'][$rowKey]) &&
                            array_key_exists($key, $records['locations'][$rowKey])
                        ) {
                            $records['locations'][$rowKey][$key] = $value;
                            $records['locations'][$rowKey]['__formulas'][$key] = $formula;
                        }
                        break;
                    case 'inspection_points':
                        if (! isset($records['points'][$rowKey]) || ! is_array($records['points'][$rowKey])) {
                            break;
                        }

                        if (isset($records['points'][$rowKey]['criteria'][$key])) {
                            $records['points'][$rowKey]['criteria'][$key] = $value;
                            $records['points'][$rowKey]['__formulas'][$key] = $formula;
                        } elseif (in_array($key, ['checked', 'catatan', 'compliant'], true)) {
                            $records['points'][$rowKey][$key] = $value;
                            $records['points'][$rowKey]['__formulas'][$key] = $formula;
                        }
                        break;
                    case 'monthly_summary':
                        if (
                            isset($records[$rowKey]) &&
                            is_array($records[$rowKey]) &&
                            array_key_exists($key, $records[$rowKey])
                        ) {
                            $records[$rowKey][$key] = $value;
                            $records[$rowKey]['__formulas'][$key] = $formula;
                        }
                        break;
                }
            }
        }

        return $records;
    }

    /**
     * Recalculate submitted formulas on the server before persisting cell values.
     *
     * @param  array<string|int, mixed>  $records
     * @param  array<string, mixed>  $schema
     * @return array<string|int, mixed>
     */
    private function calculateSpreadsheetFormulaValues(
        array $records,
        array $schema,
        string $layoutType,
        ?array $workbookSnapshot = null
    ): array {
        $columns = $this->getSpreadsheetColumnsForWorkbook($schema, $workbookSnapshot);
        $fixedRows = $layoutType === 'fixed_rows' ? ($schema['rows'] ?? []) : [];
        $headerRows = $this->getSpreadsheetHeaderRowCount($schema, $workbookSnapshot);
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $columnIndex => $column) {
            $columnCoordinate = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $worksheet->setCellValue(
                $columnCoordinate.$headerRows,
                (string) ($column['title'] ?? $column['key'])
            );
        }

        $rowKeys = $layoutType === 'fixed_rows'
            ? array_map(fn ($row): string => (string) ($row['key'] ?? ''), $fixedRows)
            : array_keys($records);

        foreach ($rowKeys as $rowIndex => $rowKey) {
            $row = $records[$rowKey] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $sheetRow = $headerRows + $rowIndex + 1;
            foreach ($columns as $columnIndex => $column) {
                $key = $column['key'];
                if ($layoutType === 'fixed_rows' && (($column['role'] ?? null) === 'label' || $columnIndex === 0)) {
                    $worksheet->setCellValue(
                        Coordinate::stringFromColumnIndex($columnIndex + 1).$sheetRow,
                        (string) ($fixedRows[$rowIndex]['label'] ?? '')
                    );

                    continue;
                }

                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$sheetRow;
                $value = $row[$key] ?? null;
                $worksheet->setCellValue($coordinate, is_scalar($value) ? $value : null);
            }
        }

        $calculatedRecords = $records;
        foreach ($rowKeys as $rowIndex => $rowKey) {
            $formulas = $records[$rowKey]['__formulas'] ?? [];
            if (! is_array($formulas)) {
                continue;
            }

            $sheetRow = $headerRows + $rowIndex + 1;
            foreach ($formulas as $key => $formula) {
                $columnIndex = array_search($key, array_column($columns, 'key'), true);
                if ($columnIndex === false) {
                    continue;
                }

                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$sheetRow;
                $worksheet->setCellValue($coordinate, $formula);
                try {
                    $value = $worksheet->getCell($coordinate)->getCalculatedValue();
                } catch (SpreadsheetCalculationException) {
                    throw ValidationException::withMessages([
                        'records' => 'Rumus spreadsheet tidak dapat dihitung. Periksa sintaks dan referensi sel.',
                    ]);
                }

                if (is_string($value) && str_starts_with($value, '#')) {
                    $column = $columns[$columnIndex];
                    $isTextOnlyColumn = ($column['type'] ?? 'text') === 'text'
                        && ! in_array($column['role'] ?? null, ['numerator', 'denominator'], true);

                    if (! $isTextOnlyColumn) {
                        throw ValidationException::withMessages([
                            'records' => "Rumus pada sel {$coordinate} menghasilkan {$value}. Periksa referensi sel dan nilai yang digunakan.",
                        ]);
                    }
                }

                $calculatedRecords[$rowKey][$key] = $value;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $calculatedRecords;
    }

    private function validateSpreadsheetFormula(string $formula): void
    {
        $allowedFunctions = [
            'ABS',
            'AVERAGE',
            'AND',
            'COUNT',
            'COUNTA',
            'IF',
            'MAX',
            'MIN',
            'NOT',
            'OR',
            'POWER',
            'PRODUCT',
            'ROUND',
            'SUM',
        ];

        if (
            strlen($formula) > 500 ||
            preg_match('/^=[A-Z0-9_$ .,+\-*\/^():<>="]+$/iD', $formula) !== 1 ||
            preg_match('/\b([A-Z_][A-Z0-9_.]*)\s*\(/i', $formula, $matches) === 1 &&
                ! in_array(strtoupper($matches[1]), $allowedFunctions, true) ||
            preg_match_all('/\b([A-Z_][A-Z0-9_.]*)\s*\(/i', $formula, $functionMatches) > 0 &&
                array_diff(array_map('strtoupper', $functionMatches[1]), $allowedFunctions) !== []
        ) {
            throw ValidationException::withMessages([
                'records' => 'Rumus tidak valid atau menggunakan fungsi yang tidak diizinkan.',
            ]);
        }
    }

    private function spreadsheetMetricValue(mixed $value, string $type): float
    {
        if ($type === 'checkbox') {
            return $this->isSpreadsheetChecked($value) ? 1.0 : 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $this->isSpreadsheetChecked($value) ? 1.0 : 0.0;
    }

    private function isSpreadsheetChecked(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'v', 'V', '√', 'ya', 'Ya', 'Sesuai'], true);
    }

    private function hasSpreadsheetValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== false && $value !== 0 && $value !== '0';
    }

    /**
     * Ajukan Tally Sheet Periode Ini ke Komite Mutu (Validasi)
     */
    public function submitPeriod(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isDireksi() || $user->isKomiteMutu()) {
            return redirect()->back()->with('error', 'Akses dibatasi: Pengajuan validasi hanya dilakukan oleh Unit Kerja atau Administrator.');
        }

        $validated = $request->validate([
            'indicator_id' => 'required|exists:indicators,id',
            'unit_id' => 'required|exists:units,id',
            'tahun' => 'required|integer|between:1900,2100',
            'bulan' => 'required|integer|between:1,12',
        ]);

        return DB::transaction(function () use ($user, $validated): RedirectResponse {
            $indicator = Indicator::whereKey($validated['indicator_id'])->lockForUpdate()->firstOrFail();
            $unit = Unit::findOrFail($validated['unit_id']);
            $this->authorizeUnitManagement($user, $unit);
            abort_if($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id, 404);

            $annualValidationStatus = Validation::getIndicatorValidationStatus(
                (int) $unit->id,
                (int) $indicator->id,
                (int) $validated['tahun'],
                (int) $validated['bulan'],
            );
            if ($annualValidationStatus['is_exempt']) {
                return redirect()->back()->with('error', $annualValidationStatus['message']);
            }

            $period = IndicatorPeriod::where([
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
            ])->lockForUpdate()->first();

            if (! $period) {
                return redirect()->back()->with(
                    'error',
                    'Periode belum memiliki data Form Pengumpul Data untuk diajukan.'
                );
            }

            $pendingEntryExists = DataEntry::where('indicator_id', $indicator->id)
                ->where('unit_id', $unit->id)
                ->where('status', 'submitted')
                ->whereHas('period', fn ($query) => $query->where('tahun', $validated['tahun']))
                ->exists();
            if ($pendingEntryExists) {
                return redirect()->back()->with('error', 'Masih ada pengajuan validasi profil ini yang menunggu keputusan.');
            }

            $entry = DataEntry::where([
                'indicator_id' => $indicator->id,
                'period_id' => $period->id,
                'unit_id' => $unit->id,
            ])->lockForUpdate()->first();

            if (! $entry || (float) $entry->denominator <= 0) {
                return redirect()->back()->with(
                    'error',
                    'Data indikator belum memiliki denominator. Lengkapi dan simpan data sebelum mengajukan validasi.'
                );
            }

            if (! in_array($entry->status, ['draft', 'revision', 'rejected', 'exempt'], true)) {
                return redirect()->back()->with(
                    'error',
                    $entry->status === 'submitted'
                        ? 'Data ini sudah menunggu validasi.'
                        : 'Data yang sudah disetujui tidak dapat diajukan ulang sebelum dibuka sebagai revisi.'
                );
            }

            $dailyRecords = IndicatorDailyRecord::where([
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
            ])->orderBy('hari')->get();

            $now = now();
            $entry->update([
                'status' => 'submitted',
                'submitted_at' => $now,
            ]);

            $snapshotData = [
                'submitted_at' => $now->toIso8601String(),
                'submitted_by' => $user->name,
                'log_harian' => $entry->log_harian,
                'form_schema_version' => $entry->form_schema_version,
                'workbook_snapshot' => $entry->workbook_snapshot,
                'daily_records' => $dailyRecords->map(fn ($record) => [
                    'hari' => $record->hari,
                    'tanggal' => $record->tanggal?->format('Y-m-d'),
                    'data' => $record->data,
                ])->values()->all(),
            ];

            Validation::create([
                'data_entry_id' => $entry->id,
                'validator_id' => $user->id,
                'status' => 'submitted',
                'unit_numerator' => (float) $entry->numerator,
                'unit_denominator' => (float) $entry->denominator,
                'unit_capaian' => (float) $entry->capaian,
                'unit_data_snapshot' => $snapshotData,
                'tanggal_validasi' => $now,
            ]);

            return redirect()->back()->with(
                'success',
                'Tally Sheet berhasil diajukan untuk proses validasi internal unit.'
            );
        });
    }

    /**
     * Konfigurasi Kolom Instrumen Pengumpulan Data (Form Fields)
     */
    public function saveFormFields(Request $request, Indicator $indicator)
    {
        $user = $request->user();
        if (! $user->isSuperAdmin() && ! $user->isKomiteMutu()) {
            return redirect()->back()->with('error', 'Akses dibatasi: Hanya Admin dan Komite Mutu yang dapat mengonfigurasi kolom instrumen.');
        }

        $validated = $request->validate([
            'fields' => 'required|array|min:2',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.key' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'],
            'fields.*.type' => 'required|in:numerator,denominator,auxiliary',
        ]);

        if (
            collect($validated['fields'])->where('type', 'numerator')->count() !== 1 ||
            collect($validated['fields'])->where('type', 'denominator')->count() !== 1
        ) {
            throw ValidationException::withMessages([
                'fields' => 'Kolom instrumen harus memiliki tepat satu Numerator dan satu Denominator.',
            ]);
        }

        DB::transaction(function () use ($indicator, $validated): void {
            IndicatorFormField::where('indicator_id', $indicator->id)->delete();
            foreach ($validated['fields'] as $index => $field) {
                IndicatorFormField::create([
                    'indicator_id' => $indicator->id,
                    'name' => $field['name'],
                    'key' => $field['key'],
                    'type' => $field['type'],
                    'order' => $index + 1,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Kolom instrumen pengumpulan data berhasil diperbarui.');
    }

    /**
     * Ekspor Tally Sheet Harian ke Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $validated = $request->validate([
            'indicator_id' => 'required|integer|exists:indicators,id',
            'unit_id' => 'required|integer|exists:units,id',
            'tahun' => 'required|integer|between:1900,2100',
            'bulan' => 'required|integer|between:1,12',
        ]);

        $indicator = Indicator::with('formFields')->findOrFail($validated['indicator_id']);
        $unit = Unit::findOrFail($validated['unit_id']);
        $this->authorizeUnitView($request->user(), $unit);
        abort_if($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id, 404);
        $tahun = $validated['tahun'];
        $bulan = $validated['bulan'];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

        $fields = $indicator->formFields;
        if ($fields->isEmpty()) {
            $fields = collect([
                (object) ['key' => 'num', 'name' => 'Numerator', 'type' => 'numerator'],
                (object) ['key' => 'den', 'name' => 'Denominator', 'type' => 'denominator'],
            ]);
        }

        $dailyRecords = IndicatorDailyRecord::where('indicator_id', $indicator->id)
            ->where('unit_id', $unit->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('hari');

        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $entry = DataEntry::where('indicator_id', $indicator->id)
            ->where('unit_id', $unit->id)
            ->whereHas('period', fn ($q) => $q->where('tahun', $tahun)->where('bulan', $bulan))
            ->first();

        $entrySchema = $indicator->form_schema;
        if (
            $entry &&
            $entry->form_schema_version !== null &&
            (int) ($entrySchema['schema_version'] ?? 0) !== $entry->form_schema_version
        ) {
            $entrySchema = $entrySchema['schema_history'][(string) $entry->form_schema_version] ?? null;
            abort_if(
                ! is_array($entrySchema),
                409,
                'Versi format yang digunakan untuk data periode ini tidak tersedia. Ekspor tidak dapat dibuat dengan aman; hubungi administrator.'
            );
        }

        $entryLayoutType = $entrySchema['row_type'] ?? ($indicator->form_layout_type ?: 'daily_grid');
        if (
            ($entrySchema['renderer'] ?? null) === 'spreadsheet' &&
            in_array($entryLayoutType, ['dynamic_rows', 'fixed_rows'], true) &&
            ! empty($entrySchema['columns'])
        ) {
            $entrySchema['row_type'] = $entryLayoutType;

            return $this->exportSpreadsheetEntry(
                $entry,
                $entrySchema,
                $indicator,
                $unit,
                $tahun,
                $bulan,
                $bulanNames[$bulan],
            );
        }

        $patientLogs = (is_array($entry?->log_harian) && count($entry->log_harian) > 0 && isset($entry->log_harian[0]['nama'])) ? $entry->log_harian : null;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($indicator->kode.' '.$bulanNames[$bulan], 0, 31));

        // Judul Header
        $sheet->setCellValue('A1', 'FORM PENGUMPUL DATA INDIKATOR MUTU');
        $sheet->setCellValue('A2', "Unit Kerja: {$unit->nama} | Periode: {$bulanNames[$bulan]} {$tahun}");
        $sheet->setCellValue('A3', "Indikator: [{$indicator->kode}] {$indicator->judul}");
        $sheet->setCellValue('A4', "Target: {$indicator->target_operator} {$indicator->target_capaian} ".($indicator->satuan_pengukuran ?? '%'));

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A4')->getFont()->setSize(10);

        if ($patientLogs) {
            // FORMAT REGISTER PASIEN DINAMIS (RAWAT INAP, IGD, ICU)
            $sheet->setCellValue('A6', 'No');
            $sheet->setCellValue('B6', 'Tanggal Observasi');
            $sheet->setCellValue('C6', 'No. Rekam Medis (RM)');
            $sheet->setCellValue('D6', 'Nama Pasien');
            $sheet->setCellValue('E6', 'Kepatuhan Standar [N]');
            $sheet->setCellValue('F6', 'Denominator [D]');
            $sheet->setCellValue('G6', 'Status / Keterangan');

            $sheet->getStyle('A6:G6')->getFont()->setBold(true);
            $sheet->getStyle('A6:G6')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE2E8F0');

            $currentRow = 7;
            $idx = 1;
            foreach ($patientLogs as $row) {
                $sheet->setCellValue('A'.$currentRow, $idx++);
                $sheet->setCellValue('B'.$currentRow, $row['tanggal'] ?? '-');
                $sheet->setCellValue('C'.$currentRow, $row['norm'] ?? '-');
                $sheet->setCellValue('D'.$currentRow, $row['nama'] ?? '-');
                $nVal = (int) ($row['num'] ?? ($row['kepatuhan'] === 'Sesuai' ? 1 : 0));
                $sheet->setCellValue('E'.$currentRow, $nVal);
                $sheet->setCellValue('F'.$currentRow, 1);
                $sheet->setCellValue('G'.$currentRow, $nVal === 1 ? 'Sesuai' : 'Tidak Sesuai');
                $currentRow++;
            }

            $totalRow = $currentRow;
            $sheet->setCellValue('A'.$totalRow, 'TOTAL');
            $sheet->setCellValue('D'.$totalRow, 'Total Pasien Diobservasi');
            $sheet->setCellValue('E'.$totalRow, '=SUM(E7:E'.($totalRow - 1).')');
            $sheet->setCellValue('F'.$totalRow, '=SUM(F7:F'.($totalRow - 1).')');
            $sheet->setCellValue('G'.$totalRow, "=IF(F{$totalRow}>0, (E{$totalRow}/F{$totalRow})*100, 0)");

            $sheet->getStyle("A{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$totalRow}:G{$totalRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFEF08A');

            foreach (range('A', 'G') as $colLetter) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $filename = "Tally_Sheet_Register_{$indicator->kode}_{$bulanNames[$bulan]}_{$tahun}.xlsx";

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'max-age=0',
            ]);
        }

        // Header Tabel di baris 6
        $sheet->setCellValue('A6', 'Tgl');
        $sheet->setCellValue('B6', 'Tanggal');
        $colIdx = 3;
        foreach ($fields as $field) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter.'6', $field->name.($field->type === 'numerator' ? ' (N)' : ($field->type === 'denominator' ? ' (D)' : '')));
            $colIdx++;
        }
        $capaianColLetter = Coordinate::stringFromColumnIndex($colIdx);
        $mult = (float) ($indicator->multiplier ?? 100);
        $sheet->setCellValue($capaianColLetter.'6', $mult == 100 ? 'Capaian (%)' : 'Capaian');

        $lastColLetter = $capaianColLetter;
        $sheet->getStyle("A6:{$lastColLetter}6")->getFont()->setBold(true);
        $sheet->getStyle("A6:{$lastColLetter}6")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        // Isi Data Harian (baris 7 s.d. 7 + daysInMonth - 1)
        $currentRow = 7;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%02d/%02d/%04d', $d, $bulan, $tahun);
            $sheet->setCellValue('A'.$currentRow, $d);
            $sheet->setCellValue('B'.$currentRow, $dateStr);

            $rec = $dailyRecords->get($d);
            $data = $rec ? $rec->data : [];

            $cIdx = 3;
            $nVal = 0;
            $dVal = 0;
            foreach ($fields as $field) {
                $cLet = Coordinate::stringFromColumnIndex($cIdx);
                $val = (float) ($data[$field->key] ?? 0);
                if ($field->type === 'numerator') {
                    $nVal = $val;
                }
                if ($field->type === 'denominator') {
                    $dVal = $val;
                }

                $sheet->setCellValue($cLet.$currentRow, $val);
                $cIdx++;
            }

            // Capaian Harian
            $cLet = Coordinate::stringFromColumnIndex($cIdx);
            $suffix = $mult == 100 ? '%' : '';
            $capaianStr = $dVal > 0 ? round(($nVal / $dVal) * $mult, 2).$suffix : '-';
            $sheet->setCellValue($cLet.$currentRow, $capaianStr);

            $currentRow++;
        }

        // Baris TOTAL
        $totalRow = $currentRow;
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');
        $sheet->setCellValue('B'.$totalRow, "Bulan {$bulanNames[$bulan]}");

        $cIdx = 3;
        $numTotalCol = null;
        $denTotalCol = null;
        foreach ($fields as $field) {
            $cLet = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->setCellValue($cLet.$totalRow, "=SUM({$cLet}7:{$cLet}".($totalRow - 1).')');
            if ($field->type === 'numerator') {
                $numTotalCol = $cLet;
            }
            if ($field->type === 'denominator') {
                $denTotalCol = $cLet;
            }
            $cIdx++;
        }

        // Rumus Capaian Akhir Bulan
        $cLet = Coordinate::stringFromColumnIndex($cIdx);
        if ($numTotalCol && $denTotalCol) {
            $sheet->setCellValue($cLet.$totalRow, "=IF({$denTotalCol}{$totalRow}>0, ({$numTotalCol}{$totalRow}/{$denTotalCol}{$totalRow})*{$mult}, 0)");
        }

        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFEF08A');

        // Auto width
        foreach (range(1, $colIdx) as $colNumber) {
            $sheet->getColumnDimensionByColumn($colNumber)->setAutoSize(true);
        }

        $filename = "Tally_Sheet_{$indicator->kode}_{$bulanNames[$bulan]}_{$tahun}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export configured spreadsheet records using the schema saved with the period entry.
     *
     * @param  array<string, mixed>  $schema
     */
    private function exportSpreadsheetEntry(
        ?DataEntry $entry,
        array $schema,
        Indicator $indicator,
        Unit $unit,
        int $tahun,
        int $bulan,
        string $bulanName
    ): StreamedResponse {
        $rowType = $schema['row_type'] ?? ($indicator->form_layout_type ?: 'daily_grid');
        $columns = array_values(array_filter(
            $schema['columns'],
            fn ($column): bool => is_array($column) && isset($column['key'])
        ));
        $fixedRows = $rowType === 'fixed_rows'
            ? array_values(array_filter($schema['rows'] ?? [], 'is_array'))
            : [];
        $storedRows = $entry?->log_harian ?? [];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($indicator->kode.' '.$bulanName, 0, 31));
        $sheet->setCellValue('A1', 'FORM PENGUMPUL DATA INDIKATOR MUTU');
        $sheet->setCellValue('A2', "Unit Kerja: {$unit->nama} | Periode: {$bulanName} {$tahun}");
        $sheet->setCellValue('A3', "Indikator: [{$indicator->kode}] {$indicator->judul}");
        $sheet->setCellValue('A4', "Target: {$indicator->target_operator} {$indicator->target_capaian} ".($indicator->satuan_pengukuran ?? '%'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A4')->getFont()->setSize(10);

        $headerRow = 6;
        foreach ($columns as $columnIndex => $column) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$headerRow;
            $title = (string) ($column['title'] ?? $column['key']);
            if (($column['role'] ?? null) === 'numerator') {
                $title .= ' (N)';
            } elseif (($column['role'] ?? null) === 'denominator') {
                $title .= ' (D)';
            }
            $sheet->setCellValueExplicit($coordinate, $title, DataType::TYPE_STRING);
        }
        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        $exportRows = $rowType === 'fixed_rows'
            ? array_map(
                fn (array $row): array => [
                    'definition' => $row,
                    'values' => is_array($storedRows[$row['key'] ?? ''] ?? null)
                        ? $storedRows[$row['key']] : [],
                ],
                $fixedRows,
            )
            : array_map(
                fn (array $row): array => ['definition' => [], 'values' => $row],
                array_values(array_filter($storedRows, 'is_array')),
            );

        foreach ($exportRows as $rowIndex => $exportRow) {
            $sheetRow = $headerRow + $rowIndex + 1;
            foreach ($columns as $columnIndex => $column) {
                $columnKey = $column['key'];
                $value = $exportRow['values'][$columnKey] ?? '';
                if (
                    $rowType === 'fixed_rows' &&
                    (($column['role'] ?? null) === 'label' || $columnIndex === 0)
                ) {
                    $value = $exportRow['definition']['values'][$columnKey]
                        ?? $exportRow['definition']['label']
                        ?? $value;
                }

                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$sheetRow;
                if (is_string($value)) {
                    $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
                } elseif (is_scalar($value) || $value === null) {
                    $sheet->setCellValue($coordinate, $value);
                }
            }
        }

        foreach (range(1, count($columns)) as $columnNumber) {
            $sheet->getColumnDimensionByColumn($columnNumber)->setAutoSize(true);
        }

        $filename = "Tally_Sheet_{$indicator->kode}_{$bulanName}_{$tahun}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // ─── Legacy methods for single entry compatibility ───────────────────────

    public function create(Request $request, Indicator $indicator, int $tahun, int $bulan): Response
    {
        $user = $request->user();
        abort_if(
            $tahun < 1900 ||
                $tahun > 2100 ||
                $bulan < 1 ||
                $bulan > 12,
            404
        );

        $unitId = $indicator->unit_id;
        if (! $unitId) {
            $unitId = $request->integer('unit_id', (int) $user->unit_id);
        }
        abort_if(! $unitId, 404);
        $unit = Unit::findOrFail($unitId);
        abort_if($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id, 404);
        $this->authorizeUnitManagement($user, $unit);
        $period = IndicatorPeriod::firstOrCreate(
            ['indicator_id' => $indicator->id, 'unit_id' => $unit->id, 'tahun' => $tahun, 'bulan' => $bulan],
            ['status' => 'open']
        );

        $existingEntries = DataEntry::where('period_id', $period->id)
            ->where('unit_id', $unit->id)
            ->orderBy('tanggal')
            ->get();

        return Inertia::render('DataEntry/Create', [
            'indicator' => $indicator,
            'period' => $period,
            'existingEntries' => $existingEntries,
            'unit' => $unit,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);
    }

    public function store(Request $request, PrivateEvidenceStorage $evidenceStorage): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'indicator_id' => 'required|exists:indicators,id',
            'period_id' => 'required|exists:indicator_periods,id',
            'unit_id' => 'required|exists:units,id',
            'tanggal' => 'nullable|date',
            'numerator' => 'required|numeric|min:0',
            'denominator' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,xlsx,xls',
            'log_harian' => 'nullable|array',
        ]);

        $indicator = Indicator::findOrFail($validated['indicator_id']);
        $unit = Unit::findOrFail($validated['unit_id']);
        $period = IndicatorPeriod::findOrFail($validated['period_id']);
        $this->authorizeUnitManagement($user, $unit);

        abort_if(
            ($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id) ||
                (int) $period->indicator_id !== (int) $indicator->id ||
                (int) $period->unit_id !== (int) $unit->id,
            404
        );

        $validated['user_id'] = $user->id;

        if ($request->hasFile('file_bukti')) {
            $validated['file_bukti'] = $evidenceStorage->store(
                $request->file('file_bukti'),
                'data-entry/bukti',
            );
        }

        DataEntry::create($validated);

        return redirect()->route('data-entry.index')
            ->with('success', 'Data berhasil disimpan.');
    }

    public function show(Request $request, DataEntry $dataEntry): Response
    {
        $this->authorizeEntryView($request->user(), $dataEntry);
        $dataEntry->load(['indicator', 'unit', 'user', 'validations.validator', 'validations.actor', 'period']);

        return Inertia::render('DataEntry/Show', [
            'entry' => $dataEntry,
        ]);
    }

    public function edit(Request $request, DataEntry $dataEntry): Response
    {
        $this->authorizeEntryManagement($request->user(), $dataEntry);
        $dataEntry->load(['indicator', 'unit', 'period']);

        return Inertia::render('DataEntry/Edit', [
            'entry' => $dataEntry,
        ]);
    }

    public function update(Request $request, DataEntry $dataEntry, PrivateEvidenceStorage $evidenceStorage)
    {
        $this->authorizeEntryManagement($request->user(), $dataEntry);
        abort_if(! in_array($dataEntry->status, ['draft', 'revision']), 403, 'Data tidak bisa diedit.');

        $validated = $request->validate([
            'tanggal' => 'nullable|date',
            'numerator' => 'required|numeric|min:0',
            'denominator' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,xlsx,xls',
            'log_harian' => 'nullable|array',
        ]);

        $previousFilePath = $dataEntry->file_bukti;
        if ($request->hasFile('file_bukti')) {
            $validated['file_bukti'] = $evidenceStorage->store(
                $request->file('file_bukti'),
                'data-entry/bukti',
            );
        }

        $dataEntry->update($validated);

        if ($request->hasFile('file_bukti') && $previousFilePath) {
            $evidenceStorage->delete($previousFilePath);
        }

        return redirect()->route('data-entry.show', $dataEntry)
            ->with('success', 'Data berhasil diperbarui.');
    }

    public function submit(Request $request, DataEntry $dataEntry): RedirectResponse
    {
        $this->authorizeEntryManagement($request->user(), $dataEntry);
        abort_if(! in_array($dataEntry->status, ['draft', 'revision']), 403);

        $dataEntry->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Data berhasil diajukan untuk validasi.');
    }

    public function destroy(
        Request $request,
        DataEntry $dataEntry,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $this->authorizeEntryManagement($request->user(), $dataEntry);
        abort_if($dataEntry->status !== 'draft', 403, 'Hanya data draft yang bisa dihapus.');

        if ($dataEntry->file_bukti) {
            $evidenceStorage->delete($dataEntry->file_bukti);
        }

        $dataEntry->delete();

        return redirect()->route('data-entry.index')
            ->with('success', 'Data berhasil dihapus.');
    }

    private function authorizeUnitView(User $user, Unit $unit): void
    {
        if (
            $user->isSuperAdmin() ||
            $user->isKomiteMutu() ||
            $user->isDireksi() ||
            (in_array($user->role, ['kepala_unit', 'pic_unit'], true) &&
                (int) $user->unit_id === (int) $unit->id)
        ) {
            return;
        }

        abort(404);
    }

    private function canTemporarilyCorrectPeriod(User $user, Unit $unit, int $year, int $month): bool
    {
        $isCurrentYearPeriod = $year === (int) now()->format('Y')
            && $month <= (int) now()->format('n');
        $canManageUnit = $user->isSuperAdmin()
            || $user->isKomiteMutu()
            || (
                in_array($user->role, ['kepala_unit', 'pic_unit'], true)
                && (int) $user->unit_id === (int) $unit->id
            );

        return $unit->kode === 'IGD' && $isCurrentYearPeriod && $canManageUnit;
    }

    private function authorizeUnitManagement(User $user, Unit $unit): void
    {
        if (
            $user->isSuperAdmin() ||
            $user->isKomiteMutu() ||
            (in_array($user->role, ['kepala_unit', 'pic_unit'], true) &&
                (int) $user->unit_id === (int) $unit->id)
        ) {
            return;
        }

        abort(404);
    }

    private function authorizeEntryView(User $user, DataEntry $dataEntry): void
    {
        $unit = Unit::find($dataEntry->unit_id);
        $indicator = Indicator::find($dataEntry->indicator_id);
        abort_if(
            ! $unit ||
                ! $indicator ||
                ($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id),
            404
        );

        $this->authorizeUnitView($user, $unit);
    }

    private function authorizeEntryManagement(User $user, DataEntry $dataEntry): void
    {
        $unit = Unit::find($dataEntry->unit_id);
        $indicator = Indicator::find($dataEntry->indicator_id);
        abort_if(
            ! $unit ||
                ! $indicator ||
                ($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $unit->id),
            404
        );

        $this->authorizeUnitManagement($user, $unit);
    }
}
