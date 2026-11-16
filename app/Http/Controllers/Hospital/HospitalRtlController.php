<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Indicator;
use App\Models\PdsaRecord;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HospitalRtlController extends Controller
{
    /**
     * Display Rencana Tindak Lanjut (RTL) Tingkat Rumah Sakit (Butir B.3 Roadmap)
     * Otomatis dari data unit
     * Kategori: IMP-RS, IMN, IMP-U, SPM
     * Filter Waktu: Bulanan, Triwulan, Semester, Tahunan
     */
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $kategori = $request->query('kategori', 'IMP-RS'); // IMP-RS, IMN, IMP-U, SPM
        $rentang = $request->query('rentang', 'bulanan');   // bulanan, triwulan, semester, tahunan
        $periodeId = $request->query('bulan');              // optional month filter
        $unitId = $request->query('unit_id');

        $query = PdsaRecord::query()
            ->with(['indicator', 'unit', 'user', 'period', 'reviewedBy'])
            ->whereNotNull('rekomendasi_tindak_lanjut')
            ->whereHas('period', fn($p) => $p->where('tahun', $tahun))
            ->when($unitId, fn($q, $u) => $q->where('unit_id', $u))
            ->when($periodeId, fn($q, $m) => $q->whereHas('period', fn($p) => $p->where('bulan', $m)));

        // Filter by category
        $query->whereHas('indicator', function ($q) use ($kategori) {
            if ($kategori === 'IMP-RS') {
                $q->where('jenis', 'like', '%IMP-RS%');
            } elseif ($kategori === 'IMN') {
                $q->where(fn($sub) => $sub->where('jenis', 'like', '%IMN%')->orWhere('jenis', 'like', '%INM%'));
            } elseif ($kategori === 'IMP-U') {
                $q->where(fn($sub) => $sub->where('jenis', 'like', '%IMP-U%')->orWhere('jenis', 'like', '%IMU%'));
            } elseif ($kategori === 'SPM') {
                $q->where('jenis', 'like', '%SPM%');
            }
        });

        $records = (clone $query)->latest()->paginate(25)->withQueryString();
        $allRecords = (clone $query)->get();

        // Summary Stats
        $stats = [
            'total'        => $allRecords->count(),
            'selesai'      => $allRecords->where('status', 'reviewed')->count(),
            'dalam_proses' => $allRecords->whereIn('status', ['submitted', 'in_progress'])->count(),
            'draft'        => $allRecords->where('status', 'draft')->count(),
            'ada_bukti'    => $allRecords->filter(fn($r) => !empty($r->bukti_rtl))->count(),
        ];

        // Counts per category
        $counts = [
            'IMP-RS' => PdsaRecord::whereHas('indicator', fn($q) => $q->where('jenis', 'like', '%IMP-RS%'))->count(),
            'IMN'    => PdsaRecord::whereHas('indicator', fn($q) => $q->where('jenis', 'like', '%IMN%')->orWhere('jenis', 'like', '%INM%'))->count(),
            'IMP-U'  => PdsaRecord::whereHas('indicator', fn($q) => $q->where('jenis', 'like', '%IMP-U%')->orWhere('jenis', 'like', '%IMU%'))->count(),
            'SPM'    => PdsaRecord::whereHas('indicator', fn($q) => $q->where('jenis', 'like', '%SPM%'))->count(),
        ];

        return Inertia::render('Hospital/Rtl/Index', [
            'kategori'   => $kategori,
            'rentang'    => $rentang,
            'tahun'      => $tahun,
            'counts'     => $counts,
            'stats'      => $stats,
            'records'    => $records,
            'units'      => Unit::aktif()->get(['id', 'nama', 'kode']),
            'filters'    => [
                'kategori' => $kategori,
                'rentang'  => $rentang,
                'tahun'    => $tahun,
                'unit_id'  => $unitId ? (int) $unitId : '',
                'bulan'    => $periodeId ? (int) $periodeId : '',
            ],
        ]);
    }

    /**
     * Verifikasi RTL oleh Komite Mutu
     */
    public function verify(Request $request, PdsaRecord $pdsa)
    {
        $request->validate([
            'status' => 'required|in:reviewed,submitted,draft',
            'catatan_review' => 'nullable|string',
        ]);

        $pdsa->update([
            'status'         => $request->status,
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'catatan_review' => $request->catatan_review,
        ]);

        return back()->with('success', 'RTL Rumah Sakit berhasil diverifikasi oleh Komite Mutu.');
    }
}
