<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tahun: Number,
    selectedKode: String,
    availableCodes: Array,
    indicatorsData: Object,
    currentIndicator: Object,
});

const activeYear = ref(props.tahun || 2026);
const activeKode = ref(props.selectedKode || 'IMP-RS 1');

const changeIndicator = (kode) => {
    activeKode.value = kode;
    router.get(route('hospital.analysis.index'), {
        tahun: activeYear.value,
        kode: kode,
    }, { preserveScroll: true, replace: true });
};

const changeYear = () => {
    router.get(route('hospital.analysis.index'), {
        tahun: activeYear.value,
        kode: activeKode.value,
    }, { preserveScroll: true, replace: true });
};

// Hitung koordinat SVG untuk Run Chart
const chartPoints = computed(() => {
    const list = props.currentIndicator.bulanan || [];
    const validMonths = list.filter(m => m.capaian !== null);
    if (!validMonths.length) return '';

    const width = 800;
    const height = 240;
    const padding = 40;
    const stepX = (width - padding * 2) / 11;

    return list.map((m, i) => {
        if (m.capaian === null) return null;
        const x = padding + i * stepX;
        // Skala 0 - 100%
        const y = height - padding - (m.capaian / 100) * (height - padding * 2);
        return { x, y, capaian: m.capaian, bulan: m.bulan };
    }).filter(p => p !== null);
});

const polylinePoints = computed(() => {
    return chartPoints.value.map(p => `${p.x},${p.y}`).join(' ');
});

const targetY = computed(() => {
    const height = 240;
    const padding = 40;
    const target = props.currentIndicator.standar || 80;
    return height - padding - (target / 100) * (height - padding * 2);
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Data & Analisa Mutu Rumah Sakit (Run Chart)</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Pemantauan tren bulanan indikator prioritas RS & evaluasi pencapaian target KARS</p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="activeYear" @change="changeYear" class="text-xs font-semibold border-gray-200 rounded-lg px-3 py-1.5 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">Tahun {{ y }}</option>
                    </select>
                    <Link :href="route('hospital.pdsa.index', { tahun: activeYear })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        RTL & PDSA RS
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Selector Tab IMP-RS -->
            <div class="flex flex-wrap items-center gap-2 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                <button
                    v-for="(ind, k) in indicatorsData"
                    :key="k"
                    type="button"
                    @click="changeIndicator(k)"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5',
                        activeKode === k ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                    ]"
                >
                    <span>{{ k }}</span>
                    <span :class="['w-2 h-2 rounded-full', ind.capaian_tahunan >= ind.standar ? 'bg-emerald-400' : 'bg-rose-400']"></span>
                </button>
            </div>

            <!-- Header Info Indikator Aktif -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b pb-4 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-purple-100 text-purple-800 text-xs font-bold rounded-lg">{{ currentIndicator.kode }}</span>
                            <h2 class="text-base font-bold text-gray-900">{{ currentIndicator.judul }}</h2>
                        </div>
                        <p class="text-xs text-purple-700 font-medium mt-1">{{ currentIndicator.fokus_kars }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-[11px] text-gray-400">STANDAR TARGET</p>
                            <p class="text-sm font-bold text-gray-800">{{ currentIndicator.operator }} {{ currentIndicator.standar }}{{ currentIndicator.satuan }}</p>
                        </div>
                        <div class="text-right pl-3 border-l">
                            <p class="text-[11px] text-gray-400">RATA-RATA TAHUNAN</p>
                            <p :class="['text-base font-bold', currentIndicator.capaian_tahunan >= currentIndicator.standar ? 'text-emerald-600' : 'text-rose-600']">
                                {{ currentIndicator.capaian_tahunan }}%
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <p class="text-gray-400 font-medium">UNIT PENGUMPUL DATA</p>
                        <p class="font-bold text-gray-800 mt-0.5">{{ currentIndicator.unit }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">METODE PENGUMPULAN</p>
                        <p class="font-bold text-gray-800 mt-0.5">Retrospektif / Sensus Harian</p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">STATUS PENCAPAIAN</p>
                        <p :class="['font-bold mt-0.5', currentIndicator.capaian_tahunan >= currentIndicator.standar ? 'text-emerald-700' : 'text-rose-700']">
                            {{ currentIndicator.capaian_tahunan >= currentIndicator.standar ? 'Memenuhi Standar' : 'Belum Memenuhi Standar' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">PERIODE LAPORAN</p>
                        <p class="font-bold text-gray-800 mt-0.5">Bulanan (Tahun {{ tahun }})</p>
                    </div>
                </div>
            </div>

            <!-- Run Chart Visual (SVG) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">Grafik Run Chart Tren Capaian Bulanan</h3>
                        <p class="text-xs text-gray-500">Garis ungu menunjukkan realisasi bulanan, garis merah putus-putus menunjukkan target standar KARS</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 bg-purple-600 rounded-full inline-block"></span>
                            <span class="text-gray-700">Realisasi Bulanan</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-0.5 border-t-2 border-dashed border-rose-500 inline-block"></span>
                            <span class="text-gray-700">Standar ({{ currentIndicator.standar }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- SVG Area -->
                <div class="w-full overflow-x-auto">
                    <svg viewBox="0 0 800 240" class="w-full h-64 bg-slate-50/50 rounded-xl border border-gray-100">
                        <!-- Grid Lines -->
                        <line x1="40" y1="40" x2="760" y2="40" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="40" y1="80" x2="760" y2="80" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="40" y1="120" x2="760" y2="120" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="40" y1="160" x2="760" y2="160" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="40" y1="200" x2="760" y2="200" stroke="#cbd5e1" stroke-width="1" />

                        <!-- Y Axis Labels -->
                        <text x="10" y="45" font-size="10" fill="#94a3b8">100%</text>
                        <text x="15" y="85" font-size="10" fill="#94a3b8">75%</text>
                        <text x="15" y="125" font-size="10" fill="#94a3b8">50%</text>
                        <text x="15" y="165" font-size="10" fill="#94a3b8">25%</text>
                        <text x="20" y="205" font-size="10" fill="#94a3b8">0%</text>

                        <!-- Standar Line (Target) -->
                        <line x1="40" :y1="targetY" x2="760" :y2="targetY" stroke="#f43f5e" stroke-width="2" stroke-dasharray="6,4" />

                        <!-- Data Polyline -->
                        <polyline
                            v-if="polylinePoints"
                            fill="none"
                            stroke="#7c3aed"
                            stroke-width="3"
                            :points="polylinePoints"
                        />

                        <!-- Data Dots & Labels -->
                        <g v-for="pt in chartPoints" :key="pt.bulan">
                            <circle :cx="pt.x" :cy="pt.y" r="5" fill="#7c3aed" stroke="#ffffff" stroke-width="2" />
                            <text :cx="pt.x" :x="pt.x" :y="pt.y - 10" text-anchor="middle" font-size="10" font-weight="bold" fill="#4c1d95">
                                {{ pt.capaian }}%
                            </text>
                        </g>

                        <!-- X Axis Month Labels -->
                        <g v-for="(m, idx) in currentIndicator.bulanan" :key="m.bulan">
                            <text :x="40 + idx * 65.4" y="225" text-anchor="middle" font-size="10" fill="#64748b">
                                {{ m.bulan.substring(0, 3) }}
                            </text>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Tabel Data 12 Bulan -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <h3 class="text-sm font-bold text-gray-800 mb-3">Tabel Numerator & Denominator Bulanan (Januari - Desember)</h3>
                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-left">Bulan</th>
                            <th class="p-2 border text-center">Standar</th>
                            <th class="p-2 border text-center">Numerator (N)</th>
                            <th class="p-2 border text-center">Denominator (D)</th>
                            <th class="p-2 border text-center bg-purple-50 font-bold">Capaian (%)</th>
                            <th class="p-2 border text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="b in currentIndicator.bulanan" :key="b.bulan" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border font-bold text-gray-800">{{ b.bulan }}</td>
                            <td class="p-2 border text-center font-medium">{{ currentIndicator.operator }} {{ b.target }}%</td>
                            <td class="p-2 border text-center font-medium">{{ b.num > 0 ? b.num : '-' }}</td>
                            <td class="p-2 border text-center font-medium">{{ b.den > 0 ? b.den : '-' }}</td>
                            <td class="p-2 border text-center font-bold bg-purple-50/50 text-purple-900">
                                {{ b.capaian !== null ? b.capaian + '%' : '-' }}
                            </td>
                            <td class="p-2 border text-center">
                                <span v-if="b.capaian !== null" :class="['px-2 py-0.5 rounded text-[11px] font-bold', b.status === 'hijau' ? 'bg-emerald-100 text-emerald-800' : (b.status === 'kuning' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')]">
                                    {{ b.status === 'hijau' ? 'Tercapai' : (b.status === 'kuning' ? 'Mendekati' : 'Belum') }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Analisa Naratif Triwulan & Rekomendasi -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-2">
                    <div class="flex items-center gap-2 text-indigo-700 font-bold text-xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        ANALISA TRIWULAN I
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed">{{ currentIndicator.analisa_tw1 }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-2">
                    <div class="flex items-center gap-2 text-indigo-700 font-bold text-xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        ANALISA TRIWULAN II
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed">{{ currentIndicator.analisa_tw2 }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-2">
                    <div class="flex items-center gap-2 text-indigo-700 font-bold text-xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        ANALISA TRIWULAN III
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed">{{ currentIndicator.analisa_tw3 }}</p>
                </div>
            </div>

            <!-- Rekomendasi Tindak Lanjut Komite Mutu -->
            <div class="bg-gradient-to-r from-purple-900 to-indigo-900 rounded-xl p-6 text-white shadow-md">
                <h4 class="text-xs font-bold uppercase tracking-wider text-purple-200 mb-1">Rekomendasi Strategis Komite Mutu & Direksi</h4>
                <p class="text-sm font-medium leading-relaxed">{{ currentIndicator.rekomendasi }}</p>
            </div>
        </div>
    </AppLayout>
</template>
