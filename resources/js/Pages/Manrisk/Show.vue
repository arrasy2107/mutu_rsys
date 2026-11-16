<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    risk: Object,
});

const printSheet = () => {
    window.print();
};

const toggleRs = () => {
    router.post(route('manrisk.toggle-rs', props.risk.id), {}, { preserveScroll: true });
};

const bandBadge = (skorVal) => {
    const s = Number(skorVal || 0);
    if (s > 19) return 'bg-red-600 text-white font-black shadow-xs';
    if (s > 14) return 'bg-amber-600 text-white font-black shadow-xs';
    if (s > 9) return 'bg-yellow-400 text-gray-900 font-black shadow-xs';
    if (s > 4) return 'bg-emerald-600 text-white font-black shadow-xs';
    return 'bg-blue-600 text-white font-black shadow-xs';
};

const bandLabel = (skorVal) => {
    const s = Number(skorVal || 0);
    if (s > 19) return 'Sangat Tinggi (Merah)';
    if (s > 14) return 'Tinggi (Kuning Tua / Oranye)';
    if (s > 9) return 'Sedang (Kuning Muda)';
    if (s > 4) return 'Rendah (Hijau)';
    return 'Sangat Rendah (Biru)';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-amber-500 to-orange-600 rounded-full inline-block"></span>
                        Lembar Kerja Profil Risiko 8 Tahap: {{ risk.nomor_register || risk.kode_risiko }}
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Format resmi 47-kolom Manajemen Risiko RS Yos Sudarso Tahun {{ risk.tahun }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        @click="printSheet"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Cetak Formulir
                    </button>
                    <button
                        @click="toggleRs"
                        type="button"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold shadow-xs transition border',
                            risk.is_rs_priority
                                ? 'bg-blue-50 text-blue-800 border-blue-300'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ risk.is_rs_priority ? 'Prioritas RS (Aktif)' : 'Tandai Prioritas RS' }}
                    </button>
                    <Link
                        :href="route('manrisk.edit', risk.id)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-xs transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Ubah Data
                    </Link>
                    <Link
                        :href="route('manrisk.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition"
                    >
                        &larr; Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
            <!-- ─── HEADER IDENTITY BANNER ────────────────────────────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 flex-wrap mb-2">
                        <span class="font-mono font-black text-lg text-amber-700 bg-amber-50 px-3 py-1 rounded-lg border border-amber-200">
                            {{ risk.nomor_register || risk.kode_risiko }}
                        </span>
                        <span class="px-2.5 py-1 text-xs rounded-md font-bold uppercase tracking-wider bg-gray-100 text-gray-700 border border-gray-200">
                            Kode: {{ risk.kode_risiko }}
                        </span>
                        <span v-if="risk.is_rs_priority" class="px-2.5 py-1 text-xs rounded-md font-bold bg-blue-100 text-blue-800 border border-blue-200">
                            ★ Prioritas RS {{ risk.nomor_prioritas_rs ? `#${risk.nomor_prioritas_rs}` : '' }}
                        </span>
                        <span v-if="risk.kategori_kars" class="px-2.5 py-1 text-xs rounded-md font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            8 Pilar KARS: {{ risk.kategori_kars }}
                        </span>
                        <span class="px-2 py-0.5 text-xs rounded-md font-semibold bg-gray-50 text-gray-600 border">
                            Tahun {{ risk.tahun }}
                        </span>
                    </div>

                    <h2 class="text-lg font-black text-gray-900 leading-snug">
                        {{ risk.kegiatan || risk.pernyataan_risiko }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Pemilik Risiko: <strong class="text-gray-800">{{ risk.unit?.nama || risk.pemilik_risiko }}</strong> &bull;
                        Kategori: <strong class="text-gray-800">{{ risk.kategori_risiko }}</strong> &bull;
                        Ruang Lingkup: <strong class="text-gray-800">{{ risk.ruang_lingkup || 'Pelayanan' }}</strong>
                    </p>
                </div>

                <div class="md:text-right flex flex-col items-start md:items-end gap-1">
                    <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Tingkat Risiko Akhir</p>
                    <div :class="['px-4 py-2 rounded-xl text-center', bandBadge(risk.skoring_akhir || risk.inherent_skor)]">
                        <p class="text-2xl font-black font-mono">
                            {{ Number(risk.skoring_akhir || risk.inherent_skor || 0).toFixed(2) }}
                        </p>
                        <p class="text-[10px] uppercase font-bold tracking-wider mt-0.5">
                            {{ bandLabel(risk.skoring_akhir || risk.inherent_skor) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── TAHAP 1: IDENTIFIKASI RISIKO ──────────────────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-[10px]">1</span>
                        Identifikasi Risiko (Kolom 1 - 10)
                    </h3>
                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold', risk.kontrolabilitas === 'C' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                        Kontrolabilitas: {{ risk.kontrolabilitas === 'C' ? 'C (Controllable)' : 'UC (Uncontrollable)' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-gray-500 font-semibold">Tujuan Kegiatan (Standar SMART):</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1 min-h-[60px]">
                            {{ risk.tujuan_kegiatan || '-' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 font-semibold">Pernyataan Risiko (Formula RSYS):</span>
                        <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100 text-gray-900 mt-1 font-medium min-h-[60px]">
                            {{ risk.pernyataan_risiko }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 font-semibold">Akar Masalah / Sebab:</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1 min-h-[60px]">
                            {{ risk.penyebab || '-' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 font-semibold">Dampak / Akibat:</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1 min-h-[60px]">
                            {{ risk.dampak || '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── TAHAP 2 & 3: PENGENDALIAN EKSISTING & ANALISIS SKORING ─────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Pengendalian Yang Ada -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">2</span>
                        Pengendalian yang Ada (Kolom 11 - 16)
                    </h3>

                    <div class="text-xs space-y-3">
                        <div>
                            <span class="text-gray-500 font-semibold">Uraian Pengendalian Eksisting (SOP/Sistem):</span>
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1 whitespace-pre-line min-h-[80px]">
                                {{ risk.pengendalian_ada || 'Belum ada pengendalian yang tercatat.' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <p class="text-[10px] text-gray-500 uppercase font-semibold">Desain Pengendalian</p>
                                <p class="text-xs font-bold text-gray-800 mt-0.5">
                                    {{ risk.pengendalian_desain === 'ada' ? 'Ada (Komprehensif)' : 'Tidak / Belum Komprehensif' }}
                                </p>
                            </div>

                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <p class="text-[10px] text-gray-500 uppercase font-semibold">Efektivitas</p>
                                <p class="text-xs font-bold text-gray-800 mt-0.5 uppercase">
                                    {{ risk.pengendalian_efektifitas_text || risk.efektivitas_pengendalian }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analisis Risiko & Formula Skoring -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px]">3</span>
                        Analisis Risiko & Formula Skoring (Kolom 17 - 23)
                    </h3>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                            <p class="text-[10px] text-blue-600 font-bold uppercase">Probabilitas (P)</p>
                            <p class="text-xl font-black text-blue-900 mt-1">{{ risk.inherent_probabilitas }}</p>
                            <span class="text-[9px] text-blue-500">Skala 1 - 5</span>
                        </div>

                        <div class="p-3 bg-rose-50/50 rounded-xl border border-rose-100">
                            <p class="text-[10px] text-rose-600 font-bold uppercase">Dampak (D)</p>
                            <p class="text-xl font-black text-rose-900 mt-1">{{ risk.inherent_dampak }}</p>
                            <span class="text-[9px] text-rose-500">Skala 1 - 5</span>
                        </div>

                        <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100">
                            <p class="text-[10px] text-amber-600 font-bold uppercase">Bobot (B)</p>
                            <p class="text-xl font-black text-amber-900 mt-1">{{ Number(risk.bobot || 1).toFixed(2) }}</p>
                            <span class="text-[9px] text-amber-500">Faktor Pengali</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border text-center space-y-1" :class="bandBadge(risk.skoring_akhir || risk.inherent_skor)">
                        <p class="text-[10px] uppercase font-bold opacity-80">Rumus: P &times; D &times; Bobot</p>
                        <p class="text-2xl font-black font-mono">
                            {{ Number(risk.skoring_akhir || risk.inherent_skor).toFixed(2) }}
                        </p>
                        <p class="text-xs font-bold uppercase tracking-wider">
                            Tingkat Risiko: {{ bandLabel(risk.skoring_akhir || risk.inherent_skor) }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-600 pt-1">
                        <span>Peringkat Prioritas: <strong class="text-gray-900">#{{ risk.peringkat_risiko || '-' }}</strong></span>
                        <span>Pemilik Profil: <strong class="text-gray-900">{{ risk.pemilik_risiko || risk.unit?.nama }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- ─── TAHAP 4: RENCANA PENANGANAN & PENGENDALIAN ─────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                    <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-[10px]">4</span>
                    Rencana Penanganan & Pengendalian (Kolom 24 - 29)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-gray-500 font-semibold">Alternatif Penanganan:</span>
                        <div class="p-3 bg-purple-50/40 rounded-xl border border-purple-100 text-purple-900 font-bold mt-1">
                            {{ risk.alternatif_penanganan || risk.opsi_penanganan || 'Mengurangi kemungkinan' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 font-semibold">Pengendalian yang Harus Ada (RTL):</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1 whitespace-pre-line">
                            {{ risk.pengendalian_harus_ada || '-' }}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <span class="text-gray-500 font-semibold">Uraian Langkah-Langkah Kegiatan Mitigasi:</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1 whitespace-pre-line min-h-[70px]">
                            {{ risk.rencana_kegiatan || risk.rencana_penanganan || '-' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 font-semibold">Jadwal / Waktu Pelaksanaan:</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-800 mt-1">
                            {{ risk.waktu_jadwal || '-' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 font-semibold">Penanggung Jawab Tindak Lanjut (PJ):</span>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-900 font-bold mt-1">
                            {{ risk.penanggung_jawab || '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── TAHAP 5, 6, 7: PEMANTAUAN, RESIDU & PELAPORAN ──────────────── -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pemantauan -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
                        <span class="w-4 h-4 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-[9px]">5</span>
                        Pemantauan (Kolom 30 - 36)
                    </h3>
                    <div class="text-xs space-y-2">
                        <div>
                            <p class="text-[10px] text-gray-400 font-semibold">Rencana Pemantauan</p>
                            <p class="text-gray-800 font-medium whitespace-pre-line">{{ risk.pemantauan_rencana || '-' }}</p>
                        </div>
                        <div class="pt-1 border-t border-gray-50">
                            <p class="text-[10px] text-gray-400 font-semibold">Realisasi</p>
                            <p class="text-emerald-700 font-medium whitespace-pre-line">{{ risk.pemantauan_realisasi || '-' }}</p>
                        </div>
                        <div class="pt-1 border-t border-gray-50">
                            <p class="text-[10px] text-gray-400 font-semibold">Belum Tertangani</p>
                            <p class="text-rose-600 font-medium whitespace-pre-line">{{ risk.pemantauan_belum_tertangani || '-' }}</p>
                        </div>
                        <div class="pt-1 border-t border-gray-50">
                            <p class="text-[10px] text-gray-400 font-semibold">Usulan Perbaikan</p>
                            <p class="text-gray-800 font-medium whitespace-pre-line">{{ risk.pemantauan_usulan_perbaikan || '-' }}</p>
                        </div>
                        <div class="pt-1 border-t border-gray-50 text-[11px]">
                            <p><span class="text-gray-400">PJ Pemantauan:</span> <strong>{{ risk.pj_pemantauan || '-' }}</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Residu Risiko -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[9px]">6</span>
                        Evaluasi Residu (Kolom 37 - 41)
                    </h3>
                    <div class="text-xs space-y-2">
                        <div class="grid grid-cols-3 gap-1 text-center font-mono">
                            <div class="p-2 bg-gray-50 rounded-lg">
                                <p class="text-[9px] text-gray-400">P</p>
                                <p class="font-bold text-gray-800">{{ risk.residual_probabilitas || '-' }}</p>
                            </div>
                            <div class="p-2 bg-gray-50 rounded-lg">
                                <p class="text-[9px] text-gray-400">D</p>
                                <p class="font-bold text-gray-800">{{ risk.residual_dampak || '-' }}</p>
                            </div>
                            <div class="p-2 bg-gray-50 rounded-lg">
                                <p class="text-[9px] text-gray-400">B</p>
                                <p class="font-bold text-gray-800">{{ risk.residual_bobot || '-' }}</p>
                            </div>
                        </div>

                        <div v-if="risk.residual_skor" class="p-3 rounded-xl border text-center" :class="bandBadge(risk.residual_skor)">
                            <p class="text-[9px] uppercase font-bold opacity-80">Skor Residu</p>
                            <p class="text-xl font-black font-mono">{{ Number(risk.residual_skor).toFixed(2) }}</p>
                            <p class="text-[10px] uppercase font-semibold">{{ bandLabel(risk.residual_skor) }}</p>
                        </div>
                        <div v-else class="p-3 bg-gray-50 rounded-xl text-center text-gray-400 italic text-[11px]">
                            Belum dievaluasi
                        </div>

                        <div v-if="risk.penurunan_risiko_persen" class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-200 text-center">
                            <p class="text-[10px] text-emerald-600 font-semibold">Penurunan Risiko</p>
                            <p class="text-lg font-black text-emerald-700 font-mono">&darr; {{ risk.penurunan_risiko_persen }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Pelaporan -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
                        <span class="w-4 h-4 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-[9px]">7</span>
                        Pelaporan (Kolom 42 - 47)
                    </h3>
                    <div class="text-xs space-y-2">
                        <div>
                            <p class="text-[10px] text-gray-400 font-semibold">Output Evaluasi</p>
                            <p class="text-gray-800 font-medium">{{ risk.pelaporan_output || '-' }}</p>
                        </div>
                        <div class="pt-1 border-t border-gray-50">
                            <p class="text-[10px] text-gray-400 font-semibold">Target & Realisasi</p>
                            <p class="text-gray-800"><span class="text-gray-400">Target:</span> {{ risk.pelaporan_target || '-' }}</p>
                            <p class="text-emerald-700 font-semibold"><span class="text-gray-400">Realisasi:</span> {{ risk.pelaporan_realisasi || '-' }}</p>
                        </div>
                        <div class="pt-1 border-t border-gray-50">
                            <p class="text-[10px] text-gray-400 font-semibold">Waktu Implementasi & Tren</p>
                            <p class="text-gray-700">{{ risk.waktu_implementasi || '-' }}</p>
                            <p class="font-bold text-amber-700 mt-0.5">Tren: {{ risk.status_tren || '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
