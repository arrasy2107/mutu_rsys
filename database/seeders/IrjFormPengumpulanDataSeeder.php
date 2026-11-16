<?php

namespace Database\Seeders;

use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorDailyRecord;
use App\Models\IndicatorFormField;
use App\Models\IndicatorPeriod;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IrjFormPengumpulanDataSeeder extends Seeder
{
    public function run(): void
    {
        $irj = Unit::where('kode', 'IRJ')->orWhere('nama', 'like', '%Rawat Jalan%')->first();
        if (!$irj) {
            $this->command->error("Unit IRJ tidak ditemukan!");
            return;
        }

        $picIrj = User::where('username', 'pic_irj')->first() ?? User::first();

        // 1. Dapatkan 3 Indikator IRJ
        $ind1 = Indicator::where('unit_id', $irj->id)
            ->where(function($q) {
                $q->where('kode', 'IMP-U1')->orWhere('judul', 'like', '%pendampingan%');
            })->first();

        $ind2 = Indicator::where('unit_id', $irj->id)
            ->where(function($q) {
                $q->where('kode', 'IMU 2')->orWhere('judul', 'like', '%Pengkajian Pasien Baru%');
            })->first();

        $ind3 = Indicator::where('unit_id', $irj->id)
            ->where(function($q) {
                $q->where('kode', 'IMU 3')->orWhere('judul', 'like', '%Berkas Penagihan%');
            })->first();

        if (!$ind1 || !$ind2 || !$ind3) {
            $this->command->error("Indikator IRJ belum lengkap di database!");
            return;
        }

        // 2. Setup Form Fields untuk masing-masing Indikator
        // Form 1: Absen Dokter / Pendampingan DPJP
        IndicatorFormField::where('indicator_id', $ind1->id)->delete();
        IndicatorFormField::create(['indicator_id' => $ind1->id, 'name' => 'Jlh Pendamping yg ssi jadwal', 'key' => 'num', 'type' => 'numerator', 'order' => 1]);
        IndicatorFormField::create(['indicator_id' => $ind1->id, 'name' => 'Jlh DPJP ssi Jadwal', 'key' => 'den', 'type' => 'denominator', 'order' => 2]);
        IndicatorFormField::create(['indicator_id' => $ind1->id, 'name' => 'Jlh DPJP tdk ssi jadwal', 'key' => 'aux_1', 'type' => 'auxiliary', 'order' => 3]);

        // Form 2: Pasien Baru
        IndicatorFormField::where('indicator_id', $ind2->id)->delete();
        IndicatorFormField::create(['indicator_id' => $ind2->id, 'name' => 'Jlh PB yg dikaji', 'key' => 'num', 'type' => 'numerator', 'order' => 1]);
        IndicatorFormField::create(['indicator_id' => $ind2->id, 'name' => 'Jlh PB', 'key' => 'den', 'type' => 'denominator', 'order' => 2]);
        IndicatorFormField::create(['indicator_id' => $ind2->id, 'name' => 'Jlh seluruh pasien (B+L)', 'key' => 'aux_1', 'type' => 'auxiliary', 'order' => 3]);

        // Form 3: Berkas Penagihan
        IndicatorFormField::where('indicator_id', $ind3->id)->delete();
        IndicatorFormField::create(['indicator_id' => $ind3->id, 'name' => 'Jlh berkas yg lengkap', 'key' => 'num', 'type' => 'numerator', 'order' => 1]);
        IndicatorFormField::create(['indicator_id' => $ind3->id, 'name' => 'Jlh semua berkas', 'key' => 'den', 'type' => 'denominator', 'order' => 2]);
        IndicatorFormField::create(['indicator_id' => $ind3->id, 'name' => 'Jlh berkas tanpa srt kontrol / nota', 'key' => 'aux_1', 'type' => 'auxiliary', 'order' => 3]);

        // 3. Baca Data Excel Form Pengumpulan Data.xlsx
        $filePath = public_path('Indikator Mutu (IRJ)/Form Pengumpulan Data.xlsx');
        if (!file_exists($filePath)) {
            $this->command->error("File $filePath tidak ditemukan!");
            return;
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $tahun = 2026;

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $colBase = 3 + ($bulan - 1) * 3; // Col C=3, F=6, I=9...
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

            // Array akumulator total
            $sumInd1 = ['num' => 0, 'den' => 0, 'aux_1' => 0];
            $sumInd2 = ['num' => 0, 'den' => 0, 'aux_1' => 0];
            $sumInd3 = ['num' => 0, 'den' => 0, 'aux_1' => 0];

            $logHarian1 = [];
            $logHarian2 = [];
            $logHarian3 = [];

            for ($hari = 1; $hari <= $daysInMonth; $hari++) {
                $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);

                // --- Form 1: Rows 4 to 34 (hari 1 = row 4) ---
                $row1 = 3 + $hari;
                $v1_num = (float)($sheet->getCell([$colBase, $row1])->getValue() ?? 0);
                $v1_den = (float)($sheet->getCell([$colBase + 1, $row1])->getValue() ?? 0);
                $v1_aux = (float)($sheet->getCell([$colBase + 2, $row1])->getValue() ?? 0);

                $data1 = ['num' => $v1_num, 'den' => $v1_den, 'aux_1' => $v1_aux];
                $sumInd1['num'] += $v1_num;
                $sumInd1['den'] += $v1_den;
                $sumInd1['aux_1'] += $v1_aux;
                $logHarian1[$hari] = ['n' => $v1_num, 'd' => $v1_den, 'aux_1' => $v1_aux];

                IndicatorDailyRecord::updateOrCreate(
                    [
                        'indicator_id' => $ind1->id,
                        'unit_id'      => $irj->id,
                        'tahun'        => $tahun,
                        'bulan'        => $bulan,
                        'hari'         => $hari,
                    ],
                    [
                        'tanggal'    => $tanggal,
                        'data'       => $data1,
                        'created_by' => $picIrj->id,
                    ]
                );

                // --- Form 2: Rows 40 to 70 (hari 1 = row 40) ---
                $row2 = 39 + $hari;
                $v2_num = (float)($sheet->getCell([$colBase, $row2])->getValue() ?? 0);
                $v2_den = (float)($sheet->getCell([$colBase + 1, $row2])->getValue() ?? 0);
                $v2_aux = (float)($sheet->getCell([$colBase + 2, $row2])->getValue() ?? 0);

                $data2 = ['num' => $v2_num, 'den' => $v2_den, 'aux_1' => $v2_aux];
                $sumInd2['num'] += $v2_num;
                $sumInd2['den'] += $v2_den;
                $sumInd2['aux_1'] += $v2_aux;
                $logHarian2[$hari] = ['n' => $v2_num, 'd' => $v2_den, 'aux_1' => $v2_aux];

                IndicatorDailyRecord::updateOrCreate(
                    [
                        'indicator_id' => $ind2->id,
                        'unit_id'      => $irj->id,
                        'tahun'        => $tahun,
                        'bulan'        => $bulan,
                        'hari'         => $hari,
                    ],
                    [
                        'tanggal'    => $tanggal,
                        'data'       => $data2,
                        'created_by' => $picIrj->id,
                    ]
                );

                // --- Form 3: Rows 76 to 106 (hari 1 = row 76) ---
                $row3 = 75 + $hari;
                $v3_num = (float)($sheet->getCell([$colBase, $row3])->getValue() ?? 0);
                $v3_den = (float)($sheet->getCell([$colBase + 1, $row3])->getValue() ?? 0);
                $v3_aux = (float)($sheet->getCell([$colBase + 2, $row3])->getValue() ?? 0);

                $data3 = ['num' => $v3_num, 'den' => $v3_den, 'aux_1' => $v3_aux];
                $sumInd3['num'] += $v3_num;
                $sumInd3['den'] += $v3_den;
                $sumInd3['aux_1'] += $v3_aux;
                $logHarian3[$hari] = ['n' => $v3_num, 'd' => $v3_den, 'aux_1' => $v3_aux];

                IndicatorDailyRecord::updateOrCreate(
                    [
                        'indicator_id' => $ind3->id,
                        'unit_id'      => $irj->id,
                        'tahun'        => $tahun,
                        'bulan'        => $bulan,
                        'hari'         => $hari,
                    ],
                    [
                        'tanggal'    => $tanggal,
                        'data'       => $data3,
                        'created_by' => $picIrj->id,
                    ]
                );
            }

            // Sync ke IndicatorPeriod dan DataEntry jika ada data (misal bulan 1 s/d 9)
            if ($bulan <= 9) {
                // Indikator 1
                $period1 = IndicatorPeriod::firstOrCreate(
                    ['indicator_id' => $ind1->id, 'unit_id' => $irj->id, 'tahun' => $tahun, 'bulan' => $bulan],
                    ['status' => 'validated']
                );
                $capaian1 = $sumInd1['den'] > 0 ? round(($sumInd1['num'] / $sumInd1['den']) * 100, 2) : 0;
                DataEntry::updateOrCreate(
                    ['indicator_id' => $ind1->id, 'period_id' => $period1->id, 'unit_id' => $irj->id],
                    [
                        'user_id'      => $picIrj->id,
                        'numerator'    => $sumInd1['num'],
                        'denominator'  => $sumInd1['den'],
                        'capaian'      => $capaian1,
                        'log_harian'   => $logHarian1,
                        'status'       => 'approved',
                        'submitted_at' => now(),
                    ]
                );

                // Indikator 2
                $period2 = IndicatorPeriod::firstOrCreate(
                    ['indicator_id' => $ind2->id, 'unit_id' => $irj->id, 'tahun' => $tahun, 'bulan' => $bulan],
                    ['status' => 'validated']
                );
                $capaian2 = $sumInd2['den'] > 0 ? round(($sumInd2['num'] / $sumInd2['den']) * 100, 2) : 0;
                DataEntry::updateOrCreate(
                    ['indicator_id' => $ind2->id, 'period_id' => $period2->id, 'unit_id' => $irj->id],
                    [
                        'user_id'      => $picIrj->id,
                        'numerator'    => $sumInd2['num'],
                        'denominator'  => $sumInd2['den'],
                        'capaian'      => $capaian2,
                        'log_harian'   => $logHarian2,
                        'status'       => 'approved',
                        'submitted_at' => now(),
                    ]
                );

                // Indikator 3
                $period3 = IndicatorPeriod::firstOrCreate(
                    ['indicator_id' => $ind3->id, 'unit_id' => $irj->id, 'tahun' => $tahun, 'bulan' => $bulan],
                    ['status' => 'validated']
                );
                $capaian3 = $sumInd3['den'] > 0 ? round(($sumInd3['num'] / $sumInd3['den']) * 100, 2) : 0;
                DataEntry::updateOrCreate(
                    ['indicator_id' => $ind3->id, 'period_id' => $period3->id, 'unit_id' => $irj->id],
                    [
                        'user_id'      => $picIrj->id,
                        'numerator'    => $sumInd3['num'],
                        'denominator'  => $sumInd3['den'],
                        'capaian'      => $capaian3,
                        'log_harian'   => $logHarian3,
                        'status'       => 'approved',
                        'submitted_at' => now(),
                    ]
                );
            }
        }
    }
}
