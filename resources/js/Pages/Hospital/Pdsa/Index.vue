<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tahun: Number,
    activeTab: String,
    rtlRecords: Array,
    pdsaCycles: Array,
    dampakSekunder: Object,
});

const activeYear = ref(props.tahun || 2026);
const currentTab = ref(props.activeTab || 'rtl'); // 'rtl', 'pdsa', 'dampak'

const switchTab = (tab) => {
    currentTab.value = tab;
};

const changeYear = () => {
    router.get(route('hospital.pdsa.index'), {
        tahun: activeYear.value,
        tab: currentTab.value,
    }, { preserveScroll: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Evaluasi RTL & PDSA Tingkat Rumah Sakit</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Rencana Tindak Lanjut 5M, Siklus Perbaikan Mutu Berkelanjutan, dan Evaluasi Dampak Sekunder</p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="activeYear" @change="changeYear" class="text-xs font-semibold border-gray-200 rounded-lg px-3 py-1.5 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">Tahun {{ y }}</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Tab Navigasi -->
            <div class="flex items-center gap-2 bg-white p-1.5 rounded-xl border border-gray-200 shadow-sm w-fit">
                <button
                    type="button"
                    @click="switchTab('rtl')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', currentTab === 'rtl' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']"
                >
                    📋 Rencana Tindak Lanjut (RTL 5M)
                </button>
                <button
                    type="button"
                    @click="switchTab('pdsa')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', currentTab === 'pdsa' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']"
                >
                    🔄 Siklus PDSA (Plan - Do - Study - Act)
                </button>
                <button
                    type="button"
                    @click="switchTab('dampak')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', currentTab === 'dampak' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']"
                >
                    📈 Evaluasi Dampak Sekunder (*Secondary Impact*)
                </button>
            </div>

            <!-- Tab 1: RTL IMP-RS (Rencana Tindak Lanjut 5M) -->
            <div v-show="currentTab === 'rtl'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">Rencana Tindak Lanjut Indikator Mutu Prioritas RS (RTL IMP)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Penetapan akar masalah metode 5M (Man, Machine, Method, Material, Milieu) dan penanggung jawab perbaikan</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-center w-10">No</th>
                            <th class="p-2 border text-left w-24">Bulan</th>
                            <th class="p-2 border text-left min-w-[200px]">Indikator Mutu</th>
                            <th class="p-2 border text-left min-w-[180px]">Temuan Masalah</th>
                            <th class="p-2 border text-left min-w-[220px]">Akar Masalah (5M / Fishbone)</th>
                            <th class="p-2 border text-left min-w-[240px]">Rencana Tindak Lanjut (RTL)</th>
                            <th class="p-2 border text-left w-28">PJ RTL</th>
                            <th class="p-2 border text-center w-24">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(rtl, idx) in rtlRecords" :key="rtl.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border font-bold text-gray-800">{{ rtl.bulan }}</td>
                            <td class="p-2 border">
                                <span class="px-1.5 py-0.5 bg-purple-50 text-purple-700 rounded text-[10px] font-bold">{{ rtl.kode }}</span>
                                <p class="font-bold text-gray-900 mt-0.5">{{ rtl.judul }}</p>
                            </td>
                            <td class="p-2 border text-rose-700 font-medium">{{ rtl.masalah }}</td>
                            <td class="p-2 border text-gray-700 leading-relaxed">{{ rtl.penyebab_5m }}</td>
                            <td class="p-2 border text-indigo-950 font-medium leading-relaxed">{{ rtl.rtl }}</td>
                            <td class="p-2 border font-bold text-gray-800">{{ rtl.pj_rtl }}</td>
                            <td class="p-2 border text-center">
                                <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold', rtl.status === 'Selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                    {{ rtl.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Siklus PDSA (Plan - Do - Study - Act) -->
            <div v-show="currentTab === 'pdsa'" class="space-y-6">
                <div v-for="cycle in pdsaCycles" :key="cycle.id" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                        <div>
                            <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">{{ cycle.unit }}</span>
                            <h3 class="text-base font-bold text-gray-900 mt-0.5">{{ cycle.indikator }}</h3>
                        </div>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 font-bold text-xs rounded-full shadow-sm">
                            {{ cycle.status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- PLAN -->
                        <div class="p-4 bg-blue-50/50 border border-blue-200 rounded-xl space-y-1.5">
                            <span class="px-2 py-0.5 bg-blue-600 text-white font-bold text-[11px] rounded">1. PLAN</span>
                            <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed font-medium mt-2">{{ cycle.plan }}</p>
                        </div>

                        <!-- DO -->
                        <div class="p-4 bg-amber-50/50 border border-amber-200 rounded-xl space-y-1.5">
                            <span class="px-2 py-0.5 bg-amber-600 text-white font-bold text-[11px] rounded">2. DO</span>
                            <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed font-medium mt-2">{{ cycle.do }}</p>
                        </div>

                        <!-- STUDY -->
                        <div class="p-4 bg-emerald-50/50 border border-emerald-200 rounded-xl space-y-1.5">
                            <span class="px-2 py-0.5 bg-emerald-600 text-white font-bold text-[11px] rounded">3. STUDY</span>
                            <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed font-medium mt-2">{{ cycle.study }}</p>
                        </div>

                        <!-- ACT -->
                        <div class="p-4 bg-purple-50/50 border border-purple-200 rounded-xl space-y-1.5">
                            <span class="px-2 py-0.5 bg-purple-600 text-white font-bold text-[11px] rounded">4. ACT</span>
                            <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed font-medium mt-2">{{ cycle.act }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Evaluasi Dampak Sekunder (Secondary Impact) -->
            <div v-show="currentTab === 'dampak'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Evaluasi Dampak Sekunder Intervensi Mutu (*Secondary Impact*)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Pengukuran efek samping positif dari perbaikan mutu terhadap efisiensi biaya, waktu, dan kepuasan staf</p>
                    <p class="text-xs text-purple-700 font-bold mt-1">Studi Kasus: {{ dampakSekunder.indikator_terkait }}</p>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-center w-10">No</th>
                            <th class="p-2 border text-left min-w-[240px]">Parameter Dampak Sekunder</th>
                            <th class="p-2 border text-left min-w-[200px] bg-rose-50 font-bold">Sebelum Intervensi</th>
                            <th class="p-2 border text-left min-w-[200px] bg-emerald-50 font-bold">Setelah Intervensi (EMR Tablet)</th>
                            <th class="p-2 border text-left min-w-[240px] bg-purple-50 font-bold">Efisiensi & Nilai Tambah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="item in dampakSekunder.items" :key="item.no" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ item.no }}</td>
                            <td class="p-2 border font-bold text-gray-900">{{ item.parameter }}</td>
                            <td class="p-2 border text-rose-800 bg-rose-50/30 font-medium">{{ item.sebelum }}</td>
                            <td class="p-2 border text-emerald-800 bg-emerald-50/30 font-bold">{{ item.setelah }}</td>
                            <td class="p-2 border text-purple-900 bg-purple-50/30 font-bold">{{ item.dampak }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
