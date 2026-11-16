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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataAnalysisController extends Controller
{
    /**
     * Tampilan Utama Data & Analisa Capaian Mutu 12 Bulan
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isExecutive = $user->isDireksi();
        $isQualityCommittee = $user->isKomiteMutu();
        $isAdmin = $user->isSuperAdmin();

        $tahun = $request->integer('tahun', 2026);
        
        // 1. Tentukan Unit yang dipilih
        if ($isAdmin || $isQualityCommittee || $isExecutive) {
            $selectedUnitId = $request->integer('unit_id');
            if (!$selectedUnitId) {
                $irj = Unit::where('kode', 'IRJ')->first()
                    ?? Unit::where('nama', 'like', '%Rawat Jalan%')->where('kode', 'not like', '%FARMASI%')->first()
                    ?? Unit::aktif()->first();
                $selectedUnitId = $irj ? $irj->id : Unit::aktif()->first()?->id;
            }
        } else {
            $selectedUnitId = $user->unit_id;
        }

        $selectedUnit = Unit::find($selectedUnitId);

        // 2. Ambil Profil Indikator Mutu Aktif Unit Terkait
        $unitIndicators = Indicator::where('unit_id', $selectedUnitId)
            ->where('is_active', true)
            ->orderBy('kode')
            ->get();

        // Filter Jenis jika ada
        $filterJenis = $request->input('jenis');
        if ($filterJenis) {
            $unitIndicators = $unitIndicators->filter(function ($i) use ($filterJenis) {
                return is_array($i->jenis) && in_array($filterJenis, $i->jenis);
            })->values();
        }

        // 3. Bangun Matriks Data 12 Bulan per Indikator
        $indicatorIds = $unitIndicators->pluck('id')->toArray();
        $allEntries = DataEntry::whereIn('indicator_id', $indicatorIds)
            ->where('unit_id', $selectedUnitId)
            ->whereHas('period', fn($q) => $q->where(function ($periodQuery) use ($tahun) {
                $periodQuery->where('tahun', $tahun)
                    ->orWhere(function ($previousYearQuery) use ($tahun) {
                        $previousYearQuery->where('tahun', $tahun - 1)
                            ->where('bulan', 12);
                    });
            }))
            ->with(['period', 'latestValidation'])
            ->get();

        $allPdsas = PdsaRecord::whereIn('indicator_id', $indicatorIds)
            ->where('unit_id', $selectedUnitId)
            ->whereHas('period', fn($q) => $q->where('tahun', $tahun))
            ->with('period')
            ->get();
        $pdsasByPeriodId = $allPdsas->groupBy('period_id');

        $analysisData = [];
        $totalIndicatorsCount = $unitIndicators->count();
        $totalMetCount = 0;
        $totalUnmetCount = 0;
        $sumCapaianAll = 0;
        $countEvaluatedMonths = 0;

        foreach ($unitIndicators as $ind) {
            $monthlyData = [];
            $sumNumerator = 0;
            $sumDenominator = 0;
            $indMetMonths = 0;
            $indUnmetMonths = 0;
            $rtlMonths = 0;
            $pdsaMonths = 0;

            for ($m = 1; $m <= 12; $m++) {
                $entry = $allEntries->first(function ($e) use ($ind, $m, $tahun) {
                    return $e->indicator_id == $ind->id
                        && $e->period?->bulan == $m
                        && $e->period?->tahun == $tahun;
                });

                $num = $entry ? (float)$entry->numerator : 0;
                $den = $entry ? (float)$entry->denominator : 0;
                $cap = $entry && $entry->capaian !== null ? (float)$entry->capaian : null;
                $statusEntry = $entry ? $entry->status : 'empty';

                $isTargetMet = null;
                if ($cap !== null && $den > 0) {
                    $isTargetMet = $ind->isTargetMet($cap);
                    if ($isTargetMet) {
                        $indMetMonths++;
                    } else {
                        $indUnmetMonths++;
                    }
                    $sumCapaianAll += $cap;
                    $countEvaluatedMonths++;
                }

                $sumNumerator += $num;
                $sumDenominator += $den;

                $previousMonth = $m === 1 ? 12 : $m - 1;
                $previousYear = $m === 1 ? $tahun - 1 : $tahun;
                $previousEntry = $allEntries->first(function ($e) use ($ind, $previousMonth, $previousYear) {
                    return $e->indicator_id == $ind->id
                        && $e->period?->bulan == $previousMonth
                        && $e->period?->tahun == $previousYear;
                });
                $previousCapaian = $previousEntry && $previousEntry->denominator > 0 && $previousEntry->capaian !== null
                    ? (float) $previousEntry->capaian
                    : null;
                $previousIsMet = $previousCapaian !== null
                    ? $ind->isTargetMet($previousCapaian)
                    : null;

                $actionType = $den > 0
                    ? $ind->getMonthlyFollowUpType($cap, $previousCapaian)
                    : null;
                $periodActions = $entry ? $pdsasByPeriodId->get($entry->period_id, collect()) : collect();
                $pdsa = $periodActions->firstWhere('jenis_tindak_lanjut', 'pdsa');
                $rtl = $periodActions->firstWhere('jenis_tindak_lanjut', 'rtl');
                $action = $actionType === 'rtl' ? $rtl : ($actionType === 'pdsa' ? $pdsa : null);

                if ($actionType === 'rtl') {
                    $rtlMonths++;
                } elseif ($actionType === 'pdsa') {
                    $pdsaMonths++;
                }

                $monthlyData[$m] = [
                    'bulan'        => $m,
                    'numerator'    => $num,
                    'denominator'  => $den,
                    'capaian'      => $cap,
                    'target'       => (float)$ind->target_capaian,
                    'operator'     => $ind->target_operator,
                    'is_met'       => $isTargetMet,
                    'status'       => $statusEntry,
                    'entry_id'     => $entry?->id,
                    'period_id'    => $entry?->period_id,
                    'warna'        => $cap !== null && $den > 0
                        ? $ind->getImutPerformanceColor($cap)
                        : 'none',
                    'previous_capaian' => $previousCapaian,
                    'previous_is_met'  => $previousIsMet,
                    'action_type'      => $actionType,
                    'action_id'        => $action?->id,
                    'has_pdsa'         => $pdsa !== null,
                    'pdsa_id'          => $pdsa?->id,
                ];
            }

            // Agregat Tahunan
            $annualCapaian = $sumDenominator > 0 ? round(($sumNumerator / $sumDenominator) * ($ind->multiplier ?? 100), 2) : null;
            $isAnnualMet = $annualCapaian !== null ? $ind->isTargetMet($annualCapaian) : null;

            if ($isAnnualMet === true) {
                $totalMetCount++;
            } elseif ($isAnnualMet === false) {
                $totalUnmetCount++;
            }

            $analysisData[] = [
                'indicator'        => [
                    'id'                     => $ind->id,
                    'kode'                   => $ind->kode,
                    'judul'                  => $ind->judul,
                    'jenis'                  => $ind->jenis,
                    'target_capaian'         => (float)$ind->target_capaian,
                    'target_operator'        => $ind->target_operator,
                    'satuan_pengukuran'      => $ind->satuan_pengukuran,
                    'formula'                => $ind->formula,
                    'numerator_keterangan'   => $ind->numerator_keterangan,
                    'denominator_keterangan' => $ind->denominator_keterangan,
                    'form_layout_type'       => $ind->form_layout_type,
                ],
                'months'           => $monthlyData,
                'annual'           => [
                    'numerator'    => $sumNumerator,
                    'denominator'  => $sumDenominator,
                    'capaian'      => $annualCapaian,
                    'is_met'       => $isAnnualMet,
                    'met_months'   => $indMetMonths,
                    'unmet_months' => $indUnmetMonths,
                    'warna'        => $annualCapaian !== null
                        ? $ind->getImutPerformanceColor($annualCapaian)
                        : 'none',
                    'rtl_months'   => $rtlMonths,
                    'pdsa_months'  => $pdsaMonths,
                ],
            ];
        }

        $overallAverageCapaian = $countEvaluatedMonths > 0 ? round($sumCapaianAll / $countEvaluatedMonths, 2) : 0;

        $executiveSummary = [
            'total_indicators'  => $totalIndicatorsCount,
            'met_count'         => $totalMetCount,
            'unmet_count'       => $totalUnmetCount,
            'average_capaian'   => $overallAverageCapaian,
            'evaluated_months'  => $countEvaluatedMonths,
        ];

        return Inertia::render('DataAnalysis/Index', [
            'units'            => Unit::aktif()->get(['id', 'kode', 'nama']),
            'selectedUnit'     => $selectedUnit,
            'tahun'            => $tahun,
            'analysisData'     => $analysisData,
            'summary'          => $executiveSummary,
            'filters'          => [
                'unit_id' => $selectedUnitId,
                'tahun'   => $tahun,
                'jenis'   => $filterJenis,
            ],
            'permissions'      => [
                'canCreatePdsa'      => ($isAdmin || $user->isKomiteMutu() || ($user->canFillPdsa() && $user->unit_id === $selectedUnitId)),
                'isExecutive'        => $isExecutive,
                'isQualityCommittee' => $isQualityCommittee,
                'isAdmin'            => $isAdmin,
            ],
        ]);
    }

    /**
     * Ekspor Laporan Data & Analisa ke Format Excel Standar RS
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $user = $request->user();
        $tahun = $request->integer('tahun', 2026);
        $unitId = $request->integer('unit_id');

        $unit = Unit::findOrFail($unitId);
        $indicators = Indicator::where('unit_id', $unitId)->where('is_active', true)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DATA & ANALISA');

        // Header Dokumen
        $sheet->setCellValue('A1', 'DATA & ANALISA CAPAIAN INDIKATOR MUTU UNIT');
        $sheet->setCellValue('A2', 'Unit: ' . strtoupper($unit->nama) . ' | Tahun: ' . $tahun);
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');

        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 4;
        $bulanList = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        foreach ($indicators as $idx => $ind) {
            $sheet->setCellValue('A' . $row, ($idx + 1) . '. [' . $ind->kode . '] ' . $ind->judul);
            $sheet->setCellValue('N' . $row, 'Target: ' . $ind->target_operator . ' ' . $ind->target_capaian . '%');
            $sheet->mergeCells("A{$row}:M{$row}");
            $sheet->getStyle("A{$row}:N{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:N{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');

            $row++;
            // Header Baris Variabel
            $sheet->setCellValue('A' . $row, 'Variabel Pengukuran');
            for ($m = 1; $m <= 12; $m++) {
                $colLetter = chr(65 + $m); // B to M
                $sheet->setCellValue($colLetter . $row, $bulanList[$m]);
            }
            $sheet->setCellValue('N' . $row, 'Total Tahunan');
            $sheet->getStyle("A{$row}:N{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Ambil data 12 bulan
            $entries = DataEntry::where('indicator_id', $ind->id)
                ->where('unit_id', $unit->id)
                ->whereHas('period', fn($q) => $q->where('tahun', $tahun))
                ->with('period')
                ->get()
                ->keyBy(fn($e) => $e->period?->bulan);

            $numRow = $row + 1;
            $denRow = $row + 2;
            $capRow = $row + 3;

            $sheet->setCellValue('A' . $numRow, 'Numerator (N)');
            $sheet->setCellValue('A' . $denRow, 'Denominator (D)');
            $sheet->setCellValue('A' . $capRow, 'Capaian Mutu (%)');

            $sumN = 0;
            $sumD = 0;

            for ($m = 1; $m <= 12; $m++) {
                $colLetter = chr(65 + $m);
                $e = $entries->get($m);
                $nVal = $e ? (float)$e->numerator : 0;
                $dVal = $e ? (float)$e->denominator : 0;
                $cVal = $e && $e->capaian !== null ? (float)$e->capaian : null;

                $sumN += $nVal;
                $sumD += $dVal;

                $sheet->setCellValue($colLetter . $numRow, $nVal);
                $sheet->setCellValue($colLetter . $denRow, $dVal);
                $sheet->setCellValue($colLetter . $capRow, $cVal !== null ? $cVal . '%' : '-');

                $sheet->getStyle($colLetter . $numRow . ':' . $colLetter . $capRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Total Tahunan
            $annualCapaian = $sumD > 0 ? round(($sumN / $sumD) * ($ind->multiplier ?? 100), 2) : 0;
            $sheet->setCellValue('N' . $numRow, $sumN);
            $sheet->setCellValue('N' . $denRow, $sumD);
            $sheet->setCellValue('N' . $capRow, $annualCapaian . '%');
            $sheet->getStyle("N{$numRow}:N{$capRow}")->getFont()->setBold(true);
            $sheet->getStyle("N{$numRow}:N{$capRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Border styling
            $sheet->getStyle("A{$row}:N{$capRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row += 5; // Spasi antar indikator
        }

        // Auto width
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Data_dan_Analisa_' . str_replace(' ', '_', $unit->kode) . "_{$tahun}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
