<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IndicatorSelection;
use App\Models\IndicatorSelectionItem;
use App\Models\Unit;
use App\Models\User;

$user = User::first();
$unit = Unit::where('kode', 'IRJ')->first();

// Cek apakah sudah ada sesi untuk IRJ tanggal 2025-09-24
$selection = IndicatorSelection::updateOrCreate(
    [
        'unit_id' => $unit?->id ?? 18,
        'tanggal' => '2025-09-24',
    ],
    [
        'tipe'       => 'unit',
        'jam'        => '12:00 WIB',
        'tempat'     => 'Ruang Rapat Unit Rawat Jalan (IRJ)',
        'notulen'    => 'Pertemuan pemilihan indikator mutu prioritas unit IRJ berdasarkan identifikasi masalah pelayanan.',
        'status'     => 'final',
        'created_by' => $user->id,
    ]
);

$selection->items()->delete();

// Item 1: Pendampingan DPJP
$selection->items()->create([
    'usulan_imut'             => 'Terlaksananya pendampingan DPJP di Rawat Jalan',
    'data_pendukung'          => 'Pendampingan DPJP masih ada yang belum terlaksana karena dokter datang tidak tepat waktu',
    'ssi_visi_misi'           => true,
    'problem_prone'           => true,
    'high_volume'             => true,
    'high_risk_cost'          => false,
    'ssi_aturan'              => true,
    'dpt_dikendalikan_unit'   => false,
    'tdk_dpt_dikendalikan_rs' => false,
    'dpt_diukur'              => false,
    'is_lolos_skrining'       => false,
    'skor_masalah_banyak'     => 3,
    'skor_high_volume'        => 3,
    'skor_high_proses'        => 3,
    'skor_ketidakpuasan'      => 3,
    'skor_kemudahan_ukur'     => 1,
    'skor_ketentuan_pemerintah' => 3,
    'skor_sesuai_renstra'     => 3,
    'skor_patient_experience' => 3,
    'total_skor'              => 22,
    'is_ditetapkan'           => false,
]);

// Item 2: Pengkajian Pasien Baru
$selection->items()->create([
    'usulan_imut'             => 'Terlaksananya pengkajian pasien baru di rawat jalan',
    'data_pendukung'          => 'Pengkajian pasien baru masih belum semua dilakukan oleh perawat',
    'ssi_visi_misi'           => true,
    'problem_prone'           => true,
    'high_volume'             => true,
    'high_risk_cost'          => false,
    'ssi_aturan'              => true,
    'dpt_dikendalikan_unit'   => true,
    'tdk_dpt_dikendalikan_rs' => false,
    'dpt_diukur'              => true,
    'is_lolos_skrining'       => true,
    'skor_masalah_banyak'     => 4,
    'skor_high_volume'        => 4,
    'skor_high_proses'        => 3,
    'skor_ketidakpuasan'      => 3,
    'skor_kemudahan_ukur'     => 3,
    'skor_ketentuan_pemerintah' => 4,
    'skor_sesuai_renstra'     => 4,
    'skor_patient_experience' => 3,
    'total_skor'              => 28,
    'is_ditetapkan'           => true,
]);

// Item 3: Berkas Penagihan Rawat Jalan
$selection->items()->create([
    'usulan_imut'             => 'Berkas penagihan rawat jalan lengkap',
    'data_pendukung'          => 'Berkas penagihan pasien masih ada yang dikembalikan ke IRJ',
    'ssi_visi_misi'           => true,
    'problem_prone'           => true,
    'high_volume'             => true,
    'high_risk_cost'          => false,
    'ssi_aturan'              => true,
    'dpt_dikendalikan_unit'   => true,
    'tdk_dpt_dikendalikan_rs' => false,
    'dpt_diukur'              => true,
    'is_lolos_skrining'       => true,
    'skor_masalah_banyak'     => 4,
    'skor_high_volume'        => 4,
    'skor_high_proses'        => 3,
    'skor_ketidakpuasan'      => 3,
    'skor_kemudahan_ukur'     => 3,
    'skor_ketentuan_pemerintah' => 4,
    'skor_sesuai_renstra'     => 4,
    'skor_patient_experience' => 3,
    'total_skor'              => 28,
    'is_ditetapkan'           => true,
]);

echo "Seeded Pemilihan IRJ successfully! Selection ID: {$selection->id}\n";
