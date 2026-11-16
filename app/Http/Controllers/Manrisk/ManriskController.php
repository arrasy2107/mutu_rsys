<?php

namespace App\Http\Controllers\Manrisk;

use App\Http\Controllers\Controller;
use App\Models\RiskRegister;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManriskController extends Controller
{
    /**
     * Daftar register risiko (Unit, Rumah Sakit, KARS, dan MFK).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isUnitScope = in_array($user->role, ['kepala_unit', 'pic_unit']) && $user->unit_id;

        $tahun = (int) ($request->input('tahun', 2026));
        $scope = $request->input('scope', 'all'); // all, rs, kars, unit, mfk
        $unitId = $isUnitScope ? $user->unit_id : $request->input('unit_id');
        $band = $request->input('band');
        $kategori = $request->input('kategori');
        $kontrolabilitas = $request->input('kontrolabilitas');
        $status = $request->input('status');
        $search = $request->input('search');

        $query = RiskRegister::with(['unit', 'creator'])
            ->where('tahun', $tahun);

        // Filter Scope / Ruang Lingkup
        if ($scope === 'rs') {
            $query->where('is_rs_priority', true);
        } elseif ($scope === 'kars') {
            $query->whereNotNull('kategori_kars');
        } elseif ($scope === 'unit') {
            $query->where('is_rs_priority', false);
        } elseif ($scope === 'mfk') {
            $query->where('ruang_lingkup', 'mfk');
        }

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }
        if ($band) {
            $query->where('inherent_band', $band);
        }
        if ($kategori) {
            $query->where('kategori_risiko', 'like', "%{$kategori}%");
        }
        if ($kontrolabilitas) {
            $query->where('kontrolabilitas', $kontrolabilitas);
        }
        if ($status) {
            $query->where('status_pemantauan', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_register', 'like', "%{$search}%")
                  ->orWhere('kegiatan', 'like', "%{$search}%")
                  ->orWhere('pernyataan_risiko', 'like', "%{$search}%")
                  ->orWhere('penyebab', 'like', "%{$search}%")
                  ->orWhere('dampak', 'like', "%{$search}%")
                  ->orWhere('pemilik_risiko', 'like', "%{$search}%");
            });
        }

        $risks = $query->orderBy('skoring_akhir', 'desc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Rekapitulasi Ringkasan KPI
        $baseStatsQuery = RiskRegister::where('tahun', $tahun);
        if ($unitId) {
            $baseStatsQuery->where('unit_id', $unitId);
        }

        $totalRisks = (clone $baseStatsQuery)->count();
        $rsPriorityCount = (clone $baseStatsQuery)->where('is_rs_priority', true)->count();
        $karsCount = (clone $baseStatsQuery)->whereNotNull('kategori_kars')->count();
        $sangatTinggiCount = (clone $baseStatsQuery)->where('inherent_band', 'ekstrem')->count();
        $tinggiCount = (clone $baseStatsQuery)->where('inherent_band', 'tinggi')->count();
        $sedangCount = (clone $baseStatsQuery)->where('inherent_band', 'sedang')->count();
        $rendahCount = (clone $baseStatsQuery)->whereIn('inherent_band', ['rendah', 'sangat_rendah'])->count();
        $controlledCount = (clone $baseStatsQuery)->whereIn('status_pemantauan', ['terkendali', 'ditutup'])->count();

        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return Inertia::render('Manrisk/Index', [
            'risks' => $risks,
            'units' => $units,
            'summary' => [
                'total' => $totalRisks,
                'rs_prioritas' => $rsPriorityCount,
                'kars' => $karsCount,
                'sangat_tinggi' => $sangatTinggiCount,
                'tinggi' => $tinggiCount,
                'sedang' => $sedangCount,
                'rendah' => $rendahCount,
                'terkendali' => $controlledCount,
            ],
            'filters' => [
                'tahun' => $tahun,
                'scope' => $scope,
                'unit_id' => $unitId,
                'band' => $band,
                'kategori' => $kategori,
                'kontrolabilitas' => $kontrolabilitas,
                'status' => $status,
                'search' => $search,
            ],
            'isUnitScope' => $isUnitScope,
        ]);
    }

    /**
     * Form identifikasi & asesmen risiko baru.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $defaultUnitId = $user->unit_id;
        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);
        $autoNomor = RiskRegister::generateNomorRegister(2026);

        return Inertia::render('Manrisk/Form', [
            'risk' => null,
            'units' => $units,
            'defaultUnitId' => $defaultUnitId,
            'autoNomor' => $autoNomor,
            'isEdit' => false,
        ]);
    }

    /**
     * Simpan asesmen risiko baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_register'          => 'nullable|string|max:100|unique:risk_registers,nomor_register',
            'kode_risiko'             => 'required|string|max:50',
            'tahun'                   => 'required|integer|min:2020|max:2099',
            'unit_id'                 => 'required|exists:units,id',
            'ruang_lingkup'           => 'required|string',
            'is_rs_priority'          => 'boolean',
            'kategori_kars'           => 'nullable|string|max:100',
            'nomor_prioritas_rs'      => 'nullable|string|max:50',
            'kategori_risiko'         => 'required|string|max:100',
            'kategori_mfk'            => 'nullable|string|max:100',
            'kegiatan'                => 'required|string',
            'tujuan_kegiatan'         => 'nullable|string',
            'pernyataan_risiko'       => 'required|string',
            'deskripsi'               => 'nullable|string',
            'penyebab'                => 'nullable|string',
            'kontrolabilitas'         => 'required|in:C,UC',
            'dampak'                  => 'nullable|string',
            'pengendalian_ada'        => 'nullable|string',
            'pengendalian_desain'     => 'nullable|string',
            'pengendalian_efektifitas_text' => 'nullable|string',
            'efektivitas_pengendalian'=> 'required|in:efektif,cukup,kurang',
            'inherent_probabilitas'   => 'required|integer|between:1,5',
            'inherent_dampak'         => 'required|integer|between:1,5',
            'bobot'                   => 'required|numeric|between:0.5,3.0',
            'peringkat_risiko'        => 'nullable|integer',
            'pemilik_risiko'          => 'nullable|string',
            'alternatif_penanganan'   => 'nullable|string',
            'pengendalian_harus_ada'  => 'nullable|string',
            'opsi_penanganan'         => 'nullable|string',
            'rencana_penanganan'      => 'nullable|string',
            'rencana_kegiatan'        => 'nullable|string',
            'waktu_jadwal'            => 'nullable|string',
            'jenis_alternatif'        => 'nullable|string',
            'penanggung_jawab'        => 'nullable|string',
            'pemantauan_rencana'      => 'nullable|string',
            'pemantauan_realisasi'    => 'nullable|string',
            'pemantauan_belum_tertangani' => 'nullable|string',
            'pemantauan_usulan_perbaikan' => 'nullable|string',
            'waktu_pemantauan_rencana' => 'nullable|string',
            'waktu_pemantauan_realisasi' => 'nullable|string',
            'pj_pemantauan'           => 'nullable|string',
            'residual_probabilitas'   => 'nullable|integer|between:1,5',
            'residual_dampak'         => 'nullable|integer|between:1,5',
            'residual_bobot'          => 'nullable|numeric|between:0.5,3.0',
            'penurunan_risiko_persen' => 'nullable|numeric',
            'pelaporan_output'        => 'nullable|string',
            'pelaporan_target'        => 'nullable|string',
            'pelaporan_realisasi'     => 'nullable|string',
            'waktu_implementasi'      => 'nullable|string',
            'status_tren'             => 'nullable|string',
            'level_risiko_evaluasi'   => 'nullable|string',
            'status_pemantauan'       => 'required|in:draft,dalam_proses,terkendali,ditutup',
            'catatan_pemantauan'      => 'nullable|string',
            'tanggal_review'          => 'nullable|date',
        ]);

        if (empty($validated['nomor_register'])) {
            $validated['nomor_register'] = RiskRegister::generateNomorRegister($validated['tahun']);
        }

        // Skoring RSYS: P x D x Bobot
        $p = $validated['inherent_probabilitas'];
        $d = $validated['inherent_dampak'];
        $b = (float) $validated['bobot'];
        $skor = round($p * $d * $b, 2);

        $validated['inherent_skor'] = min(25, max(1, (int) round($p * $d)));
        $validated['skoring_akhir'] = $skor;
        $validated['inherent_band'] = RiskRegister::determineRiskBand($skor);

        // Residu
        if (!empty($validated['residual_probabilitas']) && !empty($validated['residual_dampak'])) {
            $rp = $validated['residual_probabilitas'];
            $rd = $validated['residual_dampak'];
            $rb = (float) ($validated['residual_bobot'] ?? 1.0);
            $resSkor = round($rp * $rd * $rb, 2);
            $validated['residual_skor'] = $resSkor;
            $validated['residual_band'] = RiskRegister::determineRiskBand($resSkor);

            if ($skor > 0) {
                $validated['penurunan_risiko_persen'] = round((($skor - $resSkor) / $skor) * 100, 2);
            }
        }

        $validated['created_by'] = $request->user()->id;

        $risk = RiskRegister::create($validated);

        return redirect()->route('manrisk.index')
            ->with('success', "Identifikasi risiko {$risk->nomor_register} berhasil disimpan.");
    }

    /**
     * Tampilkan lembar kerja profil risiko 8 komponen.
     */
    public function show(RiskRegister $risk): Response
    {
        $risk->load(['unit', 'creator']);

        return Inertia::render('Manrisk/Show', [
            'risk' => $risk,
        ]);
    }

    /**
     * Form edit asesmen risiko.
     */
    public function edit(RiskRegister $risk): Response
    {
        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return Inertia::render('Manrisk/Form', [
            'risk' => $risk,
            'units' => $units,
            'defaultUnitId' => $risk->unit_id,
            'autoNomor' => $risk->nomor_register,
            'isEdit' => true,
        ]);
    }

    /**
     * Update data asesmen risiko.
     */
    public function update(Request $request, RiskRegister $risk)
    {
        $validated = $request->validate([
            'nomor_register'          => "nullable|string|max:100|unique:risk_registers,nomor_register,{$risk->id}",
            'kode_risiko'             => 'required|string|max:50',
            'tahun'                   => 'required|integer|min:2020|max:2099',
            'unit_id'                 => 'required|exists:units,id',
            'ruang_lingkup'           => 'required|string',
            'is_rs_priority'          => 'boolean',
            'kategori_kars'           => 'nullable|string|max:100',
            'nomor_prioritas_rs'      => 'nullable|string|max:50',
            'kategori_risiko'         => 'required|string|max:100',
            'kategori_mfk'            => 'nullable|string|max:100',
            'kegiatan'                => 'required|string',
            'tujuan_kegiatan'         => 'nullable|string',
            'pernyataan_risiko'       => 'required|string',
            'deskripsi'               => 'nullable|string',
            'penyebab'                => 'nullable|string',
            'kontrolabilitas'         => 'required|in:C,UC',
            'dampak'                  => 'nullable|string',
            'pengendalian_ada'        => 'nullable|string',
            'pengendalian_desain'     => 'nullable|string',
            'pengendalian_efektifitas_text' => 'nullable|string',
            'efektivitas_pengendalian'=> 'required|in:efektif,cukup,kurang',
            'inherent_probabilitas'   => 'required|integer|between:1,5',
            'inherent_dampak'         => 'required|integer|between:1,5',
            'bobot'                   => 'required|numeric|between:0.5,3.0',
            'peringkat_risiko'        => 'nullable|integer',
            'pemilik_risiko'          => 'nullable|string',
            'alternatif_penanganan'   => 'nullable|string',
            'pengendalian_harus_ada'  => 'nullable|string',
            'opsi_penanganan'         => 'nullable|string',
            'rencana_penanganan'      => 'nullable|string',
            'rencana_kegiatan'        => 'nullable|string',
            'waktu_jadwal'            => 'nullable|string',
            'jenis_alternatif'        => 'nullable|string',
            'penanggung_jawab'        => 'nullable|string',
            'pemantauan_rencana'      => 'nullable|string',
            'pemantauan_realisasi'    => 'nullable|string',
            'pemantauan_belum_tertangani' => 'nullable|string',
            'pemantauan_usulan_perbaikan' => 'nullable|string',
            'waktu_pemantauan_rencana' => 'nullable|string',
            'waktu_pemantauan_realisasi' => 'nullable|string',
            'pj_pemantauan'           => 'nullable|string',
            'residual_probabilitas'   => 'nullable|integer|between:1,5',
            'residual_dampak'         => 'nullable|integer|between:1,5',
            'residual_bobot'          => 'nullable|numeric|between:0.5,3.0',
            'penurunan_risiko_persen' => 'nullable|numeric',
            'pelaporan_output'        => 'nullable|string',
            'pelaporan_target'        => 'nullable|string',
            'pelaporan_realisasi'     => 'nullable|string',
            'waktu_implementasi'      => 'nullable|string',
            'status_tren'             => 'nullable|string',
            'level_risiko_evaluasi'   => 'nullable|string',
            'status_pemantauan'       => 'required|in:draft,dalam_proses,terkendali,ditutup',
            'catatan_pemantauan'      => 'nullable|string',
            'tanggal_review'          => 'nullable|date',
        ]);

        $p = $validated['inherent_probabilitas'];
        $d = $validated['inherent_dampak'];
        $b = (float) $validated['bobot'];
        $skor = round($p * $d * $b, 2);

        $validated['inherent_skor'] = min(25, max(1, (int) round($p * $d)));
        $validated['skoring_akhir'] = $skor;
        $validated['inherent_band'] = RiskRegister::determineRiskBand($skor);

        if (!empty($validated['residual_probabilitas']) && !empty($validated['residual_dampak'])) {
            $rp = $validated['residual_probabilitas'];
            $rd = $validated['residual_dampak'];
            $rb = (float) ($validated['residual_bobot'] ?? 1.0);
            $resSkor = round($rp * $rd * $rb, 2);
            $validated['residual_skor'] = $resSkor;
            $validated['residual_band'] = RiskRegister::determineRiskBand($resSkor);

            if ($skor > 0) {
                $validated['penurunan_risiko_persen'] = round((($skor - $resSkor) / $skor) * 100, 2);
            }
        } else {
            $validated['residual_skor'] = null;
            $validated['residual_band'] = null;
            $validated['penurunan_risiko_persen'] = null;
        }

        $risk->update($validated);

        return redirect()->route('manrisk.show', $risk->id)
            ->with('success', "Data profil risiko {$risk->nomor_register} berhasil diperbarui.");
    }

    /**
     * Toggle status risiko prioritas Rumah Sakit.
     */
    public function toggleRsPriority(RiskRegister $risk)
    {
        $risk->update([
            'is_rs_priority' => !$risk->is_rs_priority,
        ]);

        $statusText = $risk->is_rs_priority ? 'ditetapkan sebagai Prioritas RS' : 'dikembalikan ke tingkat Unit';
        return back()->with('success', "Risiko {$risk->nomor_register} berhasil {$statusText}.");
    }

    /**
     * Hapus data risiko.
     */
    public function destroy(RiskRegister $risk)
    {
        $kode = $risk->nomor_register ?? $risk->kode_risiko;
        $risk->delete();

        return redirect()->route('manrisk.index')
            ->with('success', "Risiko {$kode} berhasil dihapus.");
    }

    /**
     * Heatmap & Profil Matriks Risiko 5x5 (Inherent vs Residual).
     */
    public function matrix(Request $request): Response
    {
        $tahun = (int) ($request->input('tahun', 2026));
        $scope = $request->input('scope', 'all');
        $unitId = $request->input('unit_id');

        $query = RiskRegister::with('unit')->where('tahun', $tahun);

        if ($scope === 'rs') {
            $query->where('is_rs_priority', true);
        } elseif ($scope === 'kars') {
            $query->whereNotNull('kategori_kars');
        } elseif ($scope === 'unit') {
            $query->where('is_rs_priority', false);
        } elseif ($scope === 'mfk') {
            $query->where('ruang_lingkup', 'mfk');
        }

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        $allRisks = $query->get();

        // Bangun Grid 5x5 (Probabilitas 5 s.d 1 dan Dampak 1 s.d 5)
        $inherentMatrix = [];
        $residualMatrix = [];

        for ($p = 5; $p >= 1; $p--) {
            for ($d = 1; $d <= 5; $d++) {
                $skor = $p * $d;
                $band = RiskRegister::determineRiskBand($skor);

                $matchingInherent = $allRisks->where('inherent_probabilitas', $p)->where('inherent_dampak', $d);
                $inherentMatrix["{$p}_{$d}"] = [
                    'probabilitas' => $p,
                    'dampak' => $d,
                    'skor' => $skor,
                    'band' => $band,
                    'count' => $matchingInherent->count(),
                    'risks' => $matchingInherent->values()->map(fn($r) => [
                        'id' => $r->id,
                        'nomor_register' => $r->nomor_register,
                        'kode' => $r->kode_risiko,
                        'kegiatan' => $r->kegiatan,
                        'pernyataan' => $r->pernyataan_risiko,
                        'bobot' => (float) $r->bobot,
                        'skoring_akhir' => (float) $r->skoring_akhir,
                        'unit' => $r->unit?->nama ?? $r->pemilik_risiko,
                        'is_rs_priority' => (bool) $r->is_rs_priority,
                        'kategori_kars' => $r->kategori_kars,
                    ]),
                ];

                $matchingResidual = $allRisks->where('residual_probabilitas', $p)->where('residual_dampak', $d);
                $residualMatrix["{$p}_{$d}"] = [
                    'probabilitas' => $p,
                    'dampak' => $d,
                    'skor' => $skor,
                    'band' => $band,
                    'count' => $matchingResidual->count(),
                    'risks' => $matchingResidual->values()->map(fn($r) => [
                        'id' => $r->id,
                        'nomor_register' => $r->nomor_register,
                        'kode' => $r->kode_risiko,
                        'kegiatan' => $r->kegiatan,
                        'pernyataan' => $r->pernyataan_risiko,
                        'bobot' => (float) $r->residual_bobot,
                        'skoring_akhir' => (float) $r->residual_skor,
                        'unit' => $r->unit?->nama ?? $r->pemilik_risiko,
                        'is_rs_priority' => (bool) $r->is_rs_priority,
                        'kategori_kars' => $r->kategori_kars,
                    ]),
                ];
            }
        }

        $units = Unit::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return Inertia::render('Manrisk/Matrix', [
            'total' => $allRisks->count(),
            'inherentMatrix' => $inherentMatrix,
            'residualMatrix' => $residualMatrix,
            'summary' => [
                'sangat_tinggi' => $allRisks->where('inherent_band', 'ekstrem')->count(),
                'tinggi' => $allRisks->where('inherent_band', 'tinggi')->count(),
                'sedang' => $allRisks->where('inherent_band', 'sedang')->count(),
                'rendah' => $allRisks->whereIn('inherent_band', ['rendah', 'sangat_rendah'])->count(),
            ],
            'units' => $units,
            'filters' => [
                'tahun' => $tahun,
                'scope' => $scope,
                'unit_id' => $unitId,
            ],
        ]);
    }
}
