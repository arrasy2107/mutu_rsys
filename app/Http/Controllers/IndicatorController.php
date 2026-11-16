<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\Unit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndicatorController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Cek apakah user terkunci pada unit tertentu (PIC Unit / Kepala Ruangan)
        $isUnitScoped = in_array($user->role, ['pic_unit', 'kepala_unit']) && !empty($user->unit_id);

        $selectedUnitId = $request->query('unit_id');
        if ($selectedUnitId === null || $selectedUnitId === '') {
            if ($isUnitScoped) {
                $selectedUnitId = (string) $user->unit_id;
            } else {
                $selectedUnitId = 'all'; // Default untuk Superadmin, Komite Mutu, Direksi
            }
        }

        // Jika user hanya berhak pada unitnya sendiri, paksa unit_id ke miliknya
        if ($isUnitScoped) {
            $selectedUnitId = (string) $user->unit_id;
        }

        // Daftar unit dengan jumlah indikator aktif
        $units = Unit::aktif()
            ->withCount(['indicators' => fn($q) => $q->where('is_active', true)])
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        // Jumlah indikator tingkat RS / Korporat (unit_id null)
        $rsCount = Indicator::whereNull('unit_id')->where('is_active', true)->count();

        // Hitung ringkasan jenis indikator pada unit terpilih
        $summaryQuery = Indicator::query()->where('is_active', true);
        if ($selectedUnitId !== 'all') {
            if ($selectedUnitId === 'rs') {
                $summaryQuery->whereNull('unit_id');
            } else {
                $summaryQuery->where('unit_id', $selectedUnitId);
            }
        }

        $summaryCounts = [
            'all'    => (clone $summaryQuery)->count(),
            'imn'    => (clone $summaryQuery)->whereJsonContains('jenis', 'IMN')->count(),
            'imp_rs' => (clone $summaryQuery)->whereJsonContains('jenis', 'IMP-RS')->count(),
            'imp_u'  => (clone $summaryQuery)->whereJsonContains('jenis', 'IMP-U')->count(),
            'imu'    => (clone $summaryQuery)->whereJsonContains('jenis', 'IMU')->count(),
            'spm'    => (clone $summaryQuery)->whereJsonContains('jenis', 'SPM')->count(),
        ];

        // Query data indikator
        $query = Indicator::query()->with(['unit']);

        if ($selectedUnitId !== 'all') {
            if ($selectedUnitId === 'rs') {
                $query->whereNull('unit_id');
            } else {
                $query->where('unit_id', $selectedUnitId);
            }
        }

        // Filter pencarian
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('judul', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('definisi_operasional', 'like', "%{$search}%");
            });
        });

        // Filter Jenis (IMN, IMP-RS, IMP-U, IMU, SPM)
        $selectedJenis = $request->query('jenis', 'all');
        if ($selectedJenis && $selectedJenis !== 'all') {
            $query->whereJsonContains('jenis', $selectedJenis);
        }

        // Filter Tipe / Jenis Indikator (Input, Proses, Output, Outcome)
        $query->when($request->jenis_indikator, fn($q, $t) => 
            $q->where(fn($sub) => $sub->whereJsonContains('jenis_indikator', $t)->orWhere('jenis_indikator', 'like', "%{$t}%"))
        );

        // Filter status aktif
        $query->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')));

        $indicators = $query->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        $activeUnit = is_numeric($selectedUnitId) ? Unit::find($selectedUnitId) : null;

        return Inertia::render('Indicators/Index', [
            'indicators'     => $indicators,
            'units'          => $units,
            'rsCount'        => $rsCount,
            'summaryCounts'  => $summaryCounts,
            'selectedUnitId' => (string) $selectedUnitId,
            'activeUnit'     => $activeUnit,
            'isUnitScoped'   => $isUnitScoped,
            'filters'        => [
                'search'          => $request->query('search', ''),
                'unit_id'         => (string) $selectedUnitId,
                'jenis'           => $selectedJenis,
                'jenis_indikator' => $request->query('jenis_indikator', ''),
                'is_active'       => $request->query('is_active', ''),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $defaultUnitId = $request->query('prefill_unit_id', $request->query('unit_id', $user->unit_id ? (string) $user->unit_id : ''));
        $defaultPj = $user ? $user->name : '';

        return Inertia::render('Indicators/Create', [
            'units'   => Unit::aktif()->get(),
            'prefill' => [
                'judul'             => $request->query('prefill_judul', ''),
                'dasar_pemikiran'   => $request->query('prefill_dasar_pemikiran', ''),
                'unit_id'           => $defaultUnitId,
                'penanggung_jawab'  => $request->query('prefill_penanggung_jawab', $defaultPj),
                'jenis'             => $request->query('prefill_jenis', ''),
                'selection_item_id' => $request->query('selection_item_id', ''),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode'              => [
                'required',
                'string',
                'max:30',
                Rule::unique('indicators')->where(function ($query) use ($request) {
                    return $request->filled('unit_id')
                        ? $query->where('unit_id', $request->unit_id)
                        : $query->whereNull('unit_id');
                }),
            ],
            'judul'             => 'required|string|max:255',
            'jenis'             => 'required|array',
            'jenis.*'           => 'string|in:IMN,IMP-RS,IMP-U,IMU,SPM',
            'dasar_pemikiran'   => 'nullable|string',
            'dimensi_mutu'      => 'required|array',
            'dimensi_mutu.*'    => 'string|in:Efektif,Keselamatan,Berorientasi pada pasien/ pelayanan,Tepat Waktu,Efisien,Adil,Terintegrasi',
            'tujuan'            => 'nullable|string',
            'kriteria_inklusi'  => 'nullable|string',
            'kriteria_eksklusi' => 'nullable|string',
            'formula'           => 'nullable|string',
            'cara_pengumpulan'  => 'nullable|string',
            'definisi_operasional' => 'nullable|string',
            'satuan_pengukuran' => 'required|string|in:Persentase,Permil,Indeks',
            'numerator_keterangan' => 'nullable|string',
            'denominator_keterangan' => 'nullable|string',
            'metode_pengumpulan' => 'nullable|string',
            'instrumen_pengambilan' => 'nullable|string',
            'besar_sampel'      => 'nullable|string',
            'cara_pengambilan_sampel' => 'nullable|string',
            'periode_analisis'  => 'nullable|string',
            'publikasi_data'    => 'nullable|string',
            'jenis_indikator'   => 'required|array|min:1',
            'jenis_indikator.*' => 'string|in:Input,Proses,Output,Outcome',
            'target_capaian'    => 'required|numeric|min:0',
            'target_operator'   => 'required|in:>=,<=,=,>,<',
            'frekuensi'         => 'required|string|in:Bulanan,Semester,Tahunan',
            'sumber_data'       => 'nullable|string',
            'penanggung_jawab'  => 'nullable|string|max:100',
            'validator'         => 'nullable|string|max:255',
            'pengumpul_data'    => 'nullable|string|max:255',
            'unit_id'           => 'nullable|exists:units,id',
            'is_active'         => 'boolean',
            'form_layout_type'  => 'nullable|string|in:daily_grid,grouped_category,entity_checklist,location_matrix,inspection_points,monthly_summary,dynamic_rows,fixed_rows,entity_rows,patient_register',
        ], [
            'kode.unique' => 'Kode indikator sudah digunakan untuk unit ini.',
            'kode.required' => 'Kode indikator wajib diisi.',
            'judul.required' => 'Judul indikator wajib diisi.',
            'jenis.required' => 'Pilih minimal satu jenis indikator.',
            'dimensi_mutu.required' => 'Pilih minimal satu dimensi mutu.',
            'jenis_indikator.required' => 'Pilih minimal satu jenis/tipe indikator.',
            'satuan_pengukuran.required' => 'Satuan wajib dipilih.',
            'frekuensi.required' => 'Periode pengumpulan data wajib dipilih.',
            'target_capaian.required' => 'Target capaian wajib diisi.',
            'target_operator.required' => 'Operator target wajib dipilih.',
            'target_operator.in' => 'Operator target tidak valid.',
        ]);

        $validated['created_by'] = $request->user()->id;

        Indicator::create($validated);

        return redirect()->route('indicators.index')
            ->with('success', 'Indikator berhasil ditambahkan.');
    }

    public function show(Indicator $indicator): Response
    {
        $indicator->load(['unit', 'createdBy']);

        return Inertia::render('Indicators/Show', [
            'indicator' => $indicator,
        ]);
    }

    public function edit(Indicator $indicator): Response
    {
        return Inertia::render('Indicators/Edit', [
            'indicator'  => $indicator,
            'units'      => Unit::aktif()->get(),
        ]);
    }

    public function update(Request $request, Indicator $indicator)
    {
        $validated = $request->validate([
            'kode'              => [
                'required',
                'string',
                'max:30',
                Rule::unique('indicators')->ignore($indicator->id)->where(function ($query) use ($request) {
                    return $request->filled('unit_id')
                        ? $query->where('unit_id', $request->unit_id)
                        : $query->whereNull('unit_id');
                }),
            ],
            'judul'             => 'required|string|max:255',
            'jenis'             => 'required|array',
            'jenis.*'           => 'string|in:IMN,IMP-RS,IMP-U,IMU,SPM',
            'dasar_pemikiran'   => 'nullable|string',
            'dimensi_mutu'      => 'required|array',
            'dimensi_mutu.*'    => 'string|in:Efektif,Keselamatan,Berorientasi pada pasien/ pelayanan,Tepat Waktu,Efisien,Adil,Terintegrasi',
            'tujuan'            => 'nullable|string',
            'kriteria_inklusi'  => 'nullable|string',
            'kriteria_eksklusi' => 'nullable|string',
            'formula'           => 'nullable|string',
            'cara_pengumpulan'  => 'nullable|string',
            'definisi_operasional' => 'nullable|string',
            'satuan_pengukuran' => 'required|string|in:Persentase,Permil,Indeks',
            'numerator_keterangan' => 'nullable|string',
            'denominator_keterangan' => 'nullable|string',
            'metode_pengumpulan' => 'nullable|string',
            'instrumen_pengambilan' => 'nullable|string',
            'besar_sampel'      => 'nullable|string',
            'cara_pengambilan_sampel' => 'nullable|string',
            'periode_analisis'  => 'nullable|string',
            'publikasi_data'    => 'nullable|string',
            'jenis_indikator'   => 'required|array|min:1',
            'jenis_indikator.*' => 'string|in:Input,Proses,Output,Outcome',
            'target_capaian'    => 'required|numeric|min:0',
            'target_operator'   => 'required|in:>=,<=,=,>,<',
            'frekuensi'         => 'required|string|in:Bulanan,Semester,Tahunan',
            'sumber_data'       => 'nullable|string',
            'penanggung_jawab'  => 'nullable|string|max:100',
            'validator'         => 'nullable|string|max:255',
            'pengumpul_data'    => 'nullable|string|max:255',
            'unit_id'           => 'nullable|exists:units,id',
            'is_active'         => 'boolean',
            'form_layout_type'  => 'nullable|string|in:daily_grid,grouped_category,entity_checklist,location_matrix,inspection_points,monthly_summary,dynamic_rows,fixed_rows,entity_rows,patient_register',
        ], [
            'kode.unique' => 'Kode indikator sudah digunakan untuk unit ini.',
            'kode.required' => 'Kode indikator wajib diisi.',
            'judul.required' => 'Judul indikator wajib diisi.',
            'jenis.required' => 'Pilih minimal satu jenis indikator.',
            'dimensi_mutu.required' => 'Pilih minimal satu dimensi mutu.',
            'jenis_indikator.required' => 'Pilih minimal satu jenis/tipe indikator.',
            'satuan_pengukuran.required' => 'Satuan wajib dipilih.',
            'frekuensi.required' => 'Periode pengumpulan data wajib dipilih.',
            'target_capaian.required' => 'Target capaian wajib diisi.',
            'target_operator.required' => 'Operator target wajib dipilih.',
            'target_operator.in' => 'Operator target tidak valid.',
        ]);

        $indicator->update($validated);

        return redirect()->route('indicators.show', $indicator)
            ->with('success', 'Indikator berhasil diperbarui.');
    }

    public function destroy(Indicator $indicator)
    {
        $indicator->delete();

        return redirect()->route('indicators.index')
            ->with('success', 'Indikator berhasil dihapus.');
    }

    public function toggleActive(Indicator $indicator)
    {
        $indicator->update(['is_active' => !$indicator->is_active]);

        return redirect()->back()
            ->with('success', 'Status prioritas indikator berhasil diubah.');
    }

    /**
     * Export Kamus Profil Indikator ke PDF
     */
    public function exportPdf(Request $request)
    {
        $indicatorIds = $this->parseIndicatorIds($request);
        $query = Indicator::with(['unit', 'createdBy']);

        if (!empty($indicatorIds)) {
            $query->whereIn('id', $indicatorIds);
        } else {
            // Ekspor berdasarkan filter jika tidak ada ID spesifik yang dipilih
            $selectedUnitId = $request->input('unit_id', 'all');
            if ($selectedUnitId !== 'all' && $selectedUnitId !== '') {
                if ($selectedUnitId === 'rs') {
                    $query->whereNull('unit_id');
                } else {
                    $query->where('unit_id', $selectedUnitId);
                }
            }
            if ($request->filled('jenis') && $request->input('jenis') !== 'all') {
                $query->whereJsonContains('jenis', $request->input('jenis'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('definisi_operasional', 'like', "%{$search}%");
                });
            }
        }

        $indicators = $query->orderBy('kode')->get();

        if ($indicators->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data profil indikator untuk diekspor.');
        }

        $pdf = Pdf::loadView('reports.profil-indikator', compact('indicators'));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Kamus_Profil_Indikator_' . ($indicators->count() === 1 ? Str::slug($indicators->first()->kode) : 'Multi_' . date('Ymd_His')) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Kamus Profil Indikator ke Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $indicatorIds = $this->parseIndicatorIds($request);
        $query = Indicator::with(['unit', 'createdBy']);

        if (!empty($indicatorIds)) {
            $query->whereIn('id', $indicatorIds);
        } else {
            // Ekspor berdasarkan filter jika tidak ada ID spesifik yang dipilih
            $selectedUnitId = $request->input('unit_id', 'all');
            if ($selectedUnitId !== 'all' && $selectedUnitId !== '') {
                if ($selectedUnitId === 'rs') {
                    $query->whereNull('unit_id');
                } else {
                    $query->where('unit_id', $selectedUnitId);
                }
            }
            if ($request->filled('jenis') && $request->input('jenis') !== 'all') {
                $query->whereJsonContains('jenis', $request->input('jenis'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('definisi_operasional', 'like', "%{$search}%");
                });
            }
        }

        $indicators = $query->orderBy('kode')->get();

        if ($indicators->isEmpty()) {
            abort(404, 'Indikator tidak ditemukan');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kamus Indikator');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(9.5);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->setShowGridLines(true);
        $sheet->setPrintGridLines(true);

        $sheet->getColumnDimension('A')->setWidth(5.0);
        $sheet->getColumnDimension('B')->setWidth(34.0);
        $sheet->getColumnDimension('C')->setWidth(75.0);

        // Header Kop RS Yos Sudarso Padang
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'RUMAH SAKIT YOS SUDARSO PADANG');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF1E3A8A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'KOMITE MUTU & KESELAMATAN PASIEN');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Jl. Situjuh No. 1, Padang, Sumatera Barat | Telp: (0751) 33230 | Sistem Indikator Mutu RS (MUTU RSYS)');
        $sheet->getStyle('A3')->getFont()->setSize(8.5)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3:C3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF1E3A8A');

        $sheet->mergeCells('A5:C5');
        $sheet->setCellValue('A5', 'KAMUS & PROFIL INDIKATOR MUTU RUMAH SAKIT');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 7;
        foreach ($indicators as $idx => $ind) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}", ($idx + 1) . ". PROFIL INDIKATOR: " . $ind->kode . " - " . strtoupper($ind->judul));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
            $sheet->getRowDimension($row)->setRowHeight(22);
            $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);

            $tableStart = $row + 1;
            $operatorSimbol = $ind->target_operator === '>=' ? '≥' : ($ind->target_operator === '<=' ? '≤' : ($ind->target_operator === '>' ? '>' : ($ind->target_operator === '<' ? '<' : '=')));
            $satuanSimbol = $ind->satuan_pengukuran === 'Persentase' ? '%' : ($ind->satuan_pengukuran === 'Permil' ? '‰' : ($ind->satuan_pengukuran ? "({$ind->satuan_pengukuran})" : ''));

            $items = [
                ['A. IDENTITAS INDIKATOR MUTU', 'header'],
                ['Judul Indikator', $ind->judul],
                ['Kode Indikator', $ind->kode],
                ['Unit Kerja', $ind->unit ? $ind->unit->nama : 'Tingkat Rumah Sakit (Semua Unit)'],
                ['Jenis Indikator Mutu', is_array($ind->jenis) ? implode(', ', $ind->jenis) : ($ind->jenis ?: '-')],
                ['Tipe / Jenis Indikator', is_array($ind->jenis_indikator) ? implode(', ', $ind->jenis_indikator) : ($ind->jenis_indikator ?: '-')],
                ['Dimensi Mutu', is_array($ind->dimensi_mutu) ? implode(', ', $ind->dimensi_mutu) : ($ind->dimensi_mutu ?: '-')],
                ['Dasar Pemikiran', $ind->dasar_pemikiran ?: '-'],
                ['Tujuan Pengukuran', $ind->tujuan ?: '-'],
                ['Definisi Operasional', $ind->definisi_operasional ?: '-'],
                ['Kriteria Inklusi', $ind->kriteria_inklusi ?: '-'],
                ['Kriteria Eksklusi', $ind->kriteria_eksklusi ?: '-'],
                ['B. METODE PENGUKURAN & TARGET CAPAIAN', 'header'],
                ['Numerator (Pembilang)', $ind->numerator_keterangan ?: '-'],
                ['Denominator (Penyebut)', $ind->denominator_keterangan ?: '-'],
                ['Formula Perhitungan', $ind->formula ?: '-'],
                ['Satuan Pengukuran', $ind->satuan_pengukuran ?: 'Persentase (%)'],
                ['Target Capaian', $operatorSimbol . ' ' . $ind->target_capaian . ' ' . $satuanSimbol],
                ['C. METODOLOGI & MANAJEMEN DATA', 'header'],
                ['Periode Pengumpulan Data', $ind->frekuensi ?: 'Bulanan'],
                ['Sumber Data', $ind->sumber_data ?: '-'],
                ['Metode Pengumpulan Data', $ind->metode_pengumpulan ?: 'Retrospektif / Konkuren'],
                ['Instrumen Pengambilan Data', $ind->instrumen_pengambilan ?: '-'],
                ['Besar Sampel', $ind->besar_sampel ?: 'Total Populasi'],
                ['Cara Pengambilan Sampel', $ind->cara_pengambilan_sampel ?: '-'],
                ['Periode Analisis & Pelaporan', $ind->periode_analisis ?: 'Bulanan dan Triwulanan'],
                ['Penanggung Jawab Data', $ind->penanggung_jawab ?: ($ind->unit ? 'Kepala / PJ ' . $ind->unit->nama : 'Komite Mutu RS')],
                ['Pengumpul Data', $ind->pengumpul_data ?: '-'],
                ['Validator Data', $ind->validator ?: '-'],
                ['Rencana Publikasi Data', $ind->publikasi_data ?: 'Internal Rumah Sakit'],
            ];

            $r = $tableStart;
            $numItem = 1;
            foreach ($items as $item) {
                if ($item[1] === 'header') {
                    $sheet->mergeCells("A{$r}:C{$r}");
                    $sheet->setCellValue("A{$r}", $item[0]);
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF1E40AF');
                    $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');
                    $sheet->getRowDimension($r)->setRowHeight(19);
                } else {
                    $sheet->setCellValue("A{$r}", $numItem++);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->setCellValue("B{$r}", $item[0]);
                    $sheet->getStyle("B{$r}")->getFont()->setBold(true)->getColor()->setARGB('FF334155');
                    $sheet->getStyle("B{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    $sheet->setCellValue("C{$r}", $item[1]);
                    $sheet->getStyle("C{$r}")->getAlignment()->setWrapText(true);
                    if ($item[0] === 'Target Capaian') {
                        $sheet->getStyle("C{$r}")->getFont()->setBold(true)->getColor()->setARGB('FF1D4ED8');
                    }
                }
                $sheet->getStyle("A{$r}:C{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $r++;
            }

            $sheet->getStyle("A{$tableStart}:C" . ($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

            $row = $r + 2;
        }

        $filename = 'Kamus_Profil_Indikator_' . ($indicators->count() === 1 ? Str::slug($indicators->first()->kode) : 'Multi_' . date('Ymd_His')) . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function parseIndicatorIds(Request $request): array
    {
        $ids = $request->input('ids');
        if (is_string($ids)) {
            return array_filter(explode(',', $ids));
        }
        if (is_array($ids)) {
            return array_filter($ids);
        }
        if ($request->filled('indicator_id')) {
            return [(int) $request->input('indicator_id')];
        }
        return [];
    }
}
