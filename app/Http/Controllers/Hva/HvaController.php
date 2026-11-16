<?php

namespace App\Http\Controllers\Hva;

use App\Http\Controllers\Controller;
use App\Models\HvaAssessment;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class HvaController extends Controller
{
    /**
     * Display the main HVA Dashboard / Index
     */
    public function index(Request $request): Response
    {
        $tahun = (int) $request->input('tahun', 2026);
        $scope = $request->input('scope', 'rs'); // 'rs' or 'unit'
        $unitId = $request->input('unit_id');
        $kategori = $request->input('kategori', 'all');
        $search = $request->input('search');

        $units = Unit::select('id', 'nama', 'kode')
            ->orderBy('nama')
            ->get();

        // Default unit if scope is unit and no unitId is provided
        if ($scope === 'unit' && !$unitId) {
            $user = $request->user();
            if ($user && $user->unit_id) {
                $unitId = $user->unit_id;
            } else {
                $unitId = $units->first()?->id ?? 1;
            }
        }

        // Base query
        $query = HvaAssessment::where('tahun', $tahun)
            ->where('ruang_lingkup', $scope);

        if ($scope === 'unit' && $unitId) {
            $query->where('unit_id', $unitId);
        }

        if ($kategori !== 'all') {
            $query->where('kategori', $kategori);
        }

        if ($search) {
            $query->where('nama_kejadian', 'like', "%{$search}%");
        }

        $assessments = $query->with('unit')
            ->orderByRaw('CASE WHEN skor_risiko_relatif > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('skor_risiko_relatif')
            ->orderBy('urutan')
            ->get();

        // Category summary cards
        $categoryStats = $this->getCategoryStats($tahun, $scope, $unitId);

        // Top 10 Prioritas Bahaya
        $topHazardsQuery = HvaAssessment::where('tahun', $tahun)
            ->where('ruang_lingkup', $scope)
            ->where('skor_risiko_relatif', '>', 0);

        if ($scope === 'unit' && $unitId) {
            $topHazardsQuery->where('unit_id', $unitId);
        }

        $topHazards = $topHazardsQuery->orderByDesc('skor_risiko_relatif')
            ->limit(10)
            ->get();

        $selectedUnit = $scope === 'unit' && $unitId ? Unit::find($unitId) : null;

        return Inertia::render('Hva/Index', [
            'assessments' => $assessments,
            'categoryStats' => $categoryStats,
            'topHazards' => $topHazards,
            'units' => $units,
            'selectedUnit' => $selectedUnit,
            'filters' => [
                'tahun' => $tahun,
                'scope' => $scope,
                'unit_id' => $unitId ? (int)$unitId : null,
                'kategori' => $kategori,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Dedicated Kaiser Permanente Scoring Sheet per Category
     */
    public function category(Request $request, string $kategori): Response
    {
        $validCategories = ['natural', 'technological', 'human', 'hazardous_materials'];
        if (!in_array($kategori, $validCategories)) {
            abort(404, "Kategori HVA tidak valid");
        }

        $tahun = (int) $request->input('tahun', 2026);
        $scope = $request->input('scope', 'rs');
        $unitId = $request->input('unit_id');

        $units = Unit::select('id', 'nama', 'kode')->orderBy('nama')->get();

        if ($scope === 'unit' && !$unitId) {
            $unitId = $request->user()?->unit_id ?? $units->first()?->id ?? 1;
        }

        $query = HvaAssessment::where('tahun', $tahun)
            ->where('ruang_lingkup', $scope)
            ->where('kategori', $kategori);

        if ($scope === 'unit' && $unitId) {
            $query->where('unit_id', $unitId);
        }

        $assessments = $query->orderBy('urutan')->get();

        $categoryLabels = [
            'natural' => 'Kejadian Bencana Alam (Natural Hazards)',
            'technological' => 'Kejadian Kegagalan Teknologi / Utilitas (Technological Hazards)',
            'human' => 'Kejadian Faktor Manusia & Keamanan (Human Hazards)',
            'hazardous_materials' => 'Kejadian Bahan Berbahaya & Beracun (Hazardous Materials)',
        ];

        return Inertia::render('Hva/Category', [
            'assessments' => $assessments,
            'kategori' => $kategori,
            'kategoriLabel' => $categoryLabels[$kategori] ?? ucfirst($kategori),
            'units' => $units,
            'selectedUnit' => $scope === 'unit' && $unitId ? Unit::find($unitId) : null,
            'filters' => [
                'tahun' => $tahun,
                'scope' => $scope,
                'unit_id' => $unitId ? (int)$unitId : null,
            ],
        ]);
    }

    /**
     * Summary & Graphic Analytics (ApexCharts radar & bar)
     */
    public function summary(Request $request): Response
    {
        $tahun = (int) $request->input('tahun', 2026);
        $scope = $request->input('scope', 'rs');
        $unitId = $request->input('unit_id');

        $units = Unit::select('id', 'nama', 'kode')->orderBy('nama')->get();

        if ($scope === 'unit' && !$unitId) {
            $unitId = $request->user()?->unit_id ?? $units->first()?->id ?? 1;
        }

        $query = HvaAssessment::where('tahun', $tahun)
            ->where('ruang_lingkup', $scope);

        if ($scope === 'unit' && $unitId) {
            $query->where('unit_id', $unitId);
        }

        $allData = $query->get();

        // 1. Radar & Bar Data by 4 Categories
        $categories = [
            'natural' => 'Natural Hazards',
            'technological' => 'Technological',
            'human' => 'Human Hazards',
            'hazardous_materials' => 'Hazardous Mat.',
        ];

        $categoryComparison = [];
        foreach ($categories as $catKey => $catLabel) {
            $subset = $allData->where('kategori', $catKey);
            $count = $subset->count();
            $avgRisk = $count > 0 ? round($subset->avg('skor_risiko_relatif'), 2) : 0;
            $maxRisk = $count > 0 ? round($subset->max('skor_risiko_relatif'), 2) : 0;
            $nonZero = $subset->where('skor_risiko_relatif', '>', 0)->count();

            $categoryComparison[] = [
                'key' => $catKey,
                'label' => $catLabel,
                'total_events' => $count,
                'active_events' => $nonZero,
                'avg_risk' => $avgRisk,
                'max_risk' => $maxRisk,
            ];
        }

        // 2. Top 15 Highest Threats
        $top15 = $allData->where('skor_risiko_relatif', '>', 0)
            ->sortByDesc('skor_risiko_relatif')
            ->values()
            ->take(15);

        // 3. Distribution by Threat Severity Band
        // High (>= 20%), Moderate (5 - 19.99%), Low (0.01 - 4.99%), N/A (0%)
        $threatBands = [
            'tinggi' => $allData->where('skor_risiko_relatif', '>=', 20.0)->count(),
            'sedang' => $allData->whereBetween('skor_risiko_relatif', [5.0, 19.99])->count(),
            'rendah' => $allData->whereBetween('skor_risiko_relatif', [0.01, 4.99])->count(),
            'na' => $allData->where('skor_risiko_relatif', 0.0)->count(),
        ];

        return Inertia::render('Hva/Summary', [
            'categoryComparison' => $categoryComparison,
            'top15' => $top15,
            'threatBands' => $threatBands,
            'units' => $units,
            'selectedUnit' => $scope === 'unit' && $unitId ? Unit::find($unitId) : null,
            'filters' => [
                'tahun' => $tahun,
                'scope' => $scope,
                'unit_id' => $unitId ? (int)$unitId : null,
            ],
        ]);
    }

    /**
     * Rencana Tindak Lanjut (RTL) Mitigation View
     */
    public function rtl(Request $request): Response
    {
        $tahun = (int) $request->input('tahun', 2026);
        $scope = $request->input('scope', 'rs');
        $unitId = $request->input('unit_id');
        $status = $request->input('status', 'all');

        $units = Unit::select('id', 'nama', 'kode')->orderBy('nama')->get();

        if ($scope === 'unit' && !$unitId) {
            $unitId = $request->user()?->unit_id ?? $units->first()?->id ?? 1;
        }

        $query = HvaAssessment::where('tahun', $tahun)
            ->where('ruang_lingkup', $scope)
            ->where('skor_risiko_relatif', '>', 0);

        if ($scope === 'unit' && $unitId) {
            $query->where('unit_id', $unitId);
        }

        if ($status !== 'all') {
            $query->where('status_mitigasi', $status);
        }

        $items = $query->with('unit')
            ->orderByDesc('skor_risiko_relatif')
            ->get();

        return Inertia::render('Hva/Rtl', [
            'items' => $items,
            'units' => $units,
            'selectedUnit' => $scope === 'unit' && $unitId ? Unit::find($unitId) : null,
            'filters' => [
                'tahun' => $tahun,
                'scope' => $scope,
                'unit_id' => $unitId ? (int)$unitId : null,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Update Kaiser Permanente scores for a single assessment row
     */
    public function updateRow(Request $request, HvaAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'probabilitas' => 'required|integer|min:0|max:3',
            'dampak_manusia' => 'required|integer|min:0|max:3',
            'dampak_properti' => 'required|integer|min:0|max:3',
            'dampak_bisnis' => 'required|integer|min:0|max:3',
            'kesiapan' => 'required|integer|min:0|max:3',
            'respon_internal' => 'required|integer|min:0|max:3',
            'respon_eksternal' => 'required|integer|min:0|max:3',
        ]);

        $assessment->fill($validated);
        $assessment->computeScores();
        $assessment->save();

        // Recompute ranking for this unit/scope
        $this->recomputeRanking($assessment->tahun, $assessment->ruang_lingkup, $assessment->unit_id);

        return back()->with('success', "Penilaian HVA '{$assessment->nama_kejadian}' berhasil diperbarui.");
    }

    /**
     * Update Mitigation / Rencana Tindak Lanjut (RTL)
     */
    public function updateRtl(Request $request, HvaAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'rencana_tindak_lanjut' => 'nullable|string',
            'pj_mitigasi' => 'nullable|string|max:150',
            'target_waktu' => 'nullable|string|max:100',
            'status_mitigasi' => 'required|in:belum_mulai,proses,selesai',
        ]);

        $assessment->update($validated);

        return back()->with('success', "Rencana Tindak Lanjut '{$assessment->nama_kejadian}' berhasil disimpan.");
    }

    /**
     * Helper: Category Statistics
     */
    private function getCategoryStats(int $tahun, string $scope, ?int $unitId): array
    {
        $categories = [
            'natural' => ['label' => 'Bencana Alam', 'desc' => 'Gempa, tsunami, badai, banjir', 'color' => 'amber'],
            'technological' => ['label' => 'Kegagalan Teknologi', 'desc' => 'Listrik, air, genset, TI, api', 'color' => 'blue'],
            'human' => ['label' => 'Faktor Manusia', 'desc' => 'Korban massal, trauma, terorisme', 'color' => 'rose'],
            'hazardous_materials' => ['label' => 'Bahan Berbahaya (B3)', 'desc' => 'Tumpahan kimia, radiasi, hazmat', 'color' => 'purple'],
        ];

        $stats = [];
        foreach ($categories as $key => $meta) {
            $q = HvaAssessment::where('tahun', $tahun)
                ->where('ruang_lingkup', $scope)
                ->where('kategori', $key);

            if ($scope === 'unit' && $unitId) {
                $q->where('unit_id', $unitId);
            }

            $count = $q->count();
            $avgRisk = $count > 0 ? round($q->avg('skor_risiko_relatif'), 2) : 0;
            $maxRisk = $count > 0 ? round($q->max('skor_risiko_relatif'), 2) : 0;
            $activeCount = $q->where('skor_risiko_relatif', '>', 0)->count();

            $stats[$key] = array_merge($meta, [
                'total_events' => $count,
                'active_events' => $activeCount,
                'avg_risk' => $avgRisk,
                'max_risk' => $maxRisk,
            ]);
        }

        return $stats;
    }

    /**
     * Helper: Recompute ranking for a given scope & unit
     */
    private function recomputeRanking(int $tahun, string $scope, ?int $unitId): void
    {
        $query = HvaAssessment::where('tahun', $tahun)
            ->where('ruang_lingkup', $scope);

        if ($scope === 'unit' && $unitId) {
            $query->where('unit_id', $unitId);
        }

        $items = $query->orderByDesc('skor_risiko_relatif')->get();

        $rank = 1;
        foreach ($items as $item) {
            if ($item->skor_risiko_relatif > 0) {
                $item->peringkat_prioritas = $rank++;
            } else {
                $item->peringkat_prioritas = null;
            }
            $item->saveQuietly();
        }
    }
}

