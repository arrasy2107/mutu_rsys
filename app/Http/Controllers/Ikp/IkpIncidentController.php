<?php

namespace App\Http\Controllers\Ikp;

use App\Http\Controllers\Controller;
use App\Models\PatientSafetyIncident;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class IkpIncidentController extends Controller
{
    /**
     * Tampilkan daftar laporan insiden keselamatan pasien.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isUnitScope = in_array($user->role, ['kepala_unit', 'pic_unit']) && $user->unit_id;

        $tahun = (int) ($request->input('tahun', date('Y')));
        $bulan = $request->input('bulan');
        $grading = $request->input('grading');
        $jenis = $request->input('jenis_insiden');
        $tipe = $request->input('tipe_insiden');
        $status = $request->input('status');
        $unitId = $isUnitScope ? $user->unit_id : $request->input('unit_id');
        $search = $request->input('search');

        $query = PatientSafetyIncident::with(['unitPelapor', 'unitTerkait', 'creator'])
            ->whereYear('tanggal_laporan', $tahun);

        if ($bulan) {
            $query->whereMonth('tanggal_laporan', $bulan);
        }
        if ($unitId) {
            $query->where(function ($q) use ($unitId) {
                $q->where('unit_pelapor_id', $unitId)
                  ->orWhere('unit_terkait_id', $unitId);
            });
        }
        if ($grading) {
            $query->where('grading', strtolower($grading));
        }
        if ($jenis) {
            $query->where('jenis_insiden', $jenis);
        }
        if ($tipe) {
            $query->where('tipe_insiden', $tipe);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_laporan', 'like', "%{$search}%")
                  ->orWhere('insiden', 'like', "%{$search}%")
                  ->orWhere('kronologi', 'like', "%{$search}%");
            });
        }

        $incidents = $query->orderBy('tanggal_kejadian', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Rekapitulasi Cepat (Quick Stats Card)
        $baseStatsQuery = PatientSafetyIncident::whereYear('tanggal_laporan', $tahun);
        if ($unitId) {
            $baseStatsQuery->where('unit_pelapor_id', $unitId);
        }

        $totalIncidents = (clone $baseStatsQuery)->count();
        $sentinelCount = (clone $baseStatsQuery)->where('jenis_insiden', 'Sentinel')->count();
        $highRiskCount = (clone $baseStatsQuery)->whereIn('grading', ['merah', 'kuning'])->count();
        $completedCount = (clone $baseStatsQuery)->where('status', 'selesai')->count();
        
        $evaluatedCount = (clone $baseStatsQuery)->whereIn('kriteria_waktu_penyelesaian', ['sesuai', 'tidak_sesuai'])->count();
        $onTimeCount = (clone $baseStatsQuery)->where('kriteria_waktu_penyelesaian', 'sesuai')->count();
        $complianceRate = $evaluatedCount > 0 ? round(($onTimeCount / $evaluatedCount) * 100, 1) : 100;

        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return Inertia::render('Ikp/Index', [
            'incidents' => $incidents,
            'units' => $units,
            'summary' => [
                'total' => $totalIncidents,
                'sentinel' => $sentinelCount,
                'high_risk' => $highRiskCount,
                'completed' => $completedCount,
                'compliance_rate' => $complianceRate,
            ],
            'filters' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'unit_id' => $unitId,
                'grading' => $grading,
                'jenis_insiden' => $jenis,
                'tipe_insiden' => $tipe,
                'status' => $status,
                'search' => $search,
            ],
            'isUnitScope' => $isUnitScope,
        ]);
    }

    /**
     * Form tambah laporan IKP baru.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $defaultUnitId = $user->unit_id;
        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);
        $autoNomor = PatientSafetyIncident::generateNomorLaporan();

        return Inertia::render('Ikp/Form', [
            'incident' => null,
            'units' => $units,
            'defaultUnitId' => $defaultUnitId,
            'autoNomor' => $autoNomor,
            'isEdit' => false,
        ]);
    }

    /**
     * Simpan laporan IKP baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_laporan'               => 'required|string|max:50|unique:patient_safety_incidents,nomor_laporan',
            'tanggal_laporan'             => 'required|date',
            'tanggal_kejadian'            => 'required|date',
            'tipe_insiden'                => 'required|string|max:100',
            'insiden'                     => 'required|string|max:255',
            'kronologi'                   => 'required|string',
            'jenis_insiden'               => 'required|in:KTC,KTD,KNC,KPC,Sentinel',
            'grading'                     => 'required|in:biru,hijau,kuning,merah',
            'unit_pelapor_id'             => 'required|exists:units,id',
            'unit_terkait_id'             => 'nullable|exists:units,id',
            'tipe_penyelesaian'           => 'nullable|in:investigasi_sederhana,rca',
            'tanggal_selesai_penyelesaian'=> 'nullable|date|after_or_equal:tanggal_laporan',
            'rencana_tindak_lanjut'       => 'nullable|string',
            'penanggung_jawab_rtl'        => 'nullable|string|max:150',
            'status'                      => 'nullable|in:draft,dilaporkan,investigasi,selesai',
            'bukti_rtl'                   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('bukti_rtl')) {
            $path = $request->file('bukti_rtl')->store('ikp_bukti', 'public');
            $validated['bukti_rtl_path'] = $path;
        }

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'dilaporkan';

        $incident = new PatientSafetyIncident($validated);
        $incident->kriteria_waktu_penyelesaian = $incident->evaluateKriteriaWaktu();
        $incident->save();

        return redirect()->route('ikp.index')
            ->with('success', "Laporan insiden {$incident->nomor_laporan} berhasil dilaporkan.");
    }

    /**
     * Tampilkan detail laporan IKP lengkap.
     */
    public function show(PatientSafetyIncident $incident): Response
    {
        $incident->load(['unitPelapor', 'unitTerkait', 'creator']);

        return Inertia::render('Ikp/Show', [
            'incident' => $incident,
        ]);
    }

    /**
     * Form edit laporan IKP atau pembaharuan investigasi/RTL.
     */
    public function edit(PatientSafetyIncident $incident): Response
    {
        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return Inertia::render('Ikp/Form', [
            'incident' => $incident,
            'units' => $units,
            'defaultUnitId' => $incident->unit_pelapor_id,
            'autoNomor' => $incident->nomor_laporan,
            'isEdit' => true,
        ]);
    }

    /**
     * Update data laporan IKP dan resolusi.
     */
    public function update(Request $request, PatientSafetyIncident $incident)
    {
        $validated = $request->validate([
            'nomor_laporan'               => "required|string|max:50|unique:patient_safety_incidents,nomor_laporan,{$incident->id}",
            'tanggal_laporan'             => 'required|date',
            'tanggal_kejadian'            => 'required|date',
            'tipe_insiden'                => 'required|string|max:100',
            'insiden'                     => 'required|string|max:255',
            'kronologi'                   => 'required|string',
            'jenis_insiden'               => 'required|in:KTC,KTD,KNC,KPC,Sentinel',
            'grading'                     => 'required|in:biru,hijau,kuning,merah',
            'unit_pelapor_id'             => 'required|exists:units,id',
            'unit_terkait_id'             => 'nullable|exists:units,id',
            'tipe_penyelesaian'           => 'nullable|in:investigasi_sederhana,rca',
            'tanggal_selesai_penyelesaian'=> 'nullable|date',
            'kriteria_waktu_penyelesaian' => 'nullable|in:sesuai,tidak_sesuai,dalam_proses',
            'rencana_tindak_lanjut'       => 'nullable|string',
            'penanggung_jawab_rtl'        => 'nullable|string|max:150',
            'status'                      => 'required|in:draft,dilaporkan,investigasi,selesai',
            'bukti_rtl'                   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('bukti_rtl')) {
            if ($incident->bukti_rtl_path && Storage::disk('public')->exists($incident->bukti_rtl_path)) {
                Storage::disk('public')->delete($incident->bukti_rtl_path);
            }
            $validated['bukti_rtl_path'] = $request->file('bukti_rtl')->store('ikp_bukti', 'public');
        }

        $incident->fill($validated);

        // Evaluasi kriteria waktu jika tidak di-override secara manual
        if (!$request->filled('kriteria_waktu_penyelesaian')) {
            $incident->kriteria_waktu_penyelesaian = $incident->evaluateKriteriaWaktu();
        }

        $incident->save();

        return redirect()->route('ikp.show', $incident->id)
            ->with('success', "Laporan insiden {$incident->nomor_laporan} berhasil diperbarui.");
    }

    /**
     * Upload cepat berkas bukti RTL dari halaman detail.
     */
    public function uploadBukti(Request $request, PatientSafetyIncident $incident)
    {
        $request->validate([
            'bukti_rtl' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($incident->bukti_rtl_path && Storage::disk('public')->exists($incident->bukti_rtl_path)) {
            Storage::disk('public')->delete($incident->bukti_rtl_path);
        }

        $path = $request->file('bukti_rtl')->store('ikp_bukti', 'public');
        $incident->update([
            'bukti_rtl_path' => $path,
        ]);

        return back()->with('success', 'Berkas bukti RTL berhasil diunggah.');
    }

    /**
     * Hapus laporan IKP.
     */
    public function destroy(PatientSafetyIncident $incident)
    {
        if ($incident->bukti_rtl_path && Storage::disk('public')->exists($incident->bukti_rtl_path)) {
            Storage::disk('public')->delete($incident->bukti_rtl_path);
        }

        $nomor = $incident->nomor_laporan;
        $incident->delete();

        return redirect()->route('ikp.index')
            ->with('success', "Laporan insiden {$nomor} berhasil dihapus.");
    }
}

