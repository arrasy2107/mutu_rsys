<?php

namespace App\Http\Controllers;

use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorDailyRecord;
use App\Models\IndicatorPeriod;
use App\Models\PdsaRecord;
use App\Models\Unit;
use App\Models\Validation;
use App\Services\PrivateEvidenceStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidationController extends Controller
{
    /**
     * Daftar Pengajuan & Riwayat Validasi Data
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isExecutive = $user->isDireksi();
        $isQualityCommittee = $user->isKomiteMutu();
        $isAdmin = $user->isSuperAdmin();
        $isUnitStaff = in_array($user->role, ['kepala_unit', 'pic_unit']);

        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $category = $request->input('category', 'history');
        $search = $request->input('search');

        abort_if(
            ! $isAdmin && ! $isQualityCommittee && ! $isExecutive && ! $user->unit_id,
            403,
            'Akun Anda belum ditautkan ke unit kerja.'
        );

        // Scoping Unit
        if ($isAdmin || $isQualityCommittee || $isExecutive) {
            $unitId = $request->input('unit_id');
        } else {
            $unitId = $user->unit_id;
        }

        $historyQuery = Validation::query()
            ->whereHas('dataEntry', function ($query) use ($unitId, $tahun, $bulan, $request, $search) {
                $query->when($unitId, fn ($entryQuery) => $entryQuery->where('unit_id', $unitId))
                    ->when($tahun, fn ($entryQuery) => $entryQuery->whereHas(
                        'period',
                        fn ($periodQuery) => $periodQuery->where('tahun', (int) $tahun)
                    ))
                    ->when($bulan, fn ($entryQuery) => $entryQuery->whereHas(
                        'period',
                        fn ($periodQuery) => $periodQuery->where('bulan', (int) $bulan)
                    ))
                    ->when($request->jenis, fn ($entryQuery) => $entryQuery->whereHas(
                        'indicator',
                        fn ($indicatorQuery) => $indicatorQuery->whereJsonContains('jenis', $request->jenis)
                    ))
                    ->when($search, fn ($entryQuery) => $entryQuery->whereHas(
                        'indicator',
                        fn ($indicatorQuery) => $indicatorQuery
                            ->where('judul', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%")
                    ));
            });

        $validQuery = fn () => (clone $historyQuery)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->where('is_valid', true)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->whereNull('is_valid')
                            ->where('tingkat_kesesuaian', '>=', 90);
                    });
            });
        $invalidQuery = fn () => (clone $historyQuery)
            ->where(function ($query) {
                $query->where('status', 'rejected')
                    ->orWhere(function ($approvedQuery) {
                        $approvedQuery->where('status', 'approved')
                            ->where(function ($validityQuery) {
                                $validityQuery->where('is_valid', false)
                                    ->orWhere(function ($legacyQuery) {
                                        $legacyQuery->whereNull('is_valid')
                                            ->where('tingkat_kesesuaian', '<', 90);
                                    });
                            });
                    });
            });

        $avgAgreement = (clone $historyQuery)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('tingkat_kesesuaian')
            ->avg('tingkat_kesesuaian');

        $stats = [
            'valid' => $validQuery()->count(),
            'tidak_valid' => $invalidQuery()->count(),
            'history' => (clone $historyQuery)->count(),
            'avg_agreement' => round((float) ($avgAgreement ?: 0), 1),
        ];

        $entriesQuery = match ($category) {
            'valid' => $validQuery(),
            'tidak_valid' => $invalidQuery(),
            default => clone $historyQuery,
        };

        $entries = $entriesQuery
            ->with([
                'dataEntry.indicator',
                'dataEntry.unit',
                'dataEntry.user',
                'dataEntry.period',
                'dataEntry.latestValidation',
                'validator',
            ])
            ->orderByDesc('tanggal_validasi')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Validation $validation): array {
                $entry = $validation->dataEntry;

                return array_merge($entry->toArray(), [
                    'status' => $validation->status,
                    'validation_event_id' => $validation->id,
                    'is_current_validation_event' => $entry->latestValidation?->id === $validation->id,
                    'latest_validation' => $validation->only([
                        'id',
                        'status',
                        'catatan_validator',
                        'tanggal_validasi',
                        'unit_numerator',
                        'unit_denominator',
                        'unit_capaian',
                        'validator_numerator',
                        'validator_denominator',
                        'validator_capaian',
                        'tingkat_kesesuaian',
                        'is_valid',
                        'validator_id',
                    ]) + ['validator' => $validation->validator?->only(['id', 'name', 'role'])],
                ]);
            });

        // Indikator aktif milik unit untuk modal "+ Ajukan Validasi"
        // Admin & Komite Mutu dapat memilih unit apa saja di modal, staf unit hanya unitnya sendiri
        // Catatan: 'multiplier' adalah accessor (computed), tidak boleh ada di ->get([...]) SELECT list
        $modalIndicators = Indicator::aktif()
            ->when(! $isAdmin && ! $isQualityCommittee && $unitId, fn ($q) => $q->where(
                fn ($indicatorQuery) => $indicatorQuery
                    ->where('unit_id', $unitId)
                    ->orWhereNull('unit_id')
            ))
            ->orderBy('kode')
            ->get(['id', 'kode', 'judul', 'jenis', 'unit_id', 'target_capaian', 'target_operator', 'formula', 'satuan_pengukuran'])
            ->map(fn ($i) => array_merge($i->toArray(), ['multiplier' => $i->multiplier]));

        return Inertia::render('Validation/Index', [
            'entries' => $entries,
            'units' => Unit::aktif()->get(['id', 'kode', 'nama']),
            'years' => IndicatorPeriod::query()
                ->select('tahun')
                ->distinct()
                ->orderByDesc('tahun')
                ->pluck('tahun')
                ->push((int) now()->format('Y'))
                ->unique()
                ->sortDesc()
                ->values(),
            'modalIndicators' => $modalIndicators,
            'stats' => $stats,
            'filters' => [
                'category' => $category,
                'unit_id' => $unitId ? (int) $unitId : null,
                'tahun' => $tahun ? (int) $tahun : null,
                'bulan' => $bulan ? (int) $bulan : null,
                'jenis' => $request->jenis,
                'search' => $search,
            ],
            'permissions' => [
                'canValidate' => ($isAdmin || $isUnitStaff) && ! $isExecutive,
                'canEditValidation' => $isAdmin && ! $isExecutive,
                'isExecutive' => $isExecutive,
                'isQualityCommittee' => $isQualityCommittee,
                'isAdmin' => $isAdmin,
                'isUnitStaff' => $isUnitStaff,
            ],
        ]);
    }

    /**
     * Ambil status kesiapan data 12 bulan untuk indikator mutu tertentu (API Modal Ajukan)
     */
    public function getIndicatorPeriods(Request $request)
    {
        $indicatorId = $request->integer('indicator_id');
        $unitId = $request->integer('unit_id');
        $tahun = $request->integer('tahun', 2026);

        if (! $indicatorId || ! $unitId) {
            return response()->json(['error' => 'Parameter indicator_id dan unit_id diperlukan.'], 422);
        }

        $indicator = Indicator::find($indicatorId);
        if (! $indicator) {
            return response()->json(['error' => 'Indikator tidak ditemukan.'], 404);
        }
        abort_if($indicator->unit_id !== null && (int) $indicator->unit_id !== $unitId, 404);
        abort_if(
            ! $request->user()->isSuperAdmin()
                && $request->user()->unit_id !== $unitId,
            404
        );

        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Ambil semua data entry indikator ini di tahun terpilih
        $entries = DataEntry::where('indicator_id', $indicatorId)
            ->where('unit_id', $unitId)
            ->whereHas('period', fn ($p) => $p->where('tahun', $tahun))
            ->with(['period', 'latestValidation'])
            ->get()
            ->keyBy(fn ($e) => $e->period?->bulan);

        $annualStatus = Validation::getIndicatorValidationStatus($unitId, $indicatorId, $tahun, 1);
        $passedMonth = $annualStatus['passed_month'];
        $pendingMonth = $entries->first(fn (DataEntry $entry): bool => $entry->status === 'submitted')?->period?->bulan;

        $months = [];
        $recommendedMonth = null;

        for ($m = 1; $m <= 12; $m++) {
            $entry = $entries->get($m);
            $hasData = $entry && ($entry->denominator > 0 || ! empty($entry->log_harian));
            $num = $entry ? (float) $entry->numerator : 0;
            $den = $entry ? (float) $entry->denominator : 0;
            $cap = $entry ? (float) $entry->capaian : 0;
            $status = $entry ? $entry->status : 'empty';

            $isExempt = $annualStatus['is_exempt'];
            $isValidated = $entry && $entry->status === 'approved'
                && ($entry->latestValidation?->is_valid === true
                    || ($entry->latestValidation?->is_valid === null
                        && (float) $entry->latestValidation?->tingkat_kesesuaian >= 90));
            $isRejected = $entry && ($entry->status === 'rejected'
                || ($entry->status === 'approved' && $entry->latestValidation?->is_valid === false));
            $isSubmitted = ($entry && $entry->status === 'submitted');
            $isMandatoryRetry = $annualStatus['is_mandatory_retry'];

            $canSubmit = $hasData
                && ! $isExempt
                && $pendingMonth === null
                && ! $isSubmitted
                && (! $entry || in_array($entry->status, ['draft', 'revision', 'rejected', 'exempt'], true));

            if ($canSubmit && $recommendedMonth === null) {
                $recommendedMonth = $m;
            }

            $months[] = [
                'bulan' => $m,
                'bulan_name' => $bulanNames[$m],
                'has_data' => $hasData,
                'numerator' => $num,
                'denominator' => $den,
                'capaian' => $cap,
                'status' => $status,
                'is_exempt' => $isExempt,
                'is_validated' => $isValidated,
                'is_rejected' => $isRejected,
                'is_submitted' => $isSubmitted,
                'is_mandatory_retry' => $isMandatoryRetry,
                'can_submit' => $canSubmit,
                'data_entry_id' => $entry?->id,
            ];
        }

        return response()->json([
            'indicator' => [
                'id' => $indicator->id,
                'kode' => $indicator->kode,
                'judul' => $indicator->judul,
                'jenis' => $indicator->jenis,
                'target_capaian' => $indicator->target_capaian,
                'target_operator' => $indicator->target_operator,
                'multiplier' => $indicator->multiplier,
                'satuan_pengukuran' => $indicator->satuan_pengukuran,
            ],
            'months' => $months,
            'passed_month' => $passedMonth,
            'passed_month_name' => $passedMonth ? $bulanNames[$passedMonth] : null,
            'pending_month' => $pendingMonth ? (int) $pendingMonth : null,
            'is_exempt' => $annualStatus['is_exempt'],
            'is_mandatory_retry' => $annualStatus['is_mandatory_retry'],
            'recommended_month' => $recommendedMonth ?? 1,
        ]);
    }

    /**
     * Tarik data dari Form Pengumpul Data dan ajukan ke Validasi Data
     */
    public function submitEntry(Request $request)
    {
        $user = $request->user();
        if ($user->isDireksi()) {
            return redirect()->back()->with('error', 'Akses dibatasi: Akun eksekutif hanya memiliki akses peninjauan (Read-Only).');
        }

        $validated = $request->validate([
            'indicator_id' => 'required|exists:indicators,id',
            'unit_id' => 'required|exists:units,id',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer|between:1,12',
        ]);

        $isStaffOfThisUnit = ($user->isKepalaUnit() || $user->isPicUnit()) && ($user->unit_id === (int) $validated['unit_id']);
        if (! $user->isSuperAdmin() && ! $isStaffOfThisUnit) {
            return redirect()->back()->with('error', 'Akses dibatasi: Anda hanya dapat mengajukan validasi untuk unit kerja Anda sendiri.');
        }

        return DB::transaction(function () use ($validated, $user): RedirectResponse {
            $indicator = Indicator::whereKey($validated['indicator_id'])->lockForUpdate()->firstOrFail();
            abort_if($indicator->unit_id !== null && (int) $indicator->unit_id !== (int) $validated['unit_id'], 404);

            $annualStatus = Validation::getIndicatorValidationStatus(
                (int) $validated['unit_id'],
                (int) $validated['indicator_id'],
                (int) $validated['tahun'],
                (int) $validated['bulan'],
            );
            if ($annualStatus['is_exempt']) {
                return redirect()->back()->with('error', $annualStatus['message']);
            }

            $pendingEntryExists = DataEntry::where('indicator_id', $indicator->id)
                ->where('unit_id', $validated['unit_id'])
                ->where('status', 'submitted')
                ->whereHas('period', fn ($query) => $query->where('tahun', $validated['tahun']))
                ->exists();
            if ($pendingEntryExists) {
                return redirect()->back()->with('error', 'Masih ada pengajuan validasi profil ini yang menunggu keputusan.');
            }

            $period = IndicatorPeriod::firstOrCreate(
                [
                    'indicator_id' => $indicator->id,
                    'unit_id' => $validated['unit_id'],
                    'tahun' => $validated['tahun'],
                    'bulan' => $validated['bulan'],
                ],
                ['status' => 'open']
            );

            $entry = DataEntry::where([
                'indicator_id' => $indicator->id,
                'period_id' => $period->id,
                'unit_id' => $validated['unit_id'],
            ])->lockForUpdate()->first();

            if (! $entry || (float) $entry->denominator <= 0 || ! in_array($entry->status, ['draft', 'revision', 'rejected', 'exempt'], true)) {
                return redirect()->back()->with(
                    'error',
                    'Pilih bulan yang memiliki data siap dan belum pernah diputuskan valid atau tidak valid.'
                );
            }

            $dailyRecords = IndicatorDailyRecord::where([
                'indicator_id' => $indicator->id,
                'unit_id' => $validated['unit_id'],
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
            ])->orderBy('hari')->get();
            $now = now();
            $snapshotData = [
                'submitted_at' => $now->toIso8601String(),
                'submitted_by' => $user->name,
                'log_harian' => $entry->log_harian,
                'daily_records' => $dailyRecords->map(fn ($record) => [
                    'hari' => $record->hari,
                    'tanggal' => $record->tanggal?->format('Y-m-d'),
                    'data' => $record->data,
                ])->values()->all(),
            ];

            $entry->update([
                'status' => 'submitted',
                'submitted_at' => $now,
            ]);
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

            $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $bulanName = $bulanNames[$validated['bulan']] ?? "Bulan {$validated['bulan']}";

            return redirect()->route('validation.show', $entry->id)->with(
                'success',
                "Data indikator [{$indicator->kode}] periode {$bulanName} {$validated['tahun']} berhasil diajukan untuk validasi."
            );
        });
    }

    /**
     * Lembar Telaah & Detail Validasi Data
     */
    public function show(Request $request, DataEntry $dataEntry): Response
    {
        $user = $request->user();
        $isExecutive = $user->isDireksi();
        $isAdmin = $user->isSuperAdmin();
        $isQualityCommittee = $user->isKomiteMutu();
        $isUnitStaff = in_array($user->role, ['kepala_unit', 'pic_unit']);

        // Check if unit staff attempts to view other unit's entry
        if ($isUnitStaff && $user->unit_id !== $dataEntry->unit_id) {
            abort(403, 'Akses dibatasi untuk unit Anda sendiri.');
        }

        $dataEntry->load([
            'indicator.formFields',
            'unit',
            'user',
            'period',
            'validations.validator',
            'validations.actor',
        ]);

        $tahun = $dataEntry->period?->tahun ?? 2026;
        $bulan = $dataEntry->period?->bulan ?? 1;

        // Riwayat memilih event validasi tertentu; direct links still default to the latest event.
        $latestValidationId = Validation::where('data_entry_id', $dataEntry->id)->latest('id')->value('id');
        $validationQuery = Validation::with(['validator', 'actor'])->where('data_entry_id', $dataEntry->id);
        $validationId = $request->integer('validation_id');
        if ($validationId) {
            $validationQuery->whereKey($validationId);
        }
        $validation = $validationQuery->latest('id')->first();
        abort_if($validationId && ! $validation, 404);
        $validation ??= new Validation;
        $isCurrentValidationEvent = ! $validationId || (int) $validationId === (int) $latestValidationId;

        if ($validation->exists) {
            $dataEntry->setRelation('latestValidation', $validation);
            if (! $isCurrentValidationEvent) {
                $dataEntry->setAttribute('status', $validation->status);
            }
        }

        if (! $validation->exists) {
            $validation->unit_numerator = (float) $dataEntry->numerator;
            $validation->unit_denominator = (float) $dataEntry->denominator;
            $validation->unit_capaian = (float) $dataEntry->capaian;
            $validation->validator_numerator = (float) $dataEntry->numerator;
            $validation->validator_denominator = (float) $dataEntry->denominator;
            $validation->validator_capaian = (float) $dataEntry->capaian;
            $validation->tingkat_kesesuaian = 100.0;
            $validation->is_valid = true;
            $validation->status = $dataEntry->status === 'approved' ? 'approved' : 'submitted';
        }

        // Load daily records if exists
        $dailyRecords = IndicatorDailyRecord::where('indicator_id', $dataEntry->indicator_id)
            ->where('unit_id', $dataEntry->unit_id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->orderBy('hari')
            ->get();

        // Jika unit_data_snapshot kosong, bentuk dari dailyRecords
        if (empty($validation->unit_data_snapshot) && $dailyRecords->isNotEmpty()) {
            $validation->unit_data_snapshot = [
                'log_harian' => $dataEntry->log_harian,
                'daily_records' => $dailyRecords->map(fn ($r) => [
                    'hari' => $r->hari,
                    'tanggal' => $r->tanggal?->format('Y-m-d'),
                    'data' => $r->data,
                ])->values()->all(),
            ];
        }

        // Data historis
        $historical = DataEntry::where('indicator_id', $dataEntry->indicator_id)
            ->where('unit_id', $dataEntry->unit_id)
            ->where('status', 'approved')
            ->where('id', '!=', $dataEntry->id)
            ->with(['period', 'latestValidation'])
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // Status kepatuhan validasi tahunan indikator ini
        $indicatorValidationStatus = Validation::getIndicatorValidationStatus(
            (int) $dataEntry->unit_id,
            (int) $dataEntry->indicator_id,
            (int) $tahun,
            (int) $bulan
        );

        $isStaffOfThisUnit = ($user->isKepalaUnit() || $user->isPicUnit()) && ($user->unit_id === $dataEntry->unit_id);

        return Inertia::render('Validation/Show', [
            'entry' => $dataEntry,
            'validation' => $validation,
            'dailyRecords' => $dailyRecords,
            'historical' => $historical,
            'indicatorValidationStatus' => $indicatorValidationStatus,
            'permissions' => [
                'canValidate' => $isCurrentValidationEvent && ($isAdmin || $isStaffOfThisUnit) && ! $isExecutive,
                'canEditValidation' => $isCurrentValidationEvent && $isAdmin && ! $isExecutive,
                'isExecutive' => $isExecutive,
                'isQualityCommittee' => $isQualityCommittee,
                'isAdmin' => $isAdmin,
                'isUnitStaff' => $isUnitStaff,
                'isStaffOfThisUnit' => $isStaffOfThisUnit,
            ],
        ]);
    }

    /**
     * Tetapkan hasil validasi oleh internal unit.
     */
    public function approve(
        Request $request,
        DataEntry $dataEntry,
        PrivateEvidenceStorage $evidenceStorage,
    ) {
        $user = $request->user();
        $isExecutive = $user->isDireksi();
        $isAdmin = $user->isSuperAdmin();
        $isStaffOfThisUnit = ($user->isKepalaUnit() || $user->isPicUnit()) && ($user->unit_id === $dataEntry->unit_id);

        if ($isExecutive) {
            abort(403, 'Akses ditolak: Akun eksekutif hanya memiliki akses pemantauan (Read-Only).');
        }

        $isEditingApproved = ($dataEntry->status === 'approved');

        // Reopening an already decided result is reserved for an administrator.
        if ($isEditingApproved) {
            if (! $isAdmin) {
                abort(403, 'Akses dibatasi: Hanya Administrator yang dapat mengubah hasil validasi yang telah ditetapkan.');
            }
        } else {
            if (! $isAdmin && ! $isStaffOfThisUnit) {
                abort(403, 'Akses dibatasi: Penetapan validitas hanya dapat dilakukan oleh PIC atau Kepala Unit terkait.');
            }
        }

        $validated = $request->validate([
            'catatan' => 'nullable|string|max:1000',
            'validator_num' => 'required|numeric|min:0',
            'validator_den' => 'required|numeric|min:0',
            'jumlah_sampel_validasi' => 'nullable|integer|min:1',
            'justifikasi_perlu_validasi' => 'nullable|string|max:255',
            'metode_validasi' => 'nullable|string|max:255',
            'hasil_validasi' => 'nullable|string|max:255',
            'hasil_analisa' => 'nullable|string',
            'kesimpulan' => 'nullable|string',
            'rencana_tindak_lanjut' => 'nullable|string',
            'lampiran_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('lampiran_file')) {
            $filePath = $evidenceStorage->store($request->file('lampiran_file'), 'validations');
        }

        // Kalkulasi capaian validator
        $validatorNum = (float) $validated['validator_num'];
        $validatorDen = (float) $validated['validator_den'];
        $multiplier = (float) ($dataEntry->indicator?->multiplier ?? 100);
        $validatorCap = $validatorDen > 0 ? round(($validatorNum / $validatorDen) * $multiplier, 2) : 0;

        // Hitung kesesuaian data unit vs validator
        $unitCap = (float) $dataEntry->capaian;
        $tingkatKesesuaian = 100.0;
        if ($unitCap > 0 || $validatorCap > 0) {
            $minVal = min($unitCap, $validatorCap);
            $maxVal = max($unitCap, $validatorCap);
            $tingkatKesesuaian = $maxVal > 0 ? round(($minVal / $maxVal) * 100, 2) : 100.0;
        }

        // Standar akreditasi RS (KARS): Kesesuaian minimal 90%
        $isValid = $tingkatKesesuaian >= 90.0;

        return DB::transaction(function () use (
            $dataEntry,
            $user,
            $validated,
            $filePath,
            $validatorNum,
            $validatorDen,
            $validatorCap,
            $tingkatKesesuaian,
            $isValid,
            $unitCap,
        ) {
            $dataEntry = DataEntry::with(['indicator', 'period'])
                ->lockForUpdate()
                ->findOrFail($dataEntry->id);

            $isEditingApprovedEntry = $dataEntry->status === 'approved';
            $canEditApprovedEntry = $user->isSuperAdmin();
            if ($isEditingApprovedEntry && ! $canEditApprovedEntry) {
                abort(403, 'Akses dibatasi: Hanya Administrator yang dapat mengubah hasil validasi yang telah ditetapkan.');
            }
            if (! $isEditingApprovedEntry && $dataEntry->status !== 'submitted') {
                return redirect()->back()->with(
                    'error',
                    'Data tidak lagi menunggu validasi. Muat ulang halaman untuk melihat status terbaru.'
                );
            }

            $dataEntry->update(['status' => 'approved']);
            if ($dataEntry->period) {
                $dataEntry->period->update([
                    'status' => 'validated',
                    'tanggal_validasi' => now(),
                    'validated_by' => $user->id,
                ]);
            }

            $existingVal = Validation::where('data_entry_id', $dataEntry->id)
                ->latest('id')
                ->first();
            $validation = new Validation;
            $validation->fill([
                'validator_id' => $user->id,
                'status' => 'approved',
                'catatan_validator' => $validated['catatan'] ?? null,
                'tanggal_validasi' => now(),
                'unit_numerator' => (float) ($existingVal?->unit_numerator ?? $dataEntry->numerator),
                'unit_denominator' => (float) ($existingVal?->unit_denominator ?? $dataEntry->denominator),
                'unit_capaian' => (float) ($existingVal?->unit_capaian ?? $dataEntry->capaian),
                'unit_data_snapshot' => $existingVal?->unit_data_snapshot,
                'validator_numerator' => $validatorNum,
                'validator_denominator' => $validatorDen,
                'validator_capaian' => $validatorCap,
                'tingkat_kesesuaian' => $tingkatKesesuaian,
                'is_valid' => $isValid,
                'jumlah_sampel_validasi' => $validated['jumlah_sampel_validasi'] ?? (int) $validatorDen,
                'justifikasi_perlu_validasi' => $validated['justifikasi_perlu_validasi'] ?? 'Wajib validasi awal tahun (Imut Prioritas Unit)',
                'metode_validasi' => $validated['metode_validasi'] ?? 'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil',
                'hasil_validasi' => $validated['hasil_validasi'] ?? "{$validatorCap}% (".number_format($validatorNum, 0, ',', '.').'/'.number_format($validatorDen, 0, ',', '.').' x 100%)',
                'hasil_analisa' => $validated['hasil_analisa'] ?? (min($unitCap, $validatorCap).' / '.max($unitCap, $validatorCap)." x 100% = {$tingkatKesesuaian}% ----> 90% (standar ".($isValid ? 'terpenuhi' : 'tidak terpenuhi').')'),
                'kesimpulan' => $validated['kesimpulan'] ?? "Data bulan {$dataEntry->period?->bulan} {$dataEntry->period?->tahun} ".($isValid ? 'valid' : 'tidak valid'),
                'rencana_tindak_lanjut' => $validated['rencana_tindak_lanjut'] ?? ($isValid ? 'Lanjutkan pengukuran indikator mutu sesuai profil indikator' : 'Samakan persepsi dan pilih bulan yang datanya siap untuk validasi ulang pada tahun yang sama'),
                'lampiran_file' => $filePath ?? $existingVal?->lampiran_file,
            ]);
            $validation->data_entry_id = $dataEntry->id;
            $validation->save();

            if ($dataEntry->period) {
                $annualValidationStatus = Validation::getIndicatorValidationStatus(
                    (int) $dataEntry->unit_id,
                    (int) $dataEntry->indicator_id,
                    (int) $dataEntry->period->tahun,
                    (int) $dataEntry->period->bulan,
                );
                DataEntry::where('indicator_id', $dataEntry->indicator_id)
                    ->where('unit_id', $dataEntry->unit_id)
                    ->where('status', $annualValidationStatus['is_exempt'] ? 'draft' : 'exempt')
                    ->when($annualValidationStatus['is_exempt'], fn ($query) => $query->where('denominator', '>', 0))
                    ->whereHas('period', fn ($query) => $query->where('tahun', $dataEntry->period->tahun))
                    ->update(['status' => $annualValidationStatus['is_exempt'] ? 'exempt' : 'draft']);
            }

            if ($dataEntry->indicator && ! $dataEntry->indicator->isTargetMet((float) $dataEntry->capaian)) {
                $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $bulanLabel = $bulanNames[$dataEntry->period?->bulan ?? 1] ?? '';
                $tahunLabel = $dataEntry->period?->tahun ?? 2026;

                PdsaRecord::firstOrCreate([
                    'indicator_id' => $dataEntry->indicator_id,
                    'period_id' => $dataEntry->period_id,
                    'unit_id' => $dataEntry->unit_id,
                ], [
                    'user_id' => $dataEntry->user_id,
                    'capaian_aktual' => $dataEntry->capaian,
                    'target_capaian' => $dataEntry->indicator->target_capaian,
                    'masalah' => "Capaian indikator [{$dataEntry->indicator->kode}] pada bulan {$bulanLabel} {$tahunLabel} tercatat {$dataEntry->capaian}%, belum mencapai target ({$dataEntry->indicator->target_operator} {$dataEntry->indicator->target_capaian}%). Diperlukan siklus perbaikan PDSA.",
                    'status' => 'draft',
                ]);
            }

            $successMsg = $isEditingApprovedEntry
                ? "Data validasi indikator [{$dataEntry->indicator?->kode}] berhasil diperbarui oleh Administrator. Kesesuaian: {$tingkatKesesuaian}%."
                : "Data indikator [{$dataEntry->indicator?->kode}] berhasil divalidasi dan disahkan. Kesesuaian: {$tingkatKesesuaian}%.";

            return redirect()->route('validation.show', $dataEntry->id)->with('success', $successMsg);
        });
    }

    /**
     * Reject a submission administratively.
     */
    public function reject(Request $request, DataEntry $dataEntry)
    {
        $user = $request->user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Akses dibatasi: hanya Administrator yang dapat menolak pengajuan.');
        }

        $validated = $request->validate([
            'catatan' => 'required|string|max:1000',
            'validator_num' => 'nullable|numeric|min:0',
            'validator_den' => 'nullable|numeric|min:0',
        ]);

        $validatorNum = isset($validated['validator_num']) ? (float) $validated['validator_num'] : null;
        $validatorDen = isset($validated['validator_den']) ? (float) $validated['validator_den'] : null;
        $validatorCap = null;
        $tingkatKesesuaian = null;

        if ($validatorNum !== null && $validatorDen !== null && $validatorDen > 0) {
            $multiplier = (float) ($dataEntry->indicator?->multiplier ?? 100);
            $validatorCap = round(($validatorNum / $validatorDen) * $multiplier, 2);
            $unitCap = (float) $dataEntry->capaian;
            $minVal = min($unitCap, $validatorCap);
            $maxVal = max($unitCap, $validatorCap);
            $tingkatKesesuaian = $maxVal > 0 ? round(($minVal / $maxVal) * 100, 2) : 0;
        }

        return DB::transaction(function () use ($dataEntry, $user, $validated, $validatorNum, $validatorDen, $validatorCap, $tingkatKesesuaian) {
            $dataEntry = DataEntry::with(['indicator', 'period'])
                ->lockForUpdate()
                ->findOrFail($dataEntry->id);
            if ($dataEntry->status !== 'submitted') {
                return redirect()->back()->with(
                    'error',
                    'Data tidak lagi menunggu validasi. Muat ulang halaman untuk melihat status terbaru.'
                );
            }
            $dataEntry->update(['status' => 'rejected']);

            if ($dataEntry->period) {
                $dataEntry->period->update(['status' => 'open']);
            }

            $existingVal = Validation::where('data_entry_id', $dataEntry->id)
                ->latest('id')
                ->first();
            $validation = new Validation;
            $validation->fill([
                'validator_id' => $user->id,
                'status' => 'rejected',
                'catatan_validator' => $validated['catatan'],
                'tanggal_validasi' => now(),
                'unit_numerator' => (float) ($existingVal?->unit_numerator ?? $dataEntry->numerator),
                'unit_denominator' => (float) ($existingVal?->unit_denominator ?? $dataEntry->denominator),
                'unit_capaian' => (float) ($existingVal?->unit_capaian ?? $dataEntry->capaian),
                'unit_data_snapshot' => $existingVal?->unit_data_snapshot,
                'validator_numerator' => $validatorNum,
                'validator_denominator' => $validatorDen,
                'validator_capaian' => $validatorCap,
                'tingkat_kesesuaian' => $tingkatKesesuaian,
                'is_valid' => false,
                'kesimpulan' => 'Pengajuan ditolak oleh Administrator. Unit dapat mengajukan ulang menggunakan bulan yang datanya siap.',
            ]);
            $validation->data_entry_id = $dataEntry->id;
            $validation->save();

            return redirect()->route('validation.show', $dataEntry->id)->with('success', "Data pengajuan indikator [{$dataEntry->indicator?->kode}] ditolak. Unit dapat mengajukan ulang menggunakan bulan yang datanya siap.");
        });
    }

    /**
     * Return a submission for revision administratively.
     */
    public function revision(Request $request, DataEntry $dataEntry)
    {
        $user = $request->user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Akses dibatasi: hanya Administrator yang dapat meminta revisi.');
        }

        $validated = $request->validate([
            'catatan' => 'required|string|max:1000',
        ]);

        return DB::transaction(function () use ($dataEntry, $user, $validated) {
            $dataEntry = DataEntry::with('period')
                ->lockForUpdate()
                ->findOrFail($dataEntry->id);
            if ($dataEntry->status !== 'submitted') {
                return redirect()->back()->with(
                    'error',
                    'Data tidak lagi menunggu validasi. Muat ulang halaman untuk melihat status terbaru.'
                );
            }
            $dataEntry->update(['status' => 'revision']);

            if ($dataEntry->period) {
                $dataEntry->period->update(['status' => 'open']);
            }

            Validation::create([
                'data_entry_id' => $dataEntry->id,
                'validator_id' => $user->id,
                'status' => 'revision',
                'catatan_validator' => $validated['catatan'],
                'tanggal_validasi' => now(),
            ]);

            return redirect()->route('validation.show', $dataEntry->id)->with('success', "Data pengajuan indikator [{$dataEntry->indicator?->kode}] dikembalikan ke unit untuk direvisi.");
        });
    }

    /**
     * Ekspor Lembar Validasi Data Indikator Tunggal ke Excel (.xlsx)
     * Format diselaraskan persis dengan sampel 'Validasi.xlsx' Unit IRJ
     */
    public function exportExcel(DataEntry $dataEntry)
    {
        $dataEntry->load([
            'indicator',
            'unit',
            'period',
            'latestValidation.validator',
        ]);

        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulanInt = $dataEntry->period?->bulan ?? 1;
        $tahun = $dataEntry->period?->tahun ?? 2026;
        $bulanName = $bulanNames[$bulanInt];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($dataEntry->indicator?->kode.' Validasi', 0, 31));

        // Header Laporan
        $sheet->setCellValue('A1', 'LAPORAN VALIDASI DATA');
        $sheet->setCellValue('A2', "Bulan  {$bulanName} {$tahun}");
        $sheet->setCellValue('A3', "Unit Kerja: {$dataEntry->unit?->nama}");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->setBold(true);

        $v = $dataEntry->latestValidation;

        $valCap = $v?->validator_capaian ?? $dataEntry->capaian;
        $valNum = $v?->validator_numerator ?? $dataEntry->numerator;
        $valDen = $v?->validator_denominator ?? $dataEntry->denominator;
        $agreeRate = $v?->tingkat_kesesuaian ?? 100;
        $minCap = min($dataEntry->capaian, $valCap);
        $maxCap = max($dataEntry->capaian, $valCap);

        $rows = [
            ['Judul Indikator', $dataEntry->indicator?->judul],
            ['Numerator', $dataEntry->indicator?->numerator_keterangan ?: 'Sesuai dengan keterangan numerator pada profil indikator mutu'],
            ['Denominator', $dataEntry->indicator?->denominator_keterangan ?: 'Sesuai dengan keterangan denominator pada profil indikator mutu'],
            ['Sumber Data', $dataEntry->indicator?->sumber_data ?: 'Sesuai dengan sumber data pada profil indikator mutu'],
            ['Capaian Indikator', "{$dataEntry->capaian}%    (".number_format($dataEntry->numerator, 0, ',', '.').'/'.number_format($dataEntry->denominator, 0, ',', '.').' x 100%)'],
            ['Jumlah Sampel Validasi', $v?->jumlah_sampel_validasi ?: number_format($valDen, 0, ',', '.')],
            ['Justifikasi perlu validasi', $v?->justifikasi_perlu_validasi ?: 'Wajib validasi awal tahun (Imut Prioritas Unit)'],
            ['Metode Validasi', $v?->metode_validasi ?: 'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil'],
            ['Hasil Validasi', $v?->hasil_validasi ?: "{$valCap}%    (".number_format($valNum, 0, ',', '.').'/'.number_format($valDen, 0, ',', '.').' x 100%)'],
            ['Hasil Analisa', $v?->hasil_analisa ?: "{$minCap} / {$maxCap} x 100% = {$agreeRate}% ----> 90% (standar)"],
            ['Kesimpulan', $v?->kesimpulan ?: "Data bulan {$bulanName} {$tahun} valid"],
            ['Rencana Tindak Lanjut', $v?->rencana_tindak_lanjut ?: 'Lanjutkan pengukuran indicator mutu sesuai profil indikator'],
        ];

        $startRow = 5;
        foreach ($rows as $idx => $r) {
            $currRow = $startRow + $idx;
            $sheet->setCellValue("A{$currRow}", $r[0]);
            $sheet->setCellValue("B{$currRow}", $r[1]);

            // Formatting
            $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$currRow}:B{$currRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        }

        // Catatan Bawah Lampiran Sesuai Panduan
        $noteRow = $startRow + count($rows);
        $sheet->setCellValue("A{$noteRow}", 'Lampirkan data imut yg divalidasi dan validasinya.');
        $sheet->mergeCells("A{$noteRow}:B{$noteRow}");
        $sheet->getStyle("A{$noteRow}")->getFont()->setItalic(true)->setSize(9)->setColor(new Color('FF64748B'));

        // Tanda Tangan
        $signRow = $noteRow + 2;
        $sheet->setCellValue("A{$signRow}", "Mengetahui,\nKepala {$dataEntry->unit?->nama}");
        $sheet->setCellValue("B{$signRow}", "Validator,\nKomite Mutu & Keselamatan RS");

        $sheet->getStyle("A{$signRow}:B{$signRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$signRow}:B{$signRow}")->getAlignment()->setWrapText(true);

        $nameRow = $signRow + 4;
        $validatorName = $v?->validator?->nama_lengkap ?: ($v?->validator?->name ?: 'Tim Validator Mutu');
        $sheet->setCellValue("A{$nameRow}", '( ................................................ )');
        $sheet->setCellValue("B{$nameRow}", "( {$validatorName} )");

        // Column Auto-width
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(75);
        $sheet->getStyle('B5:B'.($startRow + count($rows) - 1))->getAlignment()->setWrapText(true);

        $filename = "Laporan_Validasi_{$dataEntry->indicator?->kode}_{$bulanName}_{$tahun}.xlsx";

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
     * Ekspor Laporan Validasi Data Unit ke Excel dengan filter spesifik
     * Mendukung pilihan indikator tertentu, status validasi, bulan spesifik atau seluruh tahun
     */
    public function exportReport(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isSuperAdmin();
        $isQualityCommittee = $user->isKomiteMutu();
        $isExecutive = $user->isDireksi();

        $unitId = $request->integer('unit_id');
        // Jika staf unit, kunci ke unit miliknya sendiri
        if (! $isAdmin && ! $isQualityCommittee && ! $isExecutive) {
            $unitId = $user->unit_id;
        }

        if (! $unitId) {
            abort(400, 'Unit kerja harus dipilih untuk mengekspor laporan.');
        }

        $unit = Unit::findOrFail($unitId);
        $tahun = $request->integer('tahun', 2026);
        $bulanRaw = $request->input('bulan');
        $statusFilter = $request->input('status_filter', 'all');
        $indicatorIdsRaw = $request->input('indicator_ids');

        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Query Dasar
        $query = DataEntry::where('data_entries.unit_id', $unit->id)
            ->with(['indicator', 'period', 'latestValidation.validator']);

        // Filter Tahun & Bulan
        if ($bulanRaw && is_numeric($bulanRaw) && (int) $bulanRaw >= 1 && (int) $bulanRaw <= 12) {
            $bulanInt = (int) $bulanRaw;
            $bulanName = $bulanNames[$bulanInt];
            $query->whereHas('period', fn ($p) => $p->where('tahun', $tahun)->where('bulan', $bulanInt));
            $periodLabel = "Bulan {$bulanName} {$tahun}";
            $filenamePeriode = "{$bulanName}_{$tahun}";
        } else {
            $query->whereHas('period', fn ($p) => $p->where('tahun', $tahun));
            $periodLabel = "Tahun {$tahun} (Seluruh Periode)";
            $filenamePeriode = "Tahun_{$tahun}";
        }

        // Filter Spesifik Indikator Mutu
        if ($indicatorIdsRaw) {
            $indicatorIds = is_array($indicatorIdsRaw)
                ? array_filter(array_map('intval', $indicatorIdsRaw))
                : array_filter(array_map('intval', explode(',', (string) $indicatorIdsRaw)));

            if (! empty($indicatorIds)) {
                $query->whereIn('data_entries.indicator_id', $indicatorIds);
            }
        }

        // Filter Status Validasi
        if ($statusFilter === 'valid_only') {
            $query->where('data_entries.status', 'approved')
                ->whereHas('latestValidation', fn ($v) => $v->where('is_valid', true)->orWhere('tingkat_kesesuaian', '>=', 90));
        } elseif ($statusFilter === 'invalid_only') {
            $query->where(function ($q) {
                $q->where('data_entries.status', 'rejected')
                    ->orWhere(function ($sub) {
                        $sub->where('data_entries.status', '!=', 'submitted')
                            ->whereHas('latestValidation', fn ($val) => $val->where('is_valid', false)->orWhere('tingkat_kesesuaian', '<', 90));
                    });
            });
        } elseif ($statusFilter === 'approved_only') {
            $query->where('data_entries.status', 'approved');
        } elseif ($statusFilter === 'pending_only') {
            $query->where('data_entries.status', 'submitted');
        }

        $entries = $query->join('indicator_periods', 'data_entries.period_id', '=', 'indicator_periods.id')
            ->orderBy('indicator_periods.bulan')
            ->orderBy('data_entries.indicator_id')
            ->select('data_entries.*')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr("Validasi {$unit->kode}", 0, 31));

        // Header Laporan
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI VALIDASI DATA MUTU');
        $sheet->setCellValue('A2', "Periode: {$periodLabel}");
        $sheet->setCellValue('A3', "Unit Kerja: {$unit->nama} ({$unit->kode})");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->setBold(true);

        $currRow = 5;

        if ($entries->isEmpty()) {
            $sheet->setCellValue('A5', 'Tidak ada data validasi yang sesuai dengan kriteria filter yang dipilih.');
            $sheet->getStyle('A5')->getFont()->setItalic(true)->setColor(new Color('FF64748B'));
            $sheet->mergeCells('A5:B5');
        } else {
            foreach ($entries as $idx => $dataEntry) {
                $v = $dataEntry->latestValidation;
                $eBulan = $dataEntry->period?->bulan ?? 1;
                $eBulanName = $bulanNames[$eBulan] ?? "Bulan {$eBulan}";
                $eTahun = $dataEntry->period?->tahun ?? $tahun;

                $valCap = $v?->validator_capaian ?? $dataEntry->capaian;
                $valNum = $v?->validator_numerator ?? $dataEntry->numerator;
                $valDen = $v?->validator_denominator ?? $dataEntry->denominator;
                $agreeRate = $v?->tingkat_kesesuaian ?? 100;
                $minCap = min($dataEntry->capaian, $valCap);
                $maxCap = max($dataEntry->capaian, $valCap);

                // Section Header per Indikator
                $sheet->setCellValue("A{$currRow}", '#'.($idx + 1)." INDIKATOR: [{$dataEntry->indicator?->kode}] {$dataEntry->indicator?->judul} — PERIODE: {$eBulanName} {$eTahun}");
                $sheet->mergeCells("A{$currRow}:B{$currRow}");
                $sheet->getStyle("A{$currRow}")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');
                $sheet->getStyle("A{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
                $currRow++;

                $rows = [
                    ['Judul Indikator', $dataEntry->indicator?->judul],
                    ['Numerator', $dataEntry->indicator?->numerator_keterangan ?: 'Sesuai dengan keterangan numerator pada profil indikator mutu'],
                    ['Denominator', $dataEntry->indicator?->denominator_keterangan ?: 'Sesuai dengan keterangan denominator pada profil indikator mutu'],
                    ['Sumber Data', $dataEntry->indicator?->sumber_data ?: 'Data Rekam Medis / EMR Unit'],
                    ['Capaian Indikator', "{$dataEntry->capaian}%    (".number_format($dataEntry->numerator, 0, ',', '.').'/'.number_format($dataEntry->denominator, 0, ',', '.').' x 100%)'],
                    ['Jumlah Sampel Validasi', $v?->jumlah_sampel_validasi ?: number_format($valDen, 0, ',', '.')],
                    ['Justifikasi perlu validasi', $v?->justifikasi_perlu_validasi ?: 'Audit Penjaminan Mutu Berkala'],
                    ['Metode Validasi', $v?->metode_validasi ?: 'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil'],
                    ['Hasil Validasi', $v?->hasil_validasi ?: "{$valCap}%    (".number_format($valNum, 0, ',', '.').'/'.number_format($valDen, 0, ',', '.').' x 100%)'],
                    ['Hasil Analisa', $v?->hasil_analisa ?: "{$minCap} / {$maxCap} x 100% = {$agreeRate}% ----> 90% (standar)"],
                    ['Kesimpulan', $v?->kesimpulan ?: "Data bulan {$eBulanName} {$eTahun} ".($agreeRate >= 90 ? 'valid' : 'tidak valid')],
                    ['Rencana Tindak Lanjut', $v?->rencana_tindak_lanjut ?: 'Lanjutkan pengukuran indicator mutu sesuai profil indikator'],
                ];

                $tableStart = $currRow;
                foreach ($rows as $r) {
                    $sheet->setCellValue("A{$currRow}", $r[0]);
                    $sheet->setCellValue("B{$currRow}", $r[1]);

                    $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$currRow}:B{$currRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    $currRow++;
                }

                // Catatan Lampiran
                $sheet->setCellValue("A{$currRow}", 'Lampirkan data imut yg divalidasi dan validasinya.');
                $sheet->mergeCells("A{$currRow}:B{$currRow}");
                $sheet->getStyle("A{$currRow}")->getFont()->setItalic(true)->setSize(9)->setColor(new Color('FF64748B'));
                $currRow++;

                // Tanda Tangan Resmi
                $signRow = $currRow + 1;
                $sheet->setCellValue("A{$signRow}", "Mengetahui,\nKepala {$unit->nama}");
                $validatorName = $v?->validator?->nama_lengkap ?: ($v?->validator?->name ?: 'Validator Mutu');
                $sheet->setCellValue("B{$signRow}", "Validator,\nKomite Mutu / Pengesah Internal");
                $sheet->getStyle("A{$signRow}:B{$signRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signRow}:B{$signRow}")->getAlignment()->setWrapText(true);

                $nameRow = $signRow + 3;
                $sheet->setCellValue("A{$nameRow}", '( ................................................ )');
                $sheet->setCellValue("B{$nameRow}", "( {$validatorName} )");
                $sheet->getStyle("A{$nameRow}:B{$nameRow}")->getFont()->setBold(true);

                $currRow = $nameRow + 3; // Jarak pemisah antar indikator
            }
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(75);
        $sheet->getStyle("B5:B{$currRow}")->getAlignment()->setWrapText(true);

        $filename = "Laporan_Validasi_{$unit->kode}_{$filenamePeriode}.xlsx";

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
     * Ekspor Laporan Validasi Data Unit ke PDF (Standar Akreditasi Kemenkes / SNARS)
     */
    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isSuperAdmin();
        $isQualityCommittee = $user->isKomiteMutu();
        $isExecutive = $user->isDireksi();

        $unitId = $request->integer('unit_id');
        if (! $isAdmin && ! $isQualityCommittee && ! $isExecutive) {
            $unitId = $user->unit_id;
        }

        if (! $unitId) {
            abort(400, 'Unit kerja harus dipilih untuk mengekspor laporan.');
        }

        $unit = Unit::findOrFail($unitId);
        $tahun = $request->integer('tahun', 2026);
        $bulanRaw = $request->input('bulan');
        $statusFilter = $request->input('status_filter', 'all');
        $indicatorIdsRaw = $request->input('indicator_ids');

        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $query = DataEntry::where('data_entries.unit_id', $unit->id)
            ->with(['indicator', 'period', 'unit', 'user', 'latestValidation.validator']);

        if ($bulanRaw && is_numeric($bulanRaw) && (int) $bulanRaw >= 1 && (int) $bulanRaw <= 12) {
            $bulanInt = (int) $bulanRaw;
            $bulanName = $bulanNames[$bulanInt];
            $query->whereHas('period', fn ($p) => $p->where('tahun', $tahun)->where('bulan', $bulanInt));
            $periodLabel = "Bulan {$bulanName} {$tahun}";
            $filenamePeriode = "{$bulanName}_{$tahun}";
        } else {
            $query->whereHas('period', fn ($p) => $p->where('tahun', $tahun));
            $periodLabel = "Tahun {$tahun} (Seluruh Periode)";
            $filenamePeriode = "Tahun_{$tahun}";
        }

        if ($indicatorIdsRaw) {
            $indicatorIds = is_array($indicatorIdsRaw)
                ? array_filter(array_map('intval', $indicatorIdsRaw))
                : array_filter(array_map('intval', explode(',', (string) $indicatorIdsRaw)));

            if (! empty($indicatorIds)) {
                $query->whereIn('data_entries.indicator_id', $indicatorIds);
            }
        }

        if ($statusFilter === 'valid_only') {
            $query->where('data_entries.status', 'approved')
                ->whereHas('latestValidation', fn ($v) => $v->where('is_valid', true)->orWhere('tingkat_kesesuaian', '>=', 90));
        } elseif ($statusFilter === 'invalid_only') {
            $query->where(function ($q) {
                $q->where('data_entries.status', 'rejected')
                    ->orWhere(function ($sub) {
                        $sub->where('data_entries.status', '!=', 'submitted')
                            ->whereHas('latestValidation', fn ($val) => $val->where('is_valid', false)->orWhere('tingkat_kesesuaian', '<', 90));
                    });
            });
        } elseif ($statusFilter === 'approved_only') {
            $query->where('data_entries.status', 'approved');
        } elseif ($statusFilter === 'pending_only') {
            $query->where('data_entries.status', 'submitted');
        }

        $entries = $query->join('indicator_periods', 'data_entries.period_id', '=', 'indicator_periods.id')
            ->orderBy('indicator_periods.bulan')
            ->orderBy('data_entries.indicator_id')
            ->select('data_entries.*')
            ->get();

        $pdf = Pdf::loadView('reports.laporan-validasi', [
            'unit' => $unit,
            'periodLabel' => $periodLabel,
            'tahun' => $tahun,
            'entries' => $entries,
        ])->setPaper('a4', 'portrait');

        $filename = "Laporan_Validasi_{$unit->kode}_{$filenamePeriode}.pdf";

        if ($request->boolean('preview') || $request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Ekspor Lembar Validasi Tunggal ke PDF
     */
    public function exportSinglePdf(DataEntry $dataEntry, Request $request)
    {
        $dataEntry->load(['indicator', 'unit', 'period', 'user', 'latestValidation.validator']);
        $unit = $dataEntry->unit;

        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulanInt = $dataEntry->period?->bulan ?? 1;
        $tahun = $dataEntry->period?->tahun ?? 2026;
        $bulanName = $bulanNames[$bulanInt] ?? "Bulan {$bulanInt}";
        $periodLabel = "Bulan {$bulanName} {$tahun}";

        $pdf = Pdf::loadView('reports.laporan-validasi', [
            'unit' => $unit,
            'periodLabel' => $periodLabel,
            'tahun' => $tahun,
            'entries' => collect([$dataEntry]),
        ])->setPaper('a4', 'portrait');

        $filename = "Laporan_Validasi_{$dataEntry->indicator?->kode}_{$bulanName}_{$tahun}.pdf";

        if ($request->boolean('preview') || $request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }
}
