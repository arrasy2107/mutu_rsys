<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
});

defineEmits(['close']);

const activeTab = ref('alur');
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full p-5 sm:p-6 shadow-2xl border border-slate-100 space-y-5 my-8 max-h-[90vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                        <NotionIcon name="book" size="22" class="text-blue-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">
                            Petunjuk Teknis Pengisian Form Pengumpul Data
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Panduan operasional pengisian data mutu (*tally sheet*) untuk seluruh unit rumah sakit.
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                >
                    <NotionIcon name="x" size="18" />
                </button>
            </div>

            <!-- Tabs Navigasi -->
            <div class="flex border-b border-slate-200 gap-1 text-xs font-semibold text-slate-600">
                <button
                    type="button"
                    @click="activeTab = 'alur'"
                    :class="[
                        'px-4 py-2 border-b-2 transition-all flex items-center gap-1.5',
                        activeTab === 'alur'
                            ? 'border-blue-600 text-blue-700 font-bold bg-blue-50/50 rounded-t-lg'
                            : 'border-transparent hover:text-slate-800'
                    ]"
                >
                    <NotionIcon name="rocket" size="14" />
                    <span>1. Alur 3 Langkah</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'model'"
                    :class="[
                        'px-4 py-2 border-b-2 transition-all flex items-center gap-1.5',
                        activeTab === 'model'
                            ? 'border-blue-600 text-blue-700 font-bold bg-blue-50/50 rounded-t-lg'
                            : 'border-transparent hover:text-slate-800'
                    ]"
                >
                    <NotionIcon name="layers" size="14" />
                    <span>2. Mengenal 4 Model Form</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'formula'"
                    :class="[
                        'px-4 py-2 border-b-2 transition-all flex items-center gap-1.5',
                        activeTab === 'formula'
                            ? 'border-blue-600 text-blue-700 font-bold bg-blue-50/50 rounded-t-lg'
                            : 'border-transparent hover:text-slate-800'
                    ]"
                >
                    <NotionIcon name="calculator" size="14" />
                    <span>3. Numerator & Denominator</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'faq'"
                    :class="[
                        'px-4 py-2 border-b-2 transition-all flex items-center gap-1.5',
                        activeTab === 'faq'
                            ? 'border-blue-600 text-blue-700 font-bold bg-blue-50/50 rounded-t-lg'
                            : 'border-transparent hover:text-slate-800'
                    ]"
                >
                    <NotionIcon name="help" size="14" />
                    <span>4. Tanya Jawab (FAQ)</span>
                </button>
            </div>

            <!-- Tab Content 1: Alur 3 Langkah -->
            <div v-if="activeTab === 'alur'" class="space-y-4 text-xs text-slate-600 leading-relaxed">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100 flex flex-col justify-between">
                        <div>
                            <div class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center text-xs mb-2">1</div>
                            <h4 class="font-bold text-slate-800 text-xs mb-1">Pilih Indikator & Bulan</h4>
                            <p class="text-[11px] text-slate-500">Pilih tab indikator mutu unit kerja Anda, lalu sesuaikan bulan dan tahun aktif di bilah filter atas.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100 flex flex-col justify-between">
                        <div>
                            <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-xs mb-2">2</div>
                            <h4 class="font-bold text-slate-800 text-xs mb-1">Isi Data Lapangan & Simpan</h4>
                            <p class="text-[11px] text-slate-500">Isi data pada kolom input. Capaian % otomatis terhitung seketika. Klik tombol <b>Simpan Tally Sheet</b> secara berkala.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100 flex flex-col justify-between">
                        <div>
                            <div class="w-7 h-7 rounded-lg bg-purple-600 text-white font-bold flex items-center justify-center text-xs mb-2">3</div>
                            <h4 class="font-bold text-slate-800 text-xs mb-1">Ajukan Validasi (Akhir Bulan)</h4>
                            <p class="text-[11px] text-slate-500">Setelah data lengkap di akhir bulan, klik tombol <b>Ajukan Validasi</b> untuk mengirim laporan ke Komite Mutu.</p>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-xs flex items-center gap-1.5">
                    <NotionIcon name="lightbulb" size="14" class="text-amber-500 shrink-0" />
                    <span><b>Tips Pengisian:</b> Anda tidak perlu menunggu akhir bulan untuk menyimpan data. Entri dapat dilakukan setiap hari atau setiap shift agar beban administrasi tidak menumpuk di akhir bulan.</span>
                </div>
            </div>

            <!-- Tab Content 2: Mengenal 4 Model Form -->
            <div v-if="activeTab === 'model'" class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p class="text-xs text-slate-500 mb-2">
                    Setiap unit menggunakan formulir yang sesuai dengan alur layanannya. Sistem secara otomatis memuat model yang tepat untuk indikator Anda:
                </p>

                <div class="space-y-2.5">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                        <div class="p-2 rounded-lg bg-blue-100 text-blue-700 shrink-0">
                            <NotionIcon name="calendar" size="18" />
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-xs">1. Model Kalender Harian (Tanggal 1 s.d. 31)</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Digunakan oleh: <b>Lab, Gizi, Rekam Medis, IBS, IGD, Radiologi, Cuci Jahit, IPS-M, Akuntansi, AKRI</b>.<br/>
                                <i>Cara isi:</i> Masukkan jumlah Numerator (N) dan Denominator (D) pada baris tanggal berjalan (1-31). Sistem otomatis menjumlahkan total di baris paling bawah.
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                        <div class="p-2 rounded-lg bg-emerald-100 text-emerald-700 shrink-0">
                            <NotionIcon name="file-text" size="18" />
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-xs">2. Model Log Pasien & Checklist Dinamis (SBAR / Kepatuhan)</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Digunakan oleh: <b>Rawat Inap (Sudirman, Thamrin, Ade Irma, Dewi Sartika, Herlina, La Ruffa, ICU), Customer Care, Pastoral, IT, Home Care</b>.<br/>
                                <i>Cara isi:</i> Klik tombol <b>"+ Tambah Baris Pasien"</b> setiap kali ada pasien yang diobservasi. Masukkan identitas pasien dan centang kotak kepatuhan SOP (SBAR, Insulin, Respon Time). Denominator otomatis menghitung total pasien.
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                        <div class="p-2 rounded-lg bg-purple-100 text-purple-700 shrink-0">
                            <NotionIcon name="bar-chart" size="18" />
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-xs">3. Model Rekapitulasi Kegiatan Bulanan (12 Bulan / Non-Klinis)</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Digunakan oleh: <b>Casemix, Diklit, Kepegawaian (PSDM), TU, Bendahara, Gudang Umum, Sarana Umum (IPS-U)</b>.<br/>
                                <i>Cara isi:</i> Masukkan langsung total Numerator dan Denominator pada Kartu Input Bulan Berjalan, serta catatan evaluasi kegiatan. Matriks 12 bulan (Jan–Des) di bawahnya otomatis memantau tren tahunan.
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                        <div class="p-2 rounded-lg bg-amber-100 text-amber-700 shrink-0">
                            <NotionIcon name="table" size="18" />
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-xs">4. Model Matriks Kategori & Parameter Tetap</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Digunakan oleh: <b>Logistik (Stok BMHP), K3LH (Baku Mutu Air Limbah IPAL), Farmasi Rawat Jalan (Resep DPJP)</b>.<br/>
                                <i>Cara isi:</i> Tabel menyajikan item barang atau parameter baku mutu tetap. Anda cukup mengisi jumlah yang memenuhi standar mutu pada tiap baris kategori.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content 3: Formula, Numerator & Denominator -->
            <div v-if="activeTab === 'formula'" class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 space-y-2 text-slate-700">
                    <h4 class="font-bold text-blue-900 text-xs uppercase tracking-wider">Rumus Perhitungan Mutu Universal:</h4>
                    <div class="p-2.5 rounded-lg bg-white border border-blue-100 font-mono text-center text-xs font-bold text-blue-800">
                        Capaian (%) = ( Total Numerator / Total Denominator ) × 100%
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-200">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-200 text-emerald-900 uppercase">Numerator [N]</span>
                        <h5 class="font-bold text-slate-800 text-xs mt-1.5 mb-1">Peristiwa yang Memenuhi Standar Mutu</h5>
                        <p class="text-[11px] text-slate-500">
                            Contoh: Pasien yang SBAR-nya lengkap, waktu tunggu farmasi ≤ 30 menit, operasi dengan checklist lengkap, atau klaim BPJS yang tepat koding.
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-indigo-50/60 border border-indigo-200">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-200 text-indigo-900 uppercase">Denominator [D]</span>
                        <h5 class="font-bold text-slate-800 text-xs mt-1.5 mb-1">Total Seluruh Sampel / Peluang yang Diobservasi</h5>
                        <p class="text-[11px] text-slate-500">
                            Contoh: Total seluruh pasien yang diobservasi, seluruh resep obat yang dilayani, atau seluruh berkas klaim yang diajukan.
                        </p>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2">
                    <NotionIcon name="alert" size="16" class="text-amber-600 shrink-0" />
                    <span><b>Penting:</b> Nilai Denominator tidak boleh 0 saat diajukan ke Komite Mutu, karena pembagian dengan 0 tidak dapat menghasilkan persentase capaian mutu.</span>
                </div>
            </div>

            <!-- Tab Content 4: Tanya Jawab (FAQ) -->
            <div v-if="activeTab === 'faq'" class="space-y-2.5 text-xs text-slate-600 leading-relaxed">
                <details class="p-3 rounded-xl bg-slate-50 border border-slate-200 group cursor-pointer" open>
                    <summary class="font-bold text-slate-800 text-xs flex items-center justify-between">
                        <span>Bagaimana jika capaian unit saya di bawah target (berwarna merah)?</span>
                        <NotionIcon name="chevron-down" size="14" class="text-slate-400 group-open:rotate-180 transition-transform" />
                    </summary>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Data tetap dapat disimpan dan diajukan. Sistem secara transparan mencatat capaian riil. Setelah disahkan oleh Komite Mutu, indikator yang belum mencapai target akan direkomendasikan untuk analisis tindak lanjut evaluasi mutu (PDSA / Rencana Tindak Lanjut).
                    </p>
                </details>

                <details class="p-3 rounded-xl bg-slate-50 border border-slate-200 group cursor-pointer">
                    <summary class="font-bold text-slate-800 text-xs flex items-center justify-between">
                        <span>Apakah saya bisa mengedit data yang sudah terlanjur disimpan?</span>
                        <NotionIcon name="chevron-down" size="14" class="text-slate-400 group-open:rotate-180 transition-transform" />
                    </summary>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Bisa. Selama periode bulan tersebut belum disahkan oleh Komite Mutu, Anda bebas mengubah angka atau menambah baris, lalu klik tombol <b>Simpan Tally Sheet</b> kembali untuk memperbarui data.
                    </p>
                </details>

                <details class="p-3 rounded-xl bg-slate-50 border border-slate-200 group cursor-pointer">
                    <summary class="font-bold text-slate-800 text-xs flex items-center justify-between">
                        <span>Kapan saya perlu menggunakan tombol "Buat Form" atau "Format Formulir"?</span>
                        <NotionIcon name="chevron-down" size="14" class="text-slate-400 group-open:rotate-180 transition-transform" />
                    </summary>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Gunakan tombol tersebut hanya jika unit Anda membutuhkan kolom kustom tambahan (misal: menambah kolom nama dokter, observer, waktu respon, atau checklist baru). Jika format bawaan sudah sesuai, Anda cukup langsung mengisi tabel tanpa perlu mengubah format.
                    </p>
                </details>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <span class="text-[11px] text-slate-400">
                    Sistem Manajemen Mutu RS (mutu_rsys)
                </span>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition-colors"
                >
                    Saya Mengerti, Tutup Panduan
                </button>
            </div>

        </div>
    </div>
</template>
