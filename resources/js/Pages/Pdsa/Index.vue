<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    records: Object,
    matrixRecords: Array,
    units: Array,
    filters: Object,
});

const activeTab = ref('matrix'); // 'matrix' | 'cards'

const selectedUnit = ref(props.filters.unit_id || '');
const selectedTahun = ref(props.filters.tahun || 2026);
const selectedStatus = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('pdsa.index'), {
        unit_id: selectedUnit.value,
        tahun: selectedTahun.value,
        status: selectedStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const monthNames = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const statusConfig = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-700 border-gray-200' },
    submitted: { label: 'Menunggu Review', color: 'bg-amber-100 text-amber-800 border-amber-200' },
    reviewed: { label: 'Disetujui / Selesai', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
};

const currentUnitName = computed(() => {
    if (!selectedUnit.value) return 'Semua Unit';
    const found = props.units.find(u => u.id === Number(selectedUnit.value));
    return found ? found.nama : 'Unit';
});

const groupedMatrix = computed(() => {
    const list = props.matrixRecords || [];
    const groups = {};
    list.forEach(rec => {
        const m = rec.period ? rec.period.bulan : 0;
        if (!groups[m]) groups[m] = [];
        groups[m].push(rec);
    });
    return groups;
});

const printDocument = () => {
    window.print();
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <h1 class="text-base font-bold text-gray-800 leading-none">Evaluasi Mutu: RTL & PDSA</h1>
                <span class="hidden md:inline text-xs text-gray-400">• Siklus Peningkatan Mutu Berkelanjutan & Matriks RTL (Standar KARS)</span>
            </div>
        </template>

        <!-- ─── Page Title & Action Bar (Screen only) ─── -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 mb-5 print:hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-xl shrink-0 shadow-xs">
                        🔄
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 tracking-tight leading-tight">
                                Evaluasi Mutu: Rencana Tindak Lanjut & PDSA
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                Standar KARS
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Siklus Peningkatan Mutu Berkelanjutan (Plan - Do - Study - Act) dan Matriks Rencana Tindak Lanjut Standar KARS.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                    <a
                        :href="route('pdsa.export-rtl', { unit_id: selectedUnit, tahun: selectedTahun })"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors shadow-xs"
                        target="_blank"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Ekspor RTL (.xlsx)</span>
                    </a>

                    <button
                        @click="printDocument"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak Dokumen</span>
                    </button>

                    <Link
                        :href="route('pdsa.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition-all shadow-sm hover:shadow-indigo-500/20"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Buat PDSA Baru</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- KOP CETAK RESMI (Hanya Muncul Saat Print / PDF) -->
        <div class="hidden print:block mb-6 border-b-2 border-slate-800 pb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-wider text-slate-900 uppercase">RUMAH SAKIT YARSI SUMATERA BARAT</h2>
                    <p class="text-xs text-slate-600 font-medium">KOMITE PENINGKATAN MUTU DAN KESELAMATAN PASIEN (PMKP)</p>
                    <p class="text-[10px] text-slate-500">Jl. KH. Ahmad Dahlan, Bukittinggi - Sumatera Barat</p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-2 py-1 text-[11px] font-bold border border-slate-700 uppercase">
                        STANDAR AKREDITASI KARS
                    </span>
                    <p class="text-[9px] text-slate-500 mt-1">Formulir Evaluasi & Rencana Tindak Lanjut Mutu</p>
                </div>
            </div>
            <div class="mt-4 text-center">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                    MATRIKS RENCANA TINDAK LANJUT (RTL) MUTU UNIT KERJA
                </h3>
                <p class="text-xs text-slate-700 font-medium mt-0.5">
                    Unit: {{ currentUnitName }} &bull; Periode Tahun {{ selectedTahun }}
                </p>
            </div>
        </div>

        <!-- Filter Bar & Mode Tab (Screen only) -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-4 mb-6 print:hidden">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Tab Switching -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/70">
                    <button
                        @click="activeTab = 'matrix'"
                        :class="['px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-2',
                            activeTab === 'matrix' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                        <span>📋</span>
                        Matriks RTL Bulanan
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-indigo-50 text-indigo-600 font-bold">
                            {{ matrixRecords?.length || 0 }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'cards'"
                        :class="['px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-2',
                            activeTab === 'cards' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                        <span>🔄</span>
                        Siklus 4-Phase PDSA
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-indigo-50 text-indigo-600 font-bold">
                            {{ records?.total || records?.data?.length || 0 }}
                        </span>
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Unit Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-slate-500 whitespace-nowrap">Unit:</label>
                        <select
                            v-model="selectedUnit"
                            @change="applyFilters"
                            class="text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-3 pr-8 bg-slate-50 font-medium"
                        >
                            <option value="">Semua Unit</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>

                    <!-- Tahun Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-slate-500 whitespace-nowrap">Tahun:</label>
                        <select
                            v-model="selectedTahun"
                            @change="applyFilters"
                            class="text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-3 pr-8 bg-slate-50 font-medium"
                        >
                            <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-slate-500 whitespace-nowrap">Status:</label>
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-3 pr-8 bg-slate-50 font-medium"
                        >
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Menunggu Review</option>
                            <option value="reviewed">Disetujui / Selesai</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 1: MATRIKS RTL BULANAN (Format Identik RTL.xlsx) -->
        <div v-if="activeTab === 'matrix'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden print:border-none print:shadow-none">
                <!-- Table Header info -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50 print:hidden">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h2 class="text-sm font-bold text-gray-800">
                            Matriks Rencana Tindak Lanjut (RTL) Periode {{ selectedTahun }}
                        </h2>
                    </div>
                    <span class="text-xs text-gray-500 font-medium">
                        Unit: <strong class="text-indigo-600">{{ currentUnitName }}</strong> &bull; Total: {{ matrixRecords?.length || 0 }} dokumen
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse print:text-[9px]">
                        <thead>
                            <tr class="bg-emerald-50/80 text-emerald-950 border-b border-emerald-200 print:bg-slate-100 print:text-black">
                                <th class="py-3 px-3.5 font-bold uppercase tracking-wider text-center w-24">Bulan</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider min-w-[200px]">Judul Indikator Mutu</th>
                                <th class="py-3 px-3 font-bold uppercase tracking-wider text-center w-20">Target</th>
                                <th class="py-3 px-3 font-bold uppercase tracking-wider text-center w-20">Capaian</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider min-w-[220px]">Masalah / Gap</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider min-w-[220px]">Penyebab / Akar Masalah</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider min-w-[240px]">RTL (Rencana Tindak Lanjut)</th>
                                <th class="py-3 px-3.5 font-bold uppercase tracking-wider text-center min-w-[130px]">PJ RTL</th>
                                <th class="py-3 px-3.5 font-bold uppercase tracking-wider text-center min-w-[140px]">Bukti RTL</th>
                                <th class="py-3 px-3 font-bold uppercase tracking-wider text-center w-24 print:hidden">Status</th>
                                <th class="py-3 px-3 text-center w-14 print:hidden">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 print:divide-slate-300">
                            <template v-if="matrixRecords?.length">
                                <tr
                                    v-for="(rec, idx) in matrixRecords"
                                    :key="rec.id"
                                    class="hover:bg-indigo-50/30 transition-colors group"
                                >
                                    <!-- Bulan -->
                                    <td class="py-3 px-3.5 text-center font-bold text-slate-800 align-top bg-slate-50/30">
                                        <span class="inline-block px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 print:border-none print:bg-transparent">
                                            {{ rec.period ? monthNames[rec.period.bulan] : '-' }}
                                        </span>
                                    </td>

                                    <!-- Judul Imut -->
                                    <td class="py-3 px-4 align-top">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-indigo-100 text-indigo-800 print:border print:border-slate-300">
                                                {{ rec.indicator?.kode }}
                                            </span>
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-slate-100 text-slate-700">
                                                {{ rec.jenis_tindak_lanjut === 'rtl' ? 'RTL' : 'PDSA' }}
                                            </span>
                                            <span v-if="rec.unit" class="text-[10px] text-gray-500 font-medium">
                                                ({{ rec.unit.nama }})
                                            </span>
                                        </div>
                                        <p class="font-semibold text-gray-900 leading-snug">
                                            {{ rec.indicator?.judul }}
                                        </p>
                                    </td>

                                    <!-- Target -->
                                    <td class="py-3 px-3 text-center align-top font-bold text-gray-600">
                                        {{ rec.target_capaian }}%
                                    </td>

                                    <!-- Capaian -->
                                    <td class="py-3 px-3 text-center align-top font-black">
                                        <span :class="Number(rec.capaian_aktual) < Number(rec.target_capaian) ? 'text-rose-600' : 'text-emerald-600'">
                                            {{ rec.capaian_aktual }}%
                                        </span>
                                    </td>

                                    <!-- Masalah -->
                                    <td class="py-3 px-4 align-top text-gray-700 leading-relaxed">
                                        {{ rec.masalah }}
                                    </td>

                                    <!-- Penyebab / Akar Masalah -->
                                    <td class="py-3 px-4 align-top text-gray-700 leading-relaxed">
                                        <p class="font-medium text-slate-800">{{ rec.akar_masalah || '-' }}</p>
                                        <div v-if="rec.analisa_penyebab_manusia" class="mt-1 text-[11px] text-slate-500">
                                            <span class="font-semibold text-slate-700">5M:</span> {{ rec.analisa_penyebab_manusia }}
                                        </div>
                                    </td>

                                    <!-- RTL -->
                                    <td class="py-3 px-4 align-top text-indigo-950 font-medium leading-relaxed bg-indigo-50/20 print:bg-transparent">
                                        {{ rec.rekomendasi_tindak_lanjut || rec.plan || '-' }}
                                    </td>

                                    <!-- PJ RTL -->
                                    <td class="py-3 px-3.5 text-center align-top text-slate-700 font-semibold">
                                        <span class="inline-block px-2 py-1 rounded bg-slate-100 text-slate-800 border border-slate-200 print:border-none print:bg-transparent">
                                            {{ rec.pj_rtl || 'Ka. Unit / PJ Mutu' }}
                                        </span>
                                    </td>

                                    <!-- Bukti RTL -->
                                    <td class="py-3 px-3.5 align-top text-gray-600 text-center">
                                        <span class="text-[11px] italic text-slate-600">
                                            {{ rec.bukti_rtl || 'Dokumen / Laporan' }}
                                        </span>
                                    </td>

                                    <!-- Status (Screen Only) -->
                                    <td class="py-3 px-3 text-center align-top print:hidden">
                                        <span :class="['inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border', statusConfig[rec.status]?.color]">
                                            {{ statusConfig[rec.status]?.label }}
                                        </span>
                                    </td>

                                    <!-- Aksi (Screen Only) -->
                                    <td class="py-3 px-3 text-center align-top print:hidden">
                                        <Link
                                            :href="route('pdsa.show', rec.id)"
                                            class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Buka Siklus PDSA Lengkap"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="11" class="py-12 text-center text-gray-400">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <span class="text-3xl">📭</span>
                                        <p class="font-medium text-gray-600">Belum ada catatan RTL untuk filter ini.</p>
                                        <p class="text-xs text-gray-400">Pilih unit lain atau buat PDSA baru untuk indikator yang belum mencapai target.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tanda Tangan Cetak Standar KARS (Hanya Muncul Saat Print / PDF) -->
            <div class="hidden print:block mt-8 pt-4">
                <div class="grid grid-cols-2 text-center text-xs">
                    <div>
                        <p class="text-slate-600">Mengetahui,</p>
                        <p class="font-bold text-slate-900 mt-0.5">Kepala Unit / Penanggung Jawab</p>
                        <div class="h-20 flex items-center justify-center text-slate-400 italic text-[10px]">
                            ( Tanda Tangan & Cap )
                        </div>
                        <p class="font-bold text-slate-900 border-t border-slate-400 inline-block px-8 pt-1">
                            {{ currentUnitName }}
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-600">Disetujui,</p>
                        <p class="font-bold text-slate-900 mt-0.5">Ketua Komite Mutu RS</p>
                        <div class="h-20 flex items-center justify-center text-slate-400 italic text-[10px]">
                            ( Tanda Tangan & Cap )
                        </div>
                        <p class="font-bold text-slate-900 border-t border-slate-400 inline-block px-8 pt-1">
                            dr. Komite Mutu & Keselamatan Pasien
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: SIKLUS 4-PHASE PDSA CARDS -->
        <div v-else class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div
                    v-for="rec in records.data"
                    :key="rec.id"
                    class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-5 hover:border-indigo-200 hover:shadow-md transition-all flex flex-col justify-between"
                >
                    <div>
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs font-black rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ rec.indicator?.kode }}
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium">
                                        {{ rec.unit?.nama }}
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-900 mt-2 leading-snug">
                                    {{ rec.indicator?.judul }}
                                </h3>
                                <p class="text-xs text-indigo-600 font-semibold mt-0.5">
                                    Periode: {{ rec.period ? monthNames[rec.period.bulan] + ' ' + rec.period.tahun : '-' }}
                                </p>
                            </div>

                            <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border whitespace-nowrap', statusConfig[rec.status]?.color]">
                                {{ statusConfig[rec.status]?.label }}
                            </span>
                        </div>

                        <!-- Capaian Bar & Gap -->
                        <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-slate-500">Capaian Aktual:</span>
                                <span class="font-black ml-1 text-rose-600">{{ rec.capaian_aktual }}%</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Target:</span>
                                <span class="font-bold ml-1 text-slate-800">{{ rec.target_capaian }}%</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Gap:</span>
                                <span class="font-bold ml-1 text-rose-500">
                                    {{ Math.abs(Number(rec.target_capaian) - Number(rec.capaian_aktual)).toFixed(1) }}%
                                </span>
                            </div>
                        </div>

                        <!-- Masalah & Akar Masalah -->
                        <div class="mt-4 space-y-2 text-xs">
                            <div class="p-2.5 bg-rose-50/60 rounded-xl border border-rose-100">
                                <span class="font-bold text-rose-900 block mb-0.5">🔴 Masalah Teridentifikasi:</span>
                                <p class="text-rose-800 leading-relaxed">{{ rec.masalah }}</p>
                            </div>

                            <div v-if="rec.akar_masalah" class="p-2.5 bg-amber-50/60 rounded-xl border border-amber-100">
                                <span class="font-bold text-amber-900 block mb-0.5">🔍 Akar Masalah:</span>
                                <p class="text-amber-800 leading-relaxed">{{ rec.akar_masalah }}</p>
                            </div>
                        </div>

                        <!-- 4 Phases Preview Pills -->
                        <div class="grid grid-cols-4 gap-2 mt-4 text-[11px] text-center font-semibold">
                            <div :class="['py-1.5 px-1 rounded-lg border', rec.plan ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-400 border-gray-200']">
                                📋 Plan
                            </div>
                            <div :class="['py-1.5 px-1 rounded-lg border', rec.do_action ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-gray-50 text-gray-400 border-gray-200']">
                                ⚙️ Do
                            </div>
                            <div :class="['py-1.5 px-1 rounded-lg border', rec.study ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-gray-50 text-gray-400 border-gray-200']">
                                🔍 Study
                            </div>
                            <div :class="['py-1.5 px-1 rounded-lg border', rec.act ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-400 border-gray-200']">
                                ✅ Act
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">
                            PJ: <strong class="text-slate-600">{{ rec.pj_rtl || 'Unit' }}</strong>
                        </span>

                        <Link
                            :href="route('pdsa.show', rec.id)"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold rounded-lg text-xs transition-colors"
                        >
                            Detail Siklus & RTL
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!records.data?.length" class="p-12 text-center bg-white rounded-2xl border border-gray-200/80">
                <span class="text-3xl">📭</span>
                <p class="font-medium text-gray-700 mt-2">Belum ada siklus PDSA tercatat.</p>
                <p class="text-xs text-gray-400 mt-1">Buat PDSA baru untuk indikator mutu yang belum mencapai target.</p>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    body {
        background: white !important;
        font-size: 9pt !important;
        color: black !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:block {
        display: block !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    th, td {
        border: 1px solid #94a3b8 !important;
        padding: 4px 6px !important;
    }
}
</style>
