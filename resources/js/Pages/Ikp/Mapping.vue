<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    total: Number,
    tipeData: Array,
    jenisData: Array,
    gradingData: Array,
    unitData: Array,
    tipeGradingMatrix: Array,
    tipeJenisMatrix: Array,
    penyelesaianData: Object,
    units: Array,
    filters: Object,
});

const tahun = ref(props.filters.tahun || new Date().getFullYear());
const periodeType = ref(props.filters.periode_type || 'tahunan');
const periodeValue = ref(props.filters.periode_value || '');
const unitId = ref(props.filters.unit_id || '');

const applyFilter = () => {
    router.get(route('ikp.mapping'), {
        tahun: tahun.value,
        periode_type: periodeType.value,
        periode_value: periodeValue.value || null,
        unit_id: unitId.value || null,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilter = () => {
    periodeType.value = 'tahunan';
    periodeValue.value = '';
    unitId.value = '';
    applyFilter();
};

// ─── 1. Tipe Insiden Chart (Horizontal Bar) ──────────────────────────────────
const tipeChartOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '65%',
            distributed: true,
        },
    },
    colors: ['#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#84cc16', '#eab308', '#f97316', '#ef4444', '#8b5cf6'],
    dataLabels: { enabled: true, formatter: (val) => `${val} lap` },
    xaxis: {
        categories: props.tipeData.map((t) => t.name),
        labels: { style: { fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            maxWidth: 240,
            style: { fontSize: '11px', fontWeight: 600 },
        },
    },
    legend: { show: false },
    tooltip: {
        y: { formatter: (val) => `${val} insiden` },
    },
}));

const tipeChartSeries = computed(() => [
    {
        name: 'Jumlah Insiden',
        data: props.tipeData.map((t) => t.count),
    },
]);

// ─── 2. Jenis Insiden Chart (Donut) ──────────────────────────────────────────
const jenisChartOptions = computed(() => ({
    chart: { type: 'donut' },
    labels: props.jenisData.map((j) => j.name),
    colors: ['#881337', '#e11d48', '#d97706', '#059669', '#0284c7'],
    legend: { position: 'bottom', fontSize: '11px' },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Insiden',
                        formatter: () => props.total,
                    },
                },
            },
        },
    },
    dataLabels: { enabled: true },
}));

const jenisChartSeries = computed(() => props.jenisData.map((j) => j.count));

// ─── 3. Grading Risiko Chart (Donut) ─────────────────────────────────────────
const gradingChartOptions = computed(() => ({
    chart: { type: 'donut' },
    labels: ['Merah (Extreme)', 'Kuning (High)', 'Hijau (Moderate)', 'Biru (Low)'],
    colors: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6'],
    legend: { position: 'bottom', fontSize: '11px' },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Grading Terinput',
                        formatter: () => props.total,
                    },
                },
            },
        },
    },
    dataLabels: { enabled: true },
}));

const gradingChartSeries = computed(() => props.gradingData.map((g) => g.count));

// ─── 4. Unit Pelapor Chart (Top 10 Units Horizontal Bar) ──────────────────────
const topUnits = computed(() => props.unitData.slice(0, 10));

const unitChartOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '60%',
        },
    },
    colors: ['#6366f1'],
    dataLabels: { enabled: true },
    xaxis: {
        categories: topUnits.value.map((u) => u.name),
        labels: { style: { fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            maxWidth: 200,
            style: { fontSize: '11px', fontWeight: 600 },
        },
    },
    tooltip: {
        y: { formatter: (val) => `${val} laporan` },
    },
}));

const unitChartSeries = computed(() => [
    {
        name: 'Jumlah Insiden',
        data: topUnits.value.map((u) => u.count),
    },
]);

// ─── 5. Tipe Insiden x Grading Stacked Bar Chart ──────────────────────────────
const tipeGradingChartOptions = computed(() => ({
    chart: { type: 'bar', stacked: true, toolbar: { show: false } },
    plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' } },
    xaxis: {
        categories: props.tipeGradingMatrix.map((m) => m.tipe),
        labels: {
            rotate: -45,
            trim: true,
            maxHeight: 120,
            style: { fontSize: '10px' },
        },
    },
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
    legend: { position: 'top', fontSize: '11px' },
    dataLabels: { enabled: false },
}));

const tipeGradingChartSeries = computed(() => [
    { name: 'Biru (Low)', data: props.tipeGradingMatrix.map((m) => m.biru) },
    { name: 'Hijau (Moderate)', data: props.tipeGradingMatrix.map((m) => m.hijau) },
    { name: 'Kuning (High)', data: props.tipeGradingMatrix.map((m) => m.kuning) },
    { name: 'Merah (Extreme)', data: props.tipeGradingMatrix.map((m) => m.merah) },
]);

// ─── 6. Tipe Insiden x Jenis Insiden Stacked Bar Chart ───────────────────────
const tipeJenisChartOptions = computed(() => ({
    chart: { type: 'bar', stacked: true, toolbar: { show: false } },
    plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' } },
    xaxis: {
        categories: props.tipeJenisMatrix.map((m) => m.tipe),
        labels: {
            rotate: -45,
            trim: true,
            maxHeight: 120,
            style: { fontSize: '10px' },
        },
    },
    colors: ['#881337', '#e11d48', '#d97706', '#059669', '#0284c7'],
    legend: { position: 'top', fontSize: '11px' },
    dataLabels: { enabled: false },
}));

const tipeJenisChartSeries = computed(() => [
    { name: 'Sentinel', data: props.tipeJenisMatrix.map((m) => m.sentinel) },
    { name: 'KTD', data: props.tipeJenisMatrix.map((m) => m.ktd) },
    { name: 'KTC', data: props.tipeJenisMatrix.map((m) => m.ktc) },
    { name: 'KNC', data: props.tipeJenisMatrix.map((m) => m.knc) },
    { name: 'KPC', data: props.tipeJenisMatrix.map((m) => m.kpc) },
]);

// ─── 7. Penyelesaian & Kepatuhan Waktu Chart ─────────────────────────────────
const penyelesaianChartOptions = computed(() => ({
    chart: { type: 'pie' },
    labels: ['Investigasi Sederhana', 'RCA / AAM', 'Belum Ditentukan'],
    colors: ['#0284c7', '#e11d48', '#9ca3af'],
    legend: { position: 'bottom', fontSize: '11px' },
}));

const penyelesaianChartSeries = computed(() => [
    props.penyelesaianData.tipe_penyelesaian.investigasi_sederhana,
    props.penyelesaianData.tipe_penyelesaian.rca,
    props.penyelesaianData.tipe_penyelesaian.belum_ditetapkan,
]);

const kepatuhanChartOptions = computed(() => ({
    chart: { type: 'donut' },
    labels: ['Sesuai Standar Waktu', 'Melewati Batas Waktu', 'Dalam Proses'],
    colors: ['#10b981', '#ef4444', '#f59e0b'],
    legend: { position: 'bottom', fontSize: '11px' },
}));

const kepatuhanChartSeries = computed(() => [
    props.penyelesaianData.kriteria_waktu.sesuai,
    props.penyelesaianData.kriteria_waktu.tidak_sesuai,
    props.penyelesaianData.kriteria_waktu.dalam_proses,
]);
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-sky-500 to-blue-600 rounded-full inline-block"></span>
                        Pemetaan & Visualisasi Grafik Insiden Keselamatan Pasien (IKP)
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Analitik 7 matriks pemetaan otomatis sesuai Master Roadmap Modul II.B
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('ikp.index')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        &larr; Daftar Data Laporan
                    </Link>
                    <Link
                        :href="route('ikp.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-500/25 transition"
                    >
                        + Laporkan Insiden Baru
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- ─── FILTER WAKTU DINAMIS (Bulanan, Triwulan, Semester, Tahunan) ── -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tahun Analisis</label>
                        <input
                            type="number"
                            v-model="tahun"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Rentang Waktu</label>
                        <select
                            v-model="periodeType"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500 font-semibold"
                        >
                            <option value="tahunan">1 Tahun Penuh (Tahunan)</option>
                            <option value="semester">Per Semester (6 Bulan)</option>
                            <option value="triwulan">Per Triwulan (3 Bulan)</option>
                            <option value="bulanan">Per Bulan</option>
                        </select>
                    </div>

                    <div v-if="periodeType !== 'tahunan'">
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Pilihan Periode</label>
                        
                        <!-- Semester -->
                        <select
                            v-if="periodeType === 'semester'"
                            v-model="periodeValue"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500"
                        >
                            <option value="">Semua Semester</option>
                            <option value="1">Semester 1 (Januari - Juni)</option>
                            <option value="2">Semester 2 (Juli - Desember)</option>
                        </select>

                        <!-- Triwulan -->
                        <select
                            v-else-if="periodeType === 'triwulan'"
                            v-model="periodeValue"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500"
                        >
                            <option value="">Semua Triwulan</option>
                            <option value="1">Triwulan 1 (Jan - Mar)</option>
                            <option value="2">Triwulan 2 (Apr - Jun)</option>
                            <option value="3">Triwulan 3 (Jul - Sep)</option>
                            <option value="4">Triwulan 4 (Okt - Des)</option>
                        </select>

                        <!-- Bulanan -->
                        <select
                            v-else-if="periodeType === 'bulanan'"
                            v-model="periodeValue"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500"
                        >
                            <option value="">Semua Bulan</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Filter Unit</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500"
                        >
                            <option value="">Semua Unit (Seluruh RS)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs">
                    <span class="text-gray-500">
                        Total Data Terpetakan: <strong class="text-gray-900">{{ total }} insiden</strong>
                    </span>
                    <button
                        @click="resetFilter"
                        type="button"
                        class="px-3 py-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                    >
                        Reset Rentang
                    </button>
                </div>
            </div>

            <!-- ─── ROW 1: TIPE INSIDEN & JENIS INSIDEN ──────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Tipe Insiden -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">1</span>
                                Pemetaan Tipe Insiden
                            </h2>
                            <p class="text-[11px] text-gray-400">Distribusi kategori insiden keselamatan pasien</p>
                        </div>
                    </div>
                    <div v-if="total === 0" class="h-64 flex items-center justify-center text-xs text-gray-400 italic">
                        Belum ada data insiden untuk periode ini.
                    </div>
                    <div v-else class="h-72">
                        <VueApexCharts type="bar" height="100%" :options="tipeChartOptions" :series="tipeChartSeries" />
                    </div>
                </div>

                <!-- 2. Jenis Insiden -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-md bg-rose-100 text-rose-700 flex items-center justify-center text-xs font-bold">2</span>
                                Pemetaan Jenis Insiden
                            </h2>
                            <p class="text-[11px] text-gray-400">Sentinel, KTD, KTC, KNC, KPC</p>
                        </div>
                    </div>
                    <div v-if="total === 0" class="h-64 flex items-center justify-center text-xs text-gray-400 italic">
                        Belum ada data insiden untuk periode ini.
                    </div>
                    <div v-else class="h-72">
                        <VueApexCharts type="donut" height="100%" :options="jenisChartOptions" :series="jenisChartSeries" />
                    </div>
                </div>
            </div>

            <!-- ─── ROW 2: GRADING RISIKO & UNIT PELAPOR ─────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 3. Grading Risiko -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-md bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-bold">3</span>
                                Pemetaan Grading Risiko (Matrix)
                            </h2>
                            <p class="text-[11px] text-gray-400">Merah (Extreme), Kuning (High), Hijau (Moderate), Biru (Low)</p>
                        </div>
                    </div>
                    <div v-if="total === 0" class="h-64 flex items-center justify-center text-xs text-gray-400 italic">
                        Belum ada data insiden untuk periode ini.
                    </div>
                    <div v-else class="h-72">
                        <VueApexCharts type="donut" height="100%" :options="gradingChartOptions" :series="gradingChartSeries" />
                    </div>
                </div>

                <!-- 4. Unit Pelapor -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">4</span>
                                Pemetaan Unit Pelapor Terbanyak (Top Units)
                            </h2>
                            <p class="text-[11px] text-gray-400">Unit paling aktif dalam pelaporan budaya keselamatan pasien</p>
                        </div>
                    </div>
                    <div v-if="total === 0" class="h-64 flex items-center justify-center text-xs text-gray-400 italic">
                        Belum ada data insiden untuk periode ini.
                    </div>
                    <div v-else class="h-72">
                        <VueApexCharts type="bar" height="100%" :options="unitChartOptions" :series="unitChartSeries" />
                    </div>
                </div>
            </div>

            <!-- ─── ROW 3: MATRIKS TIPE INSIDEN DAN GRADING ──────────────────── -->
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                    <div>
                        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">5</span>
                            Pemetaan Tipe Insiden dan Grading Risiko (Kombinasi)
                        </h2>
                        <p class="text-[11px] text-gray-400">Distribusi tingkat keparahan risiko pada setiap tipe peristiwa insiden</p>
                    </div>
                </div>

                <div v-if="total === 0" class="h-40 flex items-center justify-center text-xs text-gray-400 italic">
                    Belum ada data insiden untuk periode ini.
                </div>
                <div v-else class="space-y-4">
                    <div class="h-80">
                        <VueApexCharts type="bar" height="100%" :options="tipeGradingChartOptions" :series="tipeGradingChartSeries" />
                    </div>

                    <!-- Tabel Cross-tab Matriks -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                    <th class="p-2.5">Tipe Insiden</th>
                                    <th class="p-2.5 text-center bg-blue-50 text-blue-800">Biru (Low)</th>
                                    <th class="p-2.5 text-center bg-emerald-50 text-emerald-800">Hijau (Mod)</th>
                                    <th class="p-2.5 text-center bg-amber-50 text-amber-800">Kuning (High)</th>
                                    <th class="p-2.5 text-center bg-red-50 text-red-800">Merah (Ext)</th>
                                    <th class="p-2.5 text-center font-bold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="m in tipeGradingMatrix" :key="m.tipe" class="hover:bg-gray-50">
                                    <td class="p-2.5 font-semibold text-gray-800">{{ m.tipe }}</td>
                                    <td class="p-2.5 text-center font-mono">{{ m.biru }}</td>
                                    <td class="p-2.5 text-center font-mono">{{ m.hijau }}</td>
                                    <td class="p-2.5 text-center font-mono text-amber-700 font-bold">{{ m.kuning }}</td>
                                    <td class="p-2.5 text-center font-mono text-red-700 font-bold">{{ m.merah }}</td>
                                    <td class="p-2.5 text-center font-bold font-mono">{{ m.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ─── ROW 4: MATRIKS TIPE INSIDEN DAN JENIS INSIDEN ────────────── -->
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                    <div>
                        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold">6</span>
                            Pemetaan Tipe Insiden dan Jenis Insiden (Sentinel/KTD/KTC/KNC/KPC)
                        </h2>
                        <p class="text-[11px] text-gray-400">Korelasi tipe insiden terhadap klasifikasi dampak klinis pada pasien</p>
                    </div>
                </div>

                <div v-if="total === 0" class="h-40 flex items-center justify-center text-xs text-gray-400 italic">
                    Belum ada data insiden untuk periode ini.
                </div>
                <div v-else class="space-y-4">
                    <div class="h-80">
                        <VueApexCharts type="bar" height="100%" :options="tipeJenisChartOptions" :series="tipeJenisChartSeries" />
                    </div>

                    <!-- Tabel Cross-tab Matriks -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                    <th class="p-2.5">Tipe Insiden</th>
                                    <th class="p-2.5 text-center bg-rose-50 text-rose-900 font-bold">Sentinel</th>
                                    <th class="p-2.5 text-center bg-red-50 text-red-800">KTD</th>
                                    <th class="p-2.5 text-center bg-amber-50 text-amber-800">KTC</th>
                                    <th class="p-2.5 text-center bg-emerald-50 text-emerald-800">KNC</th>
                                    <th class="p-2.5 text-center bg-sky-50 text-sky-800">KPC</th>
                                    <th class="p-2.5 text-center font-bold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="m in tipeJenisMatrix" :key="m.tipe" class="hover:bg-gray-50">
                                    <td class="p-2.5 font-semibold text-gray-800">{{ m.tipe }}</td>
                                    <td class="p-2.5 text-center font-mono text-rose-800 font-bold">{{ m.sentinel }}</td>
                                    <td class="p-2.5 text-center font-mono text-red-700 font-semibold">{{ m.ktd }}</td>
                                    <td class="p-2.5 text-center font-mono text-amber-700">{{ m.ktc }}</td>
                                    <td class="p-2.5 text-center font-mono text-emerald-700">{{ m.knc }}</td>
                                    <td class="p-2.5 text-center font-mono text-sky-700">{{ m.kpc }}</td>
                                    <td class="p-2.5 text-center font-bold font-mono">{{ m.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ─── ROW 5: PENYELESAIAN & KEPATUHAN WAKTU ─────────────────────── -->
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                    <div>
                        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">7</span>
                            Pemetaan Penyelesaian & Kepatuhan Waktu Investigasi
                        </h2>
                        <p class="text-[11px] text-gray-400">Rasio Investigasi Sederhana vs RCA dan tingkat kepatuhan batas waktu PMK</p>
                    </div>
                </div>

                <div v-if="total === 0" class="h-40 flex items-center justify-center text-xs text-gray-400 italic">
                    Belum ada data insiden untuk periode ini.
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                        <h3 class="text-xs font-bold text-gray-700 mb-2 text-center">Tipe Penyelesaian Kasus</h3>
                        <div class="h-64">
                            <VueApexCharts type="pie" height="100%" :options="penyelesaianChartOptions" :series="penyelesaianChartSeries" />
                        </div>
                    </div>

                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                        <h3 class="text-xs font-bold text-gray-700 mb-2 text-center">Kepatuhan Terhadap Batas Waktu Investigasi</h3>
                        <div class="h-64">
                            <VueApexCharts type="donut" height="100%" :options="kepatuhanChartOptions" :series="kepatuhanChartSeries" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

