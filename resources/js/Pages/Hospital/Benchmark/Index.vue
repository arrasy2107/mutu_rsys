<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tab: String,
    tahun: Number,
    indicators: Array,
    activeIndicator: Object,
    internalComparison: Array,
    externalBenchmarks: Array,
});

const currentTab = ref(props.tab || 'internal');
const selectedIndicatorId = ref(props.activeIndicator?.id || '');

const switchTab = (t) => {
    currentTab.value = t;
    router.get(route('hospital.benchmark.index'), {
        tab: t,
        tahun: props.tahun,
        indicator_id: selectedIndicatorId.value,
    }, { preserveState: true });
};

const changeIndicator = () => {
    router.get(route('hospital.benchmark.index'), {
        tab: currentTab.value,
        tahun: props.tahun,
        indicator_id: selectedIndicatorId.value,
    }, { preserveState: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Benchmark Mutu Tingkat RS" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold uppercase tracking-wider backdrop-blur-sm mb-2">
                            <span>⚖️ Butir B.6 Roadmap Mutu</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Tingkat Rumah Sakit</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">Benchmark Mutu Rumah Sakit</h1>
                        <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                            Komparasi capaian mutu antar unit kerja (Internal Benchmarking) dan perbandingan kinerja rumah sakit terhadap Standar Nasional Kemenkes / KARS (External Benchmarking).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab Switcher (Internal vs Eksternal) -->
            <div class="bg-white rounded-xl p-2 border border-gray-200 shadow-sm flex gap-2">
                <button
                    @click="switchTab('internal')"
                    :class="[
                        'flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        currentTab === 'internal' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>🏢 1. Benchmark Internal Antar-Unit</span>
                </button>
                <button
                    @click="switchTab('eksternal')"
                    :class="[
                        'flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        currentTab === 'eksternal' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>🇮🇩 2. Benchmark Eksternal Standar Nasional</span>
                </button>
            </div>

            <!-- TAB 1: INTERNAL BENCHMARKING -->
            <div v-if="currentTab === 'internal'" class="space-y-6">
                <!-- Indicator Picker -->
                <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Pilih Indikator untuk Komparasi Unit
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Indikator yang diukur secara bersamaan oleh beberapa unit kerja</p>
                    </div>

                    <div class="w-full sm:w-96">
                        <select
                            v-model="selectedIndicatorId"
                            @change="changeIndicator"
                            class="w-full text-xs font-semibold rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option v-for="ind in indicators" :key="ind.id" :value="ind.id">
                                [{{ ind.kode }}] {{ ind.judul }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Comparison Cards & Horizontal Bar Chart -->
                <div class="bg-white rounded-xl p-6 border border-gray-200/80 shadow-sm space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-blue-100 text-blue-800">
                                {{ activeIndicator?.kode }}
                            </span>
                            <h2 class="text-base font-bold text-gray-900 mt-2">{{ activeIndicator?.judul }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Target Standar: <strong class="text-blue-700">{{ activeIndicator?.target_operator || '>=' }} {{ activeIndicator?.target_capaian }}{{ activeIndicator?.satuan_pengukuran || '%' }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Bars Comparison -->
                    <div class="space-y-4">
                        <div v-for="u in internalComparison" :key="u.unit_id" class="space-y-1.5">
                            <div class="flex justify-between items-center text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-800">{{ u.unit_nama }}</span>
                                    <span class="text-[10px] text-gray-400 font-mono">({{ u.unit_kode }})</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-400 text-[11px] font-mono">{{ u.total_patuh }}/{{ u.total_sampel }} sampel</span>
                                    <span :class="['font-bold text-sm', u.capaian >= u.target ? 'text-emerald-600' : 'text-red-600']">
                                        {{ u.capaian }}%
                                    </span>
                                </div>
                            </div>
                            <!-- Bar Visual -->
                            <div class="w-full bg-gray-100 rounded-full h-3 relative overflow-hidden">
                                <div
                                    :class="['h-full rounded-full transition-all duration-500', u.capaian >= u.target ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-gradient-to-r from-red-500 to-rose-400']"
                                    :style="{ width: Math.min(100, u.capaian) + '%' }"
                                ></div>
                                <!-- Target Marker Line -->
                                <div
                                    class="absolute top-0 bottom-0 w-0.5 bg-red-600 z-10"
                                    :style="{ left: u.target + '%' }"
                                    title="Garis Target"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: EXTERNAL BENCHMARKING (STANDAR NASIONAL) -->
            <div v-if="currentTab === 'eksternal'" class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/70">
                        <div>
                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                                Komparasi Capaian RS vs Standar Nasional Kemenkes (INM)
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Pemenuhan standar pelayanan nasional dan akreditasi KARS</p>
                        </div>
                        <div class="text-xs text-gray-500 font-mono">
                            Tahun Evaluasi: {{ tahun }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b">
                                <tr>
                                    <th class="py-3 px-4 w-16">Kode</th>
                                    <th class="py-3 px-4 min-w-[240px]">Indikator Mutu Nasional</th>
                                    <th class="py-3 px-4 text-center">Standar Nasional</th>
                                    <th class="py-3 px-4 text-center">Capaian RSYS</th>
                                    <th class="py-3 px-4 text-center">Deviasi (Gap)</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 min-w-[200px]">Keterangan Evaluasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="b in externalBenchmarks" :key="b.kode" class="hover:bg-blue-50/40">
                                    <td class="py-3 px-4 font-bold text-blue-700 font-mono">{{ b.kode }}</td>
                                    <td class="py-3 px-4 font-semibold text-gray-900">{{ b.judul }}</td>
                                    <td class="py-3 px-4 text-center font-bold text-gray-600">{{ b.standar_nasional }}%</td>
                                    <td class="py-3 px-4 text-center font-extrabold text-blue-700">{{ b.capaian_rs }}%</td>
                                    <td class="py-3 px-4 text-center font-bold font-mono">
                                        <span :class="b.gap >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                            {{ b.gap > 0 ? '+' : '' }}{{ b.gap }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span v-if="b.status === 'melampaui'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            Melampaui Standar
                                        </span>
                                        <span v-else-if="b.status === 'mendekati'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Mendekati Standar
                                        </span>
                                        <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                            Perlu Intervensi
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-[11px] leading-relaxed">
                                        {{ b.keterangan }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
