<?php

namespace Database\Seeders;

use App\Models\CustomFormFormat;
use App\Models\Indicator;
use App\Models\IndicatorSelection;
use App\Models\IndicatorSelectionItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class PemasaranUnitSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::firstOrCreate(
            ['kode' => 'MARKETING'],
            [
                'nama' => 'Pemasaran',
                'deskripsi' => 'Unit Pemasaran dan Penanganan Komplain Rumah Sakit',
                'aktif' => true,
            ]
        );

        $adminUser = User::where('role', 'superadmin')->first() ?? User::first();
        $adminId = $adminUser?->id;

        // ── 1. PEMILIHAN INDIKATOR MUTU UNIT (Hanya 2 Usulan sesuai Sheet PEMILIHAN di Excel) ──
        $selection = IndicatorSelection::updateOrCreate(
            [
                'unit_id' => $unit->id,
                'tanggal' => '2026-01-05',
            ],
            [
                'tipe'       => 'unit',
                'jam'        => '09:00',
                'tempat'     => 'Ruang Rapat Unit Pemasaran',
                'notulen'    => 'Rapat penetapan prioritas usulan indikator mutu Unit Pemasaran tahun 2026.',
                'status'     => 'final',
                'created_by' => $adminId,
            ]
        );

        // Hapus items lama agar bersih
        $selection->items()->delete();

        // Usulan 1 di Pemilihan
        IndicatorSelectionItem::create([
            'selection_id'              => $selection->id,
            'usulan_imut'               => 'Pelaksanaan review PKS terlaksana sesuai jadwal',
            'data_pendukung'            => 'Beberapa deal PKS membutuhkan waktu yang lama',
            'ssi_visi_misi'             => true,
            'problem_prone'             => true,
            'high_volume'               => true,
            'high_risk_cost'            => true,
            'ssi_aturan'                => true,
            'dpt_dikendalikan_unit'     => true,
            'dpt_diukur'                => true,
            'is_lolos_skrining'         => true,
            'skor_masalah_banyak'       => 2,
            'skor_high_volume'          => 3,
            'skor_high_proses'          => 3,
            'skor_ketidakpuasan'        => 3,
            'skor_kemudahan_ukur'       => 4,
            'skor_ketentuan_pemerintah' => 1,
            'skor_sesuai_renstra'       => 4,
            'skor_patient_experience'   => 2,
            'total_skor'                => 20,
            'is_ditetapkan'             => true,
        ]);

        // Usulan 2 di Pemilihan
        IndicatorSelectionItem::create([
            'selection_id'              => $selection->id,
            'usulan_imut'               => 'Terlaksananya promosi sesuai jadwal',
            'data_pendukung'            => 'Sering tidak terlaksana promosi rumah sakit',
            'ssi_visi_misi'             => true,
            'problem_prone'             => true,
            'high_volume'               => true,
            'high_risk_cost'            => true,
            'ssi_aturan'                => true,
            'dpt_dikendalikan_unit'     => true,
            'dpt_diukur'                => true,
            'is_lolos_skrining'         => true,
            'skor_masalah_banyak'       => 3,
            'skor_high_volume'          => 3,
            'skor_high_proses'          => 3,
            'skor_ketidakpuasan'        => 4,
            'skor_kemudahan_ukur'       => 4,
            'skor_ketentuan_pemerintah' => 1,
            'skor_sesuai_renstra'       => 4,
            'skor_patient_experience'   => 2,
            'total_skor'                => 22,
            'is_ditetapkan'             => true,
        ]);

        // ── 2. PROFIL INDIKATOR MUTU UNIT (Ada 3 Indikator Resmi sesuai Sheet PROFIL di Excel) ──

        // IMU 1: TERLAKSANANYA PROMOSI SESUAI JADWAL
        $ind1 = Indicator::updateOrCreate(
            ['kode' => 'MKT-01', 'unit_id' => $unit->id],
            [
                'judul'                  => 'TERLAKSANANYA PROMOSI SESUAI JADWAL',
                'jenis'                  => ['IMU'],
                'dasar_pemikiran'        => 'Program Kerja Unit Pemasaran 2026',
                'dimensi_mutu'           => ['Efisien', 'Tepat Waktu'],
                'tujuan'                 => 'Mengukur terlaksananya kegiatan promosi sesuai jadwal agar program kerja terlaksana tepat waktu secara efisien',
                'definisi_operasional'   => "Promosi adalah kegiatan memperkenalkan dan mempengaruhi konsumen agar tertarik menggunakan pelayanan RSYS.\nTerlaksananya promosi sesuai jadwal adalah pelaksanaan promosi sesuai jadwal yang terdapat pada program kerja Unit Pemasaran tahun 2026.",
                'jenis_indikator'        => 'Output',
                'satuan_pengukuran'      => 'Persentase',
                'numerator_keterangan'   => 'Jadwal promosi yang terlaksana sesuai jadwal dalam satu bulan',
                'denominator_keterangan' => 'Jumlah jadwal promosi dalam satu bulan',
                'target_capaian'         => 100,
                'target_operator'        => '>=',
                'kriteria_inklusi'       => 'Semua promkes yang terjadwal di dalam program kerja.',
                'kriteria_eksklusi'      => 'Promkes yang batal dilaksanakan karena Pihak Ketiga.',
                'formula'                => '( Numerator / Denominator ) x 100%',
                'sumber_data'            => 'Data sekunder (Laporan Pelaksanaan Promkes)',
                'frekuensi'              => 'Bulanan',
                'periode_analisis'       => 'Bulanan, Tahunan',
                'penanggung_jawab'       => 'Penanggung Jawab data dan Validator : PJ. Unit Pemasaran',
                'pengumpul_data'         => 'Staff Unit Pemasaran',
                'is_active'              => true,
                'form_layout_type'       => 'fixed_rows',
                'created_by'             => $adminId,
            ]
        );

        // Format Kustom Form Pengumpul Data untuk IMU 1 (FORMULIR KEGIATAN PROMOSI)
        $formatPromosi = CustomFormFormat::updateOrCreate(
            ['indicator_id' => $ind1->id],
            [
                'unit_id'            => $unit->id,
                'nama_format'        => 'FORMULIR KEGIATAN PROMOSI',
                'table_title'        => 'Tabel Pelaksanaan Kegiatan Promosi Pemasaran',
                'row_type'           => 'fixed_rows',
                'petunjuk_pengisian' => 'Catat kegiatan promosi yang terlaksana setiap minggu. Kolom Numerator menjumlahkan kegiatan yang sukses terlaksana.',
                'created_by'         => $adminId,
                'is_active'          => true,
                'schema'             => [
                    'columns' => [
                        ['id' => 'c1', 'key' => 'periode', 'title' => 'Minggu Pengamatan', 'type' => 'label', 'role' => 'label', 'width' => '180px'],
                        ['id' => 'c2', 'key' => 'keg_1', 'title' => 'Kegiatan I', 'type' => 'number', 'role' => 'auxiliary', 'width' => '130px'],
                        ['id' => 'c3', 'key' => 'keg_2', 'title' => 'Kegiatan II', 'type' => 'number', 'role' => 'auxiliary', 'width' => '130px'],
                        ['id' => 'c4', 'key' => 'keg_3', 'title' => 'Kegiatan III', 'type' => 'number', 'role' => 'auxiliary', 'width' => '130px'],
                        ['id' => 'c5', 'key' => 'keg_4', 'title' => 'Kegiatan IV', 'type' => 'number', 'role' => 'auxiliary', 'width' => '130px'],
                        ['id' => 'c6', 'key' => 'num', 'title' => 'Total Terlaksana (N)', 'type' => 'number', 'role' => 'numerator', 'width' => '170px'],
                        ['id' => 'c7', 'key' => 'den', 'title' => 'Jadwal Tanpa Kendala (D)', 'type' => 'number', 'role' => 'denominator', 'width' => '180px'],
                        ['id' => 'c8', 'key' => 'notes', 'title' => 'Kendala Pihak Ketiga / Alasan', 'type' => 'text', 'role' => 'auxiliary', 'width' => 'auto'],
                    ],
                    'rows' => [
                        ['id' => 'r1', 'key' => 'm1', 'label' => 'Minggu I'],
                        ['id' => 'r2', 'key' => 'm2', 'label' => 'Minggu II'],
                        ['id' => 'r3', 'key' => 'm3', 'label' => 'Minggu III'],
                        ['id' => 'r4', 'key' => 'm4', 'label' => 'Minggu IV'],
                    ],
                    'formula_config' => [
                        'numerator_col' => 'num',
                        'denominator_col' => 'den',
                    ]
                ]
            ]
        );

        $ind1->update([
            'form_layout_type' => 'fixed_rows',
            'form_schema' => [
                'format_id'   => $formatPromosi->id,
                'nama_format' => $formatPromosi->nama_format,
                'table_title' => $formatPromosi->table_title,
                'row_type'    => 'fixed_rows',
                'columns'     => $formatPromosi->schema['columns'],
                'rows'        => $formatPromosi->schema['rows'],
            ]
        ]);

        // IMU 2: Pelaksanaan review PKS terlaksana sesuai jadwal
        $ind2 = Indicator::updateOrCreate(
            ['kode' => 'MKT-02', 'unit_id' => $unit->id],
            [
                'judul'                  => 'Pelaksanaan review PKS terlaksana sesuai jadwal',
                'jenis'                  => ['IMU'],
                'dasar_pemikiran'        => 'Kebijakan Rumah Sakit terkait Kemitraan',
                'dimensi_mutu'           => ['Tepat Waktu', 'Efektif'],
                'tujuan'                 => 'Mengukur ketepatan review pks agar kebijakan rumah sakit terlaksana secara efektif dan efisien',
                'definisi_operasional'   => 'Review PKS sesuai jadwal adalah pelaksanaan review isi dan perpanjangan perjanjian kerja sama sesuai dengan jadwal yang telah disepakati.',
                'jenis_indikator'        => 'Output',
                'satuan_pengukuran'      => 'Persentase',
                'numerator_keterangan'   => 'Jumlah pks yang sesuai dengan jadwal',
                'denominator_keterangan' => 'Jumlah pks dalam satu bulan',
                'target_capaian'         => 100,
                'target_operator'        => '>=',
                'kriteria_inklusi'       => 'Semua PKS yang terjadwal dalam bulan berjalan',
                'kriteria_eksklusi'      => 'Tidak ada',
                'formula'                => '( Numerator / Denominator ) x 100%',
                'sumber_data'            => 'Data sekunder (Laporan PKS Perusahaan, Asuransi dan Umum)',
                'frekuensi'              => 'Bulanan',
                'periode_analisis'       => 'Bulanan, Tahunan',
                'penanggung_jawab'       => 'Penanggung jawab data dan validator : PJ. Unit Pemasaran',
                'pengumpul_data'         => 'Staff Unit Pemasaran',
                'is_active'              => true,
                'form_layout_type'       => 'fixed_rows',
                'created_by'             => $adminId,
            ]
        );

        // Format Kustom Form Pengumpul Data untuk IMU 2 (FORMULIR DATA PKS PERUSAHAAN, ASURANSI, DAN UMUM)
        $formatPks = CustomFormFormat::updateOrCreate(
            ['indicator_id' => $ind2->id],
            [
                'unit_id'            => $unit->id,
                'nama_format'        => 'FORMULIR DATA PKS PERUSAHAAN, ASURANSI, DAN UMUM',
                'table_title'        => 'Tabel Evaluasi dan Review PKS Rekanan',
                'row_type'           => 'fixed_rows',
                'petunjuk_pengisian' => 'Masukkan jumlah PKS yang berhasil direview tepat waktu dan total PKS terjadwal untuk masing-masing kelompok mitra.',
                'created_by'         => $adminId,
                'is_active'          => true,
                'schema'             => [
                    'columns' => [
                        ['id' => 'c1', 'key' => 'kategori', 'title' => 'Kelompok Mitra / Asuransi', 'type' => 'label', 'role' => 'label', 'width' => '240px'],
                        ['id' => 'c2', 'key' => 'num', 'title' => 'Sesuai Jadwal (N)', 'type' => 'number', 'role' => 'numerator', 'width' => '170px'],
                        ['id' => 'c3', 'key' => 'den', 'title' => 'Total PKS Terjadwal (D)', 'type' => 'number', 'role' => 'denominator', 'width' => '180px'],
                        ['id' => 'c4', 'key' => 'notes', 'title' => 'Keterangan Evaluasi', 'type' => 'text', 'role' => 'auxiliary', 'width' => 'auto'],
                    ],
                    'rows' => [
                        ['id' => 'r1', 'key' => 'perusahaan', 'label' => 'PKS Perusahaan Swasta & BUMN'],
                        ['id' => 'r2', 'key' => 'asuransi', 'label' => 'PKS Asuransi Komersial & TPA'],
                        ['id' => 'r3', 'key' => 'umum', 'label' => 'PKS Faskes Rujukan & Umum'],
                    ],
                    'formula_config' => [
                        'numerator_col' => 'num',
                        'denominator_col' => 'den',
                    ]
                ]
            ]
        );

        $ind2->update([
            'form_layout_type' => 'fixed_rows',
            'form_schema' => [
                'format_id'   => $formatPks->id,
                'nama_format' => $formatPks->nama_format,
                'table_title' => $formatPks->table_title,
                'row_type'    => 'fixed_rows',
                'columns'     => $formatPks->schema['columns'],
                'rows'        => $formatPks->schema['rows'],
            ]
        ]);

        // IMU 3: KETEPATAN WAKTU TANGGAP KOMPLAIN (TIDAK ADA DI LEMBAR PEMILIHAN, TAPI ADA DI PROFIL RESMI)
        $ind3 = Indicator::updateOrCreate(
            ['kode' => 'MKT-03', 'unit_id' => $unit->id],
            [
                'judul'                  => 'KETEPATAN WAKTU TANGGAP KOMPLAIN',
                'jenis'                  => ['IMU'],
                'dasar_pemikiran'        => "1. UU Nomor 44 Tahun 2009 tentang Rumah Sakit Pasal 32 tentang hak pasien mengajukan komplain.\n2. RS berkewajiban memberikan pelayanan bermutu dan menindaklanjuti keluhan pasien tepat waktu.",
                'dimensi_mutu'           => ['Berorientasi pada pasien'],
                'tujuan'                 => 'Tergambarnya kecepatan rumah sakit dalam merespon keluhan pasien agar dapat diperbaiki dan ditingkatkan sebagai bentuk pemenuhan hak pasien',
                'definisi_operasional'   => "Kecepatan waktu tanggap komplain adalah rentang waktu tanggapan RS berdasarkan grading:\n- Grading Merah: maksimal 1x24 jam\n- Grading Kuning: maksimal 3 hari\n- Grading Hijau: maksimal 7 hari",
                'jenis_indikator'        => 'Proses',
                'satuan_pengukuran'      => 'Persentase',
                'numerator_keterangan'   => 'Jumlah komplain yang ditanggapi dan ditindaklanjuti sesuai waktu yang ditetapkan berdasarkan grading.',
                'denominator_keterangan' => 'Jumlah komplain yg disurvey',
                'target_capaian'         => 80,
                'target_operator'        => '>=',
                'kriteria_inklusi'       => 'Semua komplain (lisan, tertulis, dan media massa)',
                'kriteria_eksklusi'      => 'Tidak ada',
                'formula'                => '( Numerator / Denominator ) x 100%',
                'sumber_data'            => 'Data sekunder dari catatan komplain dan media sosial',
                'frekuensi'              => 'Bulanan',
                'periode_analisis'       => 'Bulanan, Triwulan, Tahunan',
                'penanggung_jawab'       => 'Ka Unit Penanganan Komplain',
                'pengumpul_data'         => 'Staff Unit Penanganan Komplain',
                'is_active'              => true,
                'form_layout_type'       => 'dynamic_rows',
                'created_by'             => $adminId,
            ]
        );

        // Format Kustom Form Pengumpul Data untuk IMU 3 (REGISTER PENANGANAN KOMPLAIN PASIEN)
        $formatKomplain = CustomFormFormat::updateOrCreate(
            ['indicator_id' => $ind3->id],
            [
                'unit_id'            => $unit->id,
                'nama_format'        => 'REGISTER PENANGANAN KOMPLAIN PASIEN',
                'table_title'        => 'Tabel Register Kecepatan Tanggap Komplain Pasien',
                'row_type'           => 'dynamic_rows',
                'petunjuk_pengisian' => 'Tambahkan baris per komplain yang masuk. Centang kolom "Tepat Waktu Sesuai Grading" jika waktu respon memenuhi standar.',
                'created_by'         => $adminId,
                'is_active'          => true,
                'schema'             => [
                    'columns' => [
                        ['id' => 'c1', 'key' => 'no', 'title' => 'No', 'type' => 'label', 'role' => 'label', 'width' => '60px'],
                        ['id' => 'c2', 'key' => 'tgl', 'title' => 'Tgl Masuk', 'type' => 'text', 'role' => 'auxiliary', 'width' => '110px'],
                        ['id' => 'c3', 'key' => 'media', 'title' => 'Media Komplain', 'type' => 'text', 'role' => 'auxiliary', 'width' => '140px'],
                        ['id' => 'c4', 'key' => 'grading', 'title' => 'Grading Risiko', 'type' => 'text', 'role' => 'auxiliary', 'width' => '130px'],
                        ['id' => 'c5', 'key' => 'num', 'title' => 'Tepat Waktu (N)', 'type' => 'checkbox', 'role' => 'numerator', 'width' => '150px'],
                        ['id' => 'c6', 'key' => 'den', 'title' => 'Total Komplain (D)', 'type' => 'number', 'role' => 'denominator', 'width' => '150px'],
                        ['id' => 'c7', 'key' => 'notes', 'title' => 'Tindak Lanjut & Solusi', 'type' => 'text', 'role' => 'auxiliary', 'width' => 'auto'],
                    ],
                    'rows' => [],
                    'formula_config' => [
                        'numerator_col' => 'num',
                        'denominator_col' => 'den',
                    ]
                ]
            ]
        );

        $ind3->update([
            'form_layout_type' => 'dynamic_rows',
            'form_schema' => [
                'format_id'   => $formatKomplain->id,
                'nama_format' => $formatKomplain->nama_format,
                'table_title' => $formatKomplain->table_title,
                'row_type'    => 'dynamic_rows',
                'columns'     => $formatKomplain->schema['columns'],
                'rows'        => [],
            ]
        ]);
    }
}
