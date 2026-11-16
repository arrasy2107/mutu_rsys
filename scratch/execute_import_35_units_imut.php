<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use App\Models\Indicator;
use App\Models\IndicatorPeriod;
use App\Models\DataEntry;
use App\Models\IndicatorDailyRecord;
use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "EKSEKUSI IMPORT DATA RIIL IMUT 35 UNIT TAHUN 2026 KE APLIKASI\n";
echo "=================================================================\n\n";

$jsonPath = __DIR__ . '/parsed_35_units_imut_data.json';
if (!file_exists($jsonPath)) {
    die("File {$jsonPath} tidak ditemukan!\n");
}

$data = json_decode(file_get_contents($jsonPath), true);
echo "Berhasil memuat data dari: {$jsonPath}\n";
echo "Total Unit yang akan diproses: " . count($data) . " unit.\n\n";

// Helper cache for periods (requires indicator_id and unit_id)
$periodCache = [];
$getPeriodId = function($indicatorId, $unitId, $tahun, $bulan) use (&$periodCache) {
    $key = "{$indicatorId}_{$unitId}_{$tahun}_{$bulan}";
    if (!isset($periodCache[$key])) {
        $p = IndicatorPeriod::firstOrCreate(
            [
                'indicator_id' => $indicatorId,
                'unit_id' => $unitId,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ],
            [
                'status' => 'validated',
            ]
        );
        $periodCache[$key] = $p->id;
    }
    return $periodCache[$key];
};

$totalIndicatorsSynced = 0;
$totalEntriesSynced = 0;
$unitResults = [];

foreach ($data as $fname => $uData) {
    $unitId = $uData['unit_id'];
    $unitKode = $uData['unit_kode'];
    $unitNama = $uData['unit_nama'];
    $inds = $uData['indicators'];

    echo "-----------------------------------------------------------------\n";
    echo "MEMPROSES UNIT: [{$unitKode}] {$unitNama} (File: {$fname})\n";

    $unit = Unit::find($unitId);
    if (!$unit) {
        $unit = Unit::where('kode', $unitKode)->first() ?? Unit::where('nama', $unitNama)->first();
    }

    if (!$unit) {
        echo "  [WARNING] Unit tidak ditemukan di database! Melewati unit ini.\n";
        continue;
    }

    $unitIndCount = 0;
    $unitEntryCount = 0;

    DB::beginTransaction();
    try {
        foreach ($inds as $iIdx => $indData) {
            $judul = trim($indData['judul']);
            if (empty($judul) || in_array(strtoupper($judul), ['HASIL', 'STANDAR', 'CAPAIAN', 'BULAN'])) {
                continue;
            }

            $targetCapaian = (float)($indData['target_capaian'] ?? 100);
            $targetOperator = $indData['target_operator'] ?? '>=';
            $monthlyData = $indData['monthly_data'] ?? [];

            // 1. Cari apakah indikator ini sudah ada untuk unit ini
            $indicator = Indicator::where('unit_id', $unit->id)
                ->where(function($q) use ($judul) {
                    $q->where('judul', $judul)
                      ->orWhere('judul', 'like', substr($judul, 0, 35) . '%');
                })
                ->first();

            // Tentukan kategori jenis
            $jenisArr = ['IMU'];
            $upperJudul = strtoupper($judul);
            if (str_contains($upperJudul, 'INM') || str_contains($upperJudul, 'NASIONAL')) {
                $jenisArr = ['INM'];
            } elseif (str_contains($upperJudul, 'IMP-RS') || str_contains($upperJudul, 'RUMAH SAKIT')) {
                $jenisArr = ['IMP-RS'];
            } elseif (str_contains($upperJudul, 'IMP-U') || str_contains($upperJudul, 'PRIORITAS UNIT')) {
                $jenisArr = ['IMP-U'];
            }

            // Tentukan form_layout_type bawaan jika belum ada
            $layoutType = 'daily_grid';
            if (str_contains($upperJudul, 'SBAR') || str_contains($upperJudul, 'DOKTER') || str_contains($upperJudul, 'PASIEN') || str_contains($upperJudul, 'LOG')) {
                $layoutType = 'dynamic_rows';
            } elseif (str_contains($upperJudul, 'WAKTU TUNGGU') || str_contains($upperJudul, 'BAKU MUTU') || str_contains($upperJudul, 'FORMULARIUM') || str_contains($upperJudul, 'KATEGORI')) {
                $layoutType = 'grouped_category';
            }

            if (!$indicator) {
                // Generate unique indicator code
                $cleanPrefix = preg_replace('/[^A-Za-z0-9]/', '', $unit->kode);
                $indCode = "IMU-{$cleanPrefix}" . ($iIdx + 1);
                $c = 1;
                while (Indicator::where('kode', $indCode)->exists()) {
                    $indCode = "IMU-{$cleanPrefix}" . ($iIdx + 1) . "-{$c}";
                    $c++;
                }

                $indicator = Indicator::create([
                    'unit_id' => $unit->id,
                    'kode' => $indCode,
                    'judul' => $judul,
                    'target_capaian' => $targetCapaian,
                    'target_operator' => $targetOperator,
                    'satuan' => '%',
                    'formula' => 'Numerator / Denominator * 100%',
                    'numerator_keterangan' => 'Jumlah sampel yang memenuhi standar mutu',
                    'denominator_keterangan' => 'Total seluruh sampel yang diobservasi',
                    'jenis' => $jenisArr,
                    'tipe' => 'rate_based',
                    'frekuensi' => 'bulanan',
                    'form_layout_type' => $layoutType,
                    'is_active' => true,
                ]);
                echo "  + Buat Indikator Baru: [{$indicator->kode}] {$indicator->judul}\n";
            } else {
                $indicator->update([
                    'target_capaian' => $targetCapaian,
                    'target_operator' => $targetOperator,
                    'is_active' => true,
                ]);
                echo "  ✓ Sinkron Indikator: [{$indicator->kode}] {$indicator->judul}\n";
            }

            $unitIndCount++;
            $totalIndicatorsSynced++;

            // 2. Simpan Data Bulanan ke data_entries dan indicator_daily_records
            foreach ($monthlyData as $mNum => $m) {
                $bulan = (int)$mNum;
                $pId = $getPeriodId($indicator->id, $unit->id, 2026, $bulan);
                $num = (float)($m['numerator'] ?? 0);
                $den = (float)($m['denominator'] ?? 0);
                $cap = (float)($m['capaian'] ?? 0);

                DataEntry::updateOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'unit_id' => $unit->id,
                        'period_id' => $pId,
                    ],
                    [
                        'numerator' => $num,
                        'denominator' => $den,
                        'capaian' => $cap,
                        'status' => 'approved',
                        'user_id' => 1,
                    ]
                );

                IndicatorDailyRecord::updateOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'unit_id' => $unit->id,
                        'tahun' => 2026,
                        'bulan' => $bulan,
                        'hari' => 0,
                    ],
                    [
                        'data' => [
                            'num' => $num,
                            'den' => $den,
                            'capaian' => $cap,
                            'note' => "Data riil 2026 ({$m['bulan_nama']})",
                        ],
                        'created_by' => 1,
                    ]
                );

                $unitEntryCount++;
                $totalEntriesSynced++;
            }
        }

        DB::commit();
        echo "  -> Selesai: {$unitIndCount} indikator, {$unitEntryCount} data entri bulanan tersimpan.\n";
        $unitResults[] = [
            'unit' => $unitNama,
            'indicators' => $unitIndCount,
            'entries' => $unitEntryCount,
            'status' => 'SUCCESS',
        ];

    } catch (\Throwable $e) {
        DB::rollBack();
        echo "  [ERROR] Gagal memproses unit {$unitNama}: " . $e->getMessage() . "\n";
        $unitResults[] = [
            'unit' => $unitNama,
            'indicators' => 0,
            'entries' => 0,
            'status' => 'ERROR: ' . $e->getMessage(),
        ];
    }
}

echo "\n=================================================================\n";
echo "RINGKASAN HASIL IMPORT IMUT 35 UNIT TAHUN 2026:\n";
echo "=================================================================\n";
echo "- Total Unit Berhasil Diproses : " . count($unitResults) . " / " . count($data) . "\n";
echo "- Total Indikator Mutu Disinkron: {$totalIndicatorsSynced} indikator\n";
echo "- Total Data Entri Bulanan Riil : {$totalEntriesSynced} entri (Jan - Des 2026)\n";
echo "=================================================================\n";
