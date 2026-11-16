<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PatientSafetyIncident;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;

echo "=== SEEDING REALISTIC PATIENT SAFETY INCIDENTS (IKP) ===\n";

$admin = User::first();
$units = Unit::all()->keyBy('nama');

$unitIgd = Unit::where('nama', 'like', '%Gawat Darurat%')->orWhere('nama', 'like', '%IGD%')->first() ?? Unit::first();
$unitIcu = Unit::where('nama', 'like', '%ICU%')->orWhere('nama', 'like', '%Intensif%')->first() ?? $unitIgd;
$unitFarmasi = Unit::where('nama', 'like', '%Farmasi%')->first() ?? $unitIgd;
$unitBedah = Unit::where('nama', 'like', '%Bedah%')->orWhere('nama', 'like', '%Operasi%')->first() ?? $unitIgd;
$unitRanap = Unit::where('nama', 'like', '%Inap%')->first() ?? $unitIgd;
$unitLab = Unit::where('nama', 'like', '%Laboratorium%')->first() ?? $unitIgd;
$unitRadiologi = Unit::where('nama', 'like', '%Radiologi%')->first() ?? $unitIgd;

$samples = [
    [
        'nomor_laporan' => 'IKP/2026/01/0001',
        'tanggal_laporan' => '2026-01-14',
        'tanggal_kejadian' => '2026-01-14 09:15:00',
        'tipe_insiden' => 'Medikasi / Obat / Farmasi',
        'insiden' => 'Nyaris salah dosis pemberian injeksi Cefotaxime pada pasien anak',
        'kronologi' => 'Perawat ruangan menyiapkan dosis Cefotaxime 1 gram untuk pasien anak usia 4 tahun (seharusnya 500 mg). Saat melakukan double-check perawat kedua mengidentifikasi kekeliruan dosis sebelum obat diinjeksikan.',
        'jenis_insiden' => 'KNC',
        'grading' => 'biru',
        'unit_pelapor_id' => $unitRanap->id,
        'unit_terkait_id' => $unitFarmasi->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-01-20',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Penyegaran SPO 7 Benar Pemberian Obat dan wajib verifikasi independen double-check pada pasien pediatrik.',
        'penanggung_jawab_rtl' => 'Kepala Ruang Rawat Inap Anak',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/02/0002',
        'tanggal_laporan' => '2026-02-10',
        'tanggal_kejadian' => '2026-02-09 21:40:00',
        'tipe_insiden' => 'Pasien Jatuh / Keselamatan Fisik',
        'insiden' => 'Pasien geriatri post-operasi jatuh dari tempat tidur saat ke toilet',
        'kronologi' => 'Pasien usia 68 tahun post ORIF femur hari ke-2 mencoba turun ke toilet tanpa memencet bel panggil. Penghalang tempat tidur (bed-rail) sisi kanan dalam posisi belum dinaikkan oleh perawat jaga.',
        'jenis_insiden' => 'KTD',
        'grading' => 'kuning',
        'unit_pelapor_id' => $unitRanap->id,
        'unit_terkait_id' => $unitRanap->id,
        'tipe_penyelesaian' => 'rca',
        'tanggal_selesai_penyelesaian' => '2026-03-15',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Pemasangan gelang kuning risiko jatuh, edukasi ulang keluarga, dan supervisi pemasangan bed rail saat serah terima pasien.',
        'penanggung_jawab_rtl' => 'Komite Mutu & Kepala Ruang Rawat Inap',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/03/0003',
        'tanggal_laporan' => '2026-03-05',
        'tanggal_kejadian' => '2026-03-05 14:00:00',
        'tipe_insiden' => 'Laboratorium & Diagnostik',
        'insiden' => 'Tertukarnya tabung sampel darah EDTA antara dua pasien di ruang rawat',
        'kronologi' => 'Petugas flebotomi mengambil darah 2 pasien di kamar bersebelahan. Saat labeling tabung, stiker barcode tertukar. Diketahui petugas saat hasil lab tidak sesuai dengan profil klinis awal pasien.',
        'jenis_insiden' => 'KTC',
        'grading' => 'hijau',
        'unit_pelapor_id' => $unitLab->id,
        'unit_terkait_id' => $unitRanap->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-03-16',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Penempelan stiker barcode wajib dilakukan di samping tempat tidur pasien (bedside labeling) satu per satu.',
        'penanggung_jawab_rtl' => 'Koordinator Laboratorium Patologi Klinik',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/04/0004',
        'tanggal_laporan' => '2026-04-18',
        'tanggal_kejadian' => '2026-04-18 10:20:00',
        'tipe_insiden' => 'Sarana / Prasarana / Alat Medis',
        'insiden' => 'Temuan kabel suction pump terkelupas berisiko korsleting di ruang tindakan IGD',
        'kronologi' => 'Saat persiapan penanganan pasien trauma di ruang resusitasi, perawat menemukan kabel daya alat suction pump terkelupas pembungkusnya dan berpotensi memicu sengatan listrik.',
        'jenis_insiden' => 'KPC',
        'grading' => 'biru',
        'unit_pelapor_id' => $unitIgd->id,
        'unit_terkait_id' => $unitIgd->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-04-22',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Penarikan unit alat untuk perbaikan oleh IPSRS dan penjadwalan inspeksi kelistrikan berkala seluruh alat medis IGD.',
        'penanggung_jawab_rtl' => 'Kepala Unit IPSRS',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/05/0005',
        'tanggal_laporan' => '2026-05-12',
        'tanggal_kejadian' => '2026-05-11 23:10:00',
        'tipe_insiden' => 'Prosedur Medis / Tindakan Klinis',
        'insiden' => 'Reaksi hemolitik akut pasca transfusi darah Packed Red Cell (PRC)',
        'kronologi' => 'Pasien menerima 1 kolf PRC. Pada menit ke-15 timbul menggigil hebat, demam tinggi, dispnea dan hipotensi. Transfusi segera dihentikan. Ditemukan ketidaksesuaian identifikasi pada saat serah terima darah.',
        'jenis_insiden' => 'Sentinel',
        'grading' => 'merah',
        'unit_pelapor_id' => $unitIcu->id,
        'unit_terkait_id' => $unitRanap->id,
        'tipe_penyelesaian' => 'rca',
        'tanggal_selesai_penyelesaian' => '2026-06-20',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Redesain protokol cross-match BDRS, penerapan verifikasi elektronik 2 tahap sebelum darah dimasukkan.',
        'penanggung_jawab_rtl' => 'Komite Keselamatan Pasien Rumah Sakit & Kepala BDRS',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/06/0006',
        'tanggal_laporan' => '2026-06-08',
        'tanggal_kejadian' => '2026-06-08 08:30:00',
        'tipe_insiden' => 'Dokumentasi & Rekam Medis',
        'insiden' => 'Kekeliruan pengisian informed consent pembedahan katarak mata kanan/kiri',
        'kronologi' => 'Saat surgical safety checklist time-out di kamar operasi, dokter spesialis mata dan perawat sirkuler mendeteksi formulir persetujuan tindakan tertulis mata kiri, padahal diagnosis klinis pada mata kanan.',
        'jenis_insiden' => 'KNC',
        'grading' => 'hijau',
        'unit_pelapor_id' => $unitBedah->id,
        'unit_terkait_id' => $unitRanap->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-06-15',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Penandatanganan informed consent wajib diverifikasi ulang bersama penandaan lokasi operasi (site marking) oleh DPJP.',
        'penanggung_jawab_rtl' => 'Kepala Instalasi Bedah Sentral',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/07/0007',
        'tanggal_laporan' => '2026-07-22',
        'tanggal_kejadian' => '2026-07-21 16:45:00',
        'tipe_insiden' => 'Infeksi Terkait Layanan (HAIs)',
        'insiden' => 'Phlebitis derajat 3 pada lokasi insersi infus perifer hari ke-4',
        'kronologi' => 'Pasien rawat inap mengeluh nyeri hebat, kemerahan, bengkak, dan terdapat indurasi sepanjang vena perifer tempat kanula terpasang. Kanula infus perifer telah terpasang >72 jam tanpa penggantian.',
        'jenis_insiden' => 'KTD',
        'grading' => 'kuning',
        'unit_pelapor_id' => $unitRanap->id,
        'unit_terkait_id' => $unitRanap->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-08-15',
        'kriteria_waktu_penyelesaian' => 'tidak_sesuai',
        'rencana_tindak_lanjut' => 'Audit bundel pencegahan phlebitis oleh Komite PPI dan rotasi kateter intravena maksimal setiap 72 jam.',
        'penanggung_jawab_rtl' => 'Komite PPI & Kepala Ruang Rawat Inap',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/08/0008',
        'tanggal_laporan' => '2026-08-19',
        'tanggal_kejadian' => '2026-08-19 11:00:00',
        'tipe_insiden' => 'Radiologi & Diagnostik',
        'insiden' => 'Permintaan foto rontgen thoraks tertukar posisi dengan rontgen abdomen',
        'kronologi' => 'Pasien rujukan dari poli penyakit dalam datang dengan lembar pengantar rontgen. Radiografer melakukan eksposisi sesuai pengantar, namun saat dokter spesialis radiologi membaca foto teridentifikasi bahwa foto tidak sesuai indikasi klinis rujukan.',
        'jenis_insiden' => 'KTC',
        'grading' => 'biru',
        'unit_pelapor_id' => $unitRadiologi->id,
        'unit_terkait_id' => $unitIgd->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-08-25',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Klarifikasi lisan dan konfirmasi klinis wajib dilakukan radiografer jika formulir pengantar kurang jelas.',
        'penanggung_jawab_rtl' => 'Kepala Instalasi Radiologi',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/09/0009',
        'tanggal_laporan' => '2026-09-10',
        'tanggal_kejadian' => '2026-09-09 20:00:00',
        'tipe_insiden' => 'Medikasi / Obat / Farmasi',
        'insiden' => 'Obat LASA (Look-Alike Sound-Alike) tertukar penempatannya di troli emergensi IGD',
        'kronologi' => 'Saat pengecekan harian troli resusitasi, ampul Epinefrin dan Efedrin ditemukan diletakkan berdampingan tanpa stiker penanda stiker tinggi resiko LASA.',
        'jenis_insiden' => 'KPC',
        'grading' => 'kuning',
        'unit_pelapor_id' => $unitIgd->id,
        'unit_terkait_id' => $unitFarmasi->id,
        'tipe_penyelesaian' => 'investigasi_sederhana',
        'tanggal_selesai_penyelesaian' => '2026-09-18',
        'kriteria_waktu_penyelesaian' => 'sesuai',
        'rencana_tindak_lanjut' => 'Pemasangan sekat fisik pemisah obat LASA di seluruh troli emergensi dan verifikasi mingguan oleh farmasi klinis.',
        'penanggung_jawab_rtl' => 'Kepala Instalasi Farmasi',
        'status' => 'selesai',
        'created_by' => $admin?->id,
    ],
    [
        'nomor_laporan' => 'IKP/2026/10/0010',
        'tanggal_laporan' => '2026-10-15',
        'tanggal_kejadian' => '2026-10-14 15:30:00',
        'tipe_insiden' => 'Prosedur Medis / Tindakan Klinis',
        'insiden' => 'Dislokasi kateter vena sentral (CVC) saat reposisi posisi miring pasien ICU',
        'kronologi' => 'Pasien terpasang CVC di vena jugularis interna kanan. Saat perawat melakukan perawatan alih baring miring kanan, fiksasi kateter terlepas dan ujung kateter tertarik keluar 3 cm.',
        'jenis_insiden' => 'KTD',
        'grading' => 'kuning',
        'unit_pelapor_id' => $unitIcu->id,
        'unit_terkait_id' => $unitIcu->id,
        'tipe_penyelesaian' => 'rca',
        'tanggal_selesai_penyelesaian' => null,
        'kriteria_waktu_penyelesaian' => 'dalam_proses',
        'rencana_tindak_lanjut' => 'Dalam proses investigasi tim RCA komite mutu rumah sakit.',
        'penanggung_jawab_rtl' => 'Komite Keselamatan Pasien',
        'status' => 'investigasi',
        'created_by' => $admin?->id,
    ],
];

foreach ($samples as $data) {
    PatientSafetyIncident::updateOrCreate(
        ['nomor_laporan' => $data['nomor_laporan']],
        $data
    );
    echo "- Laporan terinjeksi: {$data['nomor_laporan']} ({$data['jenis_insiden']} - {$data['grading']})\n";
}

$total = PatientSafetyIncident::count();
echo "\nTotal Insiden di Database: {$total} laporan.\n";

