<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    assessments: Array,
    categoryStats: Object,
    topHazards: Array,
    units: Array,
    selectedUnit: Object,
    filters: Object,
});

const tahun = ref(props.filters.tahun || 2026);
const scope = ref(props.filters.scope || 'rs');
const unitId = ref(props.filters.unit_id || '');
const kategori = ref(props.filters.kategori || 'all');
const search = ref(props.filters.search || '');

// Quick edit modal state
const isEditModalOpen = ref(false);
const editingItem = ref(null);
const editForm = ref({
    probabilitas: 0,
    dampak_manusia: 0,
    dampak_properti: 0,
    dampak_bisnis: 0,
    kesiapan: 0,
    respon_internal: 0,
    respon_eksternal: 0,
});

const openEditModal = (item) => {
    editingItem.value = item;
    editForm.value = {
        probabilitas: item.probabilitas,
        dampak_manusia: item.dampak_manusia,
        dampak_properti: item.dampak_properti,
        dampak_bisnis: item.dampak_bisnis,
        kesiapan: item.kesiapan,
        respon_internal: item.respon_internal,
        respon_eksternal: item.respon_eksternal,
    };
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingItem.value = null;
};

const computedSeverity = computed(() => {
    return Number(editForm.value.dampak_manusia) +
        Number(editForm.value.dampak_properti) +
        Number(editForm.value.dampak_bisnis) +
        Number(editForm.value.kesiapan) +
        Number(editForm.value.respon_internal) +
        Number(editForm.value.respon_eksternal);
});

const computedRelativeRisk = computed(() => {
    const p = Number(editForm.value.probabilitas);
    if (p <= 0) return 0.0;
    const sev = computedSeverity.value;
    return Number(((p / 3) * (sev / 18) * 100).toFixed(2));
});

const submitEditForm = () => {
    if (!editingItem.value) return;
    router.put(route('hva.update-row', editingItem.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

const applyFilter = (newScope = null, newCat = null) => {
    if (newScope !== null) scope.value = newScope;
    if (newCat !== null) kategori.value = newCat;

    router.get(route('hva.index'), {
        tahun: tahun.value,
        scope: scope.value,
        unit_id: scope.value === 'unit' ? (unitId.value || null) : null,
        kategori: kategori.value,
        search: search.value || null,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilter = () => {
    search.value = '';
    kategori.value = 'all';
    applyFilter();
};

const getCategoryBadgeClass = (cat) => {
    switch (cat) {
        case 'natural':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'technological':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        case 'human':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        case 'hazardous_materials':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

const getCategoryLabel = (cat) => {
    switch (cat) {
        case 'natural': return 'Bencana Alam';
        case 'technological': return 'Teknologi / Utilitas';
        case 'human': return 'Faktor Manusia';
        case 'hazardous_materials': return 'Bahan Berbahaya (B3)';
        default: return cat;
    }
};

const getRiskBadgeClass = (score) => {
    const s = Number(score);
    if (s >= 20.0) return 'bg-red-500 text-white font-bold shadow-xs';
    if (s >= 10.0) return 'bg-orange-500 text-white font-bold shadow-xs';
    if (s >= 5.0) return 'bg-yellow-400 text-slate-900 font-bold shadow-xs';
    if (s > 0.0) return 'bg-emerald-500 text-white font-bold shadow-xs';
    return 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 font-medium';
};

const getRiskLevelLabel = (score) => {
    const s = Number(score);
    if (s >= 20.0) return 'Tinggi';
    if (s >= 10.0) return 'Sedang-Tinggi';
    if (s >= 5.0) return 'Sedang';
    if (s > 0.0) return 'Rendah';
    return 'N/A';
};
</script>

<template>
    <AppLayout title="Hazard Vulnerability Analysis (HVA)">
        <div class="space-y-6 pb-12">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                Modul IV • Kaiser Permanente Standard
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                Tahun {{ tahun }}
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            Hazard Vulnerability Analysis (HVA)
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-2xl">
                            Analisis bahaya, kerentanan, dan dampak kedaruratan bencana rumah sakit untuk penetapan prioritas mitigasi dan kesiapsiagaan darurat (Disaster Plan).
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <Link
                            :href="route('hva.summary', { scope, unit_id: unitId || null, tahun })"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-md transition-all hover:scale-102"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Grafik & Analitik
                        </Link>
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

                <!-- Scope Tabs Switcher -->
                <div class="mt-6 pt-5 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="inline-flex p-1 bg-slate-800/80 rounded-xl border border-slate-700">
                        <button
                            type="button"
                            @click="applyFilter('rs')"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2',
                                scope === 'rs'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Tingkat Rumah Sakit (RSYS)
                        </button>
                        <button
                            type="button"
                            @click="applyFilter('unit')"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2',
                                scope === 'unit'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Tingkat Unit Kerja (35 Unit)
                        </button>
                    </div>

                    <!-- Unit Selector (Shown if scope == unit) -->
                    <div v-if="scope === 'unit'" class="flex items-center gap-2">
                        <label class="text-xs text-slate-300 font-medium">Pilih Unit:</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter()"
                            class="bg-slate-800 text-white text-sm rounded-lg px-3 py-1.5 border border-slate-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        >
                            <option v-for="u in units" :key="u.id" :value="u.id">
                                [{{ u.kode }}] {{ u.nama }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 4 Category Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Natural Hazards -->
                <div
                    @click="applyFilter(null, kategori === 'natural' ? 'all' : 'natural')"
                    :class="[
                        'cursor-pointer rounded-xl p-5 border transition-all hover:shadow-md relative overflow-hidden bg-white dark:bg-gray-800',
                        kategori === 'natural'
                            ? 'border-amber-500 ring-2 ring-amber-500/20 shadow-md'
                            : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400 font-bold text-lg">
                            🌋
                        </span>
                        <Link
                            :href="route('hva.category', { kategori: 'natural', scope, unit_id: unitId || null, tahun })"
                            class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1"
                            @click.stop
                        >
                            Lembar Skoring &rarr;
                        </Link>
                    </div>
                    <div class="mt-3">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Bencana Alam (Natural)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Gempa, tsunami, badai, banjir</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Rata-rata Ancaman:</span>
                        <span class="font-extrabold text-amber-600 dark:text-amber-400 text-sm">
                            {{ categoryStats.natural?.avg_risk ?? 0 }}%
                        </span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                        <span>Ancaman Tertinggi:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ categoryStats.natural?.max_risk ?? 0 }}%
                        </span>
                    </div>
                </div>

                <!-- Technological Hazards -->
                <div
                    @click="applyFilter(null, kategori === 'technological' ? 'all' : 'technological')"
                    :class="[
                        'cursor-pointer rounded-xl p-5 border transition-all hover:shadow-md relative overflow-hidden bg-white dark:bg-gray-800',
                        kategori === 'technological'
                            ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md'
                            : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400 font-bold text-lg">
                            ⚡
                        </span>
                        <Link
                            :href="route('hva.category', { kategori: 'technological', scope, unit_id: unitId || null, tahun })"
                            class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                            @click.stop
                        >
                            Lembar Skoring &rarr;
                        </Link>
                    </div>
                    <div class="mt-3">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Teknologi & Utilitas</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Listrik, air, genset, TI, api</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Rata-rata Ancaman:</span>
                        <span class="font-extrabold text-blue-600 dark:text-blue-400 text-sm">
                            {{ categoryStats.technological?.avg_risk ?? 0 }}%
                        </span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                        <span>Ancaman Tertinggi:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ categoryStats.technological?.max_risk ?? 0 }}%
                        </span>
                    </div>
                </div>

                <!-- Human Hazards -->
                <div
                    @click="applyFilter(null, kategori === 'human' ? 'all' : 'human')"
                    :class="[
                        'cursor-pointer rounded-xl p-5 border transition-all hover:shadow-md relative overflow-hidden bg-white dark:bg-gray-800',
                        kategori === 'human'
                            ? 'border-rose-500 ring-2 ring-rose-500/20 shadow-md'
                            : 'border-gray-200 dark:border-gray-700 hover:border-rose-300 dark:hover:border-rose-700'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400 font-bold text-lg">
                            👥
                        </span>
                        <Link
                            :href="route('hva.category', { kategori: 'human', scope, unit_id: unitId || null, tahun })"
                            class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1"
                            @click.stop
                        >
                            Lembar Skoring &rarr;
                        </Link>
                    </div>
                    <div class="mt-3">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Faktor Manusia</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Korban massal, trauma, terorisme</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Rata-rata Ancaman:</span>
                        <span class="font-extrabold text-rose-600 dark:text-rose-400 text-sm">
                            {{ categoryStats.human?.avg_risk ?? 0 }}%
                        </span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                        <span>Ancaman Tertinggi:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ categoryStats.human?.max_risk ?? 0 }}%
                        </span>
                    </div>
                </div>

                <!-- Hazardous Materials -->
                <div
                    @click="applyFilter(null, kategori === 'hazardous_materials' ? 'all' : 'hazardous_materials')"
                    :class="[
                        'cursor-pointer rounded-xl p-5 border transition-all hover:shadow-md relative overflow-hidden bg-white dark:bg-gray-800',
                        kategori === 'hazardous_materials'
                            ? 'border-purple-500 ring-2 ring-purple-500/20 shadow-md'
                            : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400 font-bold text-lg">
                            ☣️
                        </span>
                        <Link
                            :href="route('hva.category', { kategori: 'hazardous_materials', scope, unit_id: unitId || null, tahun })"
                            class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1"
                            @click.stop
                        >
                            Lembar Skoring &rarr;
                        </Link>
                    </div>
                    <div class="mt-3">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Bahan Berbahaya (B3)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tumpahan kimia, radiasi, gas</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Rata-rata Ancaman:</span>
                        <span class="font-extrabold text-purple-600 dark:text-purple-400 text-sm">
                            {{ categoryStats.hazardous_materials?.avg_risk ?? 0 }}%
                        </span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                        <span>Ancaman Tertinggi:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ categoryStats.hazardous_materials?.max_risk ?? 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Top 10 Prioritas Bahaya Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            10 Besar Bahaya Prioritas (Top 10 Priority Hazards)
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Berdasarkan Skor Ancaman Relatif (%) tertinggi hasil pengkajian {{ scope === 'rs' ? 'agregasi rumah sakit' : (selectedUnit ? selectedUnit.nama : 'unit') }}.
                        </p>
                    </div>
                    <span class="px-2.5 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                        {{ topHazards.length }} Bahaya Terpetakan
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                        v-for="(item, idx) in topHazards"
                        :key="item.id"
                        class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700/70 bg-gray-50/50 dark:bg-gray-900/30 flex items-center justify-between gap-3 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-all"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span
                                :class="[
                                    'w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black shrink-0',
                                    idx < 3 ? 'bg-red-600 text-white shadow-xs' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                                ]"
                            >
                                {{ idx + 1 }}
                            </span>
                            <div class="truncate">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                    {{ item.nama_kejadian }}
                                </h4>
                                <span :class="['inline-block text-[10px] font-semibold px-2 py-0.5 rounded-md mt-0.5', getCategoryBadgeClass(item.kategori)]">
                                    {{ getCategoryLabel(item.kategori) }}
                                </span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span :class="['text-xs font-extrabold px-2 py-1 rounded-lg inline-block', getRiskBadgeClass(item.skor_risiko_relatif)]">
                                {{ Number(item.skor_risiko_relatif).toFixed(2) }}%
                            </span>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                Keparahan: {{ item.skor_keparahan }}/18
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Assessment Table Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <!-- Table Filters Bar -->
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori:</span>
                        <button
                            type="button"
                            @click="applyFilter(null, 'all')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                kategori === 'all'
                                    ? 'bg-indigo-600 text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Semua ({{ assessments.length }})
                        </button>
                        <button
                            type="button"
                            @click="applyFilter(null, 'natural')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                kategori === 'natural'
                                    ? 'bg-amber-600 text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Bencana Alam
                        </button>
                        <button
                            type="button"
                            @click="applyFilter(null, 'technological')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                kategori === 'technological'
                                    ? 'bg-blue-600 text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Teknologi
                        </button>
                        <button
                            type="button"
                            @click="applyFilter(null, 'human')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                kategori === 'human'
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Faktor Manusia
                        </button>
                        <button
                            type="button"
                            @click="applyFilter(null, 'hazardous_materials')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                kategori === 'hazardous_materials'
                                    ? 'bg-purple-600 text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                            ]"
                        >
                            Bahan Berbahaya (B3)
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-64">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilter()"
                            type="text"
                            placeholder="Cari kejadian bahaya..."
                            class="w-full text-xs rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 px-3 py-2 pl-9 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                        />
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 px-3 text-center w-12">No</th>
                                <th class="py-3 px-3 w-32">Kategori</th>
                                <th class="py-3 px-4 min-w-[220px]">Nama Kejadian Bahaya</th>
                                <th class="py-3 px-2 text-center w-14" title="Probabilitas (0-3)">P</th>
                                <th class="py-3 px-2 text-center w-14" title="Dampak Manusia (0-3)">H</th>
                                <th class="py-3 px-2 text-center w-14" title="Dampak Properti (0-3)">Pr</th>
                                <th class="py-3 px-2 text-center w-14" title="Dampak Bisnis (0-3)">B</th>
                                <th class="py-3 px-2 text-center w-14" title="Kesiapsiagaan (0-3)">Pp</th>
                                <th class="py-3 px-2 text-center w-14" title="Respon Internal (0-3)">Ri</th>
                                <th class="py-3 px-2 text-center w-14" title="Respon Eksternal (0-3)">Re</th>
                                <th class="py-3 px-3 text-center w-24">Keparahan</th>
                                <th class="py-3 px-3 text-center w-28">Ancaman (%)</th>
                                <th class="py-3 px-3 text-center w-20">Prioritas</th>
                                <th class="py-3 px-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs text-gray-700 dark:text-gray-300">
                            <tr
                                v-for="(item, index) in assessments"
                                :key="item.id"
                                class="hover:bg-indigo-50/20 dark:hover:bg-indigo-950/10 transition-colors"
                            >
                                <td class="py-3 px-3 text-center font-mono text-gray-400">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-3 px-3">
                                    <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-md inline-block', getCategoryBadgeClass(item.kategori)]">
                                        {{ getCategoryLabel(item.kategori) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                    {{ item.nama_kejadian }}
                                    <div v-if="item.rencana_tindak_lanjut" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-normal mt-0.5 flex items-center gap-1">
                                        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        RTL Aktif ({{ item.status_mitigasi }})
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-center font-mono font-semibold" :class="item.probabilitas > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'">
                                    {{ item.probabilitas }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.dampak_manusia }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.dampak_properti }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.dampak_bisnis }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.kesiapan }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.respon_internal }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.respon_eksternal }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                                        {{ item.skor_keparahan }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">/18</span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span :class="['text-xs font-extrabold px-2 py-0.5 rounded-md inline-block', getRiskBadgeClass(item.skor_risiko_relatif)]">
                                        {{ Number(item.skor_risiko_relatif).toFixed(2) }}%
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span v-if="item.peringkat_prioritas" class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        #{{ item.peringkat_prioritas }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditModal(item)"
                                        class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                        title="Ubah Penilaian"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="assessments.length === 0">
                                <td colspan="14" class="py-12 text-center text-gray-400">
                                    Tidak ada data kejadian bahaya untuk kriteria filter ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Edit Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 relative animate-in fade-in zoom-in duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-md inline-block uppercase', getCategoryBadgeClass(editingItem?.kategori)]">
                            {{ getCategoryLabel(editingItem?.kategori) }}
                        </span>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            {{ editingItem?.nama_kejadian }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEditForm" class="mt-5 space-y-5">
                    <!-- Probabilitas -->
                    <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-4 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <label class="block text-xs font-bold text-indigo-950 dark:text-indigo-200 uppercase tracking-wider mb-1">
                            1. Probabilitas (Probability)
                        </label>
                        <p class="text-xs text-gray-500 mb-2">Kemungkinan terjadinya bahaya di rumah sakit</p>
                        <select
                            v-model.number="editForm.probabilitas"
                            class="w-full text-sm rounded-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="0">0 = N/A (Tidak Ada Risiko)</option>
                            <option :value="1">1 = Low (Rendah / Sangat Jarang)</option>
                            <option :value="2">2 = Moderate (Sedang / Pernah Terjadi)</option>
                            <option :value="3">3 = High (Tinggi / Sering Terjadi)</option>
                        </select>
                    </div>

                    <!-- Dampak (Impact) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            2. Dampak (Impact)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Dampak Manusia (H)</label>
                                <select v-model.number="editForm.dampak_manusia" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                    <option :value="0">0 = N/A</option>
                                    <option :value="1">1 = Rendah</option>
                                    <option :value="2">2 = Sedang</option>
                                    <option :value="3">3 = Tinggi</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Dampak Properti (Pr)</label>
                                <select v-model.number="editForm.dampak_properti" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                    <option :value="0">0 = N/A</option>
                                    <option :value="1">1 = Rendah</option>
                                    <option :value="2">2 = Sedang</option>
                                    <option :value="3">3 = Tinggi</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Dampak Bisnis (B)</label>
                                <select v-model.number="editForm.dampak_bisnis" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                    <option :value="0">0 = N/A</option>
                                    <option :value="1">1 = Rendah</option>
                                    <option :value="2">2 = Sedang</option>
                                    <option :value="3">3 = Tinggi</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Kesiapsiagaan & Mitigasi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            3. Kesiapsiagaan & Mitigasi (Preparedness & Response)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Kesiapan / SOP (Pp)</label>
                                <select v-model.number="editForm.kesiapan" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                    <option :value="0">0 = N/A</option>
                                    <option :value="1">1 = Baik/Tinggi</option>
                                    <option :value="2">2 = Sedang</option>
                                    <option :value="3">3 = Kurang/Tidak Ada</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Respon Internal (Ri)</label>
                                <select v-model.number="editForm.respon_internal" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                    <option :value="0">0 = N/A</option>
                                    <option :value="1">1 = Cepat/Tinggi</option>
                                    <option :value="2">2 = Sedang</option>
                                    <option :value="3">3 = Lambat/Kurang</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Respon Eksternal (Re)</label>
                                <select v-model.number="editForm.respon_eksternal" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                    <option :value="0">0 = N/A</option>
                                    <option :value="1">1 = Terkoordinasi</option>
                                    <option :value="2">2 = Sedang</option>
                                    <option :value="3">3 = Minim/Tidak Ada</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Live Calculated Result -->
                    <div class="bg-gray-50 dark:bg-gray-900/70 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-500">Skor Keparahan:</span>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ computedSeverity }} <span class="text-xs text-gray-400">/ 18</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-500">Ancaman Relatif (Relative Threat):</span>
                            <div>
                                <span :class="['text-base font-extrabold px-3 py-1 rounded-lg inline-block', getRiskBadgeClass(computedRelativeRisk)]">
                                    {{ computedRelativeRisk }}% ({{ getRiskLevelLabel(computedRelativeRisk) }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2 text-xs font-semibold rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-md transition-all hover:scale-102"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

