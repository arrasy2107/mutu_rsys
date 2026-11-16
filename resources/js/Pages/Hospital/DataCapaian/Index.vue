<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    kategori: String,
    rentang: String,
    tahun: Number,
    counts: Object,
    indicators: Array,
    activeIndicator: Object,
    analysisData: Object,
});

const selectedKategori = ref(props.kategori || 'IMP-RS');
const selectedRentang = ref(props.rentang || 'bulanan');
const selectedIndicatorId = ref(props.activeIndicator?.id || '');

const switchKategori = (kat) => {
    selectedKategori.value = kat;
    router.get(route('hospital.data-capaian.index'), {
        kategori: kat,
        rentang: selectedRentang.value,
        tahun: props.tahun,
    }, { preserveState: false });
};

const switchRentang = (rng) => {
    selectedRentang.value = rng;
    router.get(route('hospital.data-capaian.index'), {
        kategori: selectedKategori.value,
        rentang: rng,
        tahun: props.tahun,
        indicator_id: selectedIndicatorId.value,
    }, { preserveState: true });
};

const changeIndicator = () => {
    router.get(route('hospital.data-capaian.index'), {
        kategori: selectedKategori.value,
        rentang: selectedRentang.value,
        tahun: props.tahun,
        indicator_id: selectedIndicatorId.value,
    }, { preserveState: true });
};

// Compute aggregation according to selected time view (Bulanan, Triwulan, Semester, Tahunan)
const aggregatedPeriods = computed(() => {
    if (!props.analysisData?.bulanan) return [];
    const b = props.analysisData.bulanan;
    const std = props.analysisData.standar;

    if (selectedRentang.value === 'triwulan') {
        const quarters = [
            { label: 'Triwulan I (Jan-Mar)', months: [b[0], b[1], b[2]] },
            { label: 'Triwulan II (Apr-Jun)', months: [b[3], b[4], b[5]] },
            { label: 'Triwulan III (Jul-Sep)', months: [b[6], b[7], b[8]] },
            { label: 'Triwulan IV (Okt-Des)', months: [b[9], b[10], b[11]] },
        ];
        return quarters.map((q, idx) => {
            const num = q.months.reduce((acc, m) => acc + (m?.num || 0), 0);
            const den = q.months.reduce((acc, m) => acc + (m?.den || 0), 0);
            const validCaps = q.months.filter(m => m?.capaian !== null);
            const cap = den > 0 ? (num / den) * 100 : (validCaps.length > 0 ? validCaps.reduce((a, m) => a + m.capaian, 0) / validCaps.length : null);
            return {
                label: q.label,
                target: std,
                num: num,
                den: den,
                capaian: cap !== null ? Math.round(cap * 100) / 100 : null,
                status: cap === null ? 'gray' : (cap >= std ? 'hijau' : (cap >= std * 0.8 ? 'kuning' : 'merah')),
            };
        });
    }

    if (selectedRentang.value === 'semester') {
        const semesters = [
            { label: 'Semester I (Jan-Jun)', months: b.slice(0, 6) },
            { label: 'Semester II (Jul-Des)', months: b.slice(6, 12) },
        ];
        return semesters.map((s) => {
            const num = s.months.reduce((acc, m) => acc + (m?.num || 0), 0);
            const den = s.months.reduce((acc, m) => acc + (m?.den || 0), 0);
            const validCaps = s.months.filter(m => m?.capaian !== null);
            const cap = den > 0 ? (num / den) * 100 : (validCaps.length > 0 ? validCaps.reduce((a, m) => a + m.capaian, 0) / validCaps.length : null);
            return {
                label: s.label,
                target: std,
                num: num,
                den: den,
                capaian: cap !== null ? Math.round(cap * 100) / 100 : null,
                status: cap === null ? 'gray' : (cap >= std ? 'hijau' : (cap >= std * 0.8 ? 'kuning' : 'merah')),
            };
        });
    }

    if (selectedRentang.value === 'tahunan') {
        const num = b.reduce((acc, m) => acc + (m?.num || 0), 0);
        const den = b.reduce((acc, m) => acc + (m?.den || 0), 0);
        const cap = props.analysisData.capaian_tahunan;
        return [{
            label: 'Tahun Kumulatif ' + props.tahun,
            target: std,
            num: num,
            den: den,
            capaian: cap,
            status: cap === null ? 'gray' : (cap >= std ? 'hijau' : (cap >= std * 0.8 ? 'kuning' : 'merah')),
        }];
    }

    // Default: Bulanan
    return b.map(m => ({
        label: m.bulan,
        target: m.target,
        num: m.num,
        den: m.den,
        capaian: m.capaian,
        status: m.status,
    }));
});

// SVG Run Chart Points
const chartPoints = computed(() => {
    const data = aggregatedPeriods.value;
    if (!data.length) return '';
    const maxVal = 100;
    const chartHeight = 160;
    const chartWidth = 700;
    const stepX = chartWidth / (data.length > 1 ? (data.length - 1) : 1);

    return data
        .map((p, idx) => {
            if (p.capaian === null) return null;
            const x = 50 + idx * stepX;
            const y = 180 - (p.capaian / maxVal) * chartHeight;
            return `${x},${y}`;
        })
        .filter(Boolean)
        .join(' ');
});

const targetY = computed(() => {
    const std = props.analysisData?.standar || 80;
    return 180 - (std / 100) * 160;
});
</script>

<template>
    <AppLayout>
        <Head title="Data Capaian Tingkat Rumah Sakit" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold uppercase tracking-wider backdrop-blur-sm mb-2">
                            <span>📊 Butir B.2 Roadmap Mutu</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Tingkat Rumah Sakit (Otomatis dari Unit)</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">Data Capaian Mutu Rumah Sakit</h1>
                        <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                            Visualisasi capaian indikator mutu terintegrasi lintas unit (Zero Duplicate Data Entry) dengan dukungan filter waktu Bulanan, Triwulan, Semester, dan Tahunan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 4 Kategori Tabs (Butir B.2.a, B.2.b, B.2.c, B.2.d Roadmap) -->
            <div class="bg-white rounded-xl p-2 border border-gray-200 shadow-sm flex flex-wrap gap-2">
                <button
                    @click="switchKategori('IMP-RS')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        selectedKategori === 'IMP-RS' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>⭐ a. IMP-RS (Prioritas RS)</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', selectedKategori === 'IMP-RS' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-800']">
                        {{ counts['IMP-RS'] || 0 }}
                    </span>
                </button>

                <button
                    @click="switchKategori('IMN')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        selectedKategori === 'IMN' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>🇮🇩 b. IMN (Nasional)</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', selectedKategori === 'IMN' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-800']">
                        {{ counts['IMN'] || 0 }}
                    </span>
                </button>

                <button
                    @click="switchKategori('IMP-U')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        selectedKategori === 'IMP-U' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>🏥 c. IMP-U (Prioritas Unit)</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', selectedKategori === 'IMP-U' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800']">
                        {{ counts['IMP-U'] || 0 }}
                    </span>
                </button>

                <button
                    @click="switchKategori('SPM')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        selectedKategori === 'SPM' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>📋 d. SPM (Standar Minimal)</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', selectedKategori === 'SPM' ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-800']">
                        {{ counts['SPM'] || 0 }}
                    </span>
                </button>
            </div>

            <!-- Controls: Filter Waktu & Indicator Selector -->
            <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
                <!-- Filter Waktu (Bulanan, Triwulan, Semester, Tahunan) -->
                <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-xl w-full md:w-auto">
                    <button
                        @click="switchRentang('bulanan')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', selectedRentang === 'bulanan' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        📅 Bulanan
                    </button>
                    <button
                        @click="switchRentang('triwulan')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', selectedRentang === 'triwulan' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        📊 Triwulan
                    </button>
                    <button
                        @click="switchRentang('semester')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', selectedRentang === 'semester' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        📈 Semester
                    </button>
                    <button
                        @click="switchRentang('tahunan')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', selectedRentang === 'tahunan' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        🏆 Tahunan
                    </button>
                </div>

                <!-- Indicator Picker -->
                <div class="w-full md:w-96">
                    <select
                        v-model="selectedIndicatorId"
                        @change="changeIndicator"
                        class="w-full text-xs font-medium rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 bg-white"
                    >
                        <option v-for="ind in indicators" :key="ind.id" :value="ind.id">
                            [{{ ind.kode }}] {{ ind.judul }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Indicator Profile Header & Stat Cards -->
            <div v-if="analysisData" class="space-y-6">
                <!-- Info Header -->
                <div class="bg-white rounded-xl p-5 border border-gray-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800">
                                {{ analysisData.kode }}
                            </span>
                            <span class="text-xs text-gray-500 font-medium">
                                {{ analysisData.fokus_kars }}
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 mt-1.5">
                            {{ analysisData.judul }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Unit Penanggung Jawab: <strong class="text-gray-700">{{ analysisData.unit }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-4 border-t md:border-t-0 md:border-l border-gray-100 pt-3 md:pt-0 md:pl-6">
                        <div class="text-right">
                            <p class="text-[11px] text-gray-400 font-medium uppercase">Standar Target</p>
                            <p class="text-xl font-extrabold text-blue-600">
                                {{ analysisData.operator }} {{ analysisData.standar }}{{ analysisData.satuan }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] text-gray-400 font-medium uppercase">Rata-rata {{ tahun }}</p>
                            <p :class="['text-xl font-extrabold', analysisData.capaian_tahunan >= analysisData.standar ? 'text-emerald-600' : 'text-red-600']">
                                {{ analysisData.capaian_tahunan }}{{ analysisData.satuan }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SVG Trend Chart -->
                <div class="bg-white rounded-xl p-6 border border-gray-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">
                                Run Chart Tren Capaian (Tampilan: {{ selectedRentang.toUpperCase() }})
                            </h3>
                            <p class="text-xs text-gray-500">Garis putus-putus merah menandakan batas target standar rumah sakit</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-0.5 bg-blue-600"></span>
                                <span class="text-gray-600">Capaian Aktual</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-0.5 border-t-2 border-dashed border-red-500"></span>
                                <span class="text-gray-600">Standar ({{ analysisData.standar }}%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- SVG Chart Container -->
                    <div class="w-full overflow-x-auto">
                        <svg viewBox="0 0 800 230" class="w-full h-56 select-none">
                            <!-- Background Grid -->
                            <line x1="50" y1="20" x2="760" y2="20" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="60" x2="760" y2="60" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="100" x2="760" y2="100" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="140" x2="760" y2="140" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="180" x2="760" y2="180" stroke="#cbd5e1" stroke-width="1" />

                            <!-- Y-Axis Labels -->
                            <text x="35" y="24" class="text-[10px] fill-gray-400 font-mono" text-anchor="end">100%</text>
                            <text x="35" y="64" class="text-[10px] fill-gray-400 font-mono" text-anchor="end">75%</text>
                            <text x="35" y="104" class="text-[10px] fill-gray-400 font-mono" text-anchor="end">50%</text>
                            <text x="35" y="144" class="text-[10px] fill-gray-400 font-mono" text-anchor="end">25%</text>
                            <text x="35" y="184" class="text-[10px] fill-gray-400 font-mono" text-anchor="end">0%</text>

                            <!-- Target Line (Red Dashed) -->
                            <line
                                x1="50"
                                :y1="targetY"
                                x2="760"
                                :y2="targetY"
                                stroke="#ef4444"
                                stroke-width="1.5"
                                stroke-dasharray="4,4"
                            />

                            <!-- Polyline Line Chart -->
                            <polyline
                                v-if="chartPoints"
                                fill="none"
                                stroke="#2563eb"
                                stroke-width="2.5"
                                :points="chartPoints"
                            />

                            <!-- Data Points and Values -->
                            <g v-for="(p, idx) in aggregatedPeriods" :key="idx">
                                <g v-if="p.capaian !== null">
                                    <circle
                                        :cx="50 + idx * (700 / (aggregatedPeriods.length > 1 ? aggregatedPeriods.length - 1 : 1))"
                                        :cy="180 - (p.capaian / 100) * 160"
                                        r="4.5"
                                        :fill="p.capaian >= analysisData.standar ? '#10b981' : (p.capaian >= analysisData.standar * 0.8 ? '#f59e0b' : '#ef4444')"
                                        stroke="#ffffff"
                                        stroke-width="2"
                                    />
                                    <text
                                        :x="50 + idx * (700 / (aggregatedPeriods.length > 1 ? aggregatedPeriods.length - 1 : 1))"
                                        :y="180 - (p.capaian / 100) * 160 - 8"
                                        text-anchor="middle"
                                        class="text-[9px] font-bold fill-gray-800"
                                    >
                                        {{ p.capaian }}%
                                    </text>
                                </g>
                                <!-- X-Axis Period Label -->
                                <text
                                    :x="50 + idx * (700 / (aggregatedPeriods.length > 1 ? aggregatedPeriods.length - 1 : 1))"
                                    y="200"
                                    text-anchor="middle"
                                    class="text-[9px] fill-gray-500 font-medium"
                                >
                                    {{ p.label }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- Detailed Table for Selected Periods -->
                <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                            Rincian Capaian Mutu Periode (Numerator / Denominator)
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b">
                                <tr>
                                    <th class="py-2.5 px-4">Periode</th>
                                    <th class="py-2.5 px-4 text-center">Standar Target</th>
                                    <th class="py-2.5 px-4 text-center">Numerator (N)</th>
                                    <th class="py-2.5 px-4 text-center">Denominator (D)</th>
                                    <th class="py-2.5 px-4 text-center">Hasil Capaian</th>
                                    <th class="py-2.5 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(row, rIdx) in aggregatedPeriods" :key="rIdx" class="hover:bg-blue-50/40">
                                    <td class="py-2.5 px-4 font-semibold text-gray-800">{{ row.label }}</td>
                                    <td class="py-2.5 px-4 text-center text-gray-600">{{ row.target }}%</td>
                                    <td class="py-2.5 px-4 text-center font-mono">{{ row.num }}</td>
                                    <td class="py-2.5 px-4 text-center font-mono">{{ row.den }}</td>
                                    <td class="py-2.5 px-4 text-center font-bold">
                                        <span v-if="row.capaian !== null" :class="row.capaian >= row.target ? 'text-emerald-600' : 'text-red-600'">
                                            {{ row.capaian }}%
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span v-if="row.status === 'hijau'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            Tercapai
                                        </span>
                                        <span v-else-if="row.status === 'kuning'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Mendekati
                                        </span>
                                        <span v-else-if="row.status === 'merah'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                            Belum Tercapai
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-500">
                                            Belum Terisi
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quarterly Narrative & Recommendations -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl p-5 border border-gray-200/80 shadow-sm space-y-3">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <span>📝</span>
                            <span>Analisa Evaluasi Triwulan</span>
                        </h4>
                        <div class="text-xs space-y-2 text-gray-700">
                            <div v-if="analysisData.analisa_tw1" class="p-2.5 rounded-lg bg-gray-50 border border-gray-100">
                                <strong class="text-blue-900 block mb-0.5">Triwulan I:</strong>
                                {{ analysisData.analisa_tw1 }}
                            </div>
                            <div v-if="analysisData.analisa_tw2" class="p-2.5 rounded-lg bg-gray-50 border border-gray-100">
                                <strong class="text-blue-900 block mb-0.5">Triwulan II:</strong>
                                {{ analysisData.analisa_tw2 }}
                            </div>
                            <div v-if="analysisData.analisa_tw3" class="p-2.5 rounded-lg bg-gray-50 border border-gray-100">
                                <strong class="text-blue-900 block mb-0.5">Triwulan III:</strong>
                                {{ analysisData.analisa_tw3 }}
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-5 border border-gray-200/80 shadow-sm space-y-3">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <span>💡</span>
                            <span>Rekomendasi & Rencana Tindak Lanjut</span>
                        </h4>
                        <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100 text-xs text-blue-900 leading-relaxed">
                            {{ analysisData.rekomendasi }}
                        </div>
                        <div class="pt-2">
                            <Link :href="route('hospital.rtl.index', { kategori: selectedKategori })" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <span>Buka Modul RTL Rumah Sakit</span>
                                <span>→</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl p-12 text-center border border-gray-200 text-gray-400">
                <p class="text-base font-semibold text-gray-700">Belum ada indikator mutu untuk kategori {{ selectedKategori }}</p>
                <p class="text-xs text-gray-500 mt-1">Silakan tetapkan indikator mutu melalui menu Pemilihan atau Profil Indikator.</p>
            </div>
        </div>
    </AppLayout>
</template>
