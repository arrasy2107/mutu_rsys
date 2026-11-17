<?php

namespace App\Http\Controllers;

use App\Models\IndicatorSelection;
use App\Models\IndicatorSelectionItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\PrivateEvidenceStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndicatorSelectionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isUnitUser = $user->isPicUnit() || $user->isKepalaUnit();
        $canFilter = $user->isKomiteMutu() || $user->isSuperAdmin() || $user->isDireksi();

        $query = IndicatorSelection::query()
            ->with(['unit', 'createdBy'])
            ->withCount('items');

        if ($isUnitUser) {
            // PIC Unit & Kepala Unit hanya dapat melihat sesi pemilihan pada unit kerjanya
            $query->where('unit_id', $user->unit_id)
                ->where('tipe', 'unit');
        } else {
            // Komite Mutu, Super Admin, Direksi dapat melihat & memfilter seluruh unit / tingkat RS
            $query->when($request->unit_id, fn ($q, $u) => $q->where('unit_id', $u))
                ->when($request->tipe, fn ($q, $t) => $q->where('tipe', $t));
        }

        $query->when($request->tahun, fn ($q, $y) => $q->whereYear('tanggal', $y))
            ->latest('tanggal');

        return Inertia::render('IndicatorSelections/Index', [
            'selections' => $query->paginate(15)->withQueryString(),
            'units' => $canFilter ? Unit::aktif()->get() : ($user->unit ? [$user->unit] : []),
            'filters' => $request->only(['unit_id', 'tipe', 'tahun']),
            'canFilter' => $canFilter,
            'isUnitUser' => $isUnitUser,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $isUnitUser = $user->isPicUnit() || $user->isKepalaUnit();
        $canFilter = $user->isKomiteMutu() || $user->isSuperAdmin() || $user->isDireksi();

        $meetingLeadersQuery = User::whereIn('role', ['kepala_unit', 'pic_unit', 'komite_mutu', 'superadmin'])
            ->with('unit:id,nama')
            ->orderBy('name');

        if ($isUnitUser) {
            $meetingLeadersQuery->where(function ($q) use ($user) {
                $q->where('unit_id', $user->unit_id)
                    ->orWhereIn('role', ['komite_mutu', 'superadmin']);
            });
        }

        $meetingLeaders = $meetingLeadersQuery->get(['id', 'name', 'username', 'role', 'unit_id']);

        return Inertia::render('IndicatorSelections/Create', [
            'units' => $canFilter ? Unit::aktif()->get() : ($user->unit ? [$user->unit] : []),
            'meetingLeaders' => $meetingLeaders,
            'initialTipe' => $isUnitUser ? 'unit' : $request->query('tipe', 'unit'),
            'defaultUnitId' => $isUnitUser ? $user->unit_id : null,
            'canSelectUnit' => $canFilter,
        ]);
    }

    public function store(
        Request $request,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $user = $request->user();
        $validated = $request->validate([
            'unit_id' => 'nullable|exists:units,id',
            'tipe' => 'required|in:unit,rs',
            'tanggal' => 'required|date',
            'jam' => 'nullable|string|max:20',
            'tempat' => 'nullable|string|max:255',
            'notulen' => 'nullable|string',
            'pemimpin_rapat_id' => 'nullable|exists:users,id',
            'pemimpin_rapat_nama' => 'nullable|string|max:255',
            'status' => 'required|in:draft,final',
            'berkas' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:10240',
            'items' => 'required|array|min:1',
            'items.*.usulan_imut' => 'required|string|max:255',
            'items.*.data_pendukung' => 'nullable|string',

            // Algoritma
            'items.*.ssi_visi_misi' => 'boolean',
            'items.*.problem_prone' => 'boolean',
            'items.*.high_volume' => 'boolean',
            'items.*.high_risk_cost' => 'boolean',
            'items.*.ssi_aturan' => 'boolean',
            'items.*.dpt_dikendalikan_unit' => 'boolean',
            'items.*.tdk_dpt_dikendalikan_rs' => 'boolean',
            'items.*.dpt_diukur' => 'boolean',
            'items.*.is_lolos_skrining' => 'boolean',

            // Skoring
            'items.*.skor_masalah_banyak' => 'required|integer|min:1|max:5',
            'items.*.skor_high_volume' => 'required|integer|min:1|max:5',
            'items.*.skor_high_proses' => 'required|integer|min:1|max:5',
            'items.*.skor_ketidakpuasan' => 'required|integer|min:1|max:5',
            'items.*.skor_kemudahan_ukur' => 'required|integer|min:1|max:5',
            'items.*.skor_ketentuan_pemerintah' => 'required|integer|min:1|max:5',
            'items.*.skor_sesuai_renstra' => 'required|integer|min:1|max:5',
            'items.*.skor_patient_experience' => 'required|integer|min:1|max:5',
            'items.*.is_imp' => 'boolean',
            'items.*.is_ditetapkan' => 'boolean',
        ]);

        if ($user->isPicUnit() || $user->isKepalaUnit()) {
            $validated['unit_id'] = $user->unit_id;
            $validated['tipe'] = 'unit';
        }

        $validated['created_by'] = $user->id;

        $berkasPath = null;
        if ($request->hasFile('berkas')) {
            $berkasPath = $evidenceStorage->store($request->file('berkas'), 'berkas_pendukung_rs');
        }

        $selection = IndicatorSelection::create([
            'unit_id' => $validated['unit_id'] ?? null,
            'tipe' => $validated['tipe'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'] ?? null,
            'tempat' => $validated['tempat'] ?? null,
            'notulen' => $validated['notulen'] ?? null,
            'berkas_pendukung' => $berkasPath,
            'pemimpin_rapat_id' => $validated['pemimpin_rapat_id'] ?? null,
            'pemimpin_rapat_nama' => $validated['pemimpin_rapat_nama'] ?? null,
            'status' => $validated['status'],
            'created_by' => $validated['created_by'],
        ]);

        $impItemId = null;
        foreach ($validated['items'] as $itemData) {
            $totalSkor = (int) $itemData['skor_masalah_banyak'] +
                (int) $itemData['skor_high_volume'] +
                (int) $itemData['skor_high_proses'] +
                (int) $itemData['skor_ketidakpuasan'] +
                (int) $itemData['skor_kemudahan_ukur'] +
                (int) $itemData['skor_ketentuan_pemerintah'] +
                (int) $itemData['skor_sesuai_renstra'] +
                (int) $itemData['skor_patient_experience'];

            $isImp = ! empty($itemData['is_imp']);

            $createdItem = $selection->items()->create([
                'usulan_imut' => $itemData['usulan_imut'],
                'data_pendukung' => $itemData['data_pendukung'] ?? null,
                'ssi_visi_misi' => $itemData['ssi_visi_misi'] ?? false,
                'problem_prone' => $itemData['problem_prone'] ?? false,
                'high_volume' => $itemData['high_volume'] ?? false,
                'high_risk_cost' => $itemData['high_risk_cost'] ?? false,
                'ssi_aturan' => $itemData['ssi_aturan'] ?? false,
                'dpt_dikendalikan_unit' => $itemData['dpt_dikendalikan_unit'] ?? false,
                'tdk_dpt_dikendalikan_rs' => $itemData['tdk_dpt_dikendalikan_rs'] ?? false,
                'dpt_diukur' => $itemData['dpt_diukur'] ?? false,
                'is_lolos_skrining' => $itemData['is_ditetapkan'] ?? false,
                'skor_masalah_banyak' => $itemData['skor_masalah_banyak'],
                'skor_high_volume' => $itemData['skor_high_volume'],
                'skor_high_proses' => $itemData['skor_high_proses'],
                'skor_ketidakpuasan' => $itemData['skor_ketidakpuasan'],
                'skor_kemudahan_ukur' => $itemData['skor_kemudahan_ukur'],
                'skor_ketentuan_pemerintah' => $itemData['skor_ketentuan_pemerintah'],
                'skor_sesuai_renstra' => $itemData['skor_sesuai_renstra'],
                'skor_patient_experience' => $itemData['skor_patient_experience'],
                'total_skor' => $totalSkor,
                'is_imp' => $isImp,
                'is_ditetapkan' => $itemData['is_ditetapkan'] ?? ($isImp ? true : false),
            ]);

            if ($isImp) {
                $impItemId = $createdItem->id;
            }
        }

        if ($impItemId) {
            $selection->update(['imp_item_id' => $impItemId]);
        }

        return redirect()->route('indicator-selections.show', $selection)
            ->with('success', 'Sesi pemilihan indikator mutu berhasil disimpan.');
    }

    public function show(IndicatorSelection $indicatorSelection): Response
    {
        $user = request()->user();
        if (($user->isPicUnit() || $user->isKepalaUnit()) && $indicatorSelection->tipe === 'unit' && $indicatorSelection->unit_id !== $user->unit_id) {
            abort(403, 'Akses terbatas untuk sesi pemilihan unit Anda.');
        }

        $indicatorSelection->load(['unit', 'createdBy', 'pemimpinRapat', 'impItem', 'items.indicator']);

        $meetingLeaders = User::whereIn('role', ['kepala_unit', 'pic_unit', 'komite_mutu', 'superadmin'])
            ->with('unit:id,nama')
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'role', 'unit_id']);

        return Inertia::render('IndicatorSelections/Show', [
            'selection' => $indicatorSelection,
            'meetingLeaders' => $meetingLeaders,
        ]);
    }

    public function edit(IndicatorSelection $indicatorSelection): Response
    {
        $user = request()->user();
        $isUnitUser = $user->isPicUnit() || $user->isKepalaUnit();
        $canFilter = $user->isKomiteMutu() || $user->isSuperAdmin() || $user->isDireksi();

        if ($isUnitUser && ($indicatorSelection->unit_id !== $user->unit_id || $indicatorSelection->tipe === 'rs')) {
            abort(403, 'Anda hanya berhak mengelola sesi pemilihan indikator mutu unit kerja Anda.');
        }

        $indicatorSelection->load(['items', 'pemimpinRapat', 'impItem']);

        $meetingLeaders = User::whereIn('role', ['kepala_unit', 'pic_unit', 'komite_mutu', 'superadmin'])
            ->with('unit:id,nama')
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'role', 'unit_id']);

        return Inertia::render('IndicatorSelections/Edit', [
            'selection' => $indicatorSelection,
            'units' => $canFilter ? Unit::aktif()->get() : ($user->unit ? [$user->unit] : []),
            'meetingLeaders' => $meetingLeaders,
            'canSelectUnit' => $canFilter,
        ]);
    }

    public function update(
        Request $request,
        IndicatorSelection $indicatorSelection,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $user = $request->user();
        $isUnitUser = $user->isPicUnit() || $user->isKepalaUnit();

        if ($isUnitUser && ($indicatorSelection->unit_id !== $user->unit_id || $indicatorSelection->tipe === 'rs')) {
            abort(403, 'Anda hanya berhak memperbarui sesi pemilihan indikator mutu unit kerja Anda.');
        }

        $validated = $request->validate([
            'unit_id' => 'nullable|exists:units,id',
            'tipe' => 'required|in:unit,rs',
            'tanggal' => 'required|date',
            'jam' => 'nullable|string|max:20',
            'tempat' => 'nullable|string|max:255',
            'notulen' => 'nullable|string',
            'pemimpin_rapat_id' => 'nullable|exists:users,id',
            'pemimpin_rapat_nama' => 'nullable|string|max:255',
            'status' => 'required|in:draft,final',
            'berkas' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:10240',
            'hapus_berkas' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.usulan_imut' => 'required|string|max:255',
            'items.*.data_pendukung' => 'nullable|string',

            // Algoritma
            'items.*.ssi_visi_misi' => 'boolean',
            'items.*.problem_prone' => 'boolean',
            'items.*.high_volume' => 'boolean',
            'items.*.high_risk_cost' => 'boolean',
            'items.*.ssi_aturan' => 'boolean',
            'items.*.dpt_dikendalikan_unit' => 'boolean',
            'items.*.tdk_dpt_dikendalikan_rs' => 'boolean',
            'items.*.dpt_diukur' => 'boolean',
            'items.*.is_lolos_skrining' => 'boolean',

            // Skoring
            'items.*.skor_masalah_banyak' => 'required|integer|min:1|max:5',
            'items.*.skor_high_volume' => 'required|integer|min:1|max:5',
            'items.*.skor_high_proses' => 'required|integer|min:1|max:5',
            'items.*.skor_ketidakpuasan' => 'required|integer|min:1|max:5',
            'items.*.skor_kemudahan_ukur' => 'required|integer|min:1|max:5',
            'items.*.skor_ketentuan_pemerintah' => 'required|integer|min:1|max:5',
            'items.*.skor_sesuai_renstra' => 'required|integer|min:1|max:5',
            'items.*.skor_patient_experience' => 'required|integer|min:1|max:5',
            'items.*.is_imp' => 'boolean',
            'items.*.is_ditetapkan' => 'boolean',
        ]);

        if ($isUnitUser) {
            $validated['unit_id'] = $user->unit_id;
            $validated['tipe'] = 'unit';
        }

        $updateData = [
            'unit_id' => $validated['unit_id'] ?? null,
            'tipe' => $validated['tipe'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'] ?? null,
            'tempat' => $validated['tempat'] ?? null,
            'notulen' => $validated['notulen'] ?? null,
            'pemimpin_rapat_id' => $validated['pemimpin_rapat_id'] ?? null,
            'pemimpin_rapat_nama' => $validated['pemimpin_rapat_nama'] ?? null,
            'status' => $validated['status'],
        ];

        $previousFilePath = null;
        if (! empty($validated['hapus_berkas'])) {
            $previousFilePath = $indicatorSelection->berkas_pendukung;
            $updateData['berkas_pendukung'] = null;
        } elseif ($request->hasFile('berkas')) {
            $previousFilePath = $indicatorSelection->berkas_pendukung;
            $updateData['berkas_pendukung'] = $evidenceStorage->store(
                $request->file('berkas'),
                'berkas_pendukung_rs',
            );
        }

        $indicatorSelection->update($updateData);
        if ($previousFilePath) {
            $evidenceStorage->delete($previousFilePath);
        }

        // Reconcile items
        $existingItemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
        $indicatorSelection->items()->whereNotIn('id', $existingItemIds)->delete();

        $impItemId = null;
        foreach ($validated['items'] as $itemData) {
            $totalSkor = (int) $itemData['skor_masalah_banyak'] +
                (int) $itemData['skor_high_volume'] +
                (int) $itemData['skor_high_proses'] +
                (int) $itemData['skor_ketidakpuasan'] +
                (int) $itemData['skor_kemudahan_ukur'] +
                (int) $itemData['skor_ketentuan_pemerintah'] +
                (int) $itemData['skor_sesuai_renstra'] +
                (int) $itemData['skor_patient_experience'];

            $isImp = ! empty($itemData['is_imp']);

            $payload = [
                'usulan_imut' => $itemData['usulan_imut'],
                'data_pendukung' => $itemData['data_pendukung'] ?? null,
                'ssi_visi_misi' => $itemData['ssi_visi_misi'] ?? false,
                'problem_prone' => $itemData['problem_prone'] ?? false,
                'high_volume' => $itemData['high_volume'] ?? false,
                'high_risk_cost' => $itemData['high_risk_cost'] ?? false,
                'ssi_aturan' => $itemData['ssi_aturan'] ?? false,
                'dpt_dikendalikan_unit' => $itemData['dpt_dikendalikan_unit'] ?? false,
                'tdk_dpt_dikendalikan_rs' => $itemData['tdk_dpt_dikendalikan_rs'] ?? false,
                'dpt_diukur' => $itemData['dpt_diukur'] ?? false,
                'is_lolos_skrining' => $itemData['is_ditetapkan'] ?? false,
                'skor_masalah_banyak' => $itemData['skor_masalah_banyak'],
                'skor_high_volume' => $itemData['skor_high_volume'],
                'skor_high_proses' => $itemData['skor_high_proses'],
                'skor_ketidakpuasan' => $itemData['skor_ketidakpuasan'],
                'skor_kemudahan_ukur' => $itemData['skor_kemudahan_ukur'],
                'skor_ketentuan_pemerintah' => $itemData['skor_ketentuan_pemerintah'],
                'skor_sesuai_renstra' => $itemData['skor_sesuai_renstra'],
                'skor_patient_experience' => $itemData['skor_patient_experience'],
                'total_skor' => $totalSkor,
                'is_imp' => $isImp,
                'is_ditetapkan' => $itemData['is_ditetapkan'] ?? ($isImp ? true : false),
            ];

            if (! empty($itemData['id'])) {
                $indicatorSelection->items()->where('id', $itemData['id'])->update($payload);
                $itemId = $itemData['id'];
            } else {
                $created = $indicatorSelection->items()->create($payload);
                $itemId = $created->id;
            }

            if ($isImp) {
                $impItemId = $itemId;
            }
        }

        $indicatorSelection->update(['imp_item_id' => $impItemId]);

        return redirect()->route('indicator-selections.show', $indicatorSelection)
            ->with('success', 'Sesi pemilihan indikator mutu berhasil diperbarui.');
    }

    public function destroy(
        IndicatorSelection $indicatorSelection,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $user = request()->user();
        if (($user->isPicUnit() || $user->isKepalaUnit()) && ($indicatorSelection->unit_id !== $user->unit_id || $indicatorSelection->tipe === 'rs')) {
            abort(403, 'Anda hanya berhak menghapus sesi pemilihan unit kerja Anda.');
        }

        $evidenceStorage->delete($indicatorSelection->berkas_pendukung);

        $indicatorSelection->delete();

        return redirect()->route('indicator-selections.index')
            ->with('success', 'Sesi pemilihan indikator mutu berhasil dihapus.');
    }

    public function createProfile(IndicatorSelectionItem $item)
    {
        // Redirect to indicators.create with prefilled data
        return redirect()->route('indicators.create', [
            'prefill_judul' => $item->usulan_imut,
            'prefill_dasar_pemikiran' => $item->data_pendukung,
            'prefill_unit_id' => $item->selection?->unit_id,
            'prefill_jenis' => $item->selection?->tipe === 'rs' ? 'IMP-RS' : ($item->is_imp ? 'IMP-U' : 'IMU'),
            'selection_item_id' => $item->id,
        ]);
    }

    public function uploadBerkas(
        Request $request,
        IndicatorSelection $indicatorSelection,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $this->authorizeEvidenceManagement($request->user(), $indicatorSelection);

        $request->validate([
            'berkas' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('berkas')) {
            $path = $evidenceStorage->store($request->file('berkas'), 'berkas_pendukung_rs');
            $previousFilePath = $indicatorSelection->berkas_pendukung;
            $indicatorSelection->update(['berkas_pendukung' => $path]);
            if ($previousFilePath) {
                $evidenceStorage->delete($previousFilePath);
            }
        }

        return back()->with('success', 'Berkas data pendukung berhasil diunggah.');
    }

    public function deleteBerkas(
        Request $request,
        IndicatorSelection $indicatorSelection,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $this->authorizeEvidenceManagement($request->user(), $indicatorSelection);

        $evidenceStorage->delete($indicatorSelection->berkas_pendukung);

        $indicatorSelection->update(['berkas_pendukung' => null]);

        return back()->with('success', 'Berkas pendukung berhasil dihapus.');
    }

    private function authorizeEvidenceManagement(User $user, IndicatorSelection $selection): void
    {
        if ($user->isPicUnit() || $user->isKepalaUnit()) {
            abort_if(
                $selection->tipe !== 'unit' ||
                    (int) $selection->unit_id !== (int) $user->unit_id,
                404
            );

            return;
        }

        abort_unless(
            $user->isSuperAdmin() || $user->isKomiteMutu() || $user->isDireksi(),
            404
        );
    }

    /**
     * Export Dokumen Resmi Rekapitulasi Pemilihan Indikator Mutu (PDF)
     * Mendukung mode download langsung atau stream/preview di browser
     */
    public function exportPdf(IndicatorSelection $indicatorSelection, Request $request)
    {
        $indicatorSelection->load(['unit', 'createdBy', 'pemimpinRapat', 'items.indicator']);

        $pdf = Pdf::loadView('reports.rekapitulasi-pemilihan', [
            'selection' => $indicatorSelection,
        ])->setPaper('a4', 'landscape');

        $unitName = $indicatorSelection->unit ? str_replace(' ', '_', $indicatorSelection->unit->nama) : 'RS';
        $tanggal = Carbon::parse($indicatorSelection->tanggal)->format('Ymd');
        $filename = "Rekapitulasi_Pemilihan_{$unitName}_{$tanggal}.pdf";

        if ($request->boolean('stream') || $request->has('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Export Lembar Kerja Pemilihan Indikator Mutu (Excel Format Sheet PEMILIHAN)
     */
    public function exportExcel(IndicatorSelection $indicatorSelection): StreamedResponse
    {
        $indicatorSelection->load(['unit', 'createdBy', 'pemimpinRapat', 'items.indicator']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PEMILIHAN');

        // Font default profesional dan terbaca rapi
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(9.5);

        // Konfigurasi Print & Halaman A4 Landscape
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->setShowGridLines(true);
        $sheet->setPrintGridLines(true);

        // Lebar kolom yang konsisten, proporsional, dan sejajar antar-tabel
        $columnWidths = [
            'A' => 4.5,   // No
            'B' => 30.0,  // Usulan Indikator Mutu
            'C' => 30.0,  // Data Pendukung Masalah
            'D' => 9.0,   // Kriteria 1
            'E' => 9.0,   // Kriteria 2
            'F' => 9.0,   // Kriteria 3
            'G' => 9.0,   // Kriteria 4
            'H' => 9.0,   // Kriteria 5
            'I' => 9.0,   // Kriteria 6
            'J' => 9.0,   // Kriteria 7
            'K' => 9.0,   // Kriteria 8
            'L' => 11.5,  // Penetapan / Total Skor
            'M' => 15.0,  // Peringkat
        ];

        foreach ($columnWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // 1. KOP RESMI RSYS
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'RUMAH SAKIT YOS SUDARSO PADANG');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF1E3A8A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'KOMITE MUTU & KESELAMATAN PASIEN');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:M3');
        $sheet->setCellValue('A3', 'Jl. Situjuh No. 1, Padang, Sumatera Barat | Telp: (0751) 33230 | Sistem Indikator Mutu RS (MUTU RSYS)');
        $sheet->getStyle('A3')->getFont()->setSize(8.5)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Garis pemisah kop
        $sheet->getStyle('A3:M3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF1E3A8A');

        // Judul Dokumen
        $sheet->mergeCells('A5:M5');
        $unitNama = $indicatorSelection->unit ? $indicatorSelection->unit->nama : 'RUMAH SAKIT';
        $sheet->setCellValue('A5', 'BERITA ACARA & REKAPITULASI PEMILIHAN INDIKATOR MUTU');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $tahunDoc = Carbon::parse($indicatorSelection->tanggal)->format('Y');
        $sheet->mergeCells('A6:M6');
        $subJudul = $indicatorSelection->tipe === 'rs'
            ? 'TINGKAT RUMAH SAKIT (IMP-RS) TAHUN '.$tahunDoc
            : 'TINGKAT UNIT: '.strtoupper($unitNama).' TAHUN '.$tahunDoc;
        $sheet->setCellValue('A6', $subJudul);
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF2563EB');
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. META DATA SESI PERTEMUAN (Row 8 - 10)
        $sheet->mergeCells('A8:B8');
        $sheet->setCellValue('A8', 'Unit / Pelaksana :');
        $sheet->mergeCells('C8:F8');
        $sheet->setCellValue('C8', $indicatorSelection->unit ? $indicatorSelection->unit->nama : 'Tingkat Rumah Sakit (Komite Mutu / Direksi)');
        $sheet->mergeCells('G8:H8');
        $sheet->setCellValue('G8', 'Hari, Tanggal :');
        $sheet->mergeCells('I8:M8');
        $sheet->setCellValue('I8', Carbon::parse($indicatorSelection->tanggal)->translatedFormat('l, d F Y'));

        $sheet->mergeCells('A9:B9');
        $sheet->setCellValue('A9', 'Waktu Sesi :');
        $jamClean = $indicatorSelection->jam ? trim(str_ireplace('wib', '', $indicatorSelection->jam)).' WIB' : '-';
        $sheet->mergeCells('C9:F9');
        $sheet->setCellValue('C9', $jamClean);
        $sheet->mergeCells('G9:H9');
        $sheet->setCellValue('G9', 'Tempat Sesi :');
        $sheet->mergeCells('I9:M9');
        $sheet->setCellValue('I9', $indicatorSelection->tempat ?: '-');

        $sheet->mergeCells('A10:B10');
        $sheet->setCellValue('A10', 'Pemimpin Rapat :');
        $sheet->mergeCells('C10:F10');
        $sheet->setCellValue('C10', $indicatorSelection->pemimpinRapat?->name ?? $indicatorSelection->pemimpin_rapat_nama ?? '-');
        $sheet->mergeCells('G10:H10');
        $sheet->setCellValue('G10', 'Pencatat / Pembuat :');
        $sheet->mergeCells('I10:M10');
        $sheet->setCellValue('I10', $indicatorSelection->createdBy?->name ?? '-');

        // Style Meta Table
        $sheet->getStyle('A8:M10')->getFont()->setSize(8.5);
        $sheet->getStyle('A8:B10')->getFont()->setBold(true)->getColor()->setARGB('FF475569');
        $sheet->getStyle('G8:H10')->getFont()->setBold(true)->getColor()->setARGB('FF475569');
        $sheet->getStyle('C8:F10')->getFont()->setBold(true)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('I8:M10')->getFont()->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('A8:M10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
        $sheet->getStyle('A8:M10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('FFE2E8F0');
        $sheet->getStyle('A8:M10')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

        // 3. TABEL 1: SKRINING ALGORITMA PEMILIHAN
        $row = 12;
        $sheet->mergeCells("A{$row}:M{$row}");
        $sheet->setCellValue("A{$row}", 'I. Skrining Algoritma Pemilihan Indikator Mutu (Tahap 1, 2, dan 3)');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9.5)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $row++;
        $hStart = $row;
        $sheet->setCellValue("A{$row}", 'No');
        $sheet->mergeCells("A{$row}:A".($row + 2));

        $sheet->setCellValue("B{$row}", 'Usulan Indikator Mutu');
        $sheet->mergeCells("B{$row}:B".($row + 2));

        $sheet->setCellValue("C{$row}", 'Data Pendukung Masalah');
        $sheet->mergeCells("C{$row}:C".($row + 2));

        $sheet->setCellValue("D{$row}", 'Algoritma Pemilihan ( Checklist Skrining )');
        $sheet->mergeCells("D{$row}:K{$row}");

        $sheet->setCellValue("L{$row}", 'Penetapan');
        $sheet->mergeCells("L{$row}:M".($row + 1));

        // Sub-header Baris 2
        $r2 = $row + 1;
        $sheet->setCellValue("D{$r2}", 'Tahap 1: Skrining Kriteria');
        $sheet->mergeCells("D{$r2}:H{$r2}");

        $sheet->setCellValue("I{$r2}", 'Tahap 2: Batas Kendali');
        $sheet->mergeCells("I{$r2}:J{$r2}");

        $sheet->setCellValue("K{$r2}", 'Tahap 3');

        // Sub-header Baris 3
        $r3 = $row + 2;
        $sheet->setCellValue("D{$r3}", "Sesuai\nVisi Misi");
        $sheet->setCellValue("E{$r3}", "Problem\nProne");
        $sheet->setCellValue("F{$r3}", "High\nVolume");
        $sheet->setCellValue("G{$r3}", "High Risk/\nHigh Cost");
        $sheet->setCellValue("H{$r3}", "Sesuai\nAturan");
        $sheet->setCellValue("I{$r3}", $indicatorSelection->tipe === 'rs' ? "Kendali\nRS" : "Dpt Kendali\nUnit");
        $sheet->setCellValue("J{$r3}", $indicatorSelection->tipe === 'rs' ? "Regulasi\nRS" : "Tdk Kendali\nRS");
        $sheet->setCellValue("K{$r3}", "Dapat\nDiukur");
        $sheet->setCellValue("L{$r3}", 'Ditetapkan');
        $sheet->mergeCells("L{$r3}:M{$r3}");

        // Styling Header Tabel 1
        $sheet->getStyle("A{$hStart}:M{$r3}")->getFont()->setBold(true)->setSize(8.5);
        $sheet->getStyle("A{$hStart}:M{$r3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$hStart}:M{$r3}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $sheet->getStyle("D{$hStart}:K{$hStart}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("L{$hStart}:M{$r3}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
        $sheet->getStyle("L{$hStart}:M{$r3}")->getFont()->getColor()->setARGB('FF166534');
        $sheet->getRowDimension($r3)->setRowHeight(28);

        $row = $r3 + 1;
        foreach ($indicatorSelection->items as $idx => $item) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $item->usulan_imut);
            $sheet->setCellValue("C{$row}", $item->data_pendukung ?: '-');
            $sheet->setCellValue("D{$row}", $item->ssi_visi_misi ? 'v' : '-');
            $sheet->setCellValue("E{$row}", $item->problem_prone ? 'v' : '-');
            $sheet->setCellValue("F{$row}", $item->high_volume ? 'v' : '-');
            $sheet->setCellValue("G{$row}", $item->high_risk_cost ? 'v' : '-');
            $sheet->setCellValue("H{$row}", $item->ssi_aturan ? 'v' : '-');
            $sheet->setCellValue("I{$row}", $item->dpt_dikendalikan_unit ? 'v' : '-');
            $sheet->setCellValue("J{$row}", $item->tdk_dpt_dikendalikan_rs ? 'v' : '-');
            $sheet->setCellValue("K{$row}", $item->dpt_diukur ? 'v' : '-');

            $sheet->mergeCells("L{$row}:M{$row}");
            $sheet->setCellValue("L{$row}", $item->is_ditetapkan ? 'Ditetapkan' : 'Tidak');

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle("C{$row}")->getFont()->getColor()->setARGB('FF334155');
            $sheet->getStyle("D{$row}:K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Format Checklist
            foreach (range('D', 'K') as $cCol) {
                if ($sheet->getCell("{$cCol}{$row}")->getValue() === 'v') {
                    $sheet->getStyle("{$cCol}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("{$cCol}{$row}")->getFont()->getColor()->setARGB('FF16A34A');
                } else {
                    $sheet->getStyle("{$cCol}{$row}")->getFont()->getColor()->setARGB('FF94A3B8');
                }
            }

            if ($item->is_ditetapkan) {
                $sheet->getStyle("L{$row}:M{$row}")->getFont()->setBold(true);
                $sheet->getStyle("L{$row}:M{$row}")->getFont()->getColor()->setARGB('FF15803D');
                $sheet->getStyle("L{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0FDF4');
            } else {
                $sheet->getStyle("L{$row}:M{$row}")->getFont()->getColor()->setARGB('FF64748B');
            }

            $row++;
        }

        $sheet->getStyle("A{$hStart}:M".($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

        // 4. TABEL 2: MATRIKS SKORING PRIORITAS
        $row += 1;
        $sheet->mergeCells("A{$row}:M{$row}");
        $sheet->setCellValue("A{$row}", 'II. Matriks Skoring Prioritas Indikator Mutu (Skala 1 - 5)');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9.5)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $row++;
        $hStart2 = $row;
        $sheet->setCellValue("A{$row}", 'No');
        $sheet->mergeCells("A{$row}:A".($row + 1));

        $sheet->setCellValue("B{$row}", 'Topik Usulan Indikator Mutu');
        $sheet->mergeCells("B{$row}:C".($row + 1));

        $sheet->setCellValue("D{$row}", 'Kriteria Matriks Skoring Prioritas (Skala 1 - 5)');
        $sheet->mergeCells("D{$row}:K{$row}");

        $sheet->setCellValue("L{$row}", 'Total Skor');
        $sheet->mergeCells("L{$row}:L".($row + 1));

        $sheet->setCellValue("M{$row}", 'Peringkat');
        $sheet->mergeCells("M{$row}:M".($row + 1));

        $r2_2 = $row + 1;
        $sheet->setCellValue("D{$r2_2}", "K1: Masalah\nBanyak di RS");
        $sheet->setCellValue("E{$r2_2}", "K2: High\nVolume");
        $sheet->setCellValue("F{$r2_2}", "K3: High\nProses");
        $sheet->setCellValue("G{$r2_2}", "K4: Ketidak-\npuasan");
        $sheet->setCellValue("H{$r2_2}", "K5: Mudah\nDiukur");
        $sheet->setCellValue("I{$r2_2}", "K6: Regulasi/\nPemerintah");
        $sheet->setCellValue("J{$r2_2}", "K7: Sesuai\nRenstra RS");
        $sheet->setCellValue("K{$r2_2}", "K8: Patient\nExperience");

        // Styling Header Tabel 2
        $sheet->getStyle("A{$hStart2}:M{$r2_2}")->getFont()->setBold(true)->setSize(8.5);
        $sheet->getStyle("A{$hStart2}:M{$r2_2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$hStart2}:M{$r2_2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $sheet->getStyle("D{$hStart2}:K{$hStart2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
        $sheet->getStyle("D{$r2_2}:K{$r2_2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFBEB');
        $sheet->getStyle("L{$hStart2}:L{$r2_2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDBEAFE');
        $sheet->getStyle("M{$hStart2}:M{$r2_2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getRowDimension($r2_2)->setRowHeight(28);

        $row = $r2_2 + 1;
        $sortedItems = $indicatorSelection->items->sortByDesc('total_skor')->values();
        foreach ($sortedItems as $idx => $item) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->mergeCells("B{$row}:C{$row}");
            $sheet->setCellValue("B{$row}", $item->usulan_imut);
            $sheet->setCellValue("D{$row}", $item->skor_masalah_banyak);
            $sheet->setCellValue("E{$row}", $item->skor_high_volume);
            $sheet->setCellValue("F{$row}", $item->skor_high_proses);
            $sheet->setCellValue("G{$row}", $item->skor_ketidakpuasan);
            $sheet->setCellValue("H{$row}", $item->skor_kemudahan_ukur);
            $sheet->setCellValue("I{$row}", $item->skor_ketentuan_pemerintah);
            $sheet->setCellValue("J{$row}", $item->skor_sesuai_renstra);
            $sheet->setCellValue("K{$row}", $item->skor_patient_experience);
            $sheet->setCellValue("L{$row}", $item->total_skor);
            $sheet->setCellValue("M{$row}", $idx === 0 ? 'Prioritas 1 (IMP)' : 'Prioritas '.($idx + 1));

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $sheet->getStyle("D{$row}:K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("L{$row}")->getFont()->setBold(true);
            $sheet->getStyle("L{$row}")->getFont()->getColor()->setARGB('FF1E3A8A');
            $sheet->getStyle("L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');
            $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            if ($idx === 0) {
                $sheet->getStyle("M{$row}")->getFont()->setBold(true);
                $sheet->getStyle("M{$row}")->getFont()->getColor()->setARGB('FF1D4ED8');
            }

            $row++;
        }

        $sheet->getStyle("A{$hStart2}:M".($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

        // 5. BAGIAN III: KESIMPULAN HASIL PENETAPAN
        $row += 1;
        $sheet->mergeCells("A{$row}:M{$row}");
        $sheet->setCellValue("A{$row}", 'III. Kesimpulan Hasil Penetapan Indikator Mutu');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9.5)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $row++;
        $impItem = $indicatorSelection->items->firstWhere('is_imp', true) ?? $sortedItems->first();
        $imuItems = $indicatorSelection->items->filter(fn ($it) => $it->id !== $impItem?->id)->values();

        // Baris IMP
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", '1. INDIKATOR MUTU PRIORITAS ('.($indicatorSelection->tipe === 'rs' ? 'IMP-RS' : 'IMP-U').') :');
        $sheet->mergeCells("D{$row}:M{$row}");
        $sheet->setCellValue("D{$row}", ($impItem?->usulan_imut ?? '(Belum Ditetapkan)').'  [ Total Skor: '.($impItem?->total_skor ?? 0).' Poin ]');

        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(8.5)->getColor()->setARGB('FF1E3A8A');
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF1D4ED8');
        $sheet->getStyle("D{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');
        $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFDBFE');
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;
        // Baris IMU
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", '2. INDIKATOR MUTU UNIT (IMU / PENDUKUNG) :');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(8.5)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($imuItems->count() > 0) {
            foreach ($imuItems as $iIdx => $imu) {
                if ($iIdx > 0) {
                    $row++;
                    $sheet->mergeCells("A{$row}:C{$row}");
                }
                $sheet->mergeCells("D{$row}:M{$row}");
                $sheet->setCellValue("D{$row}", ($iIdx + 1).'. '.$imu->usulan_imut.'  [ Skor: '.$imu->total_skor.' Poin ]');
                $sheet->getStyle("D{$row}")->getFont()->setBold(true)->setSize(8.5)->getColor()->setARGB('FF334155');
                $sheet->getStyle("D{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
                $sheet->getRowDimension($row)->setRowHeight(20);
            }
        } else {
            $sheet->mergeCells("D{$row}:M{$row}");
            $sheet->setCellValue("D{$row}", '- Tidak ada usulan IMU pendukung lainnya -');
            $sheet->getStyle("D{$row}")->getFont()->setItalic(true)->setSize(8.5)->getColor()->setARGB('FF94A3B8');
            $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        // Baris Notulen
        $row++;
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'Catatan / Notulen Rapat :');
        $sheet->mergeCells("D{$row}:M{$row}");
        $sheet->setCellValue("D{$row}", $indicatorSelection->notulen ?: '-');

        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(8.5)->getColor()->setARGB('FF475569');
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$row}")->getFont()->setSize(8.5)->getColor()->setARGB('FF334155');
        $sheet->getStyle("D{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        $sheet->getRowDimension($row)->setRowHeight(24);

        // 6. TANDA TANGAN RESMI
        $row += 2;
        $sheet->mergeCells("B{$row}:E{$row}");
        $sheet->setCellValue("B{$row}", 'Pemimpin Sesi Pemilihan,');
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(9);

        $sheet->mergeCells("I{$row}:L{$row}");
        $sheet->setCellValue("I{$row}", $indicatorSelection->tipe === 'rs' ? 'Ketua Komite Mutu RSYS,' : 'Kepala Unit / PJ Mutu,');
        $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$row}")->getFont()->setBold(true)->setSize(9);

        $row += 3;
        $sheet->getRowDimension($row - 1)->setRowHeight(35);

        $sheet->mergeCells("B{$row}:E{$row}");
        $pemimpinNama = $indicatorSelection->pemimpinRapat?->name ?? $indicatorSelection->pemimpin_rapat_nama ?? '( ................................................ )';
        $sheet->setCellValue("B{$row}", $pemimpinNama);
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setUnderline(true)->setSize(9);

        $sheet->mergeCells("I{$row}:L{$row}");
        $createdByName = $indicatorSelection->createdBy?->name ?? '( ................................................ )';
        $sheet->setCellValue("I{$row}", $createdByName);
        $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$row}")->getFont()->setBold(true)->setUnderline(true)->setSize(9);

        $row++;
        $sheet->mergeCells("B{$row}:E{$row}");
        $sheet->setCellValue("B{$row}", $indicatorSelection->pemimpinRapat ? ($indicatorSelection->pemimpinRapat->role === 'kepala_unit' ? 'Kepala Unit' : 'PIC Unit') : 'Pemimpin Rapat');
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}")->getFont()->setSize(8)->getColor()->setARGB('FF64748B');

        $sheet->mergeCells("I{$row}:L{$row}");
        $sheet->setCellValue("I{$row}", $indicatorSelection->tipe === 'rs' ? 'Komite Mutu & Keselamatan Pasien' : ($indicatorSelection->unit?->nama ?? 'Unit Kerja'));
        $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$row}")->getFont()->setSize(8)->getColor()->setARGB('FF64748B');

        // 7. FOOTNOTE
        $row += 2;
        $sheet->mergeCells("A{$row}:M{$row}");
        $sheet->setCellValue("A{$row}", 'Dicetak otomatis melalui Sistem Indikator Mutu RS (MUTU RSYS) pada '.Carbon::now()->translatedFormat('d F Y H:i:s').' WIB');
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(7.5)->getColor()->setARGB('FF94A3B8');

        $unitClean = $indicatorSelection->unit ? str_replace(' ', '_', $indicatorSelection->unit->nama) : 'RS';
        $tanggalClean = Carbon::parse($indicatorSelection->tanggal)->format('Ymd');
        $filename = "Rekapitulasi_Pemilihan_{$unitClean}_{$tanggalClean}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Rekapitulasi Seluruh Sesi Pemilihan (Excel)
     */
    public function exportRekapExcel(Request $request): StreamedResponse
    {
        $user = $request->user();
        $isUnitUser = $user->isPicUnit() || $user->isKepalaUnit();

        $selections = IndicatorSelection::query()
            ->with(['unit', 'createdBy', 'pemimpinRapat', 'items']);

        if ($isUnitUser) {
            $selections->where('unit_id', $user->unit_id)
                ->where('tipe', 'unit');
        } else {
            $selections->when($request->unit_id, fn ($q, $u) => $q->where('unit_id', $u))
                ->when($request->tipe, fn ($q, $t) => $q->where('tipe', $t));
        }

        $selections = $selections->when($request->tahun, fn ($q, $y) => $q->whereYear('tanggal', $y))
            ->orderBy('tanggal', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Sesi Pemilihan');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->setShowGridLines(true);
        $sheet->setPrintGridLines(true);

        $sheet->setCellValue('A1', 'REKAPITULASI SESI PEMILIHAN INDIKATOR MUTU');
        $sheet->setCellValue('A2', 'RUMAH SAKIT YOS SUDARSO PADANG (MUTU RSYS)');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Tingkat');
        $sheet->setCellValue('C4', 'Unit Kerja');
        $sheet->setCellValue('D4', 'Tanggal');
        $sheet->setCellValue('E4', 'Jam');
        $sheet->setCellValue('F4', 'Tempat');
        $sheet->setCellValue('G4', 'Pemimpin Rapat');
        $sheet->setCellValue('H4', 'Status');
        $sheet->setCellValue('I4', 'Jml Usulan');
        $sheet->setCellValue('J4', 'Indikator Prioritas (IMP)');
        $sheet->setCellValue('K4', 'Indikator Mutu Unit (IMU)');

        $sheet->getStyle('A4:K4')->getFont()->setBold(true);
        $sheet->getStyle('A4:K4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle('A4:K4')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A4:K4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;
        foreach ($selections as $idx => $sel) {
            $impItem = $sel->items->firstWhere('is_imp', true);
            $imuItems = $sel->items->filter(fn ($it) => $it->id !== $impItem?->id)->pluck('usulan_imut')->join(', ');

            $sheet->setCellValue('A'.$row, $idx + 1);
            $sheet->setCellValue('B'.$row, $sel->tipe === 'rs' ? 'Rumah Sakit (IMP-RS)' : 'Tingkat Unit');
            $sheet->setCellValue('C'.$row, $sel->unit?->nama ?? 'Rumah Sakit');
            $sheet->setCellValue('D'.$row, Carbon::parse($sel->tanggal)->translatedFormat('d/m/Y'));
            $sheet->setCellValue('E'.$row, $sel->jam ?: '-');
            $sheet->setCellValue('F'.$row, $sel->tempat ?: '-');
            $sheet->setCellValue('G'.$row, $sel->pemimpinRapat?->name ?? $sel->pemimpin_rapat_nama ?? '-');
            $sheet->setCellValue('H'.$row, strtoupper($sel->status));
            $sheet->setCellValue('I'.$row, $sel->items->count());
            $sheet->setCellValue('J'.$row, $impItem?->usulan_imut ?? '-');
            $sheet->setCellValue('K'.$row, $imuItems ?: '-');

            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.$row.':E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H'.$row.':I'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $sheet->getStyle('A4:K'.($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF94A3B8');

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Rekapitulasi_Seluruh_Pemilihan_IMUT_'.date('Ymd_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
