<?php

namespace App\Http\Controllers\Ikp;

use App\Http\Controllers\Controller;
use App\Models\PatientSafetyIncident;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class IkpMappingController extends Controller
{
    /**
     * Tampilkan pemetaan analitik & visualisasi grafik insiden keselamatan pasien.
     * Sesuai butir Roadmap Modul II.B (1 s.d 7).
     */
    public function index(Request $request): Response
    {
        $tahun = (int) ($request->input('tahun', date('Y')));
        $periodeType = $request->input('periode_type', 'tahunan'); // tahunan, semester, triwulan, bulanan
        $periodeValue = $request->input('periode_value');
        $unitId = $request->input('unit_id');

        // Query dasar terfilter waktu
        $query = PatientSafetyIncident::whereYear('tanggal_laporan', $tahun);

        if ($unitId) {
            $query->where('unit_pelapor_id', $unitId);
        }

        // Terapkan rentang waktu dinamis
        if ($periodeType === 'bulanan' && $periodeValue) {
            $query->whereMonth('tanggal_laporan', (int) $periodeValue);
        } elseif ($periodeType === 'triwulan' && $periodeValue) {
            $quarters = [
                '1' => [1, 3],
                '2' => [4, 6],
                '3' => [7, 9],
                '4' => [10, 12],
            ];
            if (isset($quarters[$periodeValue])) {
                $query->whereBetween(DB::raw('MONTH(tanggal_laporan)'), $quarters[$periodeValue]);
            }
        } elseif ($periodeType === 'semester' && $periodeValue) {
            $semesters = [
                '1' => [1, 6],
                '2' => [7, 12],
            ];
            if (isset($semesters[$periodeValue])) {
                $query->whereBetween(DB::raw('MONTH(tanggal_laporan)'), $semesters[$periodeValue]);
            }
        }

        $allIncidents = $query->with('unitPelapor')->get();
        $totalCount = $allIncidents->count();

        // ─── 1. PEMETAAN TIPE INSIDEN ─────────────────────────────────────────
        $tipeGroups = $allIncidents->groupBy('tipe_insiden')->map(function ($items, $key) use ($totalCount) {
            $count = $items->count();
            return [
                'name' => $key ?: 'Lain-lain',
                'count' => $count,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();

        // ─── 2. PEMETAAN JENIS INSIDEN (KTC, KTD, KNC, KPC, Sentinel) ─────────
        $standardJenis = ['Sentinel', 'KTD', 'KTC', 'KNC', 'KPC'];
        $jenisCounts = $allIncidents->groupBy('jenis_insiden');
        $jenisGroups = collect($standardJenis)->map(function ($j) use ($jenisCounts, $totalCount) {
            $count = isset($jenisCounts[$j]) ? $jenisCounts[$j]->count() : 0;
            return [
                'name' => $j,
                'count' => $count,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0,
            ];
        });

        // ─── 3. PEMETAAN GRADING RISIKO (Merah, Kuning, Hijau, Biru) ──────────
        $standardGrading = ['merah', 'kuning', 'hijau', 'biru'];
        $gradingCounts = $allIncidents->groupBy(fn($i) => strtolower($i->grading));
        $gradingGroups = collect($standardGrading)->map(function ($g) use ($gradingCounts, $totalCount) {
            $count = isset($gradingCounts[$g]) ? $gradingCounts[$g]->count() : 0;
            return [
                'name' => ucfirst($g),
                'key'  => $g,
                'count' => $count,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0,
            ];
        });

        // ─── 4. PEMETAAN UNIT PELAPOR ─────────────────────────────────────────
        $unitGroups = $allIncidents->groupBy(fn($i) => $i->unitPelapor?->nama ?? 'Tanpa Unit')
            ->map(function ($items, $unitName) use ($totalCount) {
                $count = $items->count();
                return [
                    'name' => $unitName,
                    'count' => $count,
                    'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0,
                ];
            })->sortByDesc('count')->values();

        // ─── 5. PEMETAAN TIPE INSIDEN DAN GRADING (MATRIKS CROSS-TAB) ──────────
        $tipeList = $tipeGroups->pluck('name')->toArray();
        $tipeGradingMatrix = collect($tipeList)->map(function ($tipe) use ($allIncidents) {
            $subset = $allIncidents->where('tipe_insiden', $tipe);
            $biru = $subset->where('grading', 'biru')->count();
            $hijau = $subset->where('grading', 'hijau')->count();
            $kuning = $subset->where('grading', 'kuning')->count();
            $merah = $subset->where('grading', 'merah')->count();
            return [
                'tipe' => $tipe,
                'biru' => $biru,
                'hijau' => $hijau,
                'kuning' => $kuning,
                'merah' => $merah,
                'total' => $biru + $hijau + $kuning + $merah,
            ];
        });

        // ─── 6. PEMETAAN TIPE INSIDEN DAN JENIS INSIDEN (MATRIKS CROSS-TAB) ───
        $tipeJenisMatrix = collect($tipeList)->map(function ($tipe) use ($allIncidents) {
            $subset = $allIncidents->where('tipe_insiden', $tipe);
            $sentinel = $subset->where('jenis_insiden', 'Sentinel')->count();
            $ktd = $subset->where('jenis_insiden', 'KTD')->count();
            $ktc = $subset->where('jenis_insiden', 'KTC')->count();
            $knc = $subset->where('jenis_insiden', 'KNC')->count();
            $kpc = $subset->where('jenis_insiden', 'KPC')->count();
            return [
                'tipe' => $tipe,
                'sentinel' => $sentinel,
                'ktd' => $ktd,
                'ktc' => $ktc,
                'knc' => $knc,
                'kpc' => $kpc,
                'total' => $sentinel + $ktd + $ktc + $knc + $kpc,
            ];
        });

        // ─── 7. PEMETAAN PENYELESAIAN & KEPATUHAN WAKTU ───────────────────────
        $penyelesaianData = [
            'tipe_penyelesaian' => [
                'investigasi_sederhana' => $allIncidents->where('tipe_penyelesaian', 'investigasi_sederhana')->count(),
                'rca' => $allIncidents->where('tipe_penyelesaian', 'rca')->count(),
                'belum_ditetapkan' => $allIncidents->whereNull('tipe_penyelesaian')->count(),
            ],
            'kriteria_waktu' => [
                'sesuai' => $allIncidents->where('kriteria_waktu_penyelesaian', 'sesuai')->count(),
                'tidak_sesuai' => $allIncidents->where('kriteria_waktu_penyelesaian', 'tidak_sesuai')->count(),
                'dalam_proses' => $allIncidents->where('kriteria_waktu_penyelesaian', 'dalam_proses')->count(),
            ],
            'status' => [
                'dilaporkan' => $allIncidents->where('status', 'dilaporkan')->count(),
                'investigasi' => $allIncidents->where('status', 'investigasi')->count(),
                'selesai' => $allIncidents->where('status', 'selesai')->count(),
            ],
        ];

        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return Inertia::render('Ikp/Mapping', [
            'total' => $totalCount,
            'tipeData' => $tipeGroups,
            'jenisData' => $jenisGroups,
            'gradingData' => $gradingGroups,
            'unitData' => $unitGroups,
            'tipeGradingMatrix' => $tipeGradingMatrix,
            'tipeJenisMatrix' => $tipeJenisMatrix,
            'penyelesaianData' => $penyelesaianData,
            'units' => $units,
            'filters' => [
                'tahun' => $tahun,
                'periode_type' => $periodeType,
                'periode_value' => $periodeValue,
                'unit_id' => $unitId,
            ],
        ]);
    }
}

