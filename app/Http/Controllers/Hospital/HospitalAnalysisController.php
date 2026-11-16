<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Indicator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HospitalAnalysisController extends Controller
{
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $selectedKode = $request->query('kode', 'IMP-RS 1');

        // Data 12 Bulan Riil dari sheet DAT-AN IMP & DAT-AN IMN di IMUT RSYS 2026.xlsx
        $indicatorsData = [
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
                'analisa_tw3' => 'Bulan September mencapai rekor 92.76% dari 1.753 total pasien yang diobservasi.',
                'rekomendasi' => 'Pertahankan pemantauan harian dan berikan apresiasi kepada DPJP dengan kepatuhan visite 100%.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 90, 'num' => 817,  'den' => 1086, 'capaian' => 75.23, 'status' => 'merah'],
                    ['bulan' => 'Februari',  'target' => 90, 'num' => 455,  'den' => 600,  'capaian' => 75.83, 'status' => 'merah'],
                    ['bulan' => 'Maret',     'target' => 90, 'num' => 950,  'den' => 1056, 'capaian' => 89.96, 'status' => 'kuning'],
                    ['bulan' => 'April',     'target' => 90, 'num' => 1124, 'den' => 1268, 'capaian' => 88.64, 'status' => 'kuning'],
                    ['bulan' => 'Mei',       'target' => 90, 'num' => 1036, 'den' => 1128, 'capaian' => 91.84, 'status' => 'hijau'],
                    ['bulan' => 'Juni',      'target' => 90, 'num' => 1132, 'den' => 1236, 'capaian' => 91.59, 'status' => 'hijau'],
                    ['bulan' => 'Juli',      'target' => 90, 'num' => 1444, 'den' => 1577, 'capaian' => 91.57, 'status' => 'hijau'],
                    ['bulan' => 'Agustus',   'target' => 90, 'num' => 1599, 'den' => 1950, 'capaian' => 82.00, 'status' => 'merah'],
                    ['bulan' => 'September', 'target' => 90, 'num' => 1626, 'den' => 1753, 'capaian' => 92.76, 'status' => 'hijau'],
                    ['bulan' => 'Oktober',   'target' => 90, 'num' => 0,    'den' => 0,    'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 90, 'num' => 0,    'den' => 0,    'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 90, 'num' => 0,    'den' => 0,    'capaian' => null,  'status' => 'gray'],
                ]
            ],
            'IMP-RS 3' => [
                'kode' => 'IMP-RS 3',
                'judul' => 'Kelengkapan Pengisian Laporan Anestesi',
                'fokus_kars' => 'Indikator Terkait Perbaikan Sistem (Prioritas 3)',
                'unit' => 'Instalasi Bedah Sentral (IBS)',
                'standar' => 100,
                'satuan' => '%',
                'operator' => '=',
                'target_tahunan' => 100,
                'capaian_tahunan' => 71.57,
                'analisa_tw1' => 'Capaian TW I berkisar antara 58% - 73%. Form laporan anestesi belum terisi lengkap terutama pada waktu pemulihan di RR.',
                'analisa_tw2' => 'TW II stabil di kisaran 72% - 76%. Supervisi internal perawat RR mulai berjalan berkala.',
                'analisa_tw3' => 'Bulan September mencapai 79.03%. Peningkatan pendokumentasian mulai terkoordinir dengan baik.',
                'rekomendasi' => 'Peningkatan audit berkala dan sosialisasi SOP dokumentasi anestesi digital di EMR.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 100, 'num' => 187, 'den' => 319, 'capaian' => 58.62, 'status' => 'merah'],
                    ['bulan' => 'Februari',  'target' => 100, 'num' => 194, 'den' => 265, 'capaian' => 73.21, 'status' => 'merah'],
                    ['bulan' => 'Maret',     'target' => 100, 'num' => 147, 'den' => 199, 'capaian' => 73.87, 'status' => 'merah'],
                    ['bulan' => 'April',     'target' => 100, 'num' => 211, 'den' => 285, 'capaian' => 74.04, 'status' => 'merah'],
                    ['bulan' => 'Mei',       'target' => 100, 'num' => 193, 'den' => 265, 'capaian' => 72.83, 'status' => 'merah'],
                    ['bulan' => 'Juni',      'target' => 100, 'num' => 214, 'den' => 281, 'capaian' => 76.16, 'status' => 'merah'],
                    ['bulan' => 'Juli',      'target' => 100, 'num' => 258, 'den' => 355, 'capaian' => 72.68, 'status' => 'merah'],
                    ['bulan' => 'Agustus',   'target' => 100, 'num' => 216, 'den' => 327, 'capaian' => 66.06, 'status' => 'merah'],
                    ['bulan' => 'September', 'target' => 100, 'num' => 245, 'den' => 310, 'capaian' => 79.03, 'status' => 'merah'],
                    ['bulan' => 'Oktober',   'target' => 100, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 100, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 100, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                ]
            ],
            'IMP-RS 4' => [
                'kode' => 'IMP-RS 4',
                'judul' => 'Ketepatan Pengisian Aturan Pakai Obat di EMR oleh Medis',
                'fokus_kars' => 'Indikator Terkait Manajemen Risiko (Prioritas 4)',
                'unit' => 'Instalasi Farmasi',
                'standar' => 100,
                'satuan' => '%',
                'operator' => '=',
                'target_tahunan' => 100,
                'capaian_tahunan' => 69.44,
                'analisa_tw1' => 'Capaian stabil di kisaran 68% - 70%. Banyak dokter yang tidak mengisi signa lengkap atau hanya memilih aturan pakai default.',
                'analisa_tw2' => 'TW II rata-rata 69.24%. Telah dilakukan perbaikan master signa di EMR Avicena.',
                'analisa_tw3' => 'TW III capaian 69.25%. Evaluasi menunjukkan perlunya form resep elektronik dengan mandatory field.',
                'rekomendasi' => 'Kunci field aturan pakai (mandatory) pada EMR agar resep tidak bisa difinalisasi sebelum signa terisi jelas.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 100, 'num' => 2750, 'den' => 4000, 'capaian' => 68.74, 'status' => 'merah'],
                    ['bulan' => 'Februari',  'target' => 100, 'num' => 2820, 'den' => 4000, 'capaian' => 70.52, 'status' => 'merah'],
                    ['bulan' => 'Maret',     'target' => 100, 'num' => 2809, 'den' => 4000, 'capaian' => 70.22, 'status' => 'merah'],
                    ['bulan' => 'April',     'target' => 100, 'num' => 2762, 'den' => 4000, 'capaian' => 69.05, 'status' => 'merah'],
                    ['bulan' => 'Mei',       'target' => 100, 'num' => 2774, 'den' => 4000, 'capaian' => 69.34, 'status' => 'merah'],
                    ['bulan' => 'Juni',      'target' => 100, 'num' => 2774, 'den' => 4000, 'capaian' => 69.34, 'status' => 'merah'],
                    ['bulan' => 'Juli',      'target' => 100, 'num' => 2803, 'den' => 4000, 'capaian' => 70.07, 'status' => 'merah'],
                    ['bulan' => 'Agustus',   'target' => 100, 'num' => 2750, 'den' => 4000, 'capaian' => 68.76, 'status' => 'merah'],
                    ['bulan' => 'September', 'target' => 100, 'num' => 2756, 'den' => 4000, 'capaian' => 68.91, 'status' => 'merah'],
                    ['bulan' => 'Oktober',   'target' => 100, 'num' => 0,    'den' => 0,    'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 100, 'num' => 0,    'den' => 0,    'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 100, 'num' => 0,    'den' => 0,    'capaian' => null,  'status' => 'gray'],
                ]
            ],
            'IMP-RS 9' => [
                'kode' => 'IMP-RS 9',
                'judul' => 'Kepatuhan Kebersihan Tangan (Hand Hygiene)',
                'fokus_kars' => 'Sasaran Keselamatan Pasien (SKP 5)',
                'unit' => 'Seluruh Instalasi / Komite PPI',
                'standar' => 85,
                'satuan' => '%',
                'operator' => '>=',
                'target_tahunan' => 85,
                'capaian_tahunan' => 88.00,
                'analisa_tw1' => 'Capaian TW I melampaui target (86.40%) dengan observasi minimal 200 peluang per unit.',
                'analisa_tw2' => 'TW II meningkat ke 88.10% seiring kelengkapan fasilitas handrub di setiap titik rawat.',
                'analisa_tw3' => 'TW III stabil di angka 89.50%. Moment 2 (sebelum tindakan aseptik) terus didorong.',
                'rekomendasi' => 'Pertahankan ketersediaan handrub dan lakukan audit berkala oleh IPCLN.',
                'bulanan' => [
                    ['bulan' => 'Januari',   'target' => 85, 'num' => 172, 'den' => 200, 'capaian' => 86.00, 'status' => 'hijau'],
                    ['bulan' => 'Februari',  'target' => 85, 'num' => 173, 'den' => 200, 'capaian' => 86.50, 'status' => 'hijau'],
                    ['bulan' => 'Maret',     'target' => 85, 'num' => 174, 'den' => 200, 'capaian' => 87.00, 'status' => 'hijau'],
                    ['bulan' => 'April',     'target' => 85, 'num' => 175, 'den' => 200, 'capaian' => 87.50, 'status' => 'hijau'],
                    ['bulan' => 'Mei',       'target' => 85, 'num' => 176, 'den' => 200, 'capaian' => 88.00, 'status' => 'hijau'],
                    ['bulan' => 'Juni',      'target' => 85, 'num' => 177, 'den' => 200, 'capaian' => 88.50, 'status' => 'hijau'],
                    ['bulan' => 'Juli',      'target' => 85, 'num' => 178, 'den' => 200, 'capaian' => 89.00, 'status' => 'hijau'],
                    ['bulan' => 'Agustus',   'target' => 85, 'num' => 179, 'den' => 200, 'capaian' => 89.50, 'status' => 'hijau'],
                    ['bulan' => 'September', 'target' => 85, 'num' => 180, 'den' => 200, 'capaian' => 90.00, 'status' => 'hijau'],
                    ['bulan' => 'Oktober',   'target' => 85, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'November',  'target' => 85, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                    ['bulan' => 'Desember',  'target' => 85, 'num' => 0,   'den' => 0,   'capaian' => null,  'status' => 'gray'],
                ]
            ],
        ];

        $currentIndicator = $indicatorsData[$selectedKode] ?? $indicatorsData['IMP-RS 1'];

        return Inertia::render('Hospital/Analysis/Index', [
            'tahun'            => $tahun,
            'selectedKode'     => $selectedKode,
            'availableCodes'   => array_keys($indicatorsData),
            'indicatorsData'   => $indicatorsData,
            'currentIndicator' => $currentIndicator,
        ]);
    }
}
