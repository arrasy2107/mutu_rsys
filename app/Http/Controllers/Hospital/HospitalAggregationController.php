<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HospitalAggregationController extends Controller
{
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Ambil Indikator Prioritas RS (IMP-RS & INM)
        $indicators = Indicator::query()
            ->where(function ($q) {
                $q->whereJsonContains('jenis', 'IMP-RS')
                  ->orWhereJsonContains('jenis', 'INM')
                  ->orWhereNull('unit_id');
            })
            ->with(['unit'])
            ->orderBy('kode')
            ->get();

        // Sample Real Data Rekapitulasi Multi-PPA Form Edukasi dari IMUT RSYS 2026.xlsx
        $edukasiPpaData = [
            ['bulan' => 'Januari',   'keseluruhan' => 85.24, 'dokter' => 67.00, 'perawat' => 90.23, 'apoteker' => 88.00, 'dietisen' => 48.00],
            ['bulan' => 'Februari',  'keseluruhan' => 87.55, 'dokter' => 73.24, 'perawat' => 91.93, 'apoteker' => 81.76, 'dietisen' => 83.08],
            ['bulan' => 'Maret',     'keseluruhan' => 87.77, 'dokter' => 66.87, 'perawat' => 94.86, 'apoteker' => 94.07, 'dietisen' => 61.00],
            ['bulan' => 'April',     'keseluruhan' => 87.62, 'dokter' => 63.24, 'perawat' => 92.61, 'apoteker' => 96.67, 'dietisen' => 95.38],
            ['bulan' => 'Mei',       'keseluruhan' => 84.88, 'dokter' => 55.00, 'perawat' => 96.07, 'apoteker' => 85.63, 'dietisen' => 22.00],
            ['bulan' => 'Juni',      'keseluruhan' => 87.54, 'dokter' => 67.92, 'perawat' => 93.01, 'apoteker' => 85.71, 'dietisen' => 57.78],
            ['bulan' => 'Juli',      'keseluruhan' => 87.42, 'dokter' => 65.25, 'perawat' => 95.50, 'apoteker' => 90.40, 'dietisen' => 74.28],
            ['bulan' => 'Agustus',   'keseluruhan' => 86.95, 'dokter' => 62.36, 'perawat' => 94.80, 'apoteker' => 94.83, 'dietisen' => 79.31],
            ['bulan' => 'September', 'keseluruhan' => 85.00, 'dokter' => 49.67, 'perawat' => 93.57, 'apoteker' => 94.54, 'dietisen' => 97.64],
            ['bulan' => 'Oktober',   'keseluruhan' => 0.00,  'dokter' => 0.00,  'perawat' => 0.00,  'apoteker' => 0.00,  'dietisen' => 0.00],
            ['bulan' => 'November',  'keseluruhan' => 0.00,  'dokter' => 0.00,  'perawat' => 0.00,  'apoteker' => 0.00,  'dietisen' => 0.00],
            ['bulan' => 'Desember',  'keseluruhan' => 0.00,  'dokter' => 0.00,  'perawat' => 0.00,  'apoteker' => 0.00,  'dietisen' => 0.00],
        ];

        // Sample Handover & Laporan Anestesi dari IMUT RSYS 2026.xlsx
        $handoverIbsData = [
            ['bulan' => 'Januari',   'anestesi' => 58.62, 'handover' => 37.00],
            ['bulan' => 'Februari',  'anestesi' => 73.21, 'handover' => 41.00],
            ['bulan' => 'Maret',     'anestesi' => 73.87, 'handover' => 62.00],
            ['bulan' => 'April',     'anestesi' => 74.04, 'handover' => 63.00],
            ['bulan' => 'Mei',       'anestesi' => 72.83, 'handover' => 72.00],
            ['bulan' => 'Juni',      'anestesi' => 76.16, 'handover' => 74.00],
            ['bulan' => 'Juli',      'anestesi' => 72.68, 'handover' => 73.00],
            ['bulan' => 'Agustus',   'anestesi' => 66.06, 'handover' => 77.00],
            ['bulan' => 'September', 'anestesi' => 79.03, 'handover' => 74.00],
            ['bulan' => 'Oktober',   'anestesi' => 0.00,  'handover' => 0.00],
            ['bulan' => 'November',  'anestesi' => 0.00,  'handover' => 0.00],
            ['bulan' => 'Desember',  'anestesi' => 0.00,  'handover' => 0.00],
        ];

        // Ringkasan Agregasi Lintas Unit per IMP-RS
        $summaryRollup = [
            [
                'kode' => 'IMP-RS 1',
                'judul' => 'Ketepatan Waktu Pemberian AB Profilaksis Sebelum Operasi',
                'area_kars' => 'Pelayanan Klinis',
                'target' => 95,
                'target_op' => '>=',
                'satuan' => '%',
                'unit_pengumpul' => 'Instalasi Bedah Sentral (IBS)',
                'sumber_tipe' => 'Single Unit Direct',
                'status_agregasi' => 'Otomatis dari Tally Sheet IBS',
                'capaian_tw1' => 64.73,
                'capaian_tw2' => 60.16,
                'capaian_tw3' => 76.05,
                'capaian_tahun' => 68.75,
                'status' => 'Belum Tercapai',
            ],
            [
                'kode' => 'IMP-RS 2',
                'judul' => 'Kepatuhan Waktu Visite DPJP Internal',
                'area_kars' => 'Sesuai Renstra RS',
                'target' => 90,
                'target_op' => '>=',
                'satuan' => '%',
                'unit_pengumpul' => 'Instalasi Rawat Inap (IRNA)',
                'sumber_tipe' => 'Single Unit Direct',
                'status_agregasi' => 'Otomatis dari Rekam Medik IRNA',
                'capaian_tw1' => 80.34,
                'capaian_tw2' => 90.69,
                'capaian_tw3' => 88.78,
                'capaian_tahun' => 87.38,
                'status' => 'Mendekati Target',
            ],
            [
                'kode' => 'IMP-RS 3',
                'judul' => 'Kelengkapan Pengisian Laporan Anestesi',
                'area_kars' => 'Perbaikan Sistem',
                'target' => 100,
                'target_op' => '=',
                'satuan' => '%',
                'unit_pengumpul' => 'Instalasi Bedah Sentral (IBS)',
                'sumber_tipe' => 'Single Unit Direct',
                'status_agregasi' => 'Otomatis dari EMR IBS',
                'capaian_tw1' => 68.57,
                'capaian_tw2' => 74.34,
                'capaian_tw3' => 72.59,
                'capaian_tahun' => 71.57,
                'status' => 'Belum Tercapai',
            ],
            [
                'kode' => 'IMP-RS 4',
                'judul' => 'Ketepatan Pengisian Aturan Pakai Obat di EMR oleh Medis',
                'area_kars' => 'Manajemen Risiko',
                'target' => 100,
                'target_op' => '=',
                'satuan' => '%',
                'unit_pengumpul' => 'Instalasi Farmasi',
                'sumber_tipe' => 'Single Unit Direct',
                'status_agregasi' => 'Otomatis dari EMR Prescription Order',
                'capaian_tw1' => 69.83,
                'capaian_tw2' => 69.24,
                'capaian_tw3' => 69.25,
                'capaian_tahun' => 69.44,
                'status' => 'Belum Tercapai',
            ],
            [
                'kode' => 'IMP-RS 9',
                'judul' => 'Kepatuhan Kebersihan Tangan (Hand Hygiene)',
                'area_kars' => 'Sasaran Keselamatan Pasien (SKP 5)',
                'target' => 85,
                'target_op' => '>=',
                'satuan' => '%',
                'unit_pengumpul' => 'Seluruh Instalasi / Komite PPI',
                'sumber_tipe' => 'Multi-Unit Rollup',
                'status_agregasi' => 'Agregasi Terbobot Seluruh Unit',
                'capaian_tw1' => 86.40,
                'capaian_tw2' => 88.10,
                'capaian_tw3' => 89.50,
                'capaian_tahun' => 88.00,
                'status' => 'Tercapai',
            ],
        ];

        return Inertia::render('Hospital/Aggregation/Index', [
            'tahun'          => $tahun,
            'bulanList'      => $bulanList,
            'indicators'     => $indicators,
            'edukasiPpaData' => $edukasiPpaData,
            'handoverIbsData'=> $handoverIbsData,
            'summaryRollup'  => $summaryRollup,
        ]);
    }
}
