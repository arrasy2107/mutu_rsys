<?php

namespace App\Http\Controllers;

use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorSelection;
use App\Models\PdsaRecord;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = $request->integer('tahun', now()->year);
        $bulan = $request->integer('bulan', now()->month);

        $isHospitalLevel = $user->isSuperAdmin() || $user->isKomiteMutu() || $user->isDireksi();

        if ($isHospitalLevel) {
            return $this->hospitalDashboard($request, $user, $tahun, $bulan);
        }

        return $this->unitDashboard($request, $user, $tahun, $bulan);
    }

    /**
     * Dashboard Tingkat Rumah Sakit (Direksi, Komite Mutu, Superadmin)
     */
    protected function hospitalDashboard(Request $request, $user, int $tahun, int $bulan): Response
    {
        // 1. Query Entri Data yang Disetujui (Approved) Se-Rumah Sakit
        $entriesQuery = DataEntry::query()
            ->with(['indicator', 'unit'])
            ->whereIn('status', ['approved', 'exempt']);

        $entriesBulanIni = (clone $entriesQuery)
            ->whereHas('period', fn($q) => $q->where('tahun', $tahun)->where('bulan', $bulan))
            ->get();

        $totalIndikator = Indicator::aktif()->count();
        $rataRataCapaian = round($entriesBulanIni->avg('capaian') ?? 0, 1);

        // 2. Status Capaian Indikator (Hijau / Kuning / Merah)
        $statusCount = ['green' => 0, 'yellow' => 0, 'red' => 0];
        foreach ($entriesBulanIni as $entry) {
            if ($entry->indicator) {
                $color = $entry->indicator->getStatusColor((float) $entry->capaian);
                $statusCount[$color]++;
            }
        }

        // Pie Diagram 1: Distribusi Capaian Target RS
        $pieStatusCapaian = [
            'series' => [$statusCount['green'], $statusCount['yellow'], $statusCount['red']],
            'labels' => ['Tercapai (Hijau)', 'Hampir Target (Kuning)', 'Di Bawah Target (Merah)'],
            'colors' => ['#10B981', '#F59E0B', '#F43F5E'],
        ];

        // 3. Kepatuhan Pengumpulan Data Unit Bulan Berjalan
        $activeUnits = Unit::aktif()->orderBy('nama')->get();
        $unitSubmissions = [];
        $unitLengkap = 0;
        $unitExempt = 0;
        $unitPending = 0;
        $unitBelum = 0;

        foreach ($activeUnits as $u) {
            $unitEntries = DataEntry::where('unit_id', $u->id)
                ->whereHas('period', fn($q) => $q->where('tahun', $tahun)->where('bulan', $bulan))
                ->get();

            $appr = $unitEntries->where('status', 'approved')->count();
            $exempt = $unitEntries->where('status', 'exempt')->count();
            $subm = $unitEntries->where('status', 'submitted')->count();
            $totalEntries = $unitEntries->count();

            if ($totalEntries === 0) {
                $status = 'belum';
                $unitBelum++;
            } elseif ($subm > 0 && $appr === 0 && $exempt === 0) {
                $status = 'pending';
                $unitPending++;
            } elseif ($appr === 0 && $exempt > 0 && $subm === 0) {
                $status = 'exempt';
                $unitExempt++;
            } else {
                $status = 'lengkap';
                $unitLengkap++;
            }

            $unitSubmissions[] = [
                'id'        => $u->id,
                'nama'      => $u->nama,
                'kode'      => $u->kode,
                'total'     => $totalEntries,
                'approved'  => $appr,
                'exempt'    => $exempt,
                'submitted' => $subm,
                'status'    => $status,
                'avg'       => $appr + $exempt > 0
                    ? round($unitEntries->whereIn('status', ['approved', 'exempt'])->avg('capaian'), 1)
                    : 0,
            ];
        }

        // Pie Diagram 2: Kepatuhan Input Data Antar-Unit
        $pieKepatuhanUnit = [
            'series' => [$unitLengkap, $unitExempt, $unitPending, $unitBelum],
            'labels' => ['Sudah Lengkap & Valid', 'Bebas Validasi', 'Menunggu Validasi', 'Belum Mengisi Data'],
            'colors' => ['#10B981', '#14B8A6', '#0284C7', '#94A3B8'],
        ];

        // 4. Komposisi Kategori Indikator Mutu
        $allActiveIndicators = Indicator::aktif()->get();
        $kategoriCounts = [
            'INM'    => 0,
            'IMP-RS' => 0,
            'IMP-U'  => 0,
            'SPM'    => 0,
        ];
        foreach ($allActiveIndicators as $ind) {
            $jenisArr = is_array($ind->jenis) ? $ind->jenis : (json_decode($ind->jenis, true) ?? []);
            if (in_array('IMN', $jenisArr) || in_array('INM', $jenisArr)) $kategoriCounts['INM']++;
            if (in_array('IMP-RS', $jenisArr)) $kategoriCounts['IMP-RS']++;
            if (in_array('IMP-U', $jenisArr)) $kategoriCounts['IMP-U']++;
            if (in_array('SPM', $jenisArr)) $kategoriCounts['SPM']++;
        }

        // Pie Diagram 3: Komposisi Kategori
        $pieKategori = [
            'series' => array_values($kategoriCounts),
            'labels' => ['INM (Nasional)', 'IMP-RS (Prioritas RS)', 'IMP-U (Prioritas Unit)', 'SPM (Standar Pelayanan)'],
            'colors' => ['#2563EB', '#8B5CF6', '#06B6D4', '#F59E0B'],
        ];

        // 5. Workload Antrian Manajerial (Validasi & PDSA)
        $pendingValidasi = DataEntry::where('status', 'submitted')->count();
        $pdsaPending = PdsaRecord::where('status', 'submitted')->count();

        // 6. Indikator Merah (Di Bawah Target)
        $indikatorMerah = $entriesBulanIni
            ->filter(fn($e) => $e->indicator && $e->indicator->getStatusColor((float) $e->capaian) === 'red')
            ->map(fn($e) => [
                'id'        => $e->id,
                'indicator' => $e->indicator->judul,
                'kode'      => $e->indicator->kode,
                'unit'      => $e->unit->nama ?? '-',
                'capaian'   => $e->capaian,
                'target'    => $e->indicator->target_capaian,
                'operator'  => $e->indicator->target_operator,
                'hasPdsa'   => PdsaRecord::where('indicator_id', $e->indicator_id)
                                ->where('period_id', $e->period_id)
                                ->exists(),
            ])
            ->values();

        // 7. Metrik Tambahan untuk Direksi (Executive Summary)
        $inmEntries = $entriesBulanIni->filter(function($e) {
            $jenisArr = is_array($e->indicator?->jenis) ? $e->indicator->jenis : (json_decode($e->indicator?->jenis, true) ?? []);
            return in_array('IMN', $jenisArr) || in_array('INM', $jenisArr);
        });
        $inmCapaian = round($inmEntries->avg('capaian') ?? 0, 1);

        $imprsEntries = $entriesBulanIni->filter(function($e) {
            $jenisArr = is_array($e->indicator?->jenis) ? $e->indicator->jenis : (json_decode($e->indicator?->jenis, true) ?? []);
            return in_array('IMP-RS', $jenisArr);
        });
        $imprsCapaian = round($imprsEntries->avg('capaian') ?? 0, 1);

        $topUnits = collect($unitSubmissions)
            ->filter(fn (array $unit): bool => $unit['approved'] + $unit['exempt'] > 0)
            ->sortByDesc('avg')
            ->take(5)
            ->values();

        $unitsNeedingAttention = collect($unitSubmissions)
            ->where('status', 'belum')
            ->take(6)
            ->values();

        // 8. Tren 6 Bulan Terakhir
        $trenData = [];
        for ($i = 5; $i >= 0; $i--) {
            $targetBulan = now()->subMonths($i);
            $avg = (clone $entriesQuery)
                ->whereHas('period', fn($q) => $q
                    ->where('tahun', $targetBulan->year)
                    ->where('bulan', $targetBulan->month)
                )
                ->avg('capaian');

            $trenData[] = [
                'label'   => $targetBulan->translatedFormat('M Y'),
                'bulan'   => $targetBulan->month,
                'tahun'   => $targetBulan->year,
                'capaian' => round($avg ?? 0, 1),
            ];
        }

        return Inertia::render('Dashboard/Index', [
            'dashboardType' => 'hospital',
            'userRole'      => $user->role,
            'stats' => [
                'totalIndikator'       => $totalIndikator,
                'rataRataCapaian'      => $rataRataCapaian,
                'totalUnit'            => $activeUnits->count(),
                'unitLengkap'          => $unitLengkap,
                'unitExempt'           => $unitExempt,
                'unitPending'          => $unitPending,
                'unitBelum'            => $unitBelum,
                'statusCount'          => $statusCount,
                'pendingValidasi'      => $pendingValidasi,
                'pdsaPending'          => $pdsaPending,
                'inmCapaian'           => $inmCapaian,
                'imprsCapaian'         => $imprsCapaian,
            ],
            'pieStatusCapaian'         => $pieStatusCapaian,
            'pieKepatuhanUnit'         => $pieKepatuhanUnit,
            'pieKategori'              => $pieKategori,
            'trenData'                 => $trenData,
            'indikatorMerah'           => $indikatorMerah,
            'unitSubmissions'          => $unitSubmissions,
            'topUnits'                 => $topUnits,
            'unitsNeedingAttention'    => $unitsNeedingAttention,
            'filter' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
            ],
        ]);
    }

    /**
     * Dashboard Tingkat Unit Kerja (Kepala Unit & PIC Unit)
     */
    protected function unitDashboard(Request $request, $user, int $tahun, int $bulan): Response
    {
        $unitId = $user->unit_id;
        $unit = $user->unit;

        // 1. Resolve Profil Indikator Mutu Aktif Milik Unit Ini
        $unitIndicators = Indicator::aktif()
            ->where(fn ($query) => $query->where('unit_id', $unitId)->orWhereNull('unit_id'))
            ->get();

        $entryInds = Indicator::aktif()
            ->whereIn('id', DataEntry::where('unit_id', $unitId)->pluck('indicator_id'))
            ->get();
        $unitIndicators = $unitIndicators->merge($entryInds)->unique('id')->sortBy('kode')->values();

        // 2. Ambil Entri Data Bulan Ini untuk Unit Ini
        $entriesBulanIni = DataEntry::where('unit_id', $unitId)
            ->with('indicator')
            ->whereHas('period', fn($q) => $q->where('tahun', $tahun)->where('bulan', $bulan))
            ->get()
            ->keyBy('indicator_id');

        $approvedCount = 0;
        $exemptCount = 0;
        $submittedCount = 0;
        $unfilledCount = 0;
        $unitStatusCount = ['green' => 0, 'yellow' => 0, 'red' => 0];

        $indikatorDetailList = [];
        $unfilledList = [];

        foreach ($unitIndicators as $ind) {
            $entry = $entriesBulanIni->get($ind->id);
            $hasEntry = !is_null($entry);
            $statusEntry = $hasEntry ? $entry->status : 'belum';
            $capaian = $hasEntry ? (float)$entry->capaian : null;
            $statusColor = 'gray';

            if ($hasEntry) {
                if (in_array($entry->status, ['approved', 'exempt'], true)) {
                    if ($entry->status === 'approved') {
                        $approvedCount++;
                    } else {
                        $exemptCount++;
                    }
                    $statusColor = $ind->getStatusColor($capaian);
                    $unitStatusCount[$statusColor]++;
                } else {
                    $submittedCount++;
                    $statusColor = 'blue';
                }
            } else {
                $unfilledCount++;
            }

            $itemData = [
                'id'            => $ind->id,
                'kode'          => $ind->kode,
                'judul'         => $ind->judul,
                'target'        => $ind->target_capaian,
                'operator'      => $ind->target_operator,
                'has_entry'     => $hasEntry,
                'entry_id'      => $entry?->id,
                'capaian'       => $capaian,
                'status_entry'  => $statusEntry,
                'status_color'  => $statusColor,
                'has_pdsa'      => $hasEntry ? PdsaRecord::where('indicator_id', $ind->id)->where('period_id', $entry->period_id)->exists() : false,
            ];

            $indikatorDetailList[] = $itemData;

            if ($statusEntry === 'belum') {
                $unfilledList[] = $itemData;
            }
        }

        $rataRataCapaianUnit = $approvedCount + $exemptCount > 0
            ? round($entriesBulanIni->whereIn('status', ['approved', 'exempt'])->avg('capaian') ?? 0, 1)
            : 0;

        // Pie Diagram 1: Status Capaian Indikator Unit Anda
        $pieStatusCapaian = [
            'series' => [$unitStatusCount['green'], $unitStatusCount['yellow'], $unitStatusCount['red']],
            'labels' => ['Tercapai (Hijau)', 'Hampir Target (Kuning)', 'Di Bawah Target (Merah)'],
            'colors' => ['#10B981', '#F59E0B', '#F43F5E'],
        ];

        // Pie Diagram 2: Progres Pengisian Form Indikator Unit
        $pieProgresInput = [
            'series' => [$approvedCount, $exemptCount, $submittedCount, $unfilledCount],
            'labels' => ['Tervalidasi (Selesai)', 'Bebas Validasi', 'Menunggu Validasi', 'Belum Diinput'],
            'colors' => ['#10B981', '#14B8A6', '#0284C7', '#EF4444'],
        ];

        // 3. Tren 6 Bulan Terakhir Khusus Unit Ini
        $trenData = [];
        for ($i = 5; $i >= 0; $i--) {
            $targetBulan = now()->subMonths($i);
            $avg = DataEntry::where('unit_id', $unitId)
                ->whereIn('status', ['approved', 'exempt'])
                ->whereHas('period', fn($q) => $q
                    ->where('tahun', $targetBulan->year)
                    ->where('bulan', $targetBulan->month)
                )
                ->avg('capaian');

            $trenData[] = [
                'label'   => $targetBulan->translatedFormat('M Y'),
                'bulan'   => $targetBulan->month,
                'tahun'   => $targetBulan->year,
                'capaian' => round($avg ?? 0, 1),
            ];
        }

        // Indikator Merah Unit
        $indikatorMerahUnit = collect($indikatorDetailList)
            ->filter(fn($i) => $i['status_color'] === 'red')
            ->values();

        return Inertia::render('Dashboard/Index', [
            'dashboardType'        => 'unit',
            'userRole'             => $user->role,
            'unit'                 => [
                'id'   => $unit?->id,
                'nama' => $unit?->nama ?? 'Unit Kerja',
                'kode' => $unit?->kode ?? 'UNIT',
            ],
            'stats' => [
                'totalIndikator'       => $unitIndicators->count(),
                'rataRataCapaian'      => $rataRataCapaianUnit,
                'approvedCount'        => $approvedCount,
                'submittedCount'       => $submittedCount,
                'unfilledCount'        => $unfilledCount,
                'statusCount'          => $unitStatusCount,
            ],
            'pieStatusCapaian'         => $pieStatusCapaian,
            'pieProgresInput'          => $pieProgresInput,
            'trenData'                 => $trenData,
            'indikatorDetailList'      => $indikatorDetailList,
            'unfilledList'             => $unfilledList,
            'indikatorMerah'           => $indikatorMerahUnit,
            'filter' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
            ],
        ]);
    }
}
