<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    units: Array,
    selectedUnit: Object,
    tahun: Number,
    analysisData: Array,
    summary: Object,
    filters: Object,
    permissions: Object,
});

const unitId = ref(props.filters.unit_id);
const selectedTahun = ref(props.tahun || 2026);
const filterJenis = ref(props.filters.jenis || '');

const bulanNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const bulanShortNames = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

const performanceColorClasses = {
    none: 'bg-white text-gray-700 border border-gray-200',
    blue: 'bg-blue-100 text-blue-800',
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    orange: 'bg-orange-100 text-orange-800',
};

const belowTargetIndicators = computed(() => props.analysisData
    .map((item) => ({
        ...item,
        belowTargetMonths: Object.values(item.months)
            .filter((month) => month.is_met === false)
            .sort((first, second) => first.bulan - second.bulan),
    }))
    .filter((item) => item.belowTargetMonths.length > 0));

const belowTargetMonthsCount = computed(() => belowTargetIndicators.value
    .reduce((count, item) => count + item.belowTargetMonths.length, 0));

const getActionHref = (item, month) => {
    const data = item.months[month];
    if (data.action_id) {
        return route('pdsa.show', data.action_id);
    }

    if (!props.permissions.canCreatePdsa) {
        return route('pdsa.index', {
            unit_id: unitId.value,
            tahun: selectedTahun.value,
        });
    }

    const isRtl = data.action_type === 'rtl';
    const masalah = isRtl
        ? `Capaian ${bulanNames[month - 1]} ${selectedTahun.value} sebesar ${data.capaian}% belum memenuhi target ${item.indicator.target_operator} ${item.indicator.target_capaian}%.`
        : `Capaian meningkat dari ${data.previous_capaian}% pada bulan sebelumnya menjadi ${data.capaian}% dan kini memenuhi target ${item.indicator.target_operator} ${item.indicator.target_capaian}%.`;

    return route('pdsa.create', {
        jenis_tindak_lanjut: data.action_type,
        indicator_id: item.indicator.id,
        unit_id: unitId.value,
        period_id: data.period_id,
        capaian_aktual: data.capaian,
        target_capaian: item.indicator.target_capaian,
        masalah,
    });
};

const getActionLabel = (item, month) => {
    const actionType = item.months[month].action_type;
    const actionExists = Boolean(item.months[month].action_id);
    const label = actionType === 'rtl' ? 'RTL' : 'PDSA';

    if (actionExists) {
        return `Lihat ${label}`;
    }

    return props.permissions.canCreatePdsa ? `Buat ${label}` : `Menu ${label}`;
};

const jenisOptions = [
    { value: '', label: 'Semua Jenis IMUT' },
    { value: 'IMP-RS', label: 'IMP-RS (Prioritas RS)' },
    { value: 'IMN', label: 'INM / IMN (Nasional)' },
    { value: 'IMP-U', label: 'IMP-U (Prioritas Unit)' },
    { value: 'IMU', label: 'IMU (Mutu Unit)' },
    { value: 'SPM', label: 'SPM (Standar Pelayanan)' },
];

const applyFilter = () => {
    router.get(route('data-analysis.index'), {
        unit_id: unitId.value,
        tahun: selectedTahun.value,
        jenis: filterJenis.value || null,
    }, { preserveScroll: true });
};

// State Indikator Aktif untuk Tampilan Grafik Tren
const activeIndicatorIndex = ref(0);
const activeIndicator = computed(() => {
    if (!props.analysisData || props.analysisData.length === 0) return null;
    return props.analysisData[activeIndicatorIndex.value] || props.analysisData[0];
});

// Konfigurasi ApexChart Tren Mutu 12 Bulan
const chartOptions = computed(() => {
    const ind = activeIndicator.value?.indicator;
    const targetVal = ind?.target_capaian || 100;
    const operator = ind?.target_operator || '>=';

    return {
        chart: {
            type: 'line',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'inherit',
        },
        stroke: {
            curve: 'smooth',
            width: [3, 2],
            dashArray: [0, 5],
        },
        colors: ['#2563eb', '#f43f5e'],
        xaxis: {
            categories: bulanShortNames,
            labels: {
                style: {
                    colors: '#64748b',
                    fontSize: '11px',
                    fontWeight: 600,
                },
            },
        },
        yaxis: {
            min: 0,
            max: Math.max(100, targetVal + 10),
            labels: {
                formatter: (val) => `${val}%`,
                style: {
                    colors: '#64748b',
                    fontSize: '11px',
                },
            },
        },
        annotations: {
            yaxis: [
                {
                    y: targetVal,
                    borderColor: '#f43f5e',
                    borderWidth: 2,
                    strokeDashArray: 4,
                    label: {
                        borderColor: '#f43f5e',
                        style: {
                            color: '#fff',
                            background: '#f43f5e',
                            fontSize: '10px',
                            fontWeight: 700,
                        },
                        text: `Target: ${operator} ${targetVal}%`,
                    },
                },
            ],
        },
        markers: {
            size: [5, 0],
            colors: ['#2563eb'],
            strokeColors: '#ffffff',
            strokeWidth: 2,
            hover: { size: 7 },
        },
        tooltip: {
            shared: true,
            y: {
                formatter: (val) => (val !== null ? `${val}%` : 'Belum Ada Data'),
            },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px',
            fontWeight: 600,
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4,
        },
    };
});

const chartSeries = computed(() => {
    if (!activeIndicator.value) return [];
    const ind = activeIndicator.value.indicator;
    const targetVal = ind?.target_capaian || 100;

    const dataActual = [];
    const dataTarget = [];

    for (let m = 1; m <= 12; m++) {
        const item = activeIndicator.value.months[m];
        dataActual.push(item?.capaian !== null ? item.capaian : null);
        dataTarget.push(targetVal);
    }

    return [
        {
            name: 'Capaian Aktual (%)',
            data: dataActual,
        },
        {
            name: 'Target Standar Mutu',
            data: dataTarget,
        },
    ];
});

// Ekspor Excel
const exportExcel = () => {
    window.location.href = route('data-analysis.export-excel', {
        unit_id: unitId.value,
        tahun: selectedTahun.value,
    });
};

// Modal Detail Formula Indikator
const selectedDetail = ref(null);
const showDetailModal = (indData) => {
    selectedDetail.value = indData;
};
</script>

<template>
    <AppLayout title="Data & Analisa Mutu">
        <div class="flex flex-col gap-6 max-w-7xl mx-auto">
            <!-- ── HEADER UTAMA & FILTER CONTROLS ──────────────────────────────── -->
            <div class="order-1 bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 sm:p-6 transition-all">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60 uppercase tracking-wider">
                                Siklus Evaluasi Mutu
                            </span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs font-semibold text-gray-500">Tahun Periode {{ selectedTahun }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">
                            Data & Analisa Capaian Mutu
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">
                            Matriks tren performa 12 bulan, warna capaian IMUT RSYS, dan pemicu RTL/PDSA sesuai hasil bulanan.
                        </p>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Filter Unit (Admin / Komite Mutu / Direksi) -->
                        <div v-if="permissions.isAdmin || permissions.isQualityCommittee || permissions.isExecutive" class="w-48 sm:w-56">
                            <select
                                v-model="unitId"
                                @change="applyFilter"
                                class="w-full text-xs font-semibold border-gray-200 rounded-xl px-3 py-2 bg-slate-50 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="u in units" :key="u.id" :value="u.id">
                                    {{ u.kode }} - {{ u.nama }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Tahun -->
                        <select
                            v-model="selectedTahun"
                            @change="applyFilter"
                            class="text-xs font-semibold border-gray-200 rounded-xl px-3 py-2 bg-slate-50 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                        >
                            <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                        </select>

                        <!-- Filter Jenis -->
                        <select
                            v-model="filterJenis"
                            @change="applyFilter"
                            class="text-xs font-semibold border-gray-200 rounded-xl px-3 py-2 bg-slate-50 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option v-for="j in jenisOptions" :key="j.value" :value="j.value">{{ j.label }}</option>
                        </select>

                        <!-- Tombol Ekspor Excel -->
                        <button
                            @click="exportExcel"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition-all shadow-xs hover:shadow-sm"
                            title="Unduh Spreadsheet Data & Analisa sesuai format baku RS"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Ekspor Excel</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── 4 KARTU RINGKASAN EKSEKUTIF (EXECUTIVE KPI CARDS) ─────────────── -->
            <section class="order-4 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg">🔎</span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Hasil Analisa Indikator Mutu</h2>
                        <p class="text-sm text-gray-500">Indikator dan periode bulanan dengan capaian di bawah standar/target.</p>
                    </div>
                </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- Card 1: Total Indikator Ditetapkan -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Usulan IMUT</p>
                        <h3 class="text-3xl font-black text-gray-900 mt-1 font-mono">
                            {{ summary.total_indicators }}
                        </h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Indikator mutu aktif unit</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold shadow-2xs">
                        🎯
                    </div>
                </div>

                <!-- Card 2: Indikator Memenuhi Target (Hijau) -->
                <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-emerald-50/20">
                    <div>
                        <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Memenuhi Target Tahunan</p>
                        <h3 class="text-3xl font-black text-emerald-700 mt-1 font-mono">
                            {{ summary.met_count }}
                        </h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">
                            {{ summary.total_indicators > 0 ? Math.round((summary.met_count / summary.total_indicators) * 100) : 0 }}% tingkat kepatuhan
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-2xs">
                        ✅
                    </div>
                </div>

                <!-- Card 3: Bulan Di Bawah Target -->
                <div class="bg-white rounded-2xl border border-rose-100 p-5 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-rose-50/20">
                    <div>
                        <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Bulan Di Bawah Target</p>
                        <h3 class="text-3xl font-black text-rose-700 mt-1 font-mono">
                            {{ belowTargetMonthsCount }}
                        </h3>
                        <p class="text-[11px] text-rose-600 font-semibold mt-0.5 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            Evaluasi RTL/PDSA per bulan
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-100/80 text-rose-700 flex items-center justify-center text-xl font-bold shadow-2xs">
                        ⚠️
                    </div>
                </div>

                <!-- Card 4: Rata-Rata Capaian Unit -->
                <div class="bg-white rounded-2xl border border-indigo-100 p-5 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-indigo-50/20">
                    <div>
                        <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Rata-Rata Capaian</p>
                        <h3 class="text-3xl font-black text-indigo-700 mt-1 font-mono">
                            {{ summary.average_capaian }}<span class="text-lg font-bold">%</span>
                        </h3>
                        <p class="text-[11px] text-indigo-500 mt-0.5">{{ summary.evaluated_months }} bulan data terisi</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-100/80 text-indigo-700 flex items-center justify-center text-xl font-bold shadow-2xs">
                        📈
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <article
                    v-for="item in belowTargetIndicators"
                    :key="`analysis-${item.indicator.id}`"
                    class="bg-white rounded-2xl border border-rose-100 shadow-xs overflow-hidden"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-5 py-4 bg-rose-50/60 border-b border-rose-100">
                        <div>
                            <p class="text-sm font-bold text-gray-900">
                                <span class="font-mono text-rose-700">{{ item.indicator.kode }}</span>
                                <span class="mx-1 text-gray-400">·</span>
                                {{ item.indicator.judul }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                Target: {{ item.indicator.target_operator }} {{ item.indicator.target_capaian }}%
                                · {{ item.belowTargetMonths.length }} bulan tidak mencapai target
                            </p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="month in item.belowTargetMonths"
                            :key="`${item.indicator.id}-${month.bulan}`"
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3"
                        >
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold text-gray-800">{{ bulanNames[month.bulan - 1] }}</span>
                                <span :class="['px-2 py-0.5 rounded font-mono text-xs font-bold', performanceColorClasses[month.warna] || performanceColorClasses.none]">
                                    {{ month.capaian }}%
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ month.numerator }} / {{ month.denominator }}
                                </span>
                            </div>
                            <Link
                                v-if="month.action_type"
                                :href="getActionHref(item, month.bulan)"
                                class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-800 border border-orange-200 text-xs font-bold transition-colors"
                                :title="`${getActionLabel(item, month.bulan)} untuk ${bulanNames[month.bulan - 1]}`"
                            >
                                📋 {{ getActionLabel(item, month.bulan) }}
                            </Link>
                        </div>
                    </div>
                </article>

                <div v-if="belowTargetIndicators.length === 0" class="bg-white rounded-2xl border border-emerald-100 p-6 text-center">
                    <p class="text-sm font-semibold text-emerald-700">Tidak ada capaian bulanan di bawah target.</p>
                    <p v-if="summary.evaluated_months === 0" class="text-xs text-gray-500 mt-1">Belum ada capaian bulanan yang dapat dianalisis.</p>
                    <p v-else class="text-xs text-gray-500 mt-1">Seluruh capaian bulanan memenuhi target.</p>
                </div>
            </div>
            </section>

            <!-- ── SECTION GRAFIK TREN MUTU INTERAKTIF ──────────────────────────── -->
            <div v-if="activeIndicator" class="order-3 bg-white rounded-2xl border border-gray-200 shadow-xs p-5 sm:p-6 space-y-4">
                <h2 class="text-sm font-bold text-gray-800">Grafik Tren Capaian</h2>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                            📊
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    {{ activeIndicator.indicator.kode }}
                                </span>
                                <h3 class="text-sm sm:text-base font-bold text-gray-900 leading-snug">
                                    {{ activeIndicator.indicator.judul }}
                                </h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Standar Target: <strong class="text-indigo-700 font-mono">{{ activeIndicator.indicator.target_operator }} {{ activeIndicator.indicator.target_capaian }}%</strong>
                                • Satuan: {{ activeIndicator.indicator.satuan_pengukuran }}
                            </p>
                        </div>
                    </div>

                    <!-- Selector Pemilih Indikator untuk Grafik -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Pilih Grafik:</label>
                        <select
                            v-model="activeIndicatorIndex"
                            class="text-xs font-semibold border-gray-200 rounded-xl px-3 py-1.5 bg-slate-50 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option v-for="(item, idx) in analysisData" :key="item.indicator.id" :value="idx">
                                [{{ item.indicator.kode }}] {{ item.indicator.judul.substring(0, 32) }}...
                            </option>
                        </select>
                    </div>
                </div>

                <!-- ApexCharts Line Chart Display -->
                <div class="w-full h-72 sm:h-80">
                    <VueApexCharts
                        type="line"
                        height="100%"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                </div>
            </div>

            <!-- ── TABEL MATRIKS CAPAIAN MUTU 12 BULAN (SESUAI FORMAT RS) ───────── -->
            <div class="order-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                            Matriks Data Capaian Mutu 12 Bulan (Januari s.d. Desember {{ selectedTahun }})
                        </h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1 font-semibold text-gray-600">
                            <span class="w-2.5 h-2.5 rounded-sm border border-gray-300 bg-white"></span> Memenuhi target
                        </span>
                        <span class="inline-flex items-center gap-1 font-semibold text-blue-700">
                            <span class="w-2.5 h-2.5 rounded-sm bg-blue-500"></span> 81–di bawah target
                        </span>
                        <span class="inline-flex items-center gap-1 font-semibold text-green-700">
                            <span class="w-2.5 h-2.5 rounded-sm bg-green-500"></span> 71–80
                        </span>
                        <span class="inline-flex items-center gap-1 font-semibold text-yellow-700">
                            <span class="w-2.5 h-2.5 rounded-sm bg-yellow-400"></span> 51–70
                        </span>
                        <span class="inline-flex items-center gap-1 font-semibold text-orange-700">
                            <span class="w-2.5 h-2.5 rounded-sm bg-orange-500"></span> Di bawah 51 / gagal target ≤
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs border-separate border-spacing-0">
                        <thead class="bg-slate-100 text-gray-700 uppercase font-bold sticky top-0 z-10 shadow-2xs">
                            <tr>
                                <th class="px-4 py-3 text-left w-72 border-b border-r border-gray-200">
                                    Indikator & Variabel
                                </th>
                                <th v-for="(mName, idx) in bulanShortNames" :key="idx" class="px-2.5 py-3 text-center border-b border-r border-gray-200 w-16">
                                    {{ mName }}
                                </th>
                                <th class="px-3 py-3 text-right border-b border-r border-gray-200 w-24 bg-indigo-50/60 text-indigo-900 font-bold">
                                    Total Tahunan
                                </th>
                                <th class="px-3 py-3 text-center border-b border-gray-200 w-32 bg-slate-50">
                                    Tindak Lanjut
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template v-for="(item, idx) in analysisData" :key="item.indicator.id">
                                <!-- Baris Judul Indikator Header -->
                                <tr class="bg-slate-50/80 font-bold text-gray-900 border-t-2 border-indigo-100">
                                    <td colspan="15" class="px-4 py-2.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-600 text-white">
                                                    {{ item.indicator.kode }}
                                                </span>
                                                <span class="text-xs font-bold text-gray-900">{{ item.indicator.judul }}</span>
                                                <button
                                                    @click="showDetailModal(item)"
                                                    class="text-indigo-600 hover:text-indigo-800 text-[10px] font-semibold underline ml-1"
                                                >
                                                    Detail Kamus
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-3 text-[11px] font-normal text-gray-600">
                                                <span>Standar Target: <strong class="text-indigo-700 font-mono">{{ item.indicator.target_operator }} {{ item.indicator.target_capaian }}%</strong></span>
                                                <span>•</span>
                                                <span>Bulan Tercapai: <strong class="text-emerald-700">{{ item.annual.met_months }}</strong> / 12</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Sub-baris 1: Numerator (N) -->
                                <tr class="hover:bg-slate-50/40">
                                    <td class="px-4 py-1.5 text-gray-500 font-medium border-r border-gray-100 pl-8">
                                        Numerator (N)
                                    </td>
                                    <td v-for="m in 12" :key="'num-' + m" class="px-2 py-1.5 text-right font-mono border-r border-gray-100 text-gray-600 text-[11px]">
                                        {{ (item.months[m]?.numerator || 0).toLocaleString('id-ID') }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-mono font-bold border-r border-gray-100 bg-indigo-50/30 text-indigo-900 text-xs">
                                        {{ item.annual.numerator.toLocaleString('id-ID') }}
                                    </td>
                                    <td class="px-2 py-1.5 text-center text-gray-400 font-mono text-[10px]">-</td>
                                </tr>

                                <!-- Sub-baris 2: Denominator (D) -->
                                <tr class="hover:bg-slate-50/40">
                                    <td class="px-4 py-1.5 text-gray-500 font-medium border-r border-gray-100 pl-8">
                                        Denominator (D)
                                    </td>
                                    <td v-for="m in 12" :key="'den-' + m" class="px-2 py-1.5 text-right font-mono border-r border-gray-100 text-gray-600 text-[11px]">
                                        {{ (item.months[m]?.denominator || 0).toLocaleString('id-ID') }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-mono font-bold border-r border-gray-100 bg-indigo-50/30 text-indigo-900 text-xs">
                                        {{ item.annual.denominator.toLocaleString('id-ID') }}
                                    </td>
                                    <td class="px-2 py-1.5 text-center text-gray-400 font-mono text-[10px]">-</td>
                                </tr>

                                <!-- Sub-baris 3: Capaian Mutu (%) -->
                                <tr class="hover:bg-slate-50/60 font-semibold border-b border-gray-200">
                                    <td class="px-4 py-2 text-gray-900 font-bold border-r border-gray-100 pl-8 flex items-center justify-between">
                                        <span>Capaian Mutu (%)</span>
                                        <button
                                            @click="activeIndicatorIndex = idx"
                                            class="text-[10px] px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                            title="Tampilkan grafik untuk indikator ini"
                                        >
                                            Lihat Grafik 📈
                                        </button>
                                    </td>
                                    <td v-for="m in 12" :key="'cap-' + m" class="px-2 py-1.5 text-center font-mono border-r border-gray-100">
                                        <div v-if="item.months[m]?.capaian !== null && item.months[m]?.denominator > 0" class="flex flex-col items-center gap-0.5">
                                            <span
                                                :class="[
                                                    'px-1.5 py-0.5 rounded text-[11px] font-bold block w-full text-center',
                                                    performanceColorClasses[item.months[m].warna] || performanceColorClasses.none
                                                ]"
                                            >
                                                {{ Math.round(item.months[m].capaian) }}%
                                            </span>
                                            <Link
                                                v-if="item.months[m].action_type"
                                                :href="getActionHref(item, m)"
                                                :class="[
                                                    'text-[9px] px-1 py-0.5 rounded font-bold transition-colors inline-flex items-center justify-center gap-0.5 leading-tight whitespace-normal text-center',
                                                    item.months[m].action_type === 'rtl'
                                                        ? 'bg-orange-100 text-orange-800 hover:bg-orange-200'
                                                        : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'
                                                ]"
                                                :title="`${getActionLabel(item, m)} untuk bulan ini`"
                                            >
                                                <span>📋 {{ getActionLabel(item, m) }}</span>
                                            </Link>
                                            <Link
                                                v-else-if="item.months[m].has_pdsa"
                                                :href="route('pdsa.show', item.months[m].pdsa_id)"
                                                class="text-[9px] px-1 py-0.5 rounded font-bold bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors inline-flex items-center gap-0.5 leading-none"
                                                title="Lihat Lembar PDSA Bulan Ini"
                                            >
                                                <span>📋 PDSA</span>
                                            </Link>
                                        </div>
                                        <span v-else class="text-gray-300 font-normal">-</span>
                                    </td>

                                    <!-- Total Rata-rata Tahunan -->
                                    <td class="px-3 py-2 text-right font-mono font-bold border-r border-gray-100 bg-indigo-50/50">
                                        <span
                                            v-if="item.annual.capaian !== null"
                                            :class="[
                                                'px-2 py-1 rounded text-xs font-bold inline-block',
                                                performanceColorClasses[item.annual.warna] || performanceColorClasses.none
                                            ]"
                                        >
                                            {{ item.annual.capaian }}%
                                        </span>
                                        <span v-else class="text-gray-400 font-normal">-</span>
                                    </td>

                                    <td class="px-3 py-2 text-center">
                                        <div v-if="item.annual.rtl_months || item.annual.pdsa_months" class="space-y-1 text-[10px] font-semibold">
                                            <p v-if="item.annual.rtl_months" class="text-orange-700">
                                                {{ item.annual.rtl_months }} RTL
                                            </p>
                                            <p v-if="item.annual.pdsa_months" class="text-indigo-700">
                                                {{ item.annual.pdsa_months }} PDSA
                                            </p>
                                        </div>
                                        <div v-else-if="item.annual.capaian !== null" class="text-emerald-700 font-bold text-[10px]">
                                            Tidak ada pemicu
                                        </div>
                                        <div v-else class="text-gray-400 text-[10px] italic">
                                            Belum terisi
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State jika tidak ada indikator -->
                            <tr v-if="analysisData.length === 0">
                                <td colspan="15" class="py-12 text-center text-xs text-gray-500">
                                    Belum ada data indikator mutu untuk unit dan tahun yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── MODAL DETAIL KAMUS INDIKATOR ────────────────────────────────────── -->
        <div v-if="selectedDetail" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-indigo-100 text-indigo-800">
                            {{ selectedDetail.indicator.kode }}
                        </span>
                        <h3 class="text-sm font-bold text-gray-900 leading-snug">Kamus Indikator Mutu</h3>
                    </div>
                    <button @click="selectedDetail = null" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-3 text-xs text-gray-700">
                    <div>
                        <p class="font-bold text-gray-900 mb-0.5">{{ selectedDetail.indicator.judul }}</p>
                        <p class="text-gray-500 text-[11px]">Format Form: {{ selectedDetail.indicator.form_layout_type }}</p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl space-y-2 border border-slate-100">
                        <p><strong>Standar Target:</strong> <span class="font-mono text-indigo-700 font-bold">{{ selectedDetail.indicator.target_operator }} {{ selectedDetail.indicator.target_capaian }}%</span></p>
                        <p v-if="selectedDetail.indicator.formula"><strong>Formula:</strong> <span class="font-mono text-gray-800">{{ selectedDetail.indicator.formula }}</span></p>
                        <p v-if="selectedDetail.indicator.numerator_keterangan"><strong>Numerator (N):</strong> {{ selectedDetail.indicator.numerator_keterangan }}</p>
                        <p v-if="selectedDetail.indicator.denominator_keterangan"><strong>Denominator (D):</strong> {{ selectedDetail.indicator.denominator_keterangan }}</p>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        @click="selectedDetail = null"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
