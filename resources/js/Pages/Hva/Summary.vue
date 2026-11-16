<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    categoryComparison: Array,
    top15: Array,
    threatBands: Object,
    units: Array,
    selectedUnit: Object,
    filters: Object,
});

const tahun = ref(props.filters.tahun || 2026);
const scope = ref(props.filters.scope || 'rs');
const unitId = ref(props.filters.unit_id || '');

const applyFilter = (newScope = null) => {
    if (newScope !== null) scope.value = newScope;
    router.get(route('hva.summary'), {
        tahun: tahun.value,
        scope: scope.value,
        unit_id: scope.value === 'unit' ? (unitId.value || null) : null,
    }, { preserveState: true, preserveScroll: true });
};

// ─── 1. Radar Chart: 4 Categories Comparison ──────────────────────────────
const radarChartOptions = computed(() => ({
    chart: {
        type: 'radar',
        toolbar: { show: false },
        dropShadow: { enabled: true, blur: 2, left: 1, top: 1 },
    },
    labels: props.categoryComparison.map(c => c.label),
    stroke: { width: 2, curve: 'smooth' },
    fill: { opacity: 0.25 },
    colors: ['#4f46e5'],
    markers: { size: 5, hover: { size: 8 } },
    yaxis: {
        show: true,
        labels: { formatter: (val) => `${val.toFixed(1)}%` },
    },
    xaxis: {
        labels: {
            style: {
                fontSize: '11px',
                fontWeight: 600,
            },
        },
    },
    tooltip: {
        y: { formatter: (val) => `${val}% Ancaman Relatif` },
    },
}));

const radarChartSeries = computed(() => [
    {
        name: 'Rata-rata Ancaman Relatif (%)',
        data: props.categoryComparison.map(c => c.avg_risk),
    },
]);

// ─── 2. Top 15 Hazards Horizontal Bar Chart ──────────────────────────────
const barChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '65%',
            distributed: true,
        },
    },
    colors: [
        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
        '#10b981', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1',
        '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e'
    ],
    dataLabels: {
        enabled: true,
        formatter: (val) => `${Number(val).toFixed(2)}%`,
        style: { fontSize: '11px', fontWeight: 'bold' },
    },
    xaxis: {
        categories: props.top15.map(t => t.nama_kejadian),
        labels: {
            formatter: (val) => `${val}%`,
            style: { fontSize: '11px' },
        },
    },
    yaxis: {
        labels: {
            maxWidth: 260,
            style: { fontSize: '12px', fontWeight: 600 },
        },
    },
    legend: { show: false },
    tooltip: {
        y: {
            formatter: (val, { dataPointIndex }) => {
                const item = props.top15[dataPointIndex];
                return `${val}% (Keparahan: ${item?.skor_keparahan}/18, Probabilitas: ${item?.probabilitas}/3)`;
            },
        },
    },
}));

const barChartSeries = computed(() => [
    {
        name: 'Skor Ancaman Relatif',
        data: props.top15.map(t => Number(t.skor_risiko_relatif)),
    },
]);

// ─── 3. Severity Distribution Donut Chart ────────────────────────────────
const donutChartOptions = computed(() => ({
    chart: { type: 'donut' },
    labels: [
        'Tinggi (≥ 20%)',
        'Sedang (5% - 19.9%)',
        'Rendah (0.01% - 4.9%)',
        'Tidak Berisiko / N/A (0%)'
    ],
    colors: ['#ef4444', '#f59e0b', '#10b981', '#9ca3af'],
    legend: { position: 'bottom' },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Bahaya',
                        formatter: () => {
                            const b = props.threatBands;
                            return (b.tinggi + b.sedang + b.rendah + b.na);
                        },
                    },
                },
            },
        },
    },
}));

const donutChartSeries = computed(() => [
    props.threatBands.tinggi,
    props.threatBands.sedang,
    props.threatBands.rendah,
    props.threatBands.na,
]);

// Metrics
const highestThreatItem = computed(() => {
    return props.top15.length > 0 ? props.top15[0] : null;
});

const totalActiveThreats = computed(() => {
    const b = props.threatBands;
    return b.tinggi + b.sedang + b.rendah;
});
</script>

<template>
    <AppLayout title="Summary & Grafik Analisis HVA">
        <div class="space-y-6 pb-12">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <Link
                                :href="route('hva.index', { scope, unit_id: unitId || null, tahun })"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-300 hover:text-white transition-colors"
                            >
                                &larr; Kembali ke Dashboard HVA
                            </Link>
                            <span class="text-slate-600">•</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                Visualisasi Analitik & Perbandingan
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            Summary & Grafik Analisis HVA
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-2xl">
                            Pemetaan visual profil bahaya kedaruratan, distribusi keparahan risiko, serta perbandingan rata-rata ancaman antar 4 kategori utama.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('hva.rtl', { scope, unit_id: unitId || null, tahun })"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-md transition-all hover:scale-102"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Rencana Tindak Lanjut (RTL)
                        </Link>
                    </div>
                </div>

                <!-- Scope Switcher -->
                <div class="mt-6 pt-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="inline-flex p-1 bg-slate-800/80 rounded-xl border border-slate-700">
                        <button
                            type="button"
                            @click="applyFilter('rs')"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                scope === 'rs' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            Tingkat Rumah Sakit (RSYS)
                        </button>
                        <button
                            type="button"
                            @click="applyFilter('unit')"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                scope === 'unit' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            Tingkat Unit Kerja
                        </button>
                    </div>

                    <div v-if="scope === 'unit'" class="flex items-center gap-2">
                        <label class="text-xs text-slate-300 font-medium">Pilih Unit:</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter('unit')"
                            class="bg-slate-800 text-white text-xs rounded-lg px-3 py-1.5 border border-slate-700"
                        >
                            <option v-for="u in units" :key="u.id" :value="u.id">
                                [{{ u.kode }}] {{ u.nama }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold block">Bahaya Tertinggi (Top Threat)</span>
                    <h3 class="text-lg font-extrabold text-red-600 dark:text-red-400 mt-1 truncate">
                        {{ highestThreatItem ? highestThreatItem.nama_kejadian : '-' }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Skor Ancaman: <span class="font-bold text-gray-700 dark:text-gray-300">{{ highestThreatItem ? highestThreatItem.skor_risiko_relatif : 0 }}%</span>
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold block">Bahaya Aktif Teridentifikasi</span>
                    <h3 class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ totalActiveThreats }} <span class="text-sm font-normal text-gray-400">Kejadian</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Memiliki Skor Ancaman Relatif > 0%
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold block">Distribusi Kedaruratan Tinggi</span>
                    <h3 class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">
                        {{ threatBands.tinggi }} <span class="text-sm font-normal text-gray-400">Prioritas Tinggi (≥ 20%)</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Wajib memiliki SOP Tanggap Darurat & Drill
                    </p>
                </div>
            </div>

            <!-- Charts Section: Radar & Donut -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Radar Chart: 4 Categories Comparison -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-xs flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Perbandingan Rata-rata 4 Kategori Bahaya
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Visualisasi radar rata-rata persentase ancaman relatif untuk tiap kategori.
                        </p>
                    </div>

                    <div class="py-4">
                        <VueApexCharts
                            type="radar"
                            height="320"
                            :options="radarChartOptions"
                            :series="radarChartSeries"
                        />
                    </div>

                    <!-- Category Breakdown Table -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 grid grid-cols-4 text-center text-xs">
                        <div v-for="c in categoryComparison" :key="c.key">
                            <span class="text-[11px] text-gray-400 block truncate">{{ c.label }}</span>
                            <span class="font-extrabold text-indigo-600 dark:text-indigo-400 text-sm block mt-0.5">{{ c.avg_risk }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Donut Chart: Threat Severity Bands -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-xs flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Distribusi Tingkat Bahaya (Severity Bands)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Sebaran seluruh kejadian bahaya berdasarkan interval skor ancaman relatif.
                        </p>
                    </div>

                    <div class="py-4 flex justify-center">
                        <VueApexCharts
                            type="donut"
                            height="320"
                            :options="donutChartOptions"
                            :series="donutChartSeries"
                        />
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 grid grid-cols-4 text-center text-xs">
                        <div>
                            <span class="text-[11px] text-red-500 font-bold block">Tinggi</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ threatBands.tinggi }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] text-amber-500 font-bold block">Sedang</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ threatBands.sedang }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] text-emerald-500 font-bold block">Rendah</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ threatBands.rendah }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] text-gray-400 font-bold block">N/A</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ threatBands.na }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Bar Chart: Top 15 Hazards -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-xs">
                <div class="mb-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Peringkat 15 Bahaya Tertinggi (Top 15 Relative Threats)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Urutan bahaya paling signifikan yang memerlukan alokasi anggaran, pelatihan staf, dan rencana mitigasi bencana.
                    </p>
                </div>

                <div class="py-2">
                    <VueApexCharts
                        type="bar"
                        height="480"
                        :options="barChartOptions"
                        :series="barChartSeries"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

