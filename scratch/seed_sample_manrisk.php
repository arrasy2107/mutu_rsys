<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RiskRegister;
use App\Models\Unit;
use App\Models\User;

echo "=== SEEDING SAMPLE HOSPITAL RISK REGISTERS (MANRISK) ===\n";

$admin = User::first();
$units = Unit::all();

$unitIgd = Unit::where('nama', 'like', '%Gawat Darurat%')->orWhere('nama', 'like', '%IGD%')->first() ?? $units->first();
$unitIcu = Unit::where('nama', 'like', '%ICU%')->orWhere('nama', 'like', '%Intensif%')->first() ?? $unitIgd;
$unitFarmasi = Unit::where('nama', 'like', '%Farmasi%')->first() ?? $unitIgd;
$unitBedah = Unit::where('nama', 'like', '%Bedah%')->orWhere('nama', 'like', '%Operasi%')->first() ?? $unitIgd;
$unitRanap = Unit::where('nama', 'like', '%Inap%')->first() ?? $unitIgd;
$unitLab = Unit::where('nama', 'like', '%Laboratorium%')->first() ?? $unitIgd;
$unitIpsrs = Unit::where('nama', 'like', '%IPSR%')->orWhere('nama', 'like', '%Sarana%')->first() ?? $unitIgd;

$samples = [
    [
        'kode_risiko' => 'MR/2026/0001',
        'tahun' => 2026,
        'unit_id' => $unitIcu->id,
        'ruang_lingkup' => 'unit',
        'is_rs_priority' => false,
        'kategori_risiko' => 'Klinis / Pelayanan Pasien',
        'kategori_mfk' => null,
        'pernyataan_risiko' => 'Risiko infeksi aliran darah primer terkait kateter vena sentral (CLABSI) di ruang ICU',
        'deskripsi' => 'Pasien kritis yang terpasang CVC berisiko mengalami sepsis nosokomial jika aseptic handling tidak ketat.',
        'penyebab' => 'Teknik ganti balutan yang tidak steril, manipulasi threeway stopcock tanpa desinfeksi alkohol swab.',
        'dampak' => 'Perpanjangan lama rawat (LOS), syok septik, peningkatan morbiditas & mortalitas pasien.',
        'inherent_probabilitas' => 3,
        'inherent_dampak' => 4,
        'pengendalian_ada' => 'Penerapan CLABSI Bundle checklist saat insersi dan perawatan kateter.',
        'efektivitas_pengendalian' => 'cukup',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Audit kepatuhan bundle mingguan oleh IPCN dan penggantian dressing chlorhexidine transparan.',
        'target_waktu' => '2026-06-30',
        'penanggung_jawab' => 'Kepala Ruang ICU & Tim PPI',
        'residual_probabilitas' => 2,
        'residual_dampak' => 3,
        'status_pemantauan' => 'dalam_proses',
        'catatan_pemantauan' => 'Angka insidensi bulan terakhir menunjukkan tren penurunan.',
        'tanggal_review' => '2026-09-15',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0002',
        'tahun' => 2026,
        'unit_id' => $unitBedah->id,
        'ruang_lingkup' => 'unit',
        'is_rs_priority' => true,
        'kategori_risiko' => 'Klinis / Pelayanan Pasien',
        'kategori_mfk' => null,
        'pernyataan_risiko' => 'Risiko tertinggalnya kassa atau instrumen bedah dalam rongga tubuh pasca operasi laparotomi',
        'deskripsi' => 'Ketidaksesuaian penghitungan kassa sebelum rongga peritoneum ditutup pada operasi cito bedah abdomen.',
        'penyebab' => 'Komunikasi tim operasi kurang optimal saat situasi emergency hemodinamik tidak stabil.',
        'dampak' => 'Re-operasi cito laparotomi, peritonitis sekunder, tuntutan hukum malpraktik medik.',
        'inherent_probabilitas' => 1,
        'inherent_dampak' => 5,
        'pengendalian_ada' => 'Surgical Safety Checklist WHO fase sign-out dan whiteboard penghitungan kassa/jarum.',
        'efektivitas_pengendalian' => 'efektif',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Wajib foto rontgen thoraks/abdomen portabel di meja operasi jika hitungan tidak match sebelum tutup luka.',
        'target_waktu' => '2026-05-31',
        'penanggung_jawab' => 'Kepala Instalasi Bedah Sentral',
        'residual_probabilitas' => 1,
        'residual_dampak' => 4,
        'status_pemantauan' => 'terkendali',
        'catatan_pemantauan' => 'Kepatuhan sign-out 100% pada 3 bulan terakhir tanpa insiden.',
        'tanggal_review' => '2026-10-01',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0003',
        'tahun' => 2026,
        'unit_id' => $unitIpsrs->id,
        'ruang_lingkup' => 'mfk',
        'is_rs_priority' => true,
        'kategori_risiko' => 'Fasilitas, K3 & MFK',
        'kategori_mfk' => 'Peralatan Medis & Kelistrikan',
        'pernyataan_risiko' => 'Risiko kegagalan otomatis genset (ATS) saat terjadi pemadaman aliran listrik PLN mendadak',
        'deskripsi' => 'Keterlambatan perpindahan daya genset melebihi 7 detik dapat mengancam ventilator ruang ICU dan mesin anestesi OK.',
        'penyebab' => 'Aki starter genset drop atau kontaktor switch ATS aus.',
        'dampak' => 'Kematian pasien yang menggunakan alat penunjang hidup (life support), kerusakan alat elektronik medik.',
        'inherent_probabilitas' => 2,
        'inherent_dampak' => 5,
        'pengendalian_ada' => 'Pemanasan genset rutin seminggu sekali dan inspeksi baterai starter harian.',
        'efektivitas_pengendalian' => 'cukup',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Pengadaan UPS sentral redundant untuk blok kritis (ICU, OK, NICU) dan penggantian baterai bank.',
        'target_waktu' => '2026-08-30',
        'penanggung_jawab' => 'Kepala Unit IPSRS',
        'residual_probabilitas' => 1,
        'residual_dampak' => 3,
        'status_pemantauan' => 'dalam_proses',
        'catatan_pemantauan' => 'Pengadaan UPS tahap 1 selesai terpasang di kamar operasi.',
        'tanggal_review' => '2026-10-10',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0004',
        'tahun' => 2026,
        'unit_id' => $unitFarmasi->id,
        'ruang_lingkup' => 'mfk',
        'is_rs_priority' => false,
        'kategori_risiko' => 'Fasilitas, K3 & MFK',
        'kategori_mfk' => 'Bahan Berbahaya & Beracun (B3)',
        'pernyataan_risiko' => 'Risiko tumpahan obat sitotoksik / sitostatika kemoterapi saat proses rekonstitusi di ruang isolasi',
        'deskripsi' => 'Pecahnya vial obat kemoterapi atau kebocoran spuit berisiko memapar petugas farmasi dan mencemari ruangan.',
        'penyebab' => 'Biological Safety Cabinet (BSC) terganggu alirannya atau kesalahan manipulasi spuit.',
        'dampak' => 'Paparan mutagenik dan karsinogenik jangka panjang pada petugas, kontaminasi lingkungan instalasi farmasi.',
        'inherent_probabilitas' => 2,
        'inherent_dampak' => 4,
        'pengendalian_ada' => 'SOP Handling Sitostatika, APD kemoterapi lengkap, ketersediaan Spillage Kit sitostatika.',
        'efektivitas_pengendalian' => 'efektif',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Simulasi tahunan tumpahan B3 dan pemeliharaan filter HEPA BSC kelas II tipe B2 berkala.',
        'target_waktu' => '2026-07-31',
        'penanggung_jawab' => 'Penanggung Jawab Farmasi Klinis & K3RS',
        'residual_probabilitas' => 1,
        'residual_dampak' => 2,
        'status_pemantauan' => 'terkendali',
        'catatan_pemantauan' => 'Pelatihan dan simulasi tumpahan B3 telah selesai dilaksanakan 100% personil.',
        'tanggal_review' => '2026-08-20',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0005',
        'tahun' => 2026,
        'unit_id' => $unitIpsrs->id,
        'ruang_lingkup' => 'mfk',
        'is_rs_priority' => true,
        'kategori_risiko' => 'Fasilitas, K3 & MFK',
        'kategori_mfk' => 'Sistem Utilitas (Air, Listrik, Gas Medis)',
        'pernyataan_risiko' => 'Risiko penurunan tekanan suplai gas medis oksigen sentral di bawah 4 bar pada instalasi rawat inap',
        'deskripsi' => 'Fluktuasi konsumsi oksigen tinggi yang tidak terdeteksi dapat menurunkan tekanan manifold sentral.',
        'penyebab' => 'Kebocoran jalur pipa sekunder atau keterlambatan switching tangki oksigen cair VGL/VIE.',
        'dampak' => 'Kegagalan pemberian oksigen aliran tinggi bagi pasien desaturasi akut.',
        'inherent_probabilitas' => 2,
        'inherent_dampak' => 5,
        'pengendalian_ada' => 'Alarm tekanan audio-visual digital di pos jaga IPSRS dan manifold gas sentral.',
        'efektivitas_pengendalian' => 'efektif',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Kalibrasi sensor tekanan digital 6 bulanan dan pemasangan tabung oksigen backup di tiap nurse station.',
        'target_waktu' => '2026-09-30',
        'penanggung_jawab' => 'Koordinator Gas Medis IPSRS',
        'residual_probabilitas' => 1,
        'residual_dampak' => 3,
        'status_pemantauan' => 'terkendali',
        'catatan_pemantauan' => 'Tekanan suplai termonitor stabil 4.2 bar tanpa kendala.',
        'tanggal_review' => '2026-10-15',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0006',
        'tahun' => 2026,
        'unit_id' => $unitIgd->id,
        'ruang_lingkup' => 'rs',
        'is_rs_priority' => true,
        'kategori_risiko' => 'Klinis / Pelayanan Pasien',
        'kategori_mfk' => null,
        'pernyataan_risiko' => 'Risiko overcapacity (kepadatan ekstrem) ruang triage & resusitasi IGD saat terjadi lonjakan massal',
        'deskripsi' => 'Keterlambatan proses transfer pasien ke ruang rawat inap mengakibatkan penumpukan pasien di lorong IGD.',
        'penyebab' => 'Ketersediaan bed rawat inap penuh, sistem discharge planning ruang rawat lambat.',
        'dampak' => 'Penurunan mutu respon time penanganan darurat, risiko insiden keselamatan pasien meningkat tajam.',
        'inherent_probabilitas' => 4,
        'inherent_dampak' => 4,
        'pengendalian_ada' => 'Protokol Triage Emergency Severity Index (ESI) dan penempatan perawat triage khusus.',
        'efektivitas_pengendalian' => 'kurang',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Penerapan Fast-Track bed assignment terpadu dan pembukaan ruang transit sementara (Holding Room).',
        'target_waktu' => '2026-11-30',
        'penanggung_jawab' => 'Direktur Pelayanan Medik & Kepala Instalasi Gawat Darurat',
        'residual_probabilitas' => 2,
        'residual_dampak' => 3,
        'status_pemantauan' => 'dalam_proses',
        'catatan_pemantauan' => 'SOP Holding room disahkan direksi, sistem dashboard bed availability sedang diintegrasikan.',
        'tanggal_review' => '2026-10-20',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0007',
        'tahun' => 2026,
        'unit_id' => $unitLab->id,
        'ruang_lingkup' => 'unit',
        'is_rs_priority' => false,
        'kategori_risiko' => 'Klinis / Pelayanan Pasien',
        'kategori_mfk' => null,
        'pernyataan_risiko' => 'Risiko keterlambatan pelaporan nilai kritis (Critical Value) hasil laboratorium ke DPJP > 30 menit',
        'deskripsi' => 'Hasil kalium < 2.5 mEq/L atau troponin positif tidak segera terkonfirmasi ke dokter penanggung jawab.',
        'penyebab' => 'Nomor kontak DPJP sulit dihubungi atau petugas ruang rawat sibuk tindakan.',
        'dampak' => 'Keterlambatan tindakan resusitasi atau koreksi elektrolit mengancam keselamatan nyawa pasien.',
        'inherent_probabilitas' => 3,
        'inherent_dampak' => 4,
        'pengendalian_ada' => 'Buku log pelaporan nilai kritis dengan metode Read-Back (SBAR/TBaK).',
        'efektivitas_pengendalian' => 'cukup',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Integrasi notifikasi otomatis nilai kritis ke WhatsApp gateway DPJP dan kepala jaga ruang rawat.',
        'target_waktu' => '2026-07-31',
        'penanggung_jawab' => 'Kepala Instalasi Laboratorium',
        'residual_probabilitas' => 1,
        'residual_dampak' => 3,
        'status_pemantauan' => 'terkendali',
        'catatan_pemantauan' => 'Respon time rata-rata turun dari 42 menit menjadi 14 menit.',
        'tanggal_review' => '2026-08-30',
        'created_by' => $admin?->id,
    ],
    [
        'kode_risiko' => 'MR/2026/0008',
        'tahun' => 2026,
        'unit_id' => $unitRanap->id,
        'ruang_lingkup' => 'rs',
        'is_rs_priority' => true,
        'kategori_risiko' => 'Finansial & Keuangan',
        'kategori_mfk' => null,
        'pernyataan_risiko' => 'Risiko penundaan atau dispute klaim BPJS Kesehatan akibat ketidaklengkapan resume medis elektronik',
        'deskripsi' => 'Pending klaim mencapai nominal ratusan juta rupiah akibat berkas penunjang (laporan operasi, resume) belum diverifikasi DPJP.',
        'penyebab' => 'Keterbatasan waktu dokter spesialis untuk melengkapi rekam medis elektronik dalam 1x24 jam.',
        'dampak' => 'Gangguan likuiditas kas operasional rumah sakit dan penundaan pembayaran ke vendor obat/alat.',
        'inherent_probabilitas' => 4,
        'inherent_dampak' => 4,
        'pengendalian_ada' => 'Verifikasi internal tim casemix sebelum berkas diajukan ke verifikator BPJS.',
        'efektivitas_pengendalian' => 'cukup',
        'opsi_penanganan' => 'mitigasi',
        'rencana_penanganan' => 'Penerapan modul e-Resume interaktif di smartphone dokter dan insentif kelengkapan berkas tepat waktu.',
        'target_waktu' => '2026-09-30',
        'penanggung_jawab' => 'Direktur Keuangan & Tim Casemix',
        'residual_probabilitas' => 2,
        'residual_dampak' => 2,
        'status_pemantauan' => 'dalam_proses',
        'catatan_pemantauan' => 'Tingkat kelengkapan resume 24 jam naik menjadi 88%.',
        'tanggal_review' => '2026-10-12',
        'created_by' => $admin?->id,
    ],
];

foreach ($samples as $data) {
    $data['inherent_skor'] = $data['inherent_probabilitas'] * $data['inherent_dampak'];
    $data['inherent_band'] = RiskRegister::determineRiskBand($data['inherent_probabilitas'], $data['inherent_dampak']);
    
    if (!empty($data['residual_probabilitas']) && !empty($data['residual_dampak'])) {
        $data['residual_skor'] = $data['residual_probabilitas'] * $data['residual_dampak'];
        $data['residual_band'] = RiskRegister::determineRiskBand($data['residual_probabilitas'], $data['residual_dampak']);
    }

    RiskRegister::updateOrCreate(
        ['kode_risiko' => $data['kode_risiko']],
        $data
    );
    echo "- Terinjeksi: {$data['kode_risiko']} ({$data['ruang_lingkup']} - Skor {$data['inherent_skor']} [{$data['inherent_band']}])\n";
}

$total = RiskRegister::count();
echo "\nTotal Register Risiko di Database: {$total} record.\n";

