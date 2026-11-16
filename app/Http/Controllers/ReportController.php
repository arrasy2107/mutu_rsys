<?php

namespace App\Http\Controllers;

use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\PdsaRecord;
use App\Models\ReportLog;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = ReportLog::with('user')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('Reports/Index', [
            'units' => Unit::aktif()->get(),
            'logs' => $logs,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'jenis' => 'required|in:bulanan,triwulan,semesteran,tahunan',
            'tahun' => 'required|integer|min:2020',
            'bulan' => 'nullable|integer|min:1|max:12',
            'triwulan' => 'nullable|integer|min:1|max:4',
            'semester' => 'nullable|integer|min:1|max:2',
            'format' => 'required|in:pdf,excel',
            'jenis_indikator_filter' => 'nullable|string',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        // Tentukan range bulan
        [$bulanMulai, $bulanSelesai] = $this->getBulanRange($validated);

        // Ambil data
        $data = $this->getReportData($validated, $bulanMulai, $bulanSelesai);
        $unit = ! empty($validated['unit_id']) ? Unit::find($validated['unit_id']) : null;

        $pdsaRecords = PdsaRecord::query()
            ->with(['indicator', 'unit', 'period'])
            ->whereHas('period', fn ($q) => $q->where('tahun', $validated['tahun'])
                ->whereBetween('bulan', [$bulanMulai, $bulanSelesai])
            )
            ->when(! empty($validated['unit_id']), fn ($q) => $q->where('unit_id', $validated['unit_id']))
            ->get();

        // Generate file
        if ($validated['format'] === 'pdf') {
            $pdf = Pdf::loadView('reports.laporan-mutu', [
                'data' => $data,
                'validated' => $validated,
                'bulanMulai' => $bulanMulai,
                'bulanSelesai' => $bulanSelesai,
                'pdsaRecords' => $pdsaRecords,
                'unit' => $unit,
            ])->setPaper('a4', 'landscape');

            $filename = 'laporan-mutu-'.now()->format('YmdHis').'.pdf';
            $path = 'reports/'.$filename;
            Storage::disk('public')->put($path, $pdf->output());
        } else {
            // Excel export menggunakan PhpSpreadsheet
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('DATA & ANALISA');

            // Define months
            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

            // Fetch Data Entries grouped by Indicator for the selected year and unit
            $query = DataEntry::query()
                ->with(['indicator', 'period', 'pdsaRecords'])
                ->whereIn('status', ['approved', 'exempt'])
                ->whereHas('period', fn ($q) => $q->where('tahun', $validated['tahun']));

            if (! empty($validated['unit_id'])) {
                $query->where('unit_id', $validated['unit_id']);
            }
            if (! empty($validated['jenis_indikator_filter'])) {
                $query->whereHas('indicator', fn ($q) => $q->whereJsonContains('jenis', $validated['jenis_indikator_filter']));
            }

            $entries = $query->get();
            $indicators = $entries->pluck('indicator')->unique('id')->values();

            $sheet->setCellValue('A1', 'DATA IMUT TAHUN '.$validated['tahun']);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $colIndex = 2; // Column B
            foreach ($indicators as $indicator) {
                // Determine column letters for the 4 columns
                $colHasil = Coordinate::stringFromColumnIndex($colIndex);
                $colStandar = Coordinate::stringFromColumnIndex($colIndex + 1);
                $colNum = Coordinate::stringFromColumnIndex($colIndex + 2);
                $colDenom = Coordinate::stringFromColumnIndex($colIndex + 3);

                // Row 3: Indicator Title
                $sheet->setCellValue($colHasil.'3', $indicator->judul);
                $sheet->mergeCells($colHasil.'3:'.$colDenom.'3');
                $sheet->getStyle($colHasil.'3')->getFont()->setBold(true);
                $sheet->getStyle($colHasil.'3')->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle($colHasil.'3:'.$colDenom.'3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3'); // Light green

                // Row 4: Sub-headers
                $sheet->setCellValue($colHasil.'4', 'Hasil');
                $sheet->setCellValue($colStandar.'4', 'Standar');
                $sheet->setCellValue($colNum.'4', 'Numerator');
                $sheet->setCellValue($colDenom.'4', 'Denominator');
                $sheet->getStyle($colHasil.'4:'.$colDenom.'4')->getFont()->setBold(true);
                $sheet->getStyle($colHasil.'4:'.$colDenom.'4')->getAlignment()->setHorizontal('center');
                $sheet->getStyle($colHasil.'4:'.$colDenom.'4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEEEEE');

                // Row 5: Targets
                $sheet->setCellValue($colStandar.'5', $indicator->target_operator.' '.$indicator->target_capaian);
                $sheet->getStyle($colStandar.'5')->getFont()->setBold(true);

                // Fill Data rows 6 to 17
                $totalNum = 0;
                $totalDenom = 0;
                $sumHasil = 0;
                $countHasil = 0;

                foreach ($months as $idx => $monthName) {
                    $row = $idx + 6;
                    $sheet->setCellValue('A'.$row, $monthName);

                    // Find entry for this month
                    $entry = $entries->where('indicator_id', $indicator->id)
                        ->where('period.bulan', $idx + 1)
                        ->first();

                    if ($entry) {
                        $sheet->setCellValue($colHasil.$row, $entry->capaian);
                        $sheet->setCellValue($colNum.$row, $entry->numerator);
                        $sheet->setCellValue($colDenom.$row, $entry->denominator);

                        $totalNum += (float) $entry->numerator;
                        $totalDenom += (float) $entry->denominator;
                        $sumHasil += (float) $entry->capaian;
                        $countHasil++;

                        if (! $indicator->isTargetMet((float) $entry->capaian)) {
                            $sheet->getStyle($colHasil.$row)->getFont()->getColor()->setARGB(Color::COLOR_RED);
                        }
                    }
                }

                // Row 18: Total
                $sheet->setCellValue('A18', 'TOTAL');
                $sheet->getStyle('A18')->getFont()->setBold(true);
                $sheet->getStyle('A18')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC'); // Light yellow

                $avgHasil = $countHasil > 0 ? round($sumHasil / $countHasil, 2) : 0;
                $sheet->setCellValue($colHasil.'18', $avgHasil);
                $sheet->setCellValue($colNum.'18', $totalNum);
                $sheet->setCellValue($colDenom.'18', $totalDenom);
                $sheet->getStyle($colHasil.'18:'.$colDenom.'18')->getFont()->setBold(true);
                $sheet->getStyle($colHasil.'18:'.$colDenom.'18')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');

                // ANALISA SECTION
                $rowAnalisaHeader = 22;
                $sheet->setCellValue('A'.$rowAnalisaHeader, 'Untuk Imut yang tidak mencapai target');
                $sheet->getStyle('A'.$rowAnalisaHeader)->getFont()->setBold(true)->setItalic(true);

                $rowAnalisaSub = 23;
                $sheet->setCellValue('A'.$rowAnalisaSub, 'ANALISA');
                $sheet->setCellValue($colHasil.$rowAnalisaSub, 'Struktur / Masalah');
                $sheet->setCellValue($colStandar.$rowAnalisaSub, 'Akar Masalah');
                $sheet->setCellValue($colNum.$rowAnalisaSub, 'Capaian');
                $sheet->setCellValue($colDenom.$rowAnalisaSub, 'RTL (Rencana Tindak Lanjut)');
                $sheet->getStyle('A'.$rowAnalisaSub.':'.$colDenom.$rowAnalisaSub)->getFont()->setBold(true);
                $sheet->getStyle('A'.$rowAnalisaSub.':'.$colDenom.$rowAnalisaSub)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3'); // Light blue

                foreach ($months as $idx => $monthName) {
                    $row = $idx + 24;
                    $sheet->setCellValue('A'.$row, $monthName);

                    $entry = $entries->where('indicator_id', $indicator->id)
                        ->where('period.bulan', $idx + 1)
                        ->first();
                    $pdsa = $pdsaRecords->where('indicator_id', $indicator->id)
                        ->where('period.bulan', $idx + 1)
                        ->first() ?? ($entry ? $entry->pdsaRecords->first() : null);

                    if ($pdsa) {
                        $sheet->setCellValue($colHasil.$row, $pdsa->masalah);
                        $sheet->setCellValue($colStandar.$row, $pdsa->akar_masalah);
                        $sheet->setCellValue($colNum.$row, $pdsa->capaian_aktual.'%');
                        $sheet->setCellValue($colDenom.$row, $pdsa->rekomendasi_tindak_lanjut ?? $pdsa->plan);
                    }
                }

                // Apply borders to data block
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A3:'.$colDenom.'18')->applyFromArray($styleArray);
                $sheet->getStyle('A'.$rowAnalisaSub.':'.$colDenom.'35')->applyFromArray($styleArray);

                $colIndex += 4;
            }

            // Auto-size columns up to used range
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            for ($i = 1; $i <= $highestColumnIndex; $i++) {
                $colLetter = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Sheet 2: MATRIKS RTL
            if ($pdsaRecords->isNotEmpty()) {
                $rtlSheet = $spreadsheet->createSheet();
                $rtlSheet->setTitle('MATRIKS RTL');

                $rtlSheet->setCellValue('A1', 'MATRIKS RENCANA TINDAK LANJUT (RTL) MUTU');
                $rtlSheet->setCellValue('A2', ($unit ? strtoupper($unit->nama) : 'RUMAH SAKIT').' - TAHUN '.$validated['tahun']);
                $rtlSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $rtlSheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF4338CA');

                $rtlHeaders = [
                    'A4' => 'Bulan',
                    'B4' => 'Judul Imut',
                    'C4' => 'Target',
                    'D4' => 'Capaian',
                    'E4' => 'Masalah / Gap',
                    'F4' => 'Penyebab / Akar Masalah',
                    'G4' => 'RTL (Rencana Tindak Lanjut)',
                    'H4' => 'PJ RTL',
                    'I4' => 'Bukti RTL',
                    'J4' => 'Status',
                ];

                foreach ($rtlHeaders as $cell => $val) {
                    $rtlSheet->setCellValue($cell, $val);
                }
                $rtlSheet->getStyle('A4:J4')->getFont()->setBold(true);
                $rtlSheet->getStyle('A4:J4')->getAlignment()->setHorizontal('center');
                $rtlSheet->getStyle('A4:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

                $rRow = 5;
                foreach ($pdsaRecords as $pdsa) {
                    $bln = $pdsa->period ? ($months[$pdsa->period->bulan - 1] ?? 'Bln '.$pdsa->period->bulan) : '-';
                    $rtlSheet->setCellValue('A'.$rRow, $bln);
                    $rtlSheet->setCellValue('B'.$rRow, $pdsa->indicator?->judul ?? '-');
                    $rtlSheet->setCellValue('C'.$rRow, ($pdsa->target_capaian ?? '-').'%');
                    $rtlSheet->setCellValue('D'.$rRow, ($pdsa->capaian_aktual ?? '-').'%');
                    $rtlSheet->setCellValue('E'.$rRow, $pdsa->masalah ?? '-');
                    $rtlSheet->setCellValue('F'.$rRow, $pdsa->akar_masalah ?? '-');
                    $rtlSheet->setCellValue('G'.$rRow, $pdsa->rekomendasi_tindak_lanjut ?? $pdsa->plan ?? '-');
                    $rtlSheet->setCellValue('H'.$rRow, $pdsa->pj_rtl ?? '-');
                    $rtlSheet->setCellValue('I'.$rRow, $pdsa->bukti_rtl ?? '-');
                    $rtlSheet->setCellValue('J'.$rRow, ucfirst($pdsa->status));

                    $rtlSheet->getStyle('A'.$rRow)->getAlignment()->setHorizontal('center');
                    $rtlSheet->getStyle('C'.$rRow.':D'.$rRow)->getAlignment()->setHorizontal('center');
                    $rtlSheet->getStyle('H'.$rRow.':J'.$rRow)->getAlignment()->setHorizontal('center');

                    $rRow++;
                }

                $rtlSheet->getStyle('A4:J'.max(4, $rRow - 1))->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                foreach (range('A', 'J') as $c) {
                    $rtlSheet->getColumnDimension($c)->setAutoSize(true);
                }
            }

            $filename = 'laporan-mutu-'.now()->format('YmdHis').'.xlsx';
            $path = 'reports/'.$filename;

            if (! Storage::disk('public')->exists('reports')) {
                Storage::disk('public')->makeDirectory('reports');
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::disk('public')->path($path));
        }

        // Simpan log
        $log = ReportLog::create([
            'user_id' => $request->user()->id,
            'jenis' => $validated['jenis'],
            'tahun' => $validated['tahun'],
            'bulan' => $validated['bulan'] ?? null,
            'triwulan' => $validated['triwulan'] ?? null,
            'format' => $validated['format'],
            'parameter' => json_encode($validated),
            'file_path' => $path ?? null,
        ]);

        return redirect()->route('reports.download', $log)
            ->with('success', 'Laporan berhasil digenerate.');
    }

    public function download(ReportLog $log)
    {
        abort_if(! $log->file_path || ! Storage::disk('public')->exists($log->file_path), 404);

        return Storage::disk('public')->download($log->file_path);
    }

    private function getBulanRange(array $validated): array
    {
        return match ($validated['jenis']) {
            'bulanan' => [$validated['bulan'], $validated['bulan']],
            'triwulan' => [
                ($validated['triwulan'] - 1) * 3 + 1,
                ($validated['triwulan'] - 1) * 3 + 3,
            ],
            'semesteran' => [
                (($validated['semester'] ?? 1) - 1) * 6 + 1,
                (($validated['semester'] ?? 1) - 1) * 6 + 6,
            ],
            'tahunan' => [1, 12],
            default => [1, 12],
        };
    }

    private function getReportData(array $validated, int $bulanMulai, int $bulanSelesai): array
    {
        $query = DataEntry::query()
            ->with(['indicator', 'unit'])
            ->whereIn('status', ['approved', 'submitted', 'exempt'])
            ->whereHas('period', fn ($q) => $q->where('tahun', $validated['tahun'])
                ->whereBetween('bulan', [$bulanMulai, $bulanSelesai])
            );

        if (! empty($validated['unit_id'])) {
            $query->where('unit_id', $validated['unit_id']);
        }

        if (! empty($validated['jenis_indikator_filter'])) {
            $query->whereHas('indicator', fn ($q) => $q->whereJsonContains('jenis', $validated['jenis_indikator_filter']));
        }

        $entries = $query->get();

        return $entries
            ->groupBy('indicator_id')
            ->map(function ($group) {
                $indicator = $group->first()->indicator;
                $avgCapaian = round($group->avg('capaian'), 2);

                return [
                    'kode' => $indicator->kode,
                    'judul' => $indicator->judul,
                    'kategori' => is_array($indicator->jenis) ? implode(', ', $indicator->jenis) : ($indicator->jenis ?? '-'),
                    'target' => $indicator->target_capaian,
                    'operator' => $indicator->target_operator,
                    'capaian' => $avgCapaian,
                    'status' => $indicator->getStatusColor($avgCapaian),
                    'target_tercapai' => $indicator->isTargetMet($avgCapaian),
                ];
            })
            ->values()
            ->toArray();
    }
}
