<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tahun: Number,
    bulanList: Object,
    indicators: Array,
    edukasiPpaData: Array,
    handoverIbsData: Array,
    summaryRollup: Array,
});

const activeYear = ref(props.tahun || 2026);
const activeTab = ref('summary'); // 'summary', 'edukasi_ppa', 'handover'

const changeYear = () => {
    router.get(route('hospital.aggregation.index'), {
        tahun: activeYear.value,
    }, { preserveScroll: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Agregasi & Rekapitulasi Data Mutu RS</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Penarikan dan kompilasi data otomatis dari seluruh unit pengumpul tanpa entri ganda</p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="activeYear" @change="changeYear" class="text-xs font-semibold border-gray-200 rounded-lg px-3 py-1.5 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">Tahun {{ y }}</option>
                    </select>
                    <Link :href="route('hospital.analysis.index', { tahun: activeYear })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Lihat Run Chart RS
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Callout Agregasi Otomatis -->
            <div class="p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl text-purple-900 shadow-sm flex items-start gap-3">
                <span class="p-2 bg-purple-100 text-purple-700 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
                <div class="flex-1">
                    <p class="text-xs font-bold text-purple-950">Mekanisme Agregasi Data Bebas Entri Ganda (*Zero Duplicate Data Entry*)</p>
                    <p class="text-xs text-purple-800 mt-0.5 leading-relaxed">
                        Data pada modul Tingkat Rumah Sakit ini ditarik secara otomatis (*automated roll-up*) dari hasil pengisian <strong>Form Pengumpul Data (Tally Sheet)</strong> yang telah divalidasi di masing-masing unit kerja pelaksana (IBS, Farmasi, Rawat Inap, IPCN, dll).
                    </p>
                </div>
            </div>

            <!-- Tab Navigasi Sub-Modul Agregasi -->
            <div class="flex items-center gap-2 bg-white p-1.5 rounded-xl border border-gray-200 shadow-sm w-fit">
                <button
                    type="button"
                    @click="activeTab = 'summary'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', activeTab === 'summary' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']"
                >
                    📊 Rekapitulasi IMP-RS Utama
                </button>
                <button
                    type="button"
                    @click="activeTab = 'edukasi_ppa'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', activeTab === 'edukasi_ppa' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']"
                >
                    👨‍⚕️ Evaluasi Form Edukasi Lintas PPA
                </button>
                <button
                    type="button"
                    @click="activeTab = 'handover'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', activeTab === 'handover' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']"
                >
                    🏥 Handover & Laporan Anestesi IBS
                </button>
            </div>

            <!-- Tab 1: Rekapitulasi IMP-RS Utama -->
            <div v-show="activeTab === 'summary'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">Rekapitulasi Indikator Mutu Prioritas Rumah Sakit (IMP-RS)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Kompilasi capaian triwulanan dan tahunan sesuai 4 Area Fokus KARS</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-center w-12">Kode</th>
                            <th class="p-2 border text-left min-w-[220px]">Judul Indikator Mutu</th>
                            <th class="p-2 border text-left">Area Fokus KARS</th>
                            <th class="p-2 border text-center w-16">Standar</th>
                            <th class="p-2 border text-left">Unit Pengumpul Lapangan</th>
                            <th class="p-2 border text-center w-20">TW I (%)</th>
                            <th class="p-2 border text-center w-20">TW II (%)</th>
                            <th class="p-2 border text-center w-20">TW III (%)</th>
                            <th class="p-2 border text-center w-24 bg-purple-50 font-bold">Total Tahun</th>
                            <th class="p-2 border text-center w-28">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="item in summaryRollup" :key="item.kode" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-purple-700">{{ item.kode }}</td>
                            <td class="p-2 border">
                                <p class="font-bold text-gray-900">{{ item.judul }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ item.status_agregasi }}</p>
                            </td>
                            <td class="p-2 border">
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[11px] font-semibold">
                                    {{ item.area_kars }}
                                </span>
                            </td>
                            <td class="p-2 border text-center font-bold text-gray-800">
                                {{ item.target_op }} {{ item.target }}{{ item.satuan }}
                            </td>
                            <td class="p-2 border text-gray-700">{{ item.unit_pengumpul }}</td>
                            <td class="p-2 border text-center font-medium">{{ item.capaian_tw1 }}%</td>
                            <td class="p-2 border text-center font-medium">{{ item.capaian_tw2 }}%</td>
                            <td class="p-2 border text-center font-medium">{{ item.capaian_tw3 }}%</td>
                            <td class="p-2 border text-center font-bold bg-purple-50/50 text-purple-900">
                                {{ item.capaian_tahun }}%
                            </td>
                            <td class="p-2 border text-center">
                                <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold', item.status === 'Tercapai' ? 'bg-emerald-100 text-emerald-800' : (item.status === 'Mendekati Target' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')]">
                                    {{ item.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Evaluasi Form Edukasi Lintas PPA -->
            <div v-show="activeTab === 'edukasi_ppa'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">Hasil Evaluasi Kelengkapan Form Edukasi oleh Tim Review Rekam Medis</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Data audit triwulanan kelengkapan edukasi pasien lintas Profesional Pemberi Asuhan (PPA)</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-center w-10">No</th>
                            <th class="p-2 border text-left min-w-[120px]">Bulan Pelaksanaan</th>
                            <th class="p-2 border text-center bg-purple-50 font-bold">Keseluruhan RS (%)</th>
                            <th class="p-2 border text-center">Dokter (DPJP) (%)</th>
                            <th class="p-2 border text-center">Perawat (%)</th>
                            <th class="p-2 border text-center">Apoteker (Farmasi) (%)</th>
                            <th class="p-2 border text-center">Dietisen (Gizi) (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(row, idx) in edukasiPpaData" :key="row.bulan" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border font-bold text-gray-800">{{ row.bulan }}</td>
                            <td class="p-2 border text-center font-bold bg-purple-50/50 text-purple-900">
                                {{ row.keseluruhan > 0 ? row.keseluruhan + '%' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-medium" :class="row.dokter < 70 && row.dokter > 0 ? 'text-rose-600 font-bold' : ''">
                                {{ row.dokter > 0 ? row.dokter + '%' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-medium text-emerald-700">
                                {{ row.perawat > 0 ? row.perawat + '%' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-medium">
                                {{ row.apoteker > 0 ? row.apoteker + '%' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-medium">
                                {{ row.dietisen > 0 ? row.dietisen + '%' : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 3: Handover & Laporan Anestesi IBS -->
            <div v-show="activeTab === 'handover'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">Rekapitulasi Agregasi Handover & Laporan Anestesi IBS</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pemantauan kontinuitas dokumentasi kamar bedah dan ruang pemulihan (RR)</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-center w-10">No</th>
                            <th class="p-2 border text-left min-w-[120px]">Bulan</th>
                            <th class="p-2 border text-center bg-blue-50 font-bold min-w-[200px]">IMP-RS 3: Laporan Anestesi (%)</th>
                            <th class="p-2 border text-center bg-indigo-50 font-bold min-w-[200px]">IMP-RS 6: Dokumen Handover IBS (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(row, idx) in handoverIbsData" :key="row.bulan" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border font-bold text-gray-800">{{ row.bulan }}</td>
                            <td class="p-2 border text-center font-bold text-blue-800 bg-blue-50/30">
                                {{ row.anestesi > 0 ? row.anestesi + '%' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-bold text-indigo-800 bg-indigo-50/30">
                                {{ row.handover > 0 ? row.handover + '%' : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
