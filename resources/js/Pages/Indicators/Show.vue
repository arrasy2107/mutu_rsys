<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    indicator: Object,
});

const statusLabels = { Input: 'Input', Proses: 'Proses', Output: 'Output', Outcome: 'Outcome' };
const statusColors = { 
    input: 'bg-blue-50 text-blue-700 border-blue-200', 
    proses: 'bg-indigo-50 text-indigo-700 border-indigo-200', 
    output: 'bg-emerald-50 text-emerald-700 border-emerald-200', 
    outcome: 'bg-purple-50 text-purple-700 border-purple-200' 
};

const jenisBadgeStyles = {
    'IMN': 'bg-rose-50 text-rose-700 border-rose-200',
    'IMP-RS': 'bg-amber-50 text-amber-700 border-amber-200',
    'IMP-U': 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'IMU': 'bg-teal-50 text-teal-700 border-teal-200',
    'SPM': 'bg-sky-50 text-sky-700 border-sky-200'
};

const operatorLabel = (op) => {
    if (op === '>=') return '≥';
    if (op === '<=') return '≤';
    if (op === '>') return '>';
    if (op === '<') return '<';
    return '=';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <nav class="flex items-center gap-2 text-xs text-gray-500">
                    <Link :href="route('indicators.index')" class="hover:text-indigo-600 transition-colors">Profil Indikator Mutu</Link>
                    <span>/</span>
                    <span class="text-gray-800 font-bold font-mono">{{ indicator.kode }}</span>
                </nav>
                <Link
                    :href="route('indicators.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white hover:bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200 shadow-2xs transition-colors"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Daftar</span>
                </Link>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-4 pb-12">
            <!-- 1. HEADER CARD (KOMPAK & ELEGAN) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="flex-1 min-w-[280px]">
                        <!-- Badges Header -->
                        <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                            <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
                                {{ indicator.kode }}
                            </span>

                            <span
                                v-for="j in (indicator.jenis || [])"
                                :key="j"
                                :class="['inline-flex px-2 py-0.5 rounded text-[11px] font-bold border', jenisBadgeStyles[j] || 'bg-gray-100 text-gray-700 border-gray-200']"
                            >
                                {{ j }}
                            </span>

                            <span
                                v-for="ji in (Array.isArray(indicator.jenis_indikator) ? indicator.jenis_indikator : [indicator.jenis_indikator].filter(Boolean))"
                                :key="ji"
                                :class="['text-[11px] px-2 py-0.5 rounded-full font-semibold border', statusColors[ji?.toLowerCase()] || 'bg-gray-100 text-gray-700 border-gray-200']"
                            >
                                {{ statusLabels[ji] || ji }}
                            </span>

                            <span
                                :class="[
                                    'text-[11px] px-2 py-0.5 rounded-full font-semibold border',
                                    indicator.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                                ]"
                            >
                                {{ indicator.is_active ? '● Aktif' : '○ Non-Aktif' }}
                            </span>
                        </div>

                        <!-- Judul Indikator -->
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight leading-snug">
                            {{ indicator.judul }}
                        </h1>

                        <!-- Subtitle Unit & Dimensi -->
                        <div class="flex items-center gap-2 mt-1.5 text-xs text-gray-500 flex-wrap">
                            <span class="font-medium text-gray-700 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                {{ indicator.unit ? indicator.unit.nama : 'Tingkat Rumah Sakit (Semua Unit)' }}
                            </span>
                            <span v-if="indicator.dimensi_mutu?.length">&bull;</span>
                            <span v-if="indicator.dimensi_mutu?.length">
                                Dimensi Mutu: <strong class="text-gray-700">{{ (indicator.dimensi_mutu || []).join(', ') }}</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap sm:flex-nowrap shrink-0">
                        <!-- Export PDF -->
                        <a
                            :href="route('indicators.export-pdf', { ids: indicator.id })"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg shadow-2xs transition-colors"
                            title="Unduh Kamus Profil Indikator dalam bentuk PDF"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>PDF</span>
                        </a>

                        <!-- Export Excel -->
                        <a
                            :href="route('indicators.export-excel', { ids: indicator.id })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-lg shadow-2xs transition-colors"
                            title="Unduh Kamus Profil Indikator dalam bentuk Excel"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Excel</span>
                        </a>

                        <!-- Buka Tally Sheet -->
                        <Link
                            :href="route('data-entry.index', { indicator_id: indicator.id, unit_id: indicator.unit_id })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold rounded-lg shadow-2xs transition-colors"
                            title="Buka Lembar Pengumpul Data (Tally Sheet)"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Form Pengumpul</span>
                        </Link>

                        <!-- Edit Profil -->
                        <Link
                            :href="route('indicators.edit', indicator.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold rounded-lg shadow-2xs transition-colors"
                            title="Edit Data Profil Indikator"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Profil</span>
                        </Link>
                    </div>
                </div>

                <!-- Quick Metric Strip -->
                <div class="mt-4 pt-3.5 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-2.5 rounded-lg bg-indigo-50/70 border border-indigo-100">
                        <span class="text-[10px] uppercase font-bold text-indigo-700 tracking-wider">Target Capaian</span>
                        <div class="text-base font-extrabold text-indigo-900 mt-0.5">
                            {{ operatorLabel(indicator.target_operator) }} {{ indicator.target_capaian }}
                            <span class="text-xs font-semibold text-indigo-600">{{ indicator.satuan_pengukuran === 'Persentase' ? '%' : (indicator.satuan_pengukuran === 'Permil' ? '‰' : `(${indicator.satuan_pengukuran})`) }}</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Periode Pengumpulan</span>
                        <div class="text-sm font-bold text-slate-800 mt-0.5 capitalize truncate">
                            {{ indicator.frekuensi || 'Bulanan' }}
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Sumber Data</span>
                        <div class="text-sm font-bold text-slate-800 mt-0.5 truncate" :title="indicator.sumber_data">
                            {{ indicator.sumber_data || '-' }}
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Penanggung Jawab</span>
                        <div class="text-sm font-bold text-slate-800 mt-0.5 truncate" :title="indicator.penanggung_jawab">
                            {{ indicator.penanggung_jawab || (indicator.unit ? 'PJ ' + indicator.unit.nama : 'Komite Mutu') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. TABEL KAMUS & PROFIL INDIKATOR MUTU (STANDAR KARS / PMKP) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50/80 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Kamus & Parameter Profil Indikator</h2>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Standar Kamus Indikator KARS / PMKP</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100/70 border-b border-gray-200 text-gray-500 uppercase tracking-wider text-[10px]">
                                <th class="w-12 px-3 py-2 text-center font-bold">No</th>
                                <th class="w-64 sm:w-80 px-4 py-2 font-bold">Parameter / Butir Profil</th>
                                <th class="px-4 py-2 font-bold">Keterangan / Nilai Penetapan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- ================= SECTION A: IDENTITAS ================= -->
                            <tr class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white font-bold">
                                <td colspan="3" class="px-4 py-2 text-[11px] tracking-wider uppercase">
                                    A. IDENTITAS INDIKATOR MUTU
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">1</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Judul Indikator</td>
                                <td class="px-4 py-2.5 font-bold text-gray-900 text-sm leading-relaxed">{{ indicator.judul }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">2</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Kode Indikator</td>
                                <td class="px-4 py-2.5">
                                    <span class="font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded text-xs">
                                        {{ indicator.kode }}
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">3</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Unit Kerja Terkait</td>
                                <td class="px-4 py-2.5 font-semibold text-gray-800">
                                    {{ indicator.unit ? indicator.unit.nama : 'Tingkat Rumah Sakit (Semua Unit Kerja)' }}
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">4</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Jenis Indikator Mutu</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="j in (indicator.jenis || [])"
                                            :key="j"
                                            :class="['px-2 py-0.5 rounded text-[11px] font-bold border', jenisBadgeStyles[j] || 'bg-gray-100 text-gray-700 border-gray-200']"
                                        >
                                            {{ j }}
                                        </span>
                                        <span v-if="!indicator.jenis?.length" class="text-gray-400 italic">-</span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">5</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Tipe / Jenis Indikator</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="ji in (Array.isArray(indicator.jenis_indikator) ? indicator.jenis_indikator : [indicator.jenis_indikator].filter(Boolean))"
                                            :key="ji"
                                            :class="['px-2 py-0.5 rounded-full text-[11px] font-semibold border', statusColors[ji?.toLowerCase()] || 'bg-gray-100 text-gray-700 border-gray-200']"
                                        >
                                            {{ statusLabels[ji] || ji }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">6</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Dimensi Mutu</td>
                                <td class="px-4 py-2.5 text-gray-800">
                                    {{ (indicator.dimensi_mutu || []).join(', ') || '-' }}
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">7</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Dasar Pemikiran</td>
                                <td class="px-4 py-2.5 text-gray-800 whitespace-pre-line leading-relaxed">{{ indicator.dasar_pemikiran || '-' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">8</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Tujuan Pengukuran</td>
                                <td class="px-4 py-2.5 text-gray-800 whitespace-pre-line leading-relaxed">{{ indicator.tujuan || '-' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">9</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Definisi Operasional</td>
                                <td class="px-4 py-2.5 text-gray-800 whitespace-pre-line leading-relaxed">{{ indicator.definisi_operasional || '-' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">10</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Kriteria Inklusi</td>
                                <td class="px-4 py-2.5">
                                    <div v-if="indicator.kriteria_inklusi" class="inline-flex items-start gap-1.5 text-emerald-800 font-medium">
                                        <span class="text-xs">✅</span>
                                        <span>{{ indicator.kriteria_inklusi }}</span>
                                    </div>
                                    <span v-else class="text-gray-400 italic">-</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">11</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Kriteria Eksklusi</td>
                                <td class="px-4 py-2.5">
                                    <div v-if="indicator.kriteria_eksklusi" class="inline-flex items-start gap-1.5 text-rose-800 font-medium">
                                        <span class="text-xs">❌</span>
                                        <span>{{ indicator.kriteria_eksklusi }}</span>
                                    </div>
                                    <span v-else class="text-gray-400 italic">-</span>
                                </td>
                            </tr>

                            <!-- ================= SECTION B: PENGUKURAN & TARGET ================= -->
                            <tr class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white font-bold">
                                <td colspan="3" class="px-4 py-2 text-[11px] tracking-wider uppercase">
                                    B. METODE PENGUKURAN & TARGET CAPAIAN
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">12</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Numerator (Pembilang)</td>
                                <td class="px-4 py-2.5 text-gray-800 leading-relaxed">{{ indicator.numerator_keterangan || '-' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">13</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Denominator (Penyebut)</td>
                                <td class="px-4 py-2.5 text-gray-800 leading-relaxed">{{ indicator.denominator_keterangan || '-' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">14</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Formula Perhitungan</td>
                                <td class="px-4 py-2.5">
                                    <code v-if="indicator.formula" class="font-mono text-xs text-indigo-700 font-bold bg-indigo-50/70 border border-indigo-200 px-3 py-1 rounded-md inline-block">
                                        {{ indicator.formula }}
                                    </code>
                                    <span v-else class="text-gray-400 italic">-</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">15</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Satuan Pengukuran</td>
                                <td class="px-4 py-2.5 font-semibold text-gray-800">{{ indicator.satuan_pengukuran || 'Persentase (%)' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">16</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Target Capaian</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 text-xs">
                                        <span>{{ operatorLabel(indicator.target_operator) }} {{ indicator.target_capaian }} {{ indicator.satuan_pengukuran === 'Persentase' ? '%' : (indicator.satuan_pengukuran === 'Permil' ? '‰' : `(${indicator.satuan_pengukuran})`) }}</span>
                                    </span>
                                </td>
                            </tr>

                            <!-- ================= SECTION C: METODOLOGI & MANAJEMEN ================= -->
                            <tr class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white font-bold">
                                <td colspan="3" class="px-4 py-2 text-[11px] tracking-wider uppercase">
                                    C. METODOLOGI & MANAJEMEN DATA
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">17</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Periode Pengumpulan Data</td>
                                <td class="px-4 py-2.5 font-semibold text-gray-800 capitalize">{{ indicator.frekuensi || 'Bulanan' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">18</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Sumber Data</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.sumber_data || '-' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">19</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Metode Pengumpulan Data</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.metode_pengumpulan || 'Retrospektif / Konkuren' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">20</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Instrumen Pengambilan Data</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.instrumen_pengambilan || 'Formulir Pengumpul Data / SIMRS' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">21</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Besar Sampel & Cara Pengambilan</td>
                                <td class="px-4 py-2.5 text-gray-800">
                                    <span>Besar Sampel: <strong>{{ indicator.besar_sampel || 'Total Populasi' }}</strong></span>
                                    <span class="mx-2 text-gray-300">|</span>
                                    <span>Cara: <strong>{{ indicator.cara_pengambilan_sampel || 'Sampling Acak / Sensus Harian' }}</strong></span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">22</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Periode Analisis & Pelaporan</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.periode_analisis || 'Bulanan dan Triwulanan' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">23</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Penanggung Jawab Data</td>
                                <td class="px-4 py-2.5 font-semibold text-gray-800">
                                    {{ indicator.penanggung_jawab || (indicator.unit ? 'Kepala / PJ ' + indicator.unit.nama : 'Komite Mutu RS') }}
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">24</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Pengumpul Data</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.pengumpul_data || 'PIC Pengumpul Data Unit Terkait' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">25</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Validator Data</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.validator || 'PJ Mutu / Validator Unit' }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-3 py-2.5 text-center font-mono text-gray-400 font-semibold bg-gray-50/50">26</td>
                                <td class="px-4 py-2.5 font-bold text-gray-700 bg-gray-50/70 border-r border-gray-100">Rencana Publikasi Data</td>
                                <td class="px-4 py-2.5 text-gray-800">{{ indicator.publikasi_data || 'Internal Rumah Sakit (Laporan Eksekutif Mutu)' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. INSTRUMEN FORMULIR PENGUMPUL DATA (TALLY SHEET) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-blue-50/50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <NotionIcon name="table" size="18" class="text-indigo-600" />
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                                Form Pengumpul Data
                            </h2>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Pilih indikator, tahun, dan bulan untuk mengisi
                                canvas spreadsheet.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            :href="route('data-entry.index', { indicator_id: indicator.id, unit_id: indicator.unit_id })"
                            class="text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 px-3 py-1.5 rounded-lg shadow-2xs transition-colors flex items-center gap-1"
                        >
                            <span>Buka Form Pengumpul Data →</span>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
