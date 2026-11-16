<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    dashboardType: {
        type: String,
        default: 'hospital' // 'hospital' | 'unit'
    },
    userRole: {
        type: String,
        default: 'komite_mutu'
    },
    unit: {
        type: Object,
        default: () => ({ id: null, nama: 'Unit Kerja', kode: 'UNIT' })
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    pieStatusCapaian: {
        type: Object,
        default: () => ({ series: [0, 0, 0], labels: [], colors: [] })
    },
    pieKepatuhanUnit: {
        type: Object,
        default: () => ({ series: [0, 0, 0], labels: [], colors: [] })
    },
    pieKategori: {
        type: Object,
        default: () => ({ series: [0, 0, 0, 0], labels: [], colors: [] })
    },
    pieProgresInput: {
        type: Object,
        default: () => ({ series: [0, 0, 0], labels: [], colors: [] })
    },
    trenData: {
        type: Array,
        default: () => []
    },
    indikatorMerah: {
        type: Array,
        default: () => []
    },
    unitSubmissions: {
        type: Array,
        default: () => []
    },
    topUnits: {
        type: Array,
        default: () => []
    },
    unitsNeedingAttention: {
        type: Array,
        default: () => []
    },
    indikatorDetailList: {
        type: Array,
        default: () => []
    },
    unfilledList: {
        type: Array,
        default: () => []
    },
    filter: {
        type: Object,
        default: () => ({ tahun: 2026, bulan: 10 })
    },
});

// Filter state
const tahun = ref(props.filter?.tahun || 2026);
const bulan = ref(props.filter?.bulan || 10);
const unitSearch = ref('');
const unitStatusFilter = ref('all');

const bulanOptions = [
    { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' }, { value: 4, label: 'April' },
    { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' }, { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];

const selectedBulanLabel = computed(() => {
    return bulanOptions.find(b => b.value === bulan.value)?.label || 'Bulan Ini';
});

const applyFilter = () => {
    router.get(route('dashboard'), { tahun: tahun.value, bulan: bulan.value }, { preserveScroll: true });
};

// ─── ApexCharts Pie Configurations ──────────────────────────────────────────
const commonFontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif";

// Pie 1: Status Capaian (RS atau Unit)
const hasPieStatusData = computed(() => {
    return props.pieStatusCapaian?.series?.some(val => val > 0);
});
const totalPieStatusCount = computed(() => {
    return props.pieStatusCapaian?.series?.reduce((a, b) => a + b, 0) || 0;
});
const pieStatusOptions = computed(() => ({
    chart: {
        type: 'pie',
        fontFamily: commonFontFamily,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 600 }
    },
    labels: props.pieStatusCapaian?.labels || ['Tercapai', 'Hampir Target', 'Di Bawah Target'],
    colors: props.pieStatusCapaian?.colors || ['#10B981', '#F59E0B', '#F43F5E'],
    legend: {
        position: 'bottom',
        fontSize: '12px',
        fontWeight: 500,
        labels: { colors: '#475569' },
        itemMargin: { horizontal: 6, vertical: 4 }
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val > 0 ? Math.round(val) + '%' : '',
        style: { fontSize: '11px', fontWeight: '600' },
        dropShadow: { enabled: false }
    },
    stroke: { width: 2, colors: ['#ffffff'] },
    tooltip: {
        y: {
            formatter: (val) => {
                const total = totalPieStatusCount.value;
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                return `${val} indikator (${pct}%)`;
            }
        }
    },
    responsive: [{ breakpoint: 640, options: { legend: { position: 'bottom' } } }]
}));

// Pie 2: Kepatuhan Input Unit (Tingkat RS)
const hasPieKepatuhanData = computed(() => {
    return props.pieKepatuhanUnit?.series?.some(val => val > 0);
});
const totalPieKepatuhanCount = computed(() => {
    return props.pieKepatuhanUnit?.series?.reduce((a, b) => a + b, 0) || 0;
});
const pieKepatuhanOptions = computed(() => ({
    chart: {
        type: 'pie',
        fontFamily: commonFontFamily,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 600 }
    },
    labels: props.pieKepatuhanUnit?.labels || ['Lengkap & Valid', 'Menunggu Validasi', 'Belum Input'],
    colors: props.pieKepatuhanUnit?.colors || ['#10B981', '#0284C7', '#94A3B8'],
    legend: {
        position: 'bottom',
        fontSize: '12px',
        fontWeight: 500,
        labels: { colors: '#475569' },
        itemMargin: { horizontal: 6, vertical: 4 }
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val > 0 ? Math.round(val) + '%' : '',
        style: { fontSize: '11px', fontWeight: '600' },
        dropShadow: { enabled: false }
    },
    stroke: { width: 2, colors: ['#ffffff'] },
    tooltip: {
        y: {
            formatter: (val) => {
                const total = totalPieKepatuhanCount.value;
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                return `${val} unit kerja (${pct}%)`;
            }
        }
    },
    responsive: [{ breakpoint: 640, options: { legend: { position: 'bottom' } } }]
}));

// Pie 3: Komposisi Portofolio Indikator (Tingkat RS)
const hasPieKategoriData = computed(() => {
    return props.pieKategori?.series?.some(val => val > 0);
});
const totalPieKategoriCount = computed(() => {
    return props.pieKategori?.series?.reduce((a, b) => a + b, 0) || 0;
});
const pieKategoriOptions = computed(() => ({
    chart: {
        type: 'pie',
        fontFamily: commonFontFamily,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 600 }
    },
    labels: props.pieKategori?.labels || ['INM', 'IMP-RS', 'IMP-U', 'SPM'],
    colors: props.pieKategori?.colors || ['#2563EB', '#8B5CF6', '#06B6D4', '#F59E0B'],
    legend: {
        position: 'bottom',
        fontSize: '12px',
        fontWeight: 500,
        labels: { colors: '#475569' },
        itemMargin: { horizontal: 6, vertical: 4 }
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val > 0 ? Math.round(val) + '%' : '',
        style: { fontSize: '11px', fontWeight: '600' },
        dropShadow: { enabled: false }
    },
    stroke: { width: 2, colors: ['#ffffff'] },
    tooltip: {
        y: {
            formatter: (val) => {
                const total = totalPieKategoriCount.value;
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                return `${val} indikator (${pct}%)`;
            }
        }
    },
    responsive: [{ breakpoint: 640, options: { legend: { position: 'bottom' } } }]
}));

// Pie: Progres Pengisian Form Unit (Tingkat Unit)
const hasPieProgresInputData = computed(() => {
    return props.pieProgresInput?.series?.some(val => val > 0);
});
const totalPieProgresCount = computed(() => {
    return props.pieProgresInput?.series?.reduce((a, b) => a + b, 0) || 0;
});
const pieProgresInputOptions = computed(() => ({
    chart: {
        type: 'pie',
        fontFamily: commonFontFamily,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 600 }
    },
    labels: props.pieProgresInput?.labels || ['Tervalidasi (Selesai)', 'Bebas Validasi', 'Menunggu Validasi', 'Belum Diinput'],
    colors: props.pieProgresInput?.colors || ['#10B981', '#14B8A6', '#0284C7', '#EF4444'],
    legend: {
        position: 'bottom',
        fontSize: '12px',
        fontWeight: 500,
        labels: { colors: '#475569' },
        itemMargin: { horizontal: 6, vertical: 4 }
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val > 0 ? Math.round(val) + '%' : '',
        style: { fontSize: '11px', fontWeight: '600' },
        dropShadow: { enabled: false }
    },
    stroke: { width: 2, colors: ['#ffffff'] },
    tooltip: {
        y: {
            formatter: (val) => {
                const total = totalPieProgresCount.value;
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                return `${val} form (${pct}%)`;
            }
        }
    },
    responsive: [{ breakpoint: 640, options: { legend: { position: 'bottom' } } }]
}));

// ─── Area Chart: Tren 6 Bulan ────────────────────────────────────────────────
const trenChartOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        fontFamily: commonFontFamily,
        zoom: { enabled: false },
    },
    stroke: { curve: 'smooth', width: 3 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 95, 100],
            colorStops: [
                { offset: 0, color: '#2563EB', opacity: 0.4 },
                { offset: 100, color: '#38BDF8', opacity: 0.05 }
            ]
        },
    },
    colors: ['#2563EB'],
    xaxis: {
        categories: props.trenData.map(d => d.label),
        labels: { style: { colors: '#64748B', fontSize: '11px', fontFamily: commonFontFamily } }
    },
    yaxis: {
        min: 0,
        max: 100,
        labels: {
            formatter: v => v + '%',
            style: { colors: '#64748B', fontSize: '11px', fontFamily: commonFontFamily }
        }
    },
    tooltip: {
        y: { formatter: v => v + '%' },
        theme: 'light'
    },
    grid: { borderColor: '#E2E8F0', strokeDashArray: 3 },
    markers: { size: 4, colors: ['#1D4ED8'], strokeColors: '#fff', strokeWidth: 2 },
}));
const trenSeries = computed(() => [{
    name: props.dashboardType === 'hospital' ? 'Rata-rata RS' : 'Rata-rata Unit',
    data: props.trenData.map(d => d.capaian)
}]);

// ─── Filter Unit Submissions (Untuk Komite Mutu / Superadmin) ────────────────
const filteredUnitSubmissions = computed(() => {
    let list = props.unitSubmissions || [];
    if (unitStatusFilter.value !== 'all') {
        list = list.filter(u => u.status === unitStatusFilter.value);
    }
    if (unitSearch.value.trim() !== '') {
        const q = unitSearch.value.toLowerCase();
        list = list.filter(u => u.nama.toLowerCase().includes(q) || u.kode.toLowerCase().includes(q));
    }
    return list;
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight">Dashboard Mutu RSYS</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span v-if="userRole === 'direksi'">Ikhtisar Strategis Eksekutif & Kepatuhan Rumah Sakit</span>
                        <span v-else-if="userRole === 'komite_mutu' || userRole === 'superadmin'">Pusat Monitoring, Supervisi & Validasi Mutu Terpadu</span>
                        <span v-else>Pengawasan Capaian & Pengisian Data Indikator Unit Kerja</span>
                    </p>
                </div>
            </div>
        </template>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- 1. HERO CONTEXT BANNER WITH INTEGRATED FILTER                      -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-950 via-blue-900 to-slate-900 text-white p-6 shadow-xl mb-6 border border-blue-800/40">
            <!-- Background Glows -->
            <div class="absolute -top-24 -left-24 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <!-- Left: Role & Welcome Badge -->
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-md mb-3 border shadow-sm"
                        :class="{
                            'bg-amber-400/15 text-amber-300 border-amber-400/30': userRole === 'direksi',
                            'bg-emerald-400/15 text-emerald-300 border-emerald-400/30': userRole === 'komite_mutu',
                            'bg-purple-400/15 text-purple-300 border-purple-400/30': userRole === 'superadmin',
                            'bg-sky-400/15 text-sky-300 border-sky-400/30': ['kepala_unit', 'pic_unit'].includes(userRole),
                        }">
                        <span class="w-2 h-2 rounded-full animate-pulse"
                            :class="{
                                'bg-amber-400': userRole === 'direksi',
                                'bg-emerald-400': userRole === 'komite_mutu',
                                'bg-purple-400': userRole === 'superadmin',
                                'bg-sky-400': ['kepala_unit', 'pic_unit'].includes(userRole),
                            }"></span>
                        <span v-if="userRole === 'direksi'">🏛️ Panel Direksi / Manajemen Eksekutif</span>
                        <span v-else-if="userRole === 'komite_mutu'">🛡️ Panel Komite Mutu & Akreditasi</span>
                        <span v-else-if="userRole === 'superadmin'">⚙️ Panel Super Administrator Sistem</span>
                        <span v-else>🏥 Unit Kerja: {{ unit.nama }} ({{ unit.kode }})</span>
                    </div>

                    <h2 class="text-2xl font-extrabold tracking-tight text-white mb-1">
                        <span v-if="userRole === 'direksi'">Dashboard Eksekutif Capaian Mutu RS</span>
                        <span v-else-if="userRole === 'komite_mutu' || userRole === 'superadmin'">Monitoring & Supervisi Mutu Rumah Sakit</span>
                        <span v-else>Dashboard Mutu {{ unit.nama }}</span>
                    </h2>
                    <p class="text-xs text-blue-200/80 leading-relaxed">
                        <span v-if="userRole === 'direksi'">
                            Memantau rata-rata capaian mutu RS, kepatuhan indikator nasional (INM), indikator prioritas (IMP-RS), dan kepatuhan 17 unit kerja pada periode {{ selectedBulanLabel }} {{ tahun }}.
                        </span>
                        <span v-else-if="userRole === 'komite_mutu' || userRole === 'superadmin'">
                            Pantauan real-time kepatuhan entri data seluruh unit kerja, antrian validasi data, status verifikasi PDSA, serta sebaran capaian target indikator mutu.
                        </span>
                        <span v-else>
                            Kelola progres pengisian form tally sheet harian/bulanan unit Anda, pantau status validasi dari Komite Mutu, serta pantau indikator yang memerlukan rencana perbaikan (PDSA).
                        </span>
                    </p>
                </div>

                <!-- Right: Period Filter Bar -->
                <div class="bg-white/10 backdrop-blur-md p-3.5 rounded-xl border border-white/15 flex flex-wrap items-center gap-2.5 self-start lg:self-auto shadow-inner">
                    <div class="flex items-center gap-1.5 text-xs text-blue-100 font-medium mr-1">
                        <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Periode:</span>
                    </div>
                    <select v-model="bulan" class="text-xs bg-slate-900/80 text-white border border-blue-400/30 rounded-lg px-2.5 py-1.5 focus:ring-sky-400 focus:border-sky-400 font-medium">
                        <option v-for="b in bulanOptions" :key="b.value" :value="b.value">{{ b.label }}</option>
                    </select>
                    <select v-model="tahun" class="text-xs bg-slate-900/80 text-white border border-blue-400/30 rounded-lg px-2.5 py-1.5 focus:ring-sky-400 focus:border-sky-400 font-medium">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button @click="applyFilter" class="px-3.5 py-1.5 bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white text-xs font-semibold rounded-lg shadow-md transition-all active:scale-95 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Terapkan
                    </button>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- 2. ROLE-BASED METRIC CARDS                                         -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->

        <!-- CASE A: DIREKSI METRIC CARDS (EXECUTIVE FOCUS) -->
        <div v-if="userRole === 'direksi'" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Capaian RS</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl font-extrabold text-blue-900 tracking-tight">{{ stats.rataRataCapaian }}</span>
                    <span class="text-base font-bold text-blue-600">%</span>
                </div>
                <div class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full" :class="stats.rataRataCapaian >= 80 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <span>Target Ideal RS: &ge; 80%</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Indikator Nasional (INM)</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl font-extrabold text-indigo-900 tracking-tight">{{ stats.inmCapaian }}</span>
                    <span class="text-base font-bold text-indigo-600">%</span>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">Kepatuhan Standar Kemenkes RI</p>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Prioritas RS (IMP-RS)</span>
                    <span class="w-8 h-8 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl font-extrabold text-purple-900 tracking-tight">{{ stats.imprsCapaian }}</span>
                    <span class="text-base font-bold text-purple-600">%</span>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">Fokus Program Mutu Strategis RS</p>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kepatuhan Unit Lengkap</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl font-extrabold text-emerald-700 tracking-tight">{{ stats.unitLengkap }}</span>
                    <span class="text-sm font-semibold text-slate-500">/ {{ stats.totalUnit }} Unit</span>
                </div>
                <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700"
                        :style="{ width: `${stats.totalUnit > 0 ? (stats.unitLengkap / stats.totalUnit) * 100 : 0}%` }"></div>
                </div>
            </div>
        </div>

        <!-- CASE B: KOMITE MUTU & SUPERADMIN METRIC CARDS (MONITORING & VALIDATION WORKLOAD) -->
        <div v-else-if="userRole === 'komite_mutu' || userRole === 'superadmin'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Indikator</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ stats.totalIndikator }}</p>
                <p class="text-[11px] text-blue-600 font-medium mt-1">Aktif terdaftar di RS</p>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Rata-rata Capaian RS</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-extrabold text-blue-700">{{ stats.rataRataCapaian }}</span>
                    <span class="text-base font-bold text-blue-600">%</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Periode {{ selectedBulanLabel }}</p>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Unit Lengkap</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-extrabold text-emerald-600">{{ stats.unitLengkap }}</span>
                    <span class="text-xs text-slate-500">/ {{ stats.totalUnit }} Unit</span>
                </div>
                <p class="text-[11px] text-emerald-600 font-medium mt-1">{{ stats.unitBelum }} unit belum isi</p>
            </div>

            <!-- Menunggu Validasi (Interactive Link) -->
            <Link :href="route('validation.index')" class="bg-gradient-to-br from-sky-50 to-blue-50 rounded-2xl p-4 border border-sky-200 shadow-sm hover:shadow-md hover:border-sky-300 transition-all block group">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-sky-800 font-semibold uppercase tracking-wider">Menunggu Validasi</p>
                    <svg class="w-4 h-4 text-sky-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-extrabold text-sky-700 mt-1">{{ stats.pendingValidasi }}</p>
                <p class="text-[11px] text-sky-600 font-medium mt-1">Klik untuk proses data &rarr;</p>
            </Link>

            <!-- PDSA Pending Review (Interactive Link) -->
            <Link :href="route('pdsa.index')" class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-4 border border-amber-200 shadow-sm hover:shadow-md hover:border-amber-300 transition-all block group">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-amber-800 font-semibold uppercase tracking-wider">PDSA Perlu Review</p>
                    <svg class="w-4 h-4 text-amber-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-extrabold text-amber-700 mt-1">{{ stats.pdsaPending }}</p>
                <p class="text-[11px] text-amber-600 font-medium mt-1">Review rencana perbaikan &rarr;</p>
            </Link>
        </div>

        <!-- CASE C: KEPALA UNIT & PIC UNIT METRIC CARDS (UNIT OPERATIONAL SCOPE) -->
        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Indikator Unit Anda</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ stats.totalIndikator }}</p>
                <p class="text-[11px] text-blue-600 font-medium mt-1">Tugas khusus {{ unit.kode }}</p>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Rata-rata Capaian</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-extrabold text-blue-700">{{ stats.rataRataCapaian }}</span>
                    <span class="text-base font-bold text-blue-600">%</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Indikator tervalidasi</p>
            </div>

            <div class="bg-emerald-50/70 rounded-2xl p-4 border border-emerald-200 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-emerald-800 font-semibold uppercase tracking-wider">✅ Sudah Tervalidasi</p>
                <p class="text-3xl font-extrabold text-emerald-700 mt-1">{{ stats.approvedCount }}</p>
                <p class="text-[11px] text-emerald-600 font-medium mt-1">Disetujui Komite Mutu</p>
            </div>

            <div class="bg-sky-50/70 rounded-2xl p-4 border border-sky-200 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-sky-800 font-semibold uppercase tracking-wider">⏳ Menunggu Validasi</p>
                <p class="text-3xl font-extrabold text-sky-700 mt-1">{{ stats.submittedCount }}</p>
                <p class="text-[11px] text-sky-600 font-medium mt-1">Telah disubmit PIC</p>
            </div>

            <div class="bg-rose-50/70 rounded-2xl p-4 border border-rose-200 shadow-sm hover:shadow-md transition-all">
                <p class="text-xs text-rose-800 font-semibold uppercase tracking-wider">🔴 Belum Diinput</p>
                <p class="text-3xl font-extrabold text-rose-700 mt-1">{{ stats.unfilledCount }}</p>
                <p class="text-[11px] text-rose-600 font-medium mt-1">Perlu segera dilengkapi!</p>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- 3. PIE DIAGRAM VISUALIZATION SECTION                                -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->

        <!-- SECTION A: PIE CHARTS FOR HOSPITAL LEVEL (DIREKSI / KOMITE MUTU / SUPERADMIN) -->
        <div v-if="dashboardType === 'hospital'" class="space-y-6 mb-6">
            <!-- Pie Grid: 3 Pie Diagrams side by side on large screens -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Pie 1: Status Capaian Target RS -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                Status Capaian Target RS
                            </h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ totalPieStatusCount }} Indikator
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Distribusi hasil capaian terhadap target mutu se-RS</p>

                        <div v-if="hasPieStatusData" class="flex justify-center items-center min-h-[240px]">
                            <VueApexCharts width="100%" height="240" :options="pieStatusOptions" :series="pieStatusCapaian.series" />
                        </div>
                        <div v-else class="h-60 flex flex-col items-center justify-center text-slate-400 text-xs">
                            <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            Belum ada entri data yang disetujui bulan ini
                        </div>
                    </div>

                    <!-- Breakdown Mini Legend -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-center text-xs">
                        <div class="p-1.5 rounded-lg bg-emerald-50/70">
                            <p class="text-[10px] text-emerald-700 font-semibold">Tercapai</p>
                            <p class="text-base font-extrabold text-emerald-800">{{ stats.statusCount?.green || 0 }}</p>
                        </div>
                        <div class="p-1.5 rounded-lg bg-amber-50/70">
                            <p class="text-[10px] text-amber-700 font-semibold">Hampir</p>
                            <p class="text-base font-extrabold text-amber-800">{{ stats.statusCount?.yellow || 0 }}</p>
                        </div>
                        <div class="p-1.5 rounded-lg bg-rose-50/70">
                            <p class="text-[10px] text-rose-700 font-semibold">Di Bawah</p>
                            <p class="text-base font-extrabold text-rose-800">{{ stats.statusCount?.red || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pie 2: Kepatuhan Input Data 17 Unit Kerja -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-sky-500"></span>
                                Kepatuhan Input 17 Unit
                            </h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ stats.totalUnit }} Unit Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Progres pengumpulan & penyelesaian form antar unit</p>

                        <div v-if="hasPieKepatuhanData" class="flex justify-center items-center min-h-[240px]">
                            <VueApexCharts width="100%" height="240" :options="pieKepatuhanOptions" :series="pieKepatuhanUnit.series" />
                        </div>
                        <div v-else class="h-60 flex flex-col items-center justify-center text-slate-400 text-xs">
                            <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Data unit belum tersedia
                        </div>
                    </div>

                    <!-- Breakdown Mini Legend -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-center text-xs">
                        <div class="p-1.5 rounded-lg bg-emerald-50/70">
                            <p class="text-[10px] text-emerald-700 font-semibold">Lengkap</p>
                            <p class="text-base font-extrabold text-emerald-800">{{ stats.unitLengkap || 0 }}</p>
                        </div>
                        <div class="p-1.5 rounded-lg bg-sky-50/70">
                            <p class="text-[10px] text-sky-700 font-semibold">Menunggu</p>
                            <p class="text-base font-extrabold text-sky-800">{{ stats.unitPending || 0 }}</p>
                        </div>
                        <div class="p-1.5 rounded-lg bg-slate-100">
                            <p class="text-[10px] text-slate-600 font-semibold">Belum Isi</p>
                            <p class="text-base font-extrabold text-slate-700">{{ stats.unitBelum || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pie 3: Portofolio Kategori Indikator Mutu -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                                Portofolio Kategori Indikator
                            </h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ totalPieKategoriCount }} Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Sebaran jenis indikator aktif di lingkungan RS</p>

                        <div v-if="hasPieKategoriData" class="flex justify-center items-center min-h-[240px]">
                            <VueApexCharts width="100%" height="240" :options="pieKategoriOptions" :series="pieKategori.series" />
                        </div>
                        <div v-else class="h-60 flex flex-col items-center justify-center text-slate-400 text-xs">
                            <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            Kategori belum terdata
                        </div>
                    </div>

                    <!-- Breakdown Mini Legend -->
                    <div class="grid grid-cols-4 gap-1 pt-3 border-t border-slate-100 text-center text-xs">
                        <div class="p-1 rounded bg-blue-50">
                            <p class="text-[9px] text-blue-700 font-bold">INM</p>
                            <p class="text-sm font-extrabold text-blue-800">{{ pieKategori?.series?.[0] || 0 }}</p>
                        </div>
                        <div class="p-1 rounded bg-purple-50">
                            <p class="text-[9px] text-purple-700 font-bold">IMP-RS</p>
                            <p class="text-sm font-extrabold text-purple-800">{{ pieKategori?.series?.[1] || 0 }}</p>
                        </div>
                        <div class="p-1 rounded bg-cyan-50">
                            <p class="text-[9px] text-cyan-700 font-bold">IMP-U</p>
                            <p class="text-sm font-extrabold text-cyan-800">{{ pieKategori?.series?.[2] || 0 }}</p>
                        </div>
                        <div class="p-1 rounded bg-amber-50">
                            <p class="text-[9px] text-amber-700 font-bold">SPM</p>
                            <p class="text-sm font-extrabold text-amber-800">{{ pieKategori?.series?.[3] || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trend 6 Bulan Terakhir -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                            Tren Capaian Mutu Rumah Sakit (6 Bulan Terakhir)
                        </h3>
                        <p class="text-xs text-slate-500">Pergerakan rata-rata nilai capaian indikator mutu yang telah disetujui</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100">
                        Target RS: &ge; 80%
                    </span>
                </div>
                <div class="h-60">
                    <VueApexCharts width="100%" height="240" :options="trenChartOptions" :series="trenSeries" />
                </div>
            </div>
        </div>

        <!-- SECTION B: PIE CHARTS FOR UNIT LEVEL (KEPALA UNIT & PIC UNIT) -->
        <div v-else class="space-y-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Pie 1: Status Capaian Indikator Unit Anda -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                Status Capaian Indikator Unit Anda
                            </h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ totalPieStatusCount }} Terverifikasi
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Hasil perbandingan capaian form terhadap target indikator unit</p>

                        <div v-if="hasPieStatusData" class="flex justify-center items-center min-h-[250px]">
                            <VueApexCharts width="100%" height="250" :options="pieStatusOptions" :series="pieStatusCapaian.series" />
                        </div>
                        <div v-else class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                            <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            Belum ada indikator tervalidasi di unit Anda untuk bulan ini
                        </div>
                    </div>

                    <!-- Breakdown Mini Legend -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-center text-xs">
                        <div class="p-2 rounded-xl bg-emerald-50/70 border border-emerald-100">
                            <p class="text-[10px] text-emerald-700 font-semibold">Tercapai</p>
                            <p class="text-lg font-extrabold text-emerald-800">{{ stats.statusCount?.green || 0 }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-amber-50/70 border border-amber-100">
                            <p class="text-[10px] text-amber-700 font-semibold">Hampir Target</p>
                            <p class="text-lg font-extrabold text-amber-800">{{ stats.statusCount?.yellow || 0 }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-rose-50/70 border border-rose-100">
                            <p class="text-[10px] text-rose-700 font-semibold">Di Bawah Target</p>
                            <p class="text-lg font-extrabold text-rose-800">{{ stats.statusCount?.red || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pie 2: Progres Pengisian Tally Sheet Bulan Ini -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                                Progres Pengisian Form Indikator
                            </h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ stats.totalIndikator }} Indikator Unit
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Pantau status pengisian form tally sheet harian/bulanan periode ini</p>

                        <div v-if="hasPieProgresInputData" class="flex justify-center items-center min-h-[250px]">
                            <VueApexCharts width="100%" height="250" :options="pieProgresInputOptions" :series="pieProgresInput.series" />
                        </div>
                        <div v-else class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                            <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Belum ada daftar indikator aktif di unit ini
                        </div>
                    </div>

                    <!-- Breakdown Mini Legend -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-center text-xs">
                        <div class="p-2 rounded-xl bg-emerald-50/70 border border-emerald-100">
                            <p class="text-[10px] text-emerald-700 font-semibold">Tervalidasi</p>
                            <p class="text-lg font-extrabold text-emerald-800">{{ stats.approvedCount || 0 }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-sky-50/70 border border-sky-100">
                            <p class="text-[10px] text-sky-700 font-semibold">Menunggu</p>
                            <p class="text-lg font-extrabold text-sky-800">{{ stats.submittedCount || 0 }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-rose-50/70 border border-rose-100">
                            <p class="text-[10px] text-rose-700 font-semibold">Belum Diinput</p>
                            <p class="text-lg font-extrabold text-rose-800">{{ stats.unfilledCount || 0 }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tren 6 Bulan Unit -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                            Tren Capaian Mutu Unit {{ unit.nama }} (6 Bulan Terakhir)
                        </h3>
                        <p class="text-xs text-slate-500">Pergerakan rata-rata nilai capaian indikator unit yang telah disetujui</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100">
                        Scope Unit: {{ unit.kode }}
                    </span>
                </div>
                <div class="h-60">
                    <VueApexCharts width="100%" height="240" :options="trenChartOptions" :series="trenSeries" />
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- 4. ROLE-SPECIFIC ACTION PANELS & DETAILED WORKLISTS                 -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->

        <!-- CASE 1: DIREKSI EXECUTIVE ACTIONS & LEADERBOARDS -->
        <div v-if="userRole === 'direksi'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Card 1: Top Performing Units -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">⭐</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Unit Kerja Capaian Tertinggi</h3>
                                <p class="text-xs text-slate-500">5 Unit dengan rata-rata capaian mutu terbaik periode ini</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="topUnits.length === 0" class="py-8 text-center text-xs text-slate-400">
                        Belum ada unit dengan entri yang disetujui bulan ini
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="(item, idx) in topUnits" :key="item.id"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"
                                    :class="idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-slate-200 text-slate-700' : idx === 2 ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600'">
                                    {{ idx + 1 }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800">{{ item.nama }}</p>
                                    <p class="text-[11px] text-slate-500">{{ item.approved }} Indikator Tervalidasi</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-extrabold text-emerald-700">{{ item.avg }}%</span>
                                <div class="w-16 bg-slate-200 rounded-full h-1 mt-1">
                                    <div class="bg-emerald-500 h-1 rounded-full" :style="{ width: `${Math.min(item.avg, 100)}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Units Needing Attention (Belum Isi) -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">⚠️</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Unit Memerlukan Atensi / Belum Input</h3>
                                <p class="text-xs text-slate-500">Unit yang belum mengirimkan data indikator untuk bulan aktif</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                            {{ unitsNeedingAttention.length }} Unit
                        </span>
                    </div>

                    <div v-if="unitsNeedingAttention.length === 0" class="py-8 text-center text-xs text-slate-500">
                        <svg class="w-10 h-10 mx-auto mb-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Luar biasa! Seluruh unit telah mengirimkan entri data mutu.
                    </div>
                    <div v-else class="space-y-2.5 max-h-72 overflow-y-auto">
                        <div v-for="item in unitsNeedingAttention" :key="item.id"
                            class="flex items-center justify-between p-3 rounded-xl bg-rose-50/50 border border-rose-100">
                            <div>
                                <p class="text-xs font-bold text-slate-800">{{ item.nama }}</p>
                                <p class="text-[11px] text-rose-600 font-medium">Kode: {{ item.kode }} · 0 Form Disubmit</p>
                            </div>
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-rose-100 text-rose-700">
                                Belum Mengisi
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Indikator Di Bawah Target Se-RS -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            Daftar Indikator Di Bawah Target (Perlu Intervensi / PDSA)
                        </h3>
                        <p class="text-xs text-slate-500">Indikator dengan capaian zona merah yang memerlukan pemantauan manajemen eksekutif</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700">
                        {{ indikatorMerah.length }} Indikator Kritis
                    </span>
                </div>

                <div v-if="indikatorMerah.length === 0" class="py-10 text-center text-xs text-slate-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="font-bold text-slate-700 text-sm">Semua Indikator RS Memenuhi Target!</p>
                    <p class="text-slate-400 mt-0.5">Tidak ada indikator mutu berstatus merah pada periode ini.</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto">
                    <div v-for="item in indikatorMerah" :key="item.id"
                        class="p-3.5 rounded-xl bg-rose-50/60 border border-rose-100 flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-200 text-rose-800">{{ item.kode }}</span>
                                <span class="text-[11px] text-slate-500 truncate">{{ item.unit }}</span>
                            </div>
                            <p class="text-xs font-bold text-slate-800 mt-1 line-clamp-1">{{ item.indicator }}</p>
                            <p class="text-xs text-rose-700 font-semibold mt-1">
                                Capaian: {{ item.capaian }}% <span class="text-slate-400 font-normal">/ Target: {{ item.operator }} {{ item.target }}%</span>
                            </p>
                        </div>
                        <div>
                            <span v-if="item.hasPdsa" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 whitespace-nowrap">
                                PDSA Aktif ✓
                            </span>
                            <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 whitespace-nowrap">
                                Belum Ada PDSA
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CASE 2: KOMITE MUTU & SUPERADMIN ACTIONS & SUBMISSION TABLE -->
        <div v-else-if="userRole === 'komite_mutu' || userRole === 'superadmin'" class="space-y-6">

            <!-- Tabel Pemantauan Input 17 Unit Kerja -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Tabel Pemantauan Kepatuhan Entri Data Unit (17 Unit)
                        </h3>
                        <p class="text-xs text-slate-500">Supervisi pengisian dan status validasi data indikator seluruh unit kerja</p>
                    </div>

                    <!-- Filter Tabs & Search -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <input v-model="unitSearch" type="text" placeholder="Cari nama unit..."
                                class="text-xs pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 focus:ring-blue-500 focus:border-blue-500 w-44">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>

                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-xs">
                            <button @click="unitStatusFilter = 'all'"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="unitStatusFilter === 'all' ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-800'">
                                Semua ({{ unitSubmissions.length }})
                            </button>
                            <button @click="unitStatusFilter = 'belum'"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="unitStatusFilter === 'belum' ? 'bg-white shadow text-rose-700 font-bold' : 'text-slate-500 hover:text-rose-700'">
                                Belum Isi ({{ stats.unitBelum }})
                            </button>
                            <button @click="unitStatusFilter = 'pending'"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="unitStatusFilter === 'pending' ? 'bg-white shadow text-sky-700 font-bold' : 'text-slate-500 hover:text-sky-700'">
                                Menunggu ({{ stats.unitPending }})
                            </button>
                            <button @click="unitStatusFilter = 'lengkap'"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="unitStatusFilter === 'lengkap' ? 'bg-white shadow text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-700'">
                                Lengkap ({{ stats.unitLengkap }})
                            </button>
                            <button @click="unitStatusFilter = 'exempt'"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="unitStatusFilter === 'exempt' ? 'bg-white shadow text-teal-700 font-bold' : 'text-slate-500 hover:text-teal-700'">
                                Bebas Validasi ({{ stats.unitExempt }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <th class="py-2.5 px-3 font-semibold">Kode</th>
                                <th class="py-2.5 px-3 font-semibold">Nama Unit Kerja</th>
                                <th class="py-2.5 px-3 font-semibold text-center">Total Entri</th>
                                <th class="py-2.5 px-3 font-semibold text-center">Status Pengisian</th>
                                <th class="py-2.5 px-3 font-semibold text-center">Rata-rata Capaian</th>
                                <th class="py-2.5 px-3 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="u in filteredUnitSubmissions" :key="u.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-700">{{ u.kode }}</td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800">{{ u.nama }}</td>
                                <td class="py-2.5 px-3 text-center text-slate-600">
                                    <span class="font-bold text-slate-800">{{ u.approved }}</span> Disetujui
                                    <span v-if="u.exempt > 0" class="text-teal-600 font-semibold">({{ u.exempt }} Bebas Validasi)</span>
                                    <span v-if="u.submitted > 0" class="text-sky-600 font-semibold">({{ u.submitted }} Pending)</span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="u.status === 'lengkap'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Lengkap & Valid
                                    </span>
                                    <span v-else-if="u.status === 'pending'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-100 text-sky-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span> Menunggu Validasi
                                    </span>
                                    <span v-else-if="u.status === 'exempt'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-100 text-teal-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-teal-600"></span> Bebas Validasi
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> Belum Mengisi
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center font-extrabold" :class="u.avg >= 80 ? 'text-emerald-700' : u.avg > 0 ? 'text-amber-700' : 'text-slate-400'">
                                    {{ u.approved + u.exempt > 0 ? u.avg + '%' : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <Link :href="route('validation.index')" class="text-blue-600 hover:text-blue-800 font-bold hover:underline">
                                        Validasi &rarr;
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Row: Indikator Merah & Quick Management -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Indikator Merah -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                                Indikator Di Bawah Target (Bulan Ini)
                            </h3>
                            <p class="text-xs text-slate-500">Indikator dengan hasil tidak memenuhi standar yang perlu ditindaklanjuti</p>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700">
                            {{ indikatorMerah.length }} Item
                        </span>
                    </div>

                    <div v-if="indikatorMerah.length === 0" class="py-8 text-center text-xs text-slate-500">
                        <svg class="w-10 h-10 mx-auto mb-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Semua indikator pada periode ini memenuhi standar target.
                    </div>
                    <div v-else class="space-y-2.5 max-h-64 overflow-y-auto">
                        <div v-for="item in indikatorMerah" :key="item.id"
                            class="p-3 rounded-xl bg-rose-50/60 border border-rose-100 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-200 text-rose-800">{{ item.kode }}</span>
                                    <span class="text-xs font-bold text-slate-800 truncate">{{ item.indicator }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">
                                    Unit: <strong>{{ item.unit }}</strong> · Capaian: <strong class="text-rose-700">{{ item.capaian }}%</strong> (Target: {{ item.operator }} {{ item.target }}%)
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link v-if="!item.hasPdsa" :href="route('pdsa.create')" class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition-all whitespace-nowrap">
                                    + Buat PDSA
                                </Link>
                                <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                    PDSA Ada ✓
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Shortcuts -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1">Aksi Cepat Manajemen Mutu</h3>
                        <p class="text-xs text-slate-500 mb-4">Pintasan tugas prioritas harian komite mutu</p>

                        <div class="space-y-2.5">
                            <Link :href="route('validation.index')"
                                class="flex items-center justify-between p-3 rounded-xl bg-sky-50 border border-sky-100 hover:bg-sky-100 transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-sky-600 text-white flex items-center justify-center font-bold text-xs">
                                        {{ stats.pendingValidasi }}
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">Verifikasi & Validasi Data</p>
                                        <p class="text-[11px] text-sky-700">Entri baru menunggu disetujui</p>
                                    </div>
                                </div>
                                <span class="text-sky-600 font-bold">&rarr;</span>
                            </Link>

                            <Link :href="route('pdsa.index')"
                                class="flex items-center justify-between p-3 rounded-xl bg-amber-50 border border-amber-100 hover:bg-amber-100 transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center font-bold text-xs">
                                        {{ stats.pdsaPending }}
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">Review Siklus PDSA</p>
                                        <p class="text-[11px] text-amber-700">Rencana perbaikan unit kerja</p>
                                    </div>
                                </div>
                                <span class="text-amber-600 font-bold">&rarr;</span>
                            </Link>

                            <Link :href="route('reports.index')"
                                class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200 hover:bg-slate-100 transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-slate-700 text-white flex items-center justify-center font-bold text-xs">
                                        📊
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">Laporan Eksekutif Mutu</p>
                                        <p class="text-[11px] text-slate-500">Cetak & ekspor rekap periode</p>
                                    </div>
                                </div>
                                <span class="text-slate-600 font-bold">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-400 text-center">
                        Sistem Terintegrasi MUTU RSYS · Versi 1.0
                    </div>
                </div>

            </div>
        </div>

        <!-- CASE 3: KEPALA UNIT & PIC UNIT ACTIONS & TO-DO LIST -->
        <div v-else class="space-y-6">

            <!-- WIDGET TO-DO LIST: INDIKATOR YANG BELUM DIISI BULAN INI -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                            📝
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Checklist Tugas: Indikator Yang Belum Diinput Bulan Ini</h3>
                            <p class="text-xs text-slate-500">Daftar form pengumpul data (tally sheet) unit Anda yang belum dilengkapi pada periode {{ selectedBulanLabel }} {{ tahun }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full"
                        :class="unfilledList.length === 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                        {{ unfilledList.length }} Form Belum Diisi
                    </span>
                </div>

                <!-- State: All Done Celebration -->
                <div v-if="unfilledList.length === 0" class="py-8 px-4 rounded-xl bg-emerald-50/60 border border-emerald-100 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                        🎉
                    </div>
                    <h4 class="text-sm font-bold text-emerald-900">Luar Biasa! Semua Indikator Unit Telah Diinput</h4>
                    <p class="text-xs text-emerald-700 mt-1 max-w-md mx-auto">
                        Seluruh form indikator mutu unit kerja Anda telah berhasil diinput untuk periode ini dan sedang dalam proses peninjauan / validasi oleh Komite Mutu.
                    </p>
                </div>

                <!-- State: There are pending unsubmitted forms -->
                <div v-else class="space-y-3">
                    <div v-for="item in unfilledList" :key="item.id"
                        class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 hover:border-blue-300 hover:bg-blue-50/30 transition-all flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">{{ item.kode }}</span>
                                <span class="text-[11px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md">Belum Ada Data</span>
                            </div>
                            <p class="text-xs font-bold text-slate-800">{{ item.judul }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Target Standar: <strong>{{ item.operator }} {{ item.target }}%</strong>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <Link :href="route('data-entry.index')"
                                class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Input Data Sekarang
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RINCIAN SELURUH INDIKATOR UNIT KERJA -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Daftar Lengkap Indikator Unit Kerja: {{ unit.nama }}
                        </h3>
                        <p class="text-xs text-slate-500">Status pencapaian dan tindak lanjut perbaikan seluruh indikator unit</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ indikatorDetailList.length }} Indikator
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <th class="py-2.5 px-3 font-semibold">Kode</th>
                                <th class="py-2.5 px-3 font-semibold">Nama Indikator Mutu</th>
                                <th class="py-2.5 px-3 font-semibold text-center">Target</th>
                                <th class="py-2.5 px-3 font-semibold text-center">Capaian Unit</th>
                                <th class="py-2.5 px-3 font-semibold text-center">Status Form</th>
                                <th class="py-2.5 px-3 font-semibold text-right">Tindak Lanjut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in indikatorDetailList" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-700">{{ item.kode }}</td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800 max-w-xs truncate">{{ item.judul }}</td>
                                <td class="py-2.5 px-3 text-center text-slate-600">{{ item.operator }} {{ item.target }}%</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="item.capaian !== null" class="font-bold px-2 py-0.5 rounded-full text-xs"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': item.status_color === 'green',
                                            'bg-amber-100 text-amber-800': item.status_color === 'yellow',
                                            'bg-rose-100 text-rose-800': item.status_color === 'red',
                                            'bg-sky-100 text-sky-800': item.status_color === 'blue',
                                        }">
                                        {{ item.capaian }}%
                                    </span>
                                    <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="item.status_entry === 'approved'" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                                        Disetujui ✓
                                    </span>
                                    <span v-else-if="item.status_entry === 'exempt'" class="text-[11px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">
                                        Bebas Validasi
                                    </span>
                                    <span v-else-if="item.status_entry === 'submitted'" class="text-[11px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-100">
                                        Menunggu Validasi
                                    </span>
                                    <span v-else-if="item.status_entry === 'draft'" class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-100">
                                        Draft
                                    </span>
                                    <span v-else class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-100">
                                        Belum Diisi
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link v-if="item.status_entry === 'belum'" :href="route('data-entry.index')"
                                            class="text-blue-600 hover:text-blue-800 font-bold hover:underline">
                                            + Input Data
                                        </Link>
                                        <Link v-else-if="item.entry_id" :href="route('data-entry.show', item.entry_id)"
                                            class="text-slate-600 hover:text-slate-900 font-medium hover:underline">
                                            Lihat Form
                                        </Link>

                                        <Link v-if="item.status_color === 'red' && !item.has_pdsa" :href="route('pdsa.create')"
                                            class="ml-2 text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-600 text-white shadow-xs">
                                            Buat PDSA
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </AppLayout>
</template>
