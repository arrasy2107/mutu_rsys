<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HospitalPdsaController extends Controller
{
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $activeTab = $request->query('tab', 'rtl'); // 'rtl', 'pdsa', 'dampak'

        // Data Riil Rencana Tindak Lanjut (RTL IMP) dari sheet RTL IMP di IMUT RSYS 2026.xlsx
        $rtlRecords = [
            [
                'id' => 1,
                'bulan' => 'Januari',
                'kode' => 'IMP-RS 6',
                'judul' => 'Kelengkapan Dokumen Handover dari IBS',
                'masalah' => 'Capaian 37.00% (Target 100%) - Imut tidak tercapai',
                'penyebab_5m' => 'Method / Man: Hanya copy paste pesanan dokter, sementara untuk asuhan keperawatan belum tercantum lengkap pada form handover.',
                'rtl' => 'Mengingatkan dan menyampaikan agar membuat pesanan yang lengkap baik dari medis maupun dari keperawatan pada form handover digital.',
                'pj_rtl' => 'PJ IBS',
                'target_selesai' => 'Februari 2026',
                'status' => 'Selesai',
            ],
            [
                'id' => 2,
                'bulan' => 'Januari',
                'kode' => 'IMP-RS 7',
                'judul' => 'Kepatuhan Penyimpanan Obat High Alert di IBS',
                'masalah' => 'Capaian 40.00% (Target 100%) - Imut tidak tercapai',
                'penyebab_5m' => 'Material / Machine: Ruang OK memiliki 5 trolley obat di mana dalam 1 sekat wadah terdapat 3 macam obat berbeda sehingga berisiko salah ambil.',
                'rtl' => 'Memberi sekat pembatas dengan akrilik untuk masing-masing obat disertai label High Alert dan LASA/NORUM yang jelas.',
                'pj_rtl' => 'Ka Instalasi Farmasi & PJ Sterilisasi',
                'target_selesai' => 'Maret 2026',
                'status' => 'Dalam Proses',
            ],
            [
                'id' => 3,
                'bulan' => 'Januari',
                'kode' => 'IMP-RS 8',
                'judul' => 'Terlaksananya Time Out Operasi di IBS',
                'masalah' => 'Capaian terdata 86.67% (52/60) - Belum mencapai target 100%',
                'penyebab_5m' => 'Method: Terdapat kesalahan teknis input data saat entri checklist sebelum verifikasi.',
                'rtl' => 'Melakukan verifikasi data riil di form surgical checklist kamar bedah dan melatih petugas entri.',
                'pj_rtl' => 'PJ IBS',
                'target_selesai' => 'Januari 2026',
                'status' => 'Selesai',
            ],
            [
                'id' => 4,
                'bulan' => 'Februari',
                'kode' => 'IMP-RS 6',
                'judul' => 'Kelengkapan Dokumen Handover dari IBS',
                'masalah' => 'Capaian 41.00% - Masih di bawah standar KARS 100%',
                'penyebab_5m' => 'Man: Beberapa personil perawat jaga malam terburu-buru saat pergantian shift jaga pagi.',
                'rtl' => '1. Menghimbau kembali pembuatan dokumentasi asuhan lengkap pada saat timbang terima. 2. Melanjutkan monitoring pengisian booking dan handover berkala.',
                'pj_rtl' => 'PJ IBS & Ka Ruangan',
                'target_selesai' => 'Maret 2026',
                'status' => 'Selesai',
            ],
            [
                'id' => 5,
                'bulan' => 'Maret',
                'kode' => 'IMP-RS 7',
                'judul' => 'Kepatuhan Penyimpanan Obat High Alert di IBS',
                'masalah' => 'Capaian 60.00% - Belum mencapai 100%',
                'penyebab_5m' => 'Machine / Facility: Pembuatan sekat akrilik oleh bagian sarana sempat tertunda menunggu pasokan bahan akrilik.',
                'rtl' => 'Koordinasi dengan Bagian Sarana dan Prasarana RS (IPSRS) untuk percepatan instalasi sekat pada 5 trolley kamar bedah.',
                'pj_rtl' => 'Ka Instalasi Farmasi & IPSRS',
                'target_selesai' => 'April 2026',
                'status' => 'Selesai',
            ],
            [
                'id' => 6,
                'bulan' => 'April',
                'kode' => 'IMP-RS 1',
                'judul' => 'Ketepatan Waktu Pemberian AB Profilaksis Sebelum Operasi',
                'masalah' => 'Capaian 28.57% (2/7) - Terjadi penurunan signifikan',
                'penyebab_5m' => 'Method / Milieu: Waktu mulai operasi sering bergeser dari jam rencana sehingga pemberian antibiotik 60 menit sebelumnya menjadi kedaluwarsa waktu.',
                'rtl' => '1. Evaluasi penjadwalan kamar bedah. 2. Uji coba penyesuaian pemberian antibiotik 30 menit sebelum tindakan pada operasi elektif.',
                'pj_rtl' => 'Komite Medis & PJ IBS',
                'target_selesai' => 'Mei 2026',
                'status' => 'Selesai',
            ],
        ];

        // Data Riil Siklus PDSA dari sheet PDSA di IMUT RSYS 2026.xlsx
        $pdsaCycles = [
            [
                'id' => 1,
                'unit' => 'Instalasi Bedah Sentral (IBS)',
                'indikator' => 'IMP-RS 6: Kelengkapan Dokumen Handover dari IBS',
                'plan' => "1. Supervisi berkala dokumen handover.\n2. Menindaklanjuti hasil supervisi dan sosialisasi SOP terbaru.",
                'do' => "1. Evaluasi dokumen handover per ruangan.\n2. Memberi masukan kekurangan pada pertemuan briefing harian.\n3. Memberikan pengarahan dokumentasi spesifik per kasus tindakan bedah.\n4. Membuat juknis teknis pengarahan per kasus asuhan.",
                'study' => "Hasil evaluasi menunjukkan peningkatan signifikan dari bulan Januari 37% menjadi Juni 74% dan September 74%. Dokumen pengarahan efektif memandu perawat.",
                'act' => "1. Supervisi berkala tetap dijalankan secara mingguan.\n2. Dokumen pengarahan distandarisasi ke dalam regulasi internal RS.",
                'status' => 'Adopted (Ditetapkan Standar)',
            ],
            [
                'id' => 2,
                'unit' => 'IBS & Rawat Inap',
                'indikator' => 'IMP-RS 1: Ketepatan Waktu Pemberian AB Profilaksis Sebelum Operasi',
                'plan' => "1. Koordinasi mencari hambatan pelaksanaan dari kebijakan sebelumnya.\n2. Penyesuaian waktu pemberian AB menjadi 30 - 60 menit sebelum tindakan operasi.",
                'do' => "1. Membahas kendala operasional dalam rapat koordinasi Komite Medik & IBS.\n2. Menyepakati waktu pemberian AB profilaksis oleh petugas ruangan sebelum pasien dikirim ke IBS.\n3. Mengatur jam kerja 1 orang petugas IRJ masuk jam 06.30 WIB untuk persiapan pasien operasi pagi.",
                'study' => "Hasil uji coba menunjukkan kenaikan capaian dari bulan Januari 58.33% menjadi 90.91% di bulan Mei dan 76.47% di bulan September.",
                'act' => "1. Monitoring kepatuhan tetap dijalankan berkelanjutan.\n2. Menindaklanjuti kendala pergeseran jam operasi dengan koordinasi dokter bedah dan anestesi.",
                'status' => 'Ongoing (Siklus Berjalan)',
            ],
            [
                'id' => 3,
                'unit' => 'Instalasi Farmasi',
                'indikator' => 'IMP-RS 7: Kepatuhan Penyimpanan Obat High Alert di IBS',
                'plan' => "1. Mengevaluasi standar tata ruang dan penataan obat High Alert di IBS.\n2. Menata ulang dan membuat sekat akrilik pada kotak obat trolley kamar bedah.",
                'do' => "1. Memisahkan obat high alert dari obat reguler.\n2. Memasang sekat akrilik pada seluruh trolley di 5 ruang operasi.\n3. Melakukan double check dan pelabelan merah High Alert.",
                'study' => "Setelah perbaikan pengaturan tempat obat high alert, terjadi peningkatan kepatuhan dari 40% pada bulan Januari menjadi 100% pada bulan Mei s/d September.",
                'act' => "1. Penataan dan tempat penyimpanan akrilik yang diperbaharui ditetapkan sebagai standar baku di seluruh kamar bedah.\n2. Supervisi berkala mingguan oleh Farmasi tetap dipertahankan.",
                'status' => 'Adopted (Selesai Sukses)',
            ],
        ];

        // Data Riil Dampak Sekunder (Secondary Impact) dari sheet PDSA baris 16-23
        $dampakSekunder = [
            'indikator_terkait' => 'Ketepatan Waktu Penyerahan Obat di Rawat Jalan & Penerapan Resep Elektronik (EMR Tablet)',
            'items' => [
                [
                    'no' => 1,
                    'parameter' => 'Waktu Penyelesaian Resep Obat Racikan/Non-Racikan',
                    'sebelum' => '24 menit (resep kertas print manual)',
                    'setelah' => '4 menit (resep digital EMR tablet)',
                    'dampak' => 'Penghematan waktu pelayanan sebesar 83.3% per resep',
                ],
                [
                    'no' => 2,
                    'parameter' => 'Keterlambatan Waktu Pulang Karyawan (Jam Lembur per 10 orang)',
                    'sebelum' => '9.8 jam lembur',
                    'setelah' => '5.5 jam lembur',
                    'dampak' => 'Penurunan lembur kerja sebesar 43.8% (efisiensi SDM & biaya lembur)',
                ],
                [
                    'no' => 3,
                    'parameter' => 'Pemakaian Kertas Print Resep (Lembar per Hari)',
                    'sebelum' => '286 lembar / hari',
                    'setelah' => '0 lembar / hari (paperless)',
                    'dampak' => 'Penghematan 100% kertas resep (Green Hospital / cost containment)',
                ],
                [
                    'no' => 4,
                    'parameter' => 'Pemakaian Printer & Pita Tinta Kasir Resep',
                    'sebelum' => '286 cetakan / hari',
                    'setelah' => '0 cetakan / hari',
                    'dampak' => 'Penurunan biaya tinta printer dan perbaikan hardware',
                ],
            ]
        ];

        return Inertia::render('Hospital/Pdsa/Index', [
            'tahun'          => $tahun,
            'activeTab'      => $activeTab,
            'rtlRecords'     => $rtlRecords,
            'pdsaCycles'     => $pdsaCycles,
            'dampakSekunder' => $dampakSekunder,
        ]);
    }
}
