<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorPeriod;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HospitalDataCapaianController extends Controller
{
    /**
     * Display Data Capaian Tingkat Rumah Sakit (Butir B.2 Roadmap)
     * Kategori: IMP-RS, IMN, IMP-U, SPM
     * Filter Waktu: Bulanan, Triwulan, Semester, Tahunan
     */
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $kategori = $request->query('kategori', 'IMP-RS'); // IMP-RS, IMN, IMP-U, SPM
        $rentang = $request->query('rentang', 'bulanan');   // bulanan, triwulan, semester, tahunan

        // Authentic 12-Month Dataset for IMP-RS and IMN from IMUT RSYS 2026.xlsx
        $referenceDatasets = $this->getReferenceDataset();

        // Query indicators based on selected category
        $indicators = Indicator::aktif()->get()->filter(function ($i) use ($kategori) {
            $j = $i->jenis;
            if (is_array($j)) {
                if ($kategori === 'IMP-RS') return in_array('IMP-RS', $j);
                if ($kategori === 'IMN') return in_array('IMN', $j) || in_array('INM', $j);
                if ($kategori === 'IMP-U') return in_array('IMP-U', $j) || in_array('IMU', $j);
                if ($kategori === 'SPM') return in_array('SPM', $j);
            }
            if ($kategori === 'IMP-RS') return $j === 'IMP-RS';
            if ($kategori === 'IMN') return $j === 'IMN' || $j === 'INM';
            if ($kategori === 'IMP-U') return $j === 'IMP-U' || $j === 'IMU';
            if ($kategori === 'SPM') return $j === 'SPM';
            return false;
        })->values();

        $selectedId = $request->query('indicator_id');
        $activeIndicator = null;

        if ($selectedId) {
            $activeIndicator = $indicators->firstWhere('id', (int) $selectedId);
        }
        if (!$activeIndicator && $indicators->isNotEmpty()) {
            $activeIndicator = $indicators->first();
        }

        // Build 12-Month Performance Data
        $analysisData = null;
        if ($activeIndicator) {
            // Check if available in reference dataset by code or title
            $refKey = $activeIndicator->kode;
            if (!$refKey || !isset($referenceDatasets[$refKey])) {
                foreach ($referenceDatasets as $key => $d) {
                    if (str_contains(strtolower($activeIndicator->judul), strtolower(substr($d['judul'], 0, 15)))) {
                        $refKey = $key;
                        break;
                    }
                }
            }

            if ($refKey && isset($referenceDatasets[$refKey])) {
                $analysisData = $referenceDatasets[$refKey];
                $analysisData['indicator_id'] = $activeIndicator->id;
                $analysisData['judul'] = $activeIndicator->judul;
            } else {
                // Roll-up live data from data_entries
                $analysisData = $this->aggregateIndicatorData($activeIndicator, $tahun);
            }
        }

        // Summary Counts per Category
        $counts = [
            'IMP-RS' => Indicator::aktif()->get()->filter(fn($i) => is_array($i->jenis) ? in_array('IMP-RS', $i->jenis) : $i->jenis === 'IMP-RS')->count(),
            'IMN'    => Indicator::aktif()->get()->filter(fn($i) => is_array($i->jenis) ? (in_array('IMN', $i->jenis) || in_array('INM', $i->jenis)) : ($i->jenis === 'IMN' || $i->jenis === 'INM'))->count(),
            'IMP-U'  => Indicator::aktif()->get()->filter(fn($i) => is_array($i->jenis) ? (in_array('IMP-U', $i->jenis) || in_array('IMU', $i->jenis)) : ($i->jenis === 'IMP-U' || $i->jenis === 'IMU'))->count(),
            'SPM'    => Indicator::aktif()->get()->filter(fn($i) => is_array($i->jenis) ? in_array('SPM', $i->jenis) : $i->jenis === 'SPM')->count(),
        ];

        return Inertia::render('Hospital/DataCapaian/Index', [
            'kategori'         => $kategori,
            'rentang'          => $rentang,
            'tahun'            => $tahun,
            'counts'           => $counts,
            'indicators'       => $indicators->map(fn($i) => [
                'id'              => $i->id,
                'kode'            => $i->kode ?? ('IND-' . $i->id),
                'judul'           => $i->judul,
                'target_capaian'  => $i->target_capaian,
                'target_operator' => $i->target_operator,
                'satuan'          => $i->satuan_pengukuran ?? '%',
            ]),
            'activeIndicator'  => $activeIndicator ? [
                'id'              => $activeIndicator->id,
                'kode'            => $activeIndicator->kode ?? ('IND-' . $activeIndicator->id),
                'judul'           => $activeIndicator->judul,
                'target_capaian'  => $activeIndicator->target_capaian,
                'target_operator' => $activeIndicator->target_operator,
                'satuan'          => $activeIndicator->satuan_pengukuran ?? '%',
            ] : null,
            'analysisData'     => $analysisData,
        ]);
    }

    /**
     * Roll-up indicator data across all entries in the year
     */
    private function aggregateIndicatorData(Indicator $indicator, int $tahun): array
    {
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulanan = [];
        $totalNum = 0;
        $totalDen = 0;

        for ($m = 1; $m <= 12; $m++) {
            $entries = DataEntry::where('indicator_id', $indicator->id)
                ->whereIn('status', ['approved', 'exempt'])
                ->whereHas('period', fn($q) => $q->where('tahun', $tahun)->where('bulan', $m))
                ->get();

            $num = $entries->sum('numerator');
            $den = $entries->sum('denominator');
            $mult = $indicator->multiplier ?? 100;
            $capaian = $den > 0 ? round(($num / $den) * $mult, 2) : ($entries->count() > 0 ? round($entries->avg('capaian'), 2) : null);

            $status = 'gray';
            if ($capaian !== null) {
                $status = $capaian >= $indicator->target_capaian ? 'hijau' : ($capaian >= ($indicator->target_capaian * 0.8) ? 'kuning' : 'merah');
            }

            $bulanan[] = [
                'bulan'   => $months[$m - 1],
                'target'  => (float) $indicator->target_capaian,
                'num'     => (int) $num,
                'den'     => (int) $den,
                'capaian' => $capaian,
                'status'  => $status,
            ];

            if ($den > 0) {
                $totalNum += $num;
                $totalDen += $den;
            }
        }

        $capaianTahunan = $totalDen > 0 ? round(($totalNum / $totalDen) * ($indicator->multiplier ?? 100), 2) : (collect($bulanan)->whereNotNull('capaian')->avg('capaian') ?? 0);

        return [
            'kode'             => $indicator->kode ?? ('IND-' . $indicator->id),
            'judul'            => $indicator->judul,
            'fokus_kars'       => 'Indikator Mutu ' . (is_array($indicator->jenis) ? implode(', ', $indicator->jenis) : $indicator->jenis),
            'unit'             => $indicator->unit?->nama ?? 'Rumah Sakit',
            'standar'          => (float) $indicator->target_capaian,
            'satuan'           => $indicator->satuan_pengukuran ?? '%',
            'operator'         => $indicator->target_operator ?? '>=',
            'target_tahunan'   => (float) $indicator->target_capaian,
            'capaian_tahunan'  => round($capaianTahunan, 2),
            'analisa_tw1'      => 'Evaluasi Triwulan I: Kepatuhan data dan pemantauan baseline mutu awal tahun.',
            'analisa_tw2'      => 'Evaluasi Triwulan II: Implementasi perbaikan berkelanjutan dan sosialisasi SOP.',
            'analisa_tw3'      => 'Evaluasi Triwulan III: Monitoring kestabilan proses dan tindak lanjut rekomendasi mutu.',
            'rekomendasi'      => 'Mempertahankan konsistensi pengisian data harian dan pelaksanaan siklus PDCA secara periodik.',
            'bulanan'          => $bulanan,
        ];
    }

    /**
     * Reference real hospital dataset from IMUT RSYS 2026.xlsx
     */
    private function getReferenceDataset(): array
    {
        return [
            'IMP-RS 1' => [
                'kode' => 'IMP-RS 1',
                'judul' => 'Ketepatan Waktu Pemberian AB Profilaksis Sebelum Operasi',
                'fokus_kars' => 'Indikator Pelayanan Klinis (Prioritas 1)',
                'unit' => 'Instalasi Bedah Sentral (IBS)',
                'standar' => 95,
                'satuan' => '%',
                'operator' => '>=',
                'target_tahunan' => 95,
                'capaian_tahunan' => 68.75,
                'analisa_tw1' => 'Meskipun terjadi peningkatan dari bulan Jan (58%) ke Feb (69%), pada bulan April capaian turun drastis ke 28.57% karena kendala penjadwalan operasi yang mundur.',
                'analisa_tw2' => 'Pada bulan Mei capaian mencapai puncak 90.91% setelah dilakukan uji coba perawat ruangan masuk jam 06.30 WIB untuk pemberian AB lebih awal.',
                'analisa_tw3' => 'Rata-rata Triwulan III berada pada angka 76.05%. Masih ada kendala operasi yang mundur dari jadwal rencana semula sehingga pemberian AB melebihi 60 menit.',
                'rekomendasi' => 'Melanjutkan koordinasi antar unit (IBS, Rawat Inap, Farmasi) serta penyesuaian SOP pemberian AB profilaksis 30-60 menit sebelum sayatan bedah pertama.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 95, 'num' => 7,  'den' => 12, 'capaian' => 58.33, 'status' => 'merah'],
                    ['bulan' => 'Februari',  'target' => 95, 'num' => 9,  'den' => 13, 'capaian' => 69.23, 'status' => 'merah'],
                    ['bulan' => 'Maret',     'target' => 95, 'num' => 10, 'den' => 15, 'capaian' => 66.67, 'status' => 'merah'],
                    ['bulan' => 'April',     'target' => 95, 'num' => 2,  'den' => 7,  'capaian' => 28.57, 'status' => 'merah'],
                    ['bulan' => 'Mei',       'target' => 95, 'num' => 10, 'den' => 11, 'capaian' => 90.91, 'status' => 'kuning'],
                    ['bulan' => 'Juni',      'target' => 95, 'num' => 6,  'den' => 10, 'capaian' => 60.00, 'status' => 'merah'],
                    ['bulan' => 'Juli',      'target' => 95, 'num' => 11, 'den' => 12, 'capaian' => 91.67, 'status' => 'kuning'],
                    ['bulan' => 'Agustus',   'target' => 95, 'num' => 9,  'den' => 15, 'capaian' => 60.00, 'status' => 'merah'],
                    ['bulan' => 'September', 'target' => 95, 'num' => 13, 'den' => 17, 'capaian' => 76.47, 'status' => 'merah'],
                    ['bulan' => 'Oktober',   'target' => 95, 'num' => 0,  'den' => 0,  'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 95, 'num' => 0,  'den' => 0,  'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 95, 'num' => 0,  'den' => 0,  'capaian' => null,  'status' => 'gray'],
                ]
            ],
            'IMP-RS 2' => [
                'kode' => 'IMP-RS 2',
                'judul' => 'Kepatuhan Waktu Visite DPJP Internal (06.00 - 14.00)',
                'fokus_kars' => 'Indikator Sesuai Renstra RS (Prioritas 2)',
                'unit' => 'Instalasi Rawat Inap (IRNA)',
                'standar' => 90,
                'satuan' => '%',
                'operator' => '>=',
                'target_tahunan' => 90,
                'capaian_tahunan' => 87.38,
                'analisa_tw1' => 'Capaian awal tahun 75.23% meningkat ke 89.96% di bulan Maret seiring diterapkannya notifikasi pengingat visite ke DPJP.',
                'analisa_tw2' => 'Triwulan II mencapai target dengan rata-rata di atas 91% (Mei 91.84%, Juni 91.59%).',
                'analisa_tw3' => 'Triwulan III stabil di rentang 87.7% - 93.3%. Kendala utama jika dokter melakukan tindakan operasi elektif pagi hari.',
                'rekomendasi' => 'Peningkatan integrasi absensi visite elektronik pada EMR dan konfirmasi jadwal operasi dengan dokter terkait.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 90, 'num' => 486, 'den' => 646, 'capaian' => 75.23, 'status' => 'merah'],
                    ['bulan' => 'Februari',  'target' => 90, 'num' => 545, 'den' => 640, 'capaian' => 85.16, 'status' => 'kuning'],
                    ['bulan' => 'Maret',     'target' => 90, 'num' => 618, 'den' => 687, 'capaian' => 89.96, 'status' => 'kuning'],
                    ['bulan' => 'April',     'target' => 90, 'num' => 456, 'den' => 543, 'capaian' => 83.98, 'status' => 'kuning'],
                    ['bulan' => 'Mei',       'target' => 90, 'num' => 551, 'den' => 600, 'capaian' => 91.84, 'status' => 'hijau'],
                    ['bulan' => 'Juni',      'target' => 90, 'num' => 544, 'den' => 594, 'capaian' => 91.59, 'status' => 'hijau'],
                    ['bulan' => 'Juli',      'target' => 90, 'num' => 575, 'den' => 616, 'capaian' => 93.34, 'status' => 'hijau'],
                    ['bulan' => 'Agustus',   'target' => 90, 'num' => 506, 'den' => 577, 'capaian' => 87.70, 'status' => 'kuning'],
                    ['bulan' => 'September', 'target' => 90, 'num' => 522, 'den' => 595, 'capaian' => 87.73, 'status' => 'kuning'],
                    ['bulan' => 'Oktober',   'target' => 90, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 90, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 90, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                ]
            ],
            'IMN 1' => [
                'kode' => 'IMN 1',
                'judul' => 'Kepatuhan Kebersihan Tangan (Hand Hygiene 6 Langkah 5 Momen)',
                'fokus_kars' => 'Indikator Mutu Nasional (Kemenkes)',
                'unit' => 'Komite PPI & Seluruh Unit',
                'standar' => 85,
                'satuan' => '%',
                'operator' => '>=',
                'target_tahunan' => 85,
                'capaian_tahunan' => 91.24,
                'analisa_tw1' => 'Kepatuhan kebersihan tangan triwulan 1 rata-rata mencapai 89.4% memenuhi standar nasional.',
                'analisa_tw2' => 'Triwulan II mencapai 92.1% dengan ketersediaan handrub di setiap bed pasien.',
                'analisa_tw3' => 'Triwulan III mencapai 92.2% didukung audit harian IPCN.',
                'rekomendasi' => 'Pertahankan ketersediaan logistik handrub dan penguatan edukasi cuci tangan bagi keluarga pasien.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 85, 'num' => 782, 'den' => 890, 'capaian' => 87.87, 'status' => 'hijau'],
                    ['bulan' => 'Februari',  'target' => 85, 'num' => 810, 'den' => 912, 'capaian' => 88.82, 'status' => 'hijau'],
                    ['bulan' => 'Maret',     'target' => 85, 'num' => 840, 'den' => 920, 'capaian' => 91.30, 'status' => 'hijau'],
                    ['bulan' => 'April',     'target' => 85, 'num' => 790, 'den' => 860, 'capaian' => 91.86, 'status' => 'hijau'],
                    ['bulan' => 'Mei',       'target' => 85, 'num' => 830, 'den' => 900, 'capaian' => 92.22, 'status' => 'hijau'],
                    ['bulan' => 'Juni',      'target' => 85, 'num' => 815, 'den' => 880, 'capaian' => 92.61, 'status' => 'hijau'],
                    ['bulan' => 'Juli',      'target' => 85, 'num' => 850, 'den' => 920, 'capaian' => 92.39, 'status' => 'hijau'],
                    ['bulan' => 'Agustus',   'target' => 85, 'num' => 820, 'den' => 895, 'capaian' => 91.62, 'status' => 'hijau'],
                    ['bulan' => 'September', 'target' => 85, 'num' => 845, 'den' => 915, 'capaian' => 92.35, 'status' => 'hijau'],
                    ['bulan' => 'Oktober',   'target' => 85, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 85, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 85, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                ]
            ],
        ];
    }
}
