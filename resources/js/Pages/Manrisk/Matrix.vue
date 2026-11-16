<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    total: Number,
    inherentMatrix: Object,
    residualMatrix: Object,
    summary: Object,
    units: Array,
    filters: Object,
});

const activeMode = ref('inherent'); // inherent vs residual
const selectedCell = ref(null);

const tahun = ref(props.filters.tahun || 2026);
const scope = ref(props.filters.scope || 'all');
const unitId = ref(props.filters.unit_id || '');

const applyFilter = (newScope = null) => {
    if (newScope !== null) {
        scope.value = newScope;
    }
    router.get(route('manrisk.matrix'), {
        tahun: tahun.value,
        scope: scope.value,
        unit_id: unitId.value || null,
    }, { preserveState: true, preserveScroll: true });
};

const currentMatrix = computed(() => {
    return activeMode.value === 'inherent' ? props.inherentMatrix : props.residualMatrix;
});

const probabilitasLabels = [
    { level: 5, name: '5 - Hampir Pasti Terjadi', sub: '> 50% / Mingguan' },
    { level: 4, name: '4 - Kemungkinan Besar', sub: '20% - 50% / Bulanan' },
    { level: 3, name: '3 - Kemungkinan Sedang', sub: '10% - 20% / 1-2 Thn' },
    { level: 2, name: '2 - Kemungkinan Kecil', sub: '1% - 10% / 2-5 Thn' },
    { level: 1, name: '1 - Jarang Terjadi', sub: '<= 1% / > 5 Thn' },
];

const dampakLabels = [
    { level: 1, name: '1 - Sangat Rendah', sub: 'Insignificant / Tdk Cedera' },
    { level: 2, name: '2 - Rendah', sub: 'Minor / P3K' },
    { level: 3, name: '3 - Sedang', sub: 'Moderat / Perpanjang Rawat' },
    { level: 4, name: '4 - Tinggi', sub: 'Mayor / Cedera Luas' },
    { level: 5, name: '5 - Sangat Tinggi', sub: 'Katastropik / Fatal' },
];

const getCellData = (p, d) => {
    const key = `${p}_${d}`;
    return currentMatrix.value[key] || { count: 0, band: 'rendah', risks: [], skor: p * d };
};

const cellColor = (band, count) => {
    if (count === 0) {
        switch (band) {
            case 'ekstrem':
            case 'sangat_tinggi': return 'bg-red-50 text-red-700 border-red-200';
            case 'tinggi':        return 'bg-amber-50 text-amber-800 border-amber-200';
            case 'sedang':        return 'bg-yellow-50 text-yellow-900 border-yellow-200';
            case 'rendah':        return 'bg-emerald-50 text-emerald-800 border-emerald-200';
            default:              return 'bg-blue-50 text-blue-800 border-blue-200';
        }
    }

    switch (band) {
        case 'ekstrem':
        case 'sangat_tinggi': return 'bg-red-600 text-white border-red-700 shadow-sm';
        case 'tinggi':        return 'bg-amber-500 text-white border-amber-600 shadow-sm';
        case 'sedang':        return 'bg-yellow-400 text-gray-900 border-yellow-500 shadow-sm font-bold';
        case 'rendah':        return 'bg-emerald-500 text-white border-emerald-600 shadow-sm';
        default:              return 'bg-blue-600 text-white border-blue-700 shadow-sm';
    }
};

const selectCell = (p, d) => {
    const data = getCellData(p, d);
    selectedCell.value = {
        probabilitas: p,
        dampak: d,
        skor: p * d,
        band: data.band,
        count: data.count,
        risks: data.risks,
    };
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-amber-500 to-orange-600 rounded-full inline-block"></span>
                        Matriks & Profil Risiko 5&times;5 (Heatmap RSYS)
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pemetaan visualisasi tingkat risiko rumah sakit standar Panduan RSYS & KARS (Probabilitas vs Dampak)
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('manrisk.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        &larr; Daftar Register Risiko
                    </Link>
                    <Link
                        :href="route('manrisk.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/25 transition"
                    >
                        + Identifikasi Baru
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- ─── SCOPE & TAHUN FILTER BAR ─────────────────────────────────── -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tahun Asesmen</label>
                        <input
                            type="number"
                            v-model="tahun"
                            @change="applyFilter()"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Ruang Lingkup</label>
                        <select
                            v-model="scope"
                            @change="applyFilter()"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 font-bold"
                        >
                            <option value="all">Semua Risiko Terdaftar</option>
                            <option value="rs">Prioritas Rumah Sakit (Top Unit)</option>
                            <option value="kars">8 Pilar Prioritas KARS 2026</option>
                            <option value="unit">Risiko Operasional Unit</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Filter Unit</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter()"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">Semua Unit Kerja (35 Unit)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>
                </div>

                <!-- MODE TABS: INHERENT VS RESIDUAL -->
                <div class="pt-2 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 font-medium">Model Matriks:</span>
                        <div class="inline-flex rounded-xl p-1 bg-gray-100 border border-gray-200 text-xs">
                            <button
                                @click="activeMode = 'inherent'"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg font-bold transition',
                                    activeMode === 'inherent' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900'
                                ]"
                            >
                                1. Risiko Awal (Inherent)
                            </button>
                            <button
                                @click="activeMode = 'residual'"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg font-bold transition',
                                    activeMode === 'residual' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900'
                                ]"
                            >
                                2. Target Residu (Post-Mitigasi)
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs flex-wrap">
                        <span class="text-gray-500">Gradasi RSYS:</span>
                        <span class="px-2 py-0.5 rounded bg-red-600 text-white font-bold text-[10px]">Sangat Tinggi (&gt;19)</span>
                        <span class="px-2 py-0.5 rounded bg-amber-500 text-white font-bold text-[10px]">Tinggi (15-19)</span>
                        <span class="px-2 py-0.5 rounded bg-yellow-400 text-gray-900 font-bold text-[10px]">Sedang (10-14)</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-500 text-white font-bold text-[10px]">Rendah (5-9)</span>
                        <span class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px]">Sangat Rendah (0-4)</span>
                    </div>
                </div>
            </div>

            <!-- ─── GRID 5x5 HEATMAP MATRIX ─────────────────────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-sm font-bold text-gray-800">
                        Peta Kuadran Matriks Risiko 5&times;5 ({{ activeMode === 'inherent' ? 'Risiko Awal' : 'Residu Risiko' }})
                    </h2>
                    <p class="text-xs text-gray-400">Klik kotak untuk menampilkan rincian risiko pada sel tersebut</p>
                </div>

                <div class="overflow-x-auto">
                    <div class="min-w-[700px]">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-48 p-2 text-xs text-left text-gray-400 font-semibold border-b">
                                        Probabilitas \ Dampak
                                    </th>
                                    <th
                                        v-for="d in dampakLabels"
                                        :key="d.level"
                                        class="p-2.5 text-center text-xs font-bold text-gray-700 bg-gray-50/70 border border-gray-200"
                                    >
                                        <div>{{ d.name }}</div>
                                        <div class="text-[9px] text-gray-400 font-normal mt-0.5">{{ d.sub }}</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in probabilitasLabels" :key="p.level">
                                    <td class="p-2.5 text-xs font-bold text-gray-700 bg-gray-50/70 border border-gray-200 whitespace-nowrap">
                                        <div>{{ p.name }}</div>
                                        <div class="text-[9px] text-gray-400 font-normal mt-0.5">{{ p.sub }}</div>
                                    </td>
                                    <td
                                        v-for="d in dampakLabels"
                                        :key="d.level"
                                        @click="selectCell(p.level, d.level)"
                                        :class="[
                                            'p-3 text-center border cursor-pointer transition relative group hover:scale-[1.02] hover:z-10',
                                            cellColor(getCellData(p.level, d.level).band, getCellData(p.level, d.level).count),
                                            selectedCell?.probabilitas === p.level && selectedCell?.dampak === d.level && 'ring-4 ring-blue-500 ring-offset-1'
                                        ]"
                                    >
                                        <div class="flex flex-col items-center justify-center min-h-[55px]">
                                            <span class="text-xs font-mono font-black">
                                                Skor {{ p.level * d.level }}
                                            </span>
                                            <span
                                                v-if="getCellData(p.level, d.level).count > 0"
                                                class="mt-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-black/25 text-white"
                                            >
                                                {{ getCellData(p.level, d.level).count }} risiko
                                            </span>
                                            <span v-else class="text-[10px] opacity-40 mt-1">-</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ─── SELECTED CELL DETAILS ────────────────────────────────────── -->
            <div v-if="selectedCell" class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <span>Daftar Risiko pada Kuadran:</span>
                            <span :class="['px-2.5 py-0.5 rounded-md text-xs font-mono uppercase font-bold', cellColor(selectedCell.band, selectedCell.count)]">
                                Skor {{ selectedCell.skor }} &bull; {{ selectedCell.band }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Probabilitas: Tingkat {{ selectedCell.probabilitas }} &bull; Dampak: Tingkat {{ selectedCell.dampak }} (Total: {{ selectedCell.count }} risiko)
                        </p>
                    </div>
                    <button
                        @click="selectedCell = null"
                        type="button"
                        class="text-xs text-gray-400 hover:text-gray-600 font-bold"
                    >
                        Tutup Panel &times;
                    </button>
                </div>

                <div v-if="selectedCell.count === 0" class="p-6 text-center text-xs text-gray-400 italic">
                    Tidak ada risiko terdaftar pada tingkat kombinasi skor ini.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div
                        v-for="r in selectedCell.risks"
                        :key="r.id"
                        class="py-3.5 flex items-center justify-between hover:bg-amber-50/30 px-3 rounded-xl transition"
                    >
                        <div class="max-w-3xl">
                            <div class="flex items-center gap-2">
                                <Link :href="route('manrisk.show', r.id)" class="text-xs font-bold text-amber-700 hover:underline font-mono">
                                    {{ r.nomor_register || r.kode }}
                                </Link>
                                <span v-if="r.is_rs_priority" class="px-2 py-0.5 text-[9px] rounded-md bg-blue-100 text-blue-800 font-bold">
                                    Prioritas RS
                                </span>
                                <span v-if="r.kategori_kars" class="px-2 py-0.5 text-[9px] rounded-md bg-amber-100 text-amber-800 font-bold">
                                    {{ r.kategori_kars }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-mono">
                                    Bobot: {{ Number(r.bobot || 1).toFixed(2) }} | Skor Akhir: {{ Number(r.skoring_akhir).toFixed(2) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-900 font-bold mt-1">{{ r.kegiatan || r.pernyataan }}</p>
                            <p class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ r.pernyataan }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Pemilik: {{ r.unit }}</p>
                        </div>
                        <Link
                            :href="route('manrisk.show', r.id)"
                            class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition flex-shrink-0"
                        >
                            Lihat Lembar Kerja &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
