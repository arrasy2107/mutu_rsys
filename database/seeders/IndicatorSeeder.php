<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $inm = IndicatorCategory::where('kode', 'INM')->first();
        $impRs = IndicatorCategory::where('kode', 'IMP-RS')->first();
        $admin = User::where('role', 'superadmin')->first();

        if (!$inm || !$admin) return;

        // 13 Indikator Nasional Mutu (INM) sesuai PMK No. 30 Tahun 2022
        $indicators = [
            [
                'kode'             => 'INM-01',
                'judul'            => 'Kepatuhan Kebersihan Tangan',
                'dasar_pemikiran'  => 'Kebersihan tangan merupakan salah satu cara paling efektif mencegah infeksi di rumah sakit',
                'dimensi_mutu'     => 'Keselamatan',
                'tujuan'           => 'Mengukur kepatuhan petugas kesehatan dalam melakukan kebersihan tangan sesuai 5 momen WHO',
                'formula'          => '(Jumlah tindakan kebersihan tangan yang dilakukan / Jumlah peluang kebersihan tangan) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 85.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-02',
                'judul'            => 'Kepatuhan Penggunaan Alat Pelindung Diri (APD)',
                'dasar_pemikiran'  => 'Penggunaan APD yang benar melindungi petugas dan pasien dari transmisi infeksi',
                'dimensi_mutu'     => 'Keselamatan',
                'tujuan'           => 'Mengukur kepatuhan petugas menggunakan APD sesuai indikasi',
                'formula'          => '(Jumlah petugas yang menggunakan APD sesuai indikasi / Total observasi petugas) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 100.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-03',
                'judul'            => 'Kepatuhan Identifikasi Pasien',
                'dasar_pemikiran'  => 'Identifikasi yang benar mencegah kesalahan dalam pemberian pelayanan kesehatan',
                'dimensi_mutu'     => 'Keselamatan',
                'tujuan'           => 'Mengukur kepatuhan petugas melakukan identifikasi pasien minimal 2 identitas',
                'formula'          => '(Jumlah tindakan yang dilakukan dengan identifikasi benar / Total tindakan yang diobservasi) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 100.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-04',
                'judul'            => 'Waktu Tanggap Operasi Seksio Sesarea Emergensi',
                'dasar_pemikiran'  => 'Kecepatan tindakan SC emergensi menentukan keselamatan ibu dan bayi',
                'dimensi_mutu'     => 'Efektif, Tepat Waktu',
                'tujuan'           => 'Mengukur kecepatan pelayanan SC emergensi sejak keputusan operasi hingga insisi',
                'formula'          => '(Jumlah SC emergensi yang selesai ≤ 30 menit / Total SC emergensi) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-05',
                'judul'            => 'Waktu Tanggap Pelayanan Gawat Darurat',
                'dasar_pemikiran'  => 'Kecepatan respons di IGD menentukan luaran klinis pasien gawat darurat',
                'dimensi_mutu'     => 'Tepat Waktu, Efektif',
                'tujuan'           => 'Mengukur kecepatan pelayanan di IGD dari pasien datang hingga mendapat tindakan pertama',
                'formula'          => '(Jumlah pasien triage kategori 1 & 2 yang tertangani ≤ 5 menit / Total pasien triage 1 & 2) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-06',
                'judul'            => 'Waktu Tunggu Rawat Jalan',
                'dasar_pemikiran'  => 'Waktu tunggu yang lama mengurangi kepuasan pasien dan akses pelayanan',
                'dimensi_mutu'     => 'Tepat Waktu, Berorientasi Pasien',
                'tujuan'           => 'Mengukur lamanya waktu tunggu pasien rawat jalan dari pendaftaran hingga dilayani dokter',
                'formula'          => '(Jumlah pasien yang menunggu ≤ 60 menit / Total pasien rawat jalan) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-07',
                'judul'            => 'Penundaan Operasi Elektif',
                'dasar_pemikiran'  => 'Penundaan operasi mengganggu perencanaan klinis dan menambah beban pasien',
                'dimensi_mutu'     => 'Efisien, Tepat Waktu',
                'tujuan'           => 'Mengukur angka penundaan operasi elektif yang terjadwal',
                'formula'          => '(Jumlah operasi elektif yang ditunda / Total operasi elektif terjadwal) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 5.00,
                'target_operator'  => '<=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-08',
                'judul'            => 'Kepatuhan Waktu Visite Dokter Penanggung Jawab Pelayanan (DPJP)',
                'dasar_pemikiran'  => 'Visite tepat waktu memastikan pemantauan dan pengambilan keputusan klinis yang baik',
                'dimensi_mutu'     => 'Tepat Waktu, Efektif',
                'tujuan'           => 'Mengukur kepatuhan DPJP dalam melakukan visite pada jam yang ditetapkan',
                'formula'          => '(Jumlah pasien yang divisite DPJP sebelum jam 14.00 / Total pasien rawat inap) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'harian',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-09',
                'judul'            => 'Tidak Adanya Kejadian Pasien Jatuh',
                'dasar_pemikiran'  => 'Pasien jatuh merupakan insiden keselamatan yang dapat dicegah',
                'dimensi_mutu'     => 'Keselamatan',
                'tujuan'           => 'Mengukur angka kejadian pasien jatuh selama perawatan di RS',
                'formula'          => '(Jumlah kejadian pasien jatuh / Total hari perawatan pasien) × 1000',
                'tipe'             => 'outcome',
                'target_capaian'   => 0.00,
                'target_operator'  => '<=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-10',
                'judul'            => 'Kepatuhan Terhadap Alur Klinis (Clinical Pathway)',
                'dasar_pemikiran'  => 'Clinical pathway memastikan standar mutu pelayanan yang konsisten',
                'dimensi_mutu'     => 'Efektif, Berorientasi Bukti',
                'tujuan'           => 'Mengukur kepatuhan dokter dalam menerapkan clinical pathway yang telah ditetapkan',
                'formula'          => '(Jumlah kasus yang dikelola sesuai CP / Total kasus yang menjadi target CP) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-11',
                'judul'            => 'Kepatuhan Penggunaan Formularium Nasional (Fornas)',
                'dasar_pemikiran'  => 'Kepatuhan fornas mendukung efisiensi dan mutu pengobatan',
                'dimensi_mutu'     => 'Efisien, Efektif',
                'tujuan'           => 'Mengukur kepatuhan dokter dalam meresepkan obat sesuai formularium nasional',
                'formula'          => '(Jumlah resep sesuai Fornas / Total resep yang dikeluarkan) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-12',
                'judul'            => 'Kepuasan Pasien',
                'dasar_pemikiran'  => 'Kepuasan pasien merupakan indikator kualitas layanan yang berpusat pada pasien',
                'dimensi_mutu'     => 'Berorientasi Pasien',
                'tujuan'           => 'Mengukur tingkat kepuasan pasien terhadap pelayanan RS secara keseluruhan',
                'formula'          => '(Jumlah pasien yang puas dan sangat puas / Total responden survei) × 100%',
                'tipe'             => 'outcome',
                'target_capaian'   => 76.61,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
            [
                'kode'             => 'INM-13',
                'judul'            => 'Kecepatan Waktu Tanggap Komplain',
                'dasar_pemikiran'  => 'Penyelesaian komplain yang cepat meningkatkan kepercayaan pasien',
                'dimensi_mutu'     => 'Berorientasi Pasien, Tepat Waktu',
                'tujuan'           => 'Mengukur kecepatan RS dalam menangani dan menyelesaikan komplain pasien',
                'formula'          => '(Jumlah komplain yang ditanggapi dan diselesaikan ≤ 3 hari / Total komplain yang masuk) × 100%',
                'tipe'             => 'proses',
                'target_capaian'   => 80.00,
                'target_operator'  => '>=',
                'frekuensi'        => 'bulanan',
                'category_id'      => $inm->id,
            ],
        ];

        foreach ($indicators as $data) {
            Indicator::updateOrCreate(
                ['kode' => $data['kode']],
                array_merge($data, [
                    'jenis'      => 'IMN',
                    'is_active'  => true,
                    'created_by' => $admin->id,
                ])
            );
        }
    }
}
