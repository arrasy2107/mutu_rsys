<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorPeriod;
use App\Models\PdsaRecord;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PdsaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->input('tahun', 2026);
        $unitId = $request->input('unit_id');

        $query = PdsaRecord::query()
            ->with(['indicator', 'unit', 'user', 'period', 'reviewedBy'])
            ->when(!$user->isSuperAdmin() && !$user->isKomiteMutu(), fn($q) =>
                $q->where('unit_id', $user->unit_id)
            )
            ->when($unitId, fn($q, $u) => $q->where('unit_id', $u))
            ->when($tahun, fn($q, $y) => $q->whereHas('period', fn($p) => $p->where('tahun', $y)))
            ->when($request->status, fn($q, $s) => $q->where('status', $s));

        $records = (clone $query)
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $matrixRecords = (clone $query)->get()->sortBy(function($rec) {
            return $rec->period ? $rec->period->bulan : 99;
        })->values();

        return Inertia::render('Pdsa/Index', [
            'records'       => $records,
            'matrixRecords' => $matrixRecords,
            'units'         => Unit::aktif()->get(),
            'filters'       => [
                'unit_id' => $unitId ? (int)$unitId : '',
                'tahun'   => $tahun,
                'status'  => $request->status ?? '',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $jenisTindakLanjut = $request->query('jenis_tindak_lanjut', 'pdsa');
        abort_unless(in_array($jenisTindakLanjut, ['rtl', 'pdsa'], true), 404);

        $indicators = Indicator::aktif()
            ->when(!$user->isSuperAdmin() && !$user->isKomiteMutu(), fn($q) =>
                $q->where(fn($i) => $i->whereNull('unit_id')->orWhere('unit_id', $user->unit_id))
            )
            ->get();

        $selectedIndicatorId = $request->query('indicator_id');
        $selectedUnitId = $request->query('unit_id');
        $prefillIndicator = $selectedIndicatorId ? Indicator::find($selectedIndicatorId) : null;

        return Inertia::render('Pdsa/Create', [
            'indicators' => $indicators,
            'units'      => Unit::aktif()->get(),
            'prefill'    => [
                'indicator_id'   => $selectedIndicatorId ? (int)$selectedIndicatorId : '',
                'unit_id'        => $selectedUnitId ? (int)$selectedUnitId : ($prefillIndicator?->unit_id ?? ($user->unit_id ?? '')),
                'period_id'      => $request->query('period_id', ''),
                'capaian_aktual' => $request->query('capaian_aktual', ''),
                'target_capaian' => $request->query('target_capaian', $prefillIndicator?->target_capaian ?? ''),
                'masalah'        => $request->query('masalah', ''),
            ],
            'jenisTindakLanjut' => $jenisTindakLanjut,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_tindak_lanjut' => 'required|in:rtl,pdsa',
            'indicator_id'   => 'required|exists:indicators,id',
            'period_id'      => 'nullable|exists:indicator_periods,id',
            'unit_id'        => 'required|exists:units,id',
            'capaian_aktual' => 'nullable|numeric',
            'target_capaian' => 'nullable|numeric',
            'masalah'        => 'required|string',
            'analisa_penyebab_manusia' => 'nullable|string',
            'analisa_penyebab_metode'  => 'nullable|string',
            'analisa_penyebab_alat'    => 'nullable|string',
            'analisa_penyebab_material'=> 'nullable|string',
            'analisa_penyebab_lingkungan'=> 'nullable|string',
            'akar_masalah'   => 'nullable|string',
            'rekomendasi_tindak_lanjut'=> 'required_if:jenis_tindak_lanjut,rtl|nullable|string',
            'pj_rtl'         => 'nullable|string',
            'bukti_rtl'      => 'nullable|string',
            'plan'           => 'nullable|string',
            'do_action'      => 'nullable|string',
            'study'          => 'nullable|string',
            'act'            => 'nullable|string',
            'rencana_selesai'=> 'nullable|date',
        ]);

        if ($validated['jenis_tindak_lanjut'] === 'rtl') {
            $validated['plan'] = null;
            $validated['do_action'] = null;
            $validated['study'] = null;
            $validated['act'] = null;
        }

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'draft';

        PdsaRecord::create($validated);

        return redirect()->route('pdsa.index')
            ->with('success', strtoupper($validated['jenis_tindak_lanjut']) . ' berhasil disimpan.');
    }

    public function show(PdsaRecord $pdsa): Response
    {
        $pdsa->load(['indicator', 'unit', 'user', 'period', 'reviewedBy']);

        return Inertia::render('Pdsa/Show', ['pdsa' => $pdsa]);
    }

    public function edit(PdsaRecord $pdsa): Response
    {
        return Inertia::render('Pdsa/Edit', [
            'pdsa'       => $pdsa->load(['indicator', 'unit']),
            'indicators' => Indicator::aktif()->get(),
            'units'      => Unit::aktif()->get(),
        ]);
    }

    public function update(Request $request, PdsaRecord $pdsa)
    {
        $validated = $request->validate([
            'masalah'        => 'required|string',
            'analisa_penyebab_manusia' => 'nullable|string',
            'analisa_penyebab_metode'  => 'nullable|string',
            'analisa_penyebab_alat'    => 'nullable|string',
            'analisa_penyebab_material'=> 'nullable|string',
            'analisa_penyebab_lingkungan'=> 'nullable|string',
            'akar_masalah'   => 'nullable|string',
            'rekomendasi_tindak_lanjut'=> 'nullable|string',
            'pj_rtl'         => 'nullable|string',
            'bukti_rtl'      => 'nullable|string',
            'plan'           => 'nullable|string',
            'do_action'      => 'nullable|string',
            'study'          => 'nullable|string',
            'act'            => 'nullable|string',
            'rencana_selesai'=> 'nullable|date',
        ]);

        $pdsa->update($validated);

        return redirect()->route('pdsa.show', $pdsa)
            ->with('success', strtoupper($pdsa->jenis_tindak_lanjut) . ' berhasil diperbarui.');
    }

    public function destroy(PdsaRecord $pdsa)
    {
        $pdsa->delete();
        return redirect()->route('pdsa.index')->with('success', 'PDSA berhasil dihapus.');
    }

    public function submit(PdsaRecord $pdsa)
    {
        abort_if($pdsa->status !== 'draft', 403);
        $pdsa->update(['status' => 'submitted']);
        return back()->with('success', 'PDSA berhasil diajukan untuk review.');
    }

    public function review(Request $request, PdsaRecord $pdsa)
    {
        $request->validate(['catatan_review' => 'nullable|string']);

        $pdsa->update([
            'status'        => 'reviewed',
            'reviewed_by'   => $request->user()->id,
            'reviewed_at'   => now(),
            'catatan_review'=> $request->catatan_review,
        ]);

        return back()->with('success', 'PDSA berhasil di-review.');
    }

    public function exportRtl(Request $request)
    {
        $user = $request->user();
        $tahun = (int) $request->input('tahun', 2026);
        $unitId = $request->input('unit_id');

        $query = PdsaRecord::query()
            ->with(['indicator', 'unit', 'period', 'user'])
            ->when(!$user->isSuperAdmin() && !$user->isKomiteMutu(), fn($q) =>
                $q->where('unit_id', $user->unit_id)
            )
            ->when($unitId, fn($q, $u) => $q->where('unit_id', $u))
            ->when($tahun, fn($q, $y) => $q->whereHas('period', fn($p) => $p->where('tahun', $y)));

        $records = $query->get()->sortBy(function($rec) {
            return $rec->period ? $rec->period->bulan : 99;
        })->values();

        $unitNama = $unitId ? (Unit::find($unitId)?->nama ?? 'Unit') : 'Semua Unit';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RTL ' . $tahun);

        // Header Title
        $sheet->setCellValue('A1', 'RENCANA TINDAK LANJUT (RTL) MUTU RUMAH SAKIT');
        $sheet->setCellValue('A2', strtoupper($unitNama) . ' - TAHUN ' . $tahun);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF4338CA');

        // Column Headers (Row 4)
        $headers = [
            'A4' => 'Bulan',
            'B4' => 'Judul Imut',
            'C4' => 'Target',
            'D4' => 'Capaian',
            'E4' => 'Masalah',
            'F4' => 'Penyebab (Akar Masalah)',
            'G4' => 'RTL (Rencana Tindak Lanjut)',
            'H4' => 'PJ RTL',
            'I4' => 'Bukti RTL',
            'J4' => 'Status'
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $sheet->getStyle('A4:J4')->getFont()->setBold(true);
        $sheet->getStyle('A4:J4')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A4:J4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $row = 5;
        foreach ($records as $rec) {
            $bln = $rec->period ? ($monthNames[$rec->period->bulan] ?? 'Bln ' . $rec->period->bulan) : '-';
            $sheet->setCellValue('A' . $row, $bln);
            $sheet->setCellValue('B' . $row, $rec->indicator?->judul ?? '-');
            $sheet->setCellValue('C' . $row, ($rec->target_capaian ?? '-') . '%');
            $sheet->setCellValue('D' . $row, ($rec->capaian_aktual ?? '-') . '%');
            $sheet->setCellValue('E' . $row, $rec->masalah ?? '-');
            $sheet->setCellValue('F' . $row, $rec->akar_masalah ?? '-');
            $sheet->setCellValue('G' . $row, $rec->rekomendasi_tindak_lanjut ?? $rec->plan ?? '-');
            $sheet->setCellValue('H' . $row, $rec->pj_rtl ?? '-');
            $sheet->setCellValue('I' . $row, $rec->bukti_rtl ?? '-');
            $sheet->setCellValue('J' . $row, ucfirst($rec->status));

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('C' . $row . ':D' . $row)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('H' . $row . ':J' . $row)->getAlignment()->setHorizontal('center');

            if ((float)$rec->capaian_aktual < (float)$rec->target_capaian) {
                $sheet->getStyle('D' . $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            }

            $row++;
        }

        $sheet->getStyle('A4:J' . max(4, $row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'RTL_' . str_replace(' ', '_', $unitNama) . '_' . $tahun . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
