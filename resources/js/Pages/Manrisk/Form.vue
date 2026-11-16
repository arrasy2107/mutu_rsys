<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    risk: Object,
    units: Array,
    defaultUnitId: [Number, String],
    autoNomor: String,
    isEdit: Boolean,
});

const form = useForm({
    nomor_register: props.risk?.nomor_register || props.autoNomor,
    nomor_urut: props.risk?.nomor_urut || 1,
    kode_risiko: props.risk?.kode_risiko || 'RO',
    tahun: props.risk?.tahun || 2026,
    unit_id: props.risk?.unit_id || props.defaultUnitId || '',
    ruang_lingkup: props.risk?.ruang_lingkup || 'Pelayanan',
    is_rs_priority: props.risk?.is_rs_priority || false,
    kategori_kars: props.risk?.kategori_kars || '',
    nomor_prioritas_rs: props.risk?.nomor_prioritas_rs || '',
    kategori_risiko: props.risk?.kategori_risiko || 'Risiko Operasional',
    kategori_mfk: props.risk?.kategori_mfk || '',
    kegiatan: props.risk?.kegiatan || '',
    tujuan_kegiatan: props.risk?.tujuan_kegiatan || '',
    pernyataan_risiko: props.risk?.pernyataan_risiko || '',
    deskripsi: props.risk?.deskripsi || '',
    penyebab: props.risk?.penyebab || '',
    kontrolabilitas: props.risk?.kontrolabilitas || 'C',
    dampak: props.risk?.dampak || '',
    pengendalian_ada: props.risk?.pengendalian_ada || '',
    pengendalian_desain: props.risk?.pengendalian_desain || 'ada',
    pengendalian_efektifitas_text: props.risk?.pengendalian_efektifitas_text || 'kurang_efektif',
    efektivitas_pengendalian: props.risk?.efektivitas_pengendalian || 'cukup',
    inherent_probabilitas: props.risk?.inherent_probabilitas || 3,
    inherent_dampak: props.risk?.inherent_dampak || 3,
    bobot: props.risk?.bobot || 1.15,
    peringkat_risiko: props.risk?.peringkat_risiko || null,
    pemilik_risiko: props.risk?.pemilik_risiko || '',
    alternatif_penanganan: props.risk?.alternatif_penanganan || 'Mengurangi kemungkinan',
    pengendalian_harus_ada: props.risk?.pengendalian_harus_ada || '',
    opsi_penanganan: props.risk?.opsi_penanganan || 'mitigasi',
    rencana_penanganan: props.risk?.rencana_penanganan || '',
    rencana_kegiatan: props.risk?.rencana_kegiatan || '',
    waktu_jadwal: props.risk?.waktu_jadwal || '',
    jenis_alternatif: props.risk?.jenis_alternatif || '',
    penanggung_jawab: props.risk?.penanggung_jawab || '',
    pemantauan_rencana: props.risk?.pemantauan_rencana || '',
    pemantauan_realisasi: props.risk?.pemantauan_realisasi || '',
    pemantauan_belum_tertangani: props.risk?.pemantauan_belum_tertangani || '',
    pemantauan_usulan_perbaikan: props.risk?.pemantauan_usulan_perbaikan || '',
    waktu_pemantauan_rencana: props.risk?.waktu_pemantauan_rencana || '',
    waktu_pemantauan_realisasi: props.risk?.waktu_pemantauan_realisasi || '',
    pj_pemantauan: props.risk?.pj_pemantauan || '',
    residual_probabilitas: props.risk?.residual_probabilitas || null,
    residual_dampak: props.risk?.residual_dampak || null,
    residual_bobot: props.risk?.residual_bobot || 1.0,
    penurunan_risiko_persen: props.risk?.penurunan_risiko_persen || null,
    pelaporan_output: props.risk?.pelaporan_output || '',
    pelaporan_target: props.risk?.pelaporan_target || '',
    pelaporan_realisasi: props.risk?.pelaporan_realisasi || '',
    waktu_implementasi: props.risk?.waktu_implementasi || '',
    status_tren: props.risk?.status_tren || '',
    level_risiko_evaluasi: props.risk?.level_risiko_evaluasi || '',
    status_pemantauan: props.risk?.status_pemantauan || 'dalam_proses',
    catatan_pemantauan: props.risk?.catatan_pemantauan || '',
    tanggal_review: props.risk?.tanggal_review || '',
});

const probabilitasDescriptions = {
    1: '1 - Jarang Terjadi (<= 1% / > 5 tahun sekali)',
    2: '2 - Kemungkinan Kecil (1% - 10% / 2 - 5 tahun sekali)',
    3: '3 - Kemungkinan Sedang (10% - 20% / 1 - 2 tahun sekali)',
    4: '4 - Kemungkinan Besar (20% - 50% / beberapa kali setahun)',
    5: '5 - Hampir Pasti Terjadi (> 50% / tiap minggu atau bulan)',
};

const dampakDescriptions = {
    1: '1 - Sangat Rendah / Insignificant (Tidak ada cedera)',
    2: '2 - Rendah / Minor (Dapat diatasi dengan pertolongan pertama P3K)',
    3: '3 - Sedang / Moderat (Gangguan fungsi semipermanen / perpanjang rawat)',
    4: '4 - Tinggi / Mayor (Cedera luas / kehilangan fungsi permanen)',
    5: '5 - Sangat Tinggi / Katastropik (Kematian tidak berhubungan dengan penyakit)',
};

const kategoriOptions = [
    { kode: 'RK', nama: 'Risiko Klinis' },
    { kode: 'RKeu', nama: 'Risiko Keuangan' },
    { kode: 'RKeb', nama: 'Risiko Kebijakan' },
    { kode: 'RKep', nama: 'Risiko Kepatuhan' },
    { kode: 'RO', nama: 'Risiko Operasional' },
    { kode: 'RR', nama: 'Risiko Reputasi' },
    { kode: 'RL', nama: 'Risiko Legal' },
    { kode: 'RF', nama: 'Risiko Fraud' },
];

const karsOptions = [
    '',
    'Risiko Klinis',
    'Risiko Keuangan',
    'Risiko Operasional',
    'Risiko Kepatuhan',
    'Risiko Reputasi',
    'Risiko Legal',
    'Risiko Kebijakan',
    'Risiko Fraud',
];

const onKategoriChange = () => {
    const found = kategoriOptions.find(k => k.nama === form.kategori_risiko);
    if (found) {
        form.kode_risiko = found.kode;
    }
};

// Live Calculated Skoring RSYS: P x D x Bobot
const liveInherentSkor = computed(() => {
    const p = Number(form.inherent_probabilitas || 1);
    const d = Number(form.inherent_dampak || 1);
    const b = Number(form.bobot || 1);
    return Number((p * d * b).toFixed(2));
});

const liveInherentBand = computed(() => {
    const s = liveInherentSkor.value;
    if (s > 19) return { name: 'Sangat Tinggi (Merah)', color: 'bg-red-600 text-white', desc: 'Wajib RTL Direksi & Komite Manajemen Risiko' };
    if (s > 14) return { name: 'Tinggi (Kuning Tua / Oranye)', color: 'bg-amber-600 text-white', desc: 'Perhatian Khusus & Tindakan Pengendalian Unit' };
    if (s > 9)  return { name: 'Sedang (Kuning Muda)', color: 'bg-yellow-400 text-gray-900', desc: 'Tanggung Jawab Kepala Unit & Pemantauan Rutin' };
    if (s > 4)  return { name: 'Rendah (Hijau)', color: 'bg-emerald-600 text-white', desc: 'Dapat Dikendalikan dengan SPO / Prosedur Rutin' };
    return { name: 'Sangat Rendah (Biru)', color: 'bg-blue-600 text-white', desc: 'Prosedur Kegiatan Normal' };
});

// Live Residual Score
const liveResidualSkor = computed(() => {
    if (!form.residual_probabilitas || !form.residual_dampak) return null;
    const rp = Number(form.residual_probabilitas);
    const rd = Number(form.residual_dampak);
    const rb = Number(form.residual_bobot || 1);
    return Number((rp * rd * rb).toFixed(2));
});

const submit = () => {
    if (props.isEdit) {
        form.put(route('manrisk.update', props.risk.id));
    } else {
        form.post(route('manrisk.store'));
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-amber-500 to-orange-600 rounded-full inline-block"></span>
                        {{ isEdit ? `Perbarui Profil Risiko: ${form.nomor_register}` : 'Identifikasi Profil Risiko Baru (47-Kolom RSYS)' }}
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Standar formulir lembar kerja Manajemen Risiko RS Yos Sudarso Tahun {{ form.tahun }}
                    </p>
                </div>
                <Link
                    :href="route('manrisk.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition"
                >
                    &larr; Kembali ke Register
                </Link>
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- ─── TAHAP 1: IDENTIFIKASI & RUANG LINGKUP ───────────────────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">1</span>
                        Identifikasi Risiko (Kolom 1 - 10)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Nomor Register <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.nomor_register"
                                class="w-full text-xs font-mono font-bold bg-gray-50 rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Unit Kerja Penanggung Jawab <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.unit_id"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                required
                            >
                                <option value="" disabled>-- Pilih Unit --</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Kategori Risiko (RSYS) <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.kategori_risiko"
                                @change="onKategoriChange"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                required
                            >
                                <option v-for="k in kategoriOptions" :key="k.kode" :value="k.nama">{{ k.nama }} ({{ k.kode }})</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Ruang Lingkup
                            </label>
                            <input
                                type="text"
                                v-model="form.ruang_lingkup"
                                placeholder="Pelayanan / Manajemen..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>
                    </div>

                    <!-- Topik Kegiatan & Tujuan SMART -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Topik Kegiatan Secara Spesifik <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.kegiatan"
                                placeholder="Contoh: Pengkajian ulang dan upaya pencegahan jatuh"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-bold"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tujuan Kegiatan (Kriteria SMART)
                            </label>
                            <input
                                type="text"
                                v-model="form.tujuan_kegiatan"
                                placeholder="Specific, Measurable, Achievable, Relevant, Time-bound"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>
                    </div>

                    <!-- Pernyataan Risiko Baku RSYS -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Pernyataan Risiko (Formula Baku RSYS: "Karena [sebab] kemungkinan [risiko] sehingga [akibat]") <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.pernyataan_risiko"
                            rows="2"
                            placeholder='Karena... kemungkinan... sehingga...'
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-medium"
                            required
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Akar Masalah / Sebab
                            </label>
                            <textarea
                                v-model="form.penyebab"
                                rows="2"
                                placeholder="Penyebab spesifik..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Kontrolabilitas (UC / C) <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.kontrolabilitas"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-bold"
                                required
                            >
                                <option value="C">C - Controllable (Dapat Dikontrol)</option>
                                <option value="UC">UC - Uncontrollable (Tidak Dapat Dikontrol)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Dampak / Akibat
                            </label>
                            <textarea
                                v-model="form.dampak"
                                rows="2"
                                placeholder="Akibat yang timbul..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Prioritas RS & KARS Markers -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3 border-t border-gray-100 bg-amber-50/30 p-3 rounded-xl">
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_rs_priority"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                />
                                <span class="text-xs font-bold text-gray-800">
                                    Prioritas Tingkat RS (Top 2 Unit)
                                </span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-600 mb-1">8 Pilar Prioritas KARS 2026</label>
                            <select
                                v-model="form.kategori_kars"
                                class="w-full text-xs rounded-xl border-amber-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">-- Bukan 8 Pilar KARS --</option>
                                <option v-for="kp in karsOptions.filter(Boolean)" :key="kp" :value="kp">{{ kp }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-600 mb-1">Nomor Urut Prioritas RS</label>
                            <input
                                type="text"
                                v-model="form.nomor_prioritas_rs"
                                placeholder="Contoh: 1, 2(2)..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- ─── TAHAP 2 & 3: PENGENDALIAN EKSISTING & ANALISIS SKORING ─────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">2 & 3</span>
                        Pengendalian yang Ada & Analisis Skoring RSYS (P &times; D &times; Bobot)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Uraian Pengendalian yang Ada Saat Ini (SOP, Regulasi, Sistem)
                            </label>
                            <textarea
                                v-model="form.pengendalian_ada"
                                rows="3"
                                placeholder="1. SPO... 2. Regulasi... 3. Checklist..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Desain Pengendalian</label>
                                <select
                                    v-model="form.pengendalian_desain"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="ada">Ada (Dilaksanakan Komprehensif)</option>
                                    <option value="tidak">Tidak / Belum Komprehensif</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Efektivitas</label>
                                <select
                                    v-model="form.pengendalian_efektifitas_text"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="efektif">Efektif (&gt; 50% terlaksana)</option>
                                    <option value="kurang_efektif">Kurang Efektif (sebagian terlaksana)</option>
                                    <option value="tidak_efektif">Tidak Efektif (&lt; 50% terlaksana)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Skoring Formula Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Probabilitas (P: 1 s.d 5) <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model.number="form.inherent_probabilitas"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                required
                            >
                                <option v-for="(desc, val) in probabilitasDescriptions" :key="val" :value="val">{{ desc }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Dampak (D: 1 s.d 5) <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model.number="form.inherent_dampak"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                required
                            >
                                <option v-for="(desc, val) in dampakDescriptions" :key="val" :value="val">{{ desc }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Bobot Pengendalian (B: 1.00 s.d 2.00) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.5"
                                max="3.0"
                                v-model.number="form.bobot"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-mono font-bold"
                                required
                            />
                        </div>
                    </div>

                    <!-- Hasil Formula Skoring Banner -->
                    <div class="p-4 rounded-xl border flex items-center justify-between" :class="liveInherentBand.color">
                        <div>
                            <p class="text-xs uppercase tracking-wider font-extrabold opacity-90">
                                Hasil Perhitungan Skoring RSYS (P &times; D &times; Bobot)
                            </p>
                            <p class="text-2xl font-black font-mono mt-0.5">
                                {{ liveInherentSkor }} &bull; {{ liveInherentBand.name }}
                            </p>
                            <p class="text-[11px] opacity-95 mt-0.5">{{ liveInherentBand.desc }}</p>
                        </div>
                        <div class="text-right text-xs font-mono font-bold bg-black/15 px-3 py-1.5 rounded-lg">
                            {{ form.inherent_probabilitas }} (P) &times; {{ form.inherent_dampak }} (D) &times; {{ Number(form.bobot).toFixed(2) }} (B)
                        </div>
                    </div>
                </div>

                <!-- ─── TAHAP 4: RENCANA PENANGANAN & PENGENDALIAN ─────────────────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-black">4</span>
                        Rencana Penanganan & Pengendalian (Kolom 24 - 29)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Alternatif Penanganan
                            </label>
                            <select
                                v-model="form.alternatif_penanganan"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option>Mengurangi kemungkinan</option>
                                <option>Mengurangi dampak</option>
                                <option>Menerima</option>
                                <option>Berbagi</option>
                                <option>Menghindari</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Penanggung Jawab Tindak Lanjut (PJ)
                            </label>
                            <input
                                type="text"
                                v-model="form.penanggung_jawab"
                                placeholder="Contoh: Ka. Instalasi / Ka. Ruangan"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Waktu / Jadwal Pelaksanaan
                            </label>
                            <input
                                type="text"
                                v-model="form.waktu_jadwal"
                                placeholder="Contoh: 1 bulan pada Januari 2026"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Pengendalian yang Harus Ada (RTL)
                            </label>
                            <textarea
                                v-model="form.pengendalian_harus_ada"
                                rows="3"
                                placeholder="Rencana tindak lanjut pengendalian..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Uraian Langkah Kegiatan Mitigasi
                            </label>
                            <textarea
                                v-model="form.rencana_kegiatan"
                                rows="3"
                                placeholder="Langkah-langkah terperinci untuk pelaksanaan mitigasi..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- ─── TAHAP 5, 6, 7: PEMANTAUAN, RESIDU & PELAPORAN ──────────────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">5 - 7</span>
                        Pemantauan, Evaluasi Residu & Pelaporan (Kolom 30 - 47)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Pemantauan: Rencana Kegiatan
                            </label>
                            <textarea
                                v-model="form.pemantauan_rencana"
                                rows="2"
                                placeholder="Rencana pemantauan..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Pemantauan: Realisasi yang Berjalan
                            </label>
                            <textarea
                                v-model="form.pemantauan_realisasi"
                                rows="2"
                                placeholder="Realisasi pemantauan..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Target Residu Evaluasi -->
                    <div class="pt-2 border-t border-gray-100">
                        <p class="text-xs font-bold text-gray-700 mb-2">Evaluasi Residu Risiko Saat Pemantauan (Opsional):</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">P Residu</label>
                                <select
                                    v-model.number="form.residual_probabilitas"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option :value="null">-- Belum Dinilai --</option>
                                    <option v-for="(desc, val) in probabilitasDescriptions" :key="val" :value="val">{{ desc }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">D Residu</label>
                                <select
                                    v-model.number="form.residual_dampak"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option :value="null">-- Belum Dinilai --</option>
                                    <option v-for="(desc, val) in dampakDescriptions" :key="val" :value="val">{{ desc }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Bobot Residu</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="form.residual_bobot"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-mono"
                                />
                            </div>
                        </div>

                        <div v-if="liveResidualSkor" class="mt-3 p-3 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-800">Target Residu: Skor {{ liveResidualSkor }}</span>
                            <span class="font-mono text-gray-500">{{ form.residual_probabilitas }} &times; {{ form.residual_dampak }} &times; {{ form.residual_bobot }}</span>
                        </div>
                    </div>

                    <!-- Pelaporan Data -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-gray-100">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Pelaporan: Output</label>
                            <input
                                type="text"
                                v-model="form.pelaporan_output"
                                placeholder="Hasil evaluasi dampak..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Pelaporan: Realisasi Target</label>
                            <input
                                type="text"
                                v-model="form.pelaporan_realisasi"
                                placeholder="Realisasi target..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Status Pemantauan</label>
                            <select
                                v-model="form.status_pemantauan"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-bold"
                            >
                                <option value="draft">Draft</option>
                                <option value="dalam_proses">Dalam Proses Mitigasi</option>
                                <option value="terkendali">Terkendali</option>
                                <option value="ditutup">Ditutup</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ─── SUBMIT BAR ────────────────────────────────────────────── -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('manrisk.index')"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50 transition"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-xs font-bold shadow-md shadow-amber-500/25 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan Data' : 'Simpan Register Profil Risiko') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
