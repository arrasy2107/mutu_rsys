<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    indicators: Object,
    units: Array,
    rsCount: Number,
    summaryCounts: Object,
    selectedUnitId: String,
    activeUnit: Object,
    isUnitScoped: Boolean,
    filters: Object,
});

const search = ref(props.filters.search || '');
const unit_id = ref(props.filters.unit_id || 'all');
const jenis = ref(props.filters.jenis || 'all');
const jenis_indikator = ref(props.filters.jenis_indikator || '');
const is_active = ref(props.filters.is_active || '');

// Multi-select for Exporting
const selectedIds = ref([]);

const isSelected = (id) => selectedIds.value.includes(id);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const isAllSelected = computed(() => {
    const pageIds = props.indicators.data?.map(i => i.id) || [];
    return pageIds.length > 0 && pageIds.every(id => selectedIds.value.includes(id));
});

const toggleSelectAll = () => {
    const pageIds = props.indicators.data?.map(i => i.id) || [];
    if (isAllSelected.value) {
        selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id));
    } else {
        const set = new Set([...selectedIds.value, ...pageIds]);
        selectedIds.value = Array.from(set);
    }
};

const clearSelection = () => {
    selectedIds.value = [];
};

const exportPdf = () => {
    const params = new URLSearchParams();
    if (selectedIds.value.length > 0) {
        params.set('ids', selectedIds.value.join(','));
    } else {
        if (unit_id.value && unit_id.value !== 'all') params.set('unit_id', unit_id.value);
        if (jenis.value && jenis.value !== 'all') params.set('jenis', jenis.value);
        if (search.value) params.set('search', search.value);
    }
    window.open(route('indicators.export-pdf') + '?' + params.toString(), '_blank');
};

const exportExcel = () => {
    const params = new URLSearchParams();
    if (selectedIds.value.length > 0) {
        params.set('ids', selectedIds.value.join(','));
    } else {
        if (unit_id.value && unit_id.value !== 'all') params.set('unit_id', unit_id.value);
        if (jenis.value && jenis.value !== 'all') params.set('jenis', jenis.value);
        if (search.value) params.set('search', search.value);
    }
    window.location.href = route('indicators.export-excel') + '?' + params.toString();
};

const applyFilter = (customParams = {}) => {
    const params = {
        search: search.value,
        unit_id: unit_id.value,
        jenis: jenis.value,
        jenis_indikator: jenis_indikator.value,
        is_active: is_active.value,
        ...customParams,
    };

    // Bersihkan parameter kosong
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null || (key === 'jenis' && params[key] === 'all')) {
            if (key !== 'unit_id') {
                delete params[key];
            }
        }
    });

    router.get(route('indicators.index'), params, { preserveScroll: true, replace: true });
};

const selectUnit = (newUnitId) => {
    unit_id.value = newUnitId;
    applyFilter({ unit_id: newUnitId });
};

const selectJenisTab = (jenisKey) => {
    jenis.value = jenisKey;
    applyFilter({ jenis: jenisKey });
};

const resetFilter = () => {
    search.value = '';
    jenis_indikator.value = '';
    is_active.value = '';
    jenis.value = 'all';
    applyFilter({ search: '', jenis_indikator: '', is_active: '', jenis: 'all' });
};

const toggleActive = (id) => {
    router.post(route('indicators.toggle-active', id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            window.notify?.toast?.success('Status Diperbarui', 'Status aktif indikator mutu berhasil diperbarui.');
        }
    });
};

const deleteIndicator = async (id, judul) => {
    const confirmed = await window.notify.dangerConfirm({
        title: 'Hapus Profil Indikator?',
        html: `Apakah Anda yakin ingin menghapus profil indikator mutu:<br/><strong class="text-rose-700">"${judul}"</strong>?<br/><br/><span class="text-xs text-slate-500">Seluruh definisi operasional, formula, dan konfigurasi instrumen terkait akan dihapus.</span>`,
        confirmButtonText: 'Ya, Hapus Indikator',
    });
    if (confirmed) {
        router.delete(route('indicators.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                window.notify?.toast?.success('Indikator Dihapus', 'Profil indikator mutu telah berhasil dihapus.');
            }
        });
    }
};

const tipiColors = { 
    input: 'bg-blue-50 text-blue-700 border-blue-200', 
    proses: 'bg-indigo-50 text-indigo-700 border-indigo-200', 
    output: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    outcome: 'bg-purple-50 text-purple-700 border-purple-200' 
};

const jenisBadgeStyles = {
    'IMN': 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-500/20',
    'IMP-RS': 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-500/20',
    'IMP-U': 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/20',
    'IMU': 'bg-teal-50 text-teal-700 border-teal-200 ring-teal-500/20',
    'SPM': 'bg-sky-50 text-sky-700 border-sky-200 ring-sky-500/20'
};

const operatorLabel = (op) => {
    if (op === '>=') return '≥';
    if (op === '<=') return '≤';
    if (op === '>') return '>';
    if (op === '<') return '<';
    return '=';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h1 class="text-base font-bold text-gray-800 leading-none">Profil Indikator Mutu</h1>
                <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Langkah 2 dari 7
                </span>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Header & Action Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                            Langkah 2 dari 7: Profil Indikator Mutu
                        </span>
                        <span class="text-xs text-gray-400">• Road Map Mutu RS</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight">Kamus & Profil Indikator Mutu</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pengelompokan tolok ukur indikator mutu berdasarkan Unit Kerja dan Jenis (IMN, IMP-RS, IMP-U, IMU, SPM)
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap shrink-0">
                    <!-- Export PDF & Excel Quick Buttons -->
                    <div class="inline-flex items-center bg-gray-50 border border-gray-200 p-0.5 rounded-lg shadow-2xs">
                        <button
                            type="button"
                            @click="exportPdf"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-700 bg-white hover:bg-rose-50 border border-rose-200 rounded-md transition-colors shadow-2xs"
                            :title="selectedIds.length > 0 ? `Export PDF (${selectedIds.length} profil terpilih)` : 'Export PDF seluruh indikator yang tampil'"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>PDF {{ selectedIds.length ? `(${selectedIds.length})` : '' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="exportExcel"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-white hover:bg-emerald-50 border border-emerald-200 rounded-md transition-colors ml-1 shadow-2xs"
                            :title="selectedIds.length > 0 ? `Export Excel (${selectedIds.length} profil terpilih)` : 'Export Excel seluruh indikator yang tampil'"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Excel {{ selectedIds.length ? `(${selectedIds.length})` : '' }}</span>
                        </button>
                    </div>

                    <Link
                        :href="route('indicator-selections.index')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
                        title="Buka Langkah 1: Pemilihan Indikator Mutu"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Pemilihan IMUT</span>
                    </Link>

                    <Link
                        :href="route('indicators.create', { unit_id: selectedUnitId !== 'all' && selectedUnitId !== 'rs' ? selectedUnitId : '' })"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Profil</span>
                    </Link>
                </div>
            </div>
            <!-- 1. UNIT SELECTION / SCOPE BANNER -->
            <div v-if="isUnitScoped" class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 rounded-xl p-5 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-white shrink-0 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                Unit Kerja Anda
                            </span>
                            <span class="text-xs text-indigo-200 font-mono">ID: {{ activeUnit?.kode || activeUnit?.id }}</span>
                        </div>
                        <h2 class="text-lg font-bold text-white mt-1">{{ activeUnit?.nama || 'Unit Pelayanan' }}</h2>
                        <p class="text-xs text-indigo-200/80 mt-0.5">
                            Menampilkan seluruh profil indikator mutu yang menjadi tanggung jawab dan target evaluasi unit ini.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 self-end md:self-center bg-white/10 backdrop-blur rounded-lg px-4 py-2.5 border border-white/10">
                    <div class="text-right">
                        <div class="text-xs text-indigo-200">Indikator Terdaftar</div>
                        <div class="text-xl font-extrabold text-white">{{ summaryCounts?.all || 0 }} <span class="text-xs font-normal text-indigo-300">Indikator</span></div>
                    </div>
                </div>
            </div>

            <!-- UNIT SELECTOR FOR ADMIN / KOMITE MUTU / DIREKSI -->
            <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Pengelompokan Sesuai Unit</span>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-gray-800">
                                    {{ activeUnit ? activeUnit.nama : (selectedUnitId === 'rs' ? 'Tingkat Rumah Sakit / Korporat' : 'Seluruh Unit Rumah Sakit') }}
                                </h2>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 font-medium text-gray-600">
                                    {{ summaryCounts?.all || 0 }} Indikator
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="unit_filter" class="text-xs font-medium text-gray-600 whitespace-nowrap">Filter Unit Kerja:</label>
                        <select
                            id="unit_filter"
                            :value="selectedUnitId"
                            @change="selectUnit($event.target.value)"
                            class="text-xs font-semibold border-gray-300 rounded-lg shadow-sm py-2 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500 min-w-[260px] bg-gray-50/50 hover:bg-white transition-colors"
                        >
                            <option value="all">🏥 Seluruh Rumah Sakit (Semua Unit)</option>
                            <option value="rs">🏛️ Tingkat RS / Korporat ({{ rsCount }} Indikator)</option>
                            <optgroup label="Unit Kerja Pelayanan & Penunjang">
                                <option v-for="u in units" :key="u.id" :value="String(u.id)">
                                    {{ u.nama }} ({{ u.indicators_count }} Indikator)
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. SUMMARY STAT CARDS (JENIS INDIKATOR MUTU) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Semua Card -->
                <button
                    @click="selectJenisTab('all')"
                    :class="[
                        'text-left p-3.5 rounded-xl border transition-all duration-150 relative overflow-hidden group',
                        jenis === 'all'
                            ? 'bg-slate-900 text-white border-slate-900 shadow-md ring-2 ring-slate-900/30'
                            : 'bg-white hover:bg-slate-50 text-gray-800 border-gray-200 shadow-sm'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span :class="['text-[11px] font-bold uppercase tracking-wider', jenis === 'all' ? 'text-slate-300' : 'text-gray-500']">Total</span>
                        <span :class="['w-2 h-2 rounded-full', jenis === 'all' ? 'bg-indigo-400' : 'bg-gray-300']"></span>
                    </div>
                    <div class="text-2xl font-black mt-1">{{ summaryCounts?.all || 0 }}</div>
                    <div :class="['text-[11px] mt-0.5 truncate', jenis === 'all' ? 'text-slate-300' : 'text-gray-400']">Semua Jenis</div>
                </button>

                <!-- IMN Card -->
                <button
                    @click="selectJenisTab('IMN')"
                    :class="[
                        'text-left p-3.5 rounded-xl border transition-all duration-150 relative overflow-hidden group',
                        jenis === 'IMN'
                            ? 'bg-rose-600 text-white border-rose-600 shadow-md ring-2 ring-rose-500/30'
                            : 'bg-white hover:bg-rose-50/50 text-gray-800 border-rose-100 shadow-sm'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span :class="['text-[11px] font-bold uppercase tracking-wider', jenis === 'IMN' ? 'text-rose-100' : 'text-rose-600']">IMN</span>
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </div>
                    <div class="text-2xl font-black mt-1">{{ summaryCounts?.imn || 0 }}</div>
                    <div :class="['text-[11px] mt-0.5 truncate', jenis === 'IMN' ? 'text-rose-100' : 'text-gray-400']">Nasional (Kemenkes)</div>
                </button>

                <!-- IMP-RS Card -->
                <button
                    @click="selectJenisTab('IMP-RS')"
                    :class="[
                        'text-left p-3.5 rounded-xl border transition-all duration-150 relative overflow-hidden group',
                        jenis === 'IMP-RS'
                            ? 'bg-amber-600 text-white border-amber-600 shadow-md ring-2 ring-amber-500/30'
                            : 'bg-white hover:bg-amber-50/50 text-gray-800 border-amber-100 shadow-sm'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span :class="['text-[11px] font-bold uppercase tracking-wider', jenis === 'IMP-RS' ? 'text-amber-100' : 'text-amber-600']">IMP-RS</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="text-2xl font-black mt-1">{{ summaryCounts?.imp_rs || 0 }}</div>
                    <div :class="['text-[11px] mt-0.5 truncate', jenis === 'IMP-RS' ? 'text-amber-100' : 'text-gray-400']">Prioritas Rumah Sakit</div>
                </button>

                <!-- IMP-U Card -->
                <button
                    @click="selectJenisTab('IMP-U')"
                    :class="[
                        'text-left p-3.5 rounded-xl border transition-all duration-150 relative overflow-hidden group',
                        jenis === 'IMP-U'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-2 ring-emerald-500/30'
                            : 'bg-white hover:bg-emerald-50/50 text-gray-800 border-emerald-100 shadow-sm'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span :class="['text-[11px] font-bold uppercase tracking-wider', jenis === 'IMP-U' ? 'text-emerald-100' : 'text-emerald-600']">IMP-U</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-2xl font-black mt-1">{{ summaryCounts?.imp_u || 0 }}</div>
                    <div :class="['text-[11px] mt-0.5 truncate', jenis === 'IMP-U' ? 'text-emerald-100' : 'text-gray-400']">Prioritas Unit (Skoring)</div>
                </button>

                <!-- IMU Card -->
                <button
                    @click="selectJenisTab('IMU')"
                    :class="[
                        'text-left p-3.5 rounded-xl border transition-all duration-150 relative overflow-hidden group',
                        jenis === 'IMU'
                            ? 'bg-teal-600 text-white border-teal-600 shadow-md ring-2 ring-teal-500/30'
                            : 'bg-white hover:bg-teal-50/50 text-gray-800 border-teal-100 shadow-sm'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span :class="['text-[11px] font-bold uppercase tracking-wider', jenis === 'IMU' ? 'text-teal-100' : 'text-teal-600']">IMU</span>
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    </div>
                    <div class="text-2xl font-black mt-1">{{ summaryCounts?.imu || 0 }}</div>
                    <div :class="['text-[11px] mt-0.5 truncate', jenis === 'IMU' ? 'text-teal-100' : 'text-gray-400']">Mutu Unit Operasional</div>
                </button>

                <!-- SPM Card -->
                <button
                    @click="selectJenisTab('SPM')"
                    :class="[
                        'text-left p-3.5 rounded-xl border transition-all duration-150 relative overflow-hidden group',
                        jenis === 'SPM'
                            ? 'bg-sky-600 text-white border-sky-600 shadow-md ring-2 ring-sky-500/30'
                            : 'bg-white hover:bg-sky-50/50 text-gray-800 border-sky-100 shadow-sm'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span :class="['text-[11px] font-bold uppercase tracking-wider', jenis === 'SPM' ? 'text-sky-100' : 'text-sky-600']">SPM</span>
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    </div>
                    <div class="text-2xl font-black mt-1">{{ summaryCounts?.spm || 0 }}</div>
                    <div :class="['text-[11px] mt-0.5 truncate', jenis === 'SPM' ? 'text-sky-100' : 'text-gray-400']">Standar Pelayanan Minimum</div>
                </button>
            </div>

            <!-- 3. FILTER TABS & SEARCH CONTROLS -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
                <!-- Nav Tabs Pill Jenis -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            @click="selectJenisTab('all')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5',
                                jenis === 'all'
                                    ? 'bg-gray-900 text-white shadow-sm'
                                    : 'text-gray-600 hover:bg-gray-100'
                            ]"
                        >
                            <span>Semua Jenis</span>
                            <span :class="['text-[11px] px-1.5 py-0.2 rounded-full', jenis === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600']">
                                {{ summaryCounts?.all || 0 }}
                            </span>
                        </button>

                        <button
                            @click="selectJenisTab('IMN')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5',
                                jenis === 'IMN'
                                    ? 'bg-rose-600 text-white shadow-sm'
                                    : 'text-rose-700 hover:bg-rose-50'
                            ]"
                        >
                            <span>IMN</span>
                            <span :class="['text-[11px] px-1.5 py-0.2 rounded-full', jenis === 'IMN' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-700']">
                                {{ summaryCounts?.imn || 0 }}
                            </span>
                        </button>

                        <button
                            @click="selectJenisTab('IMP-RS')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5',
                                jenis === 'IMP-RS'
                                    ? 'bg-amber-600 text-white shadow-sm'
                                    : 'text-amber-700 hover:bg-amber-50'
                            ]"
                        >
                            <span>IMP-RS</span>
                            <span :class="['text-[11px] px-1.5 py-0.2 rounded-full', jenis === 'IMP-RS' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-700']">
                                {{ summaryCounts?.imp_rs || 0 }}
                            </span>
                        </button>

                        <button
                            @click="selectJenisTab('IMP-U')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5',
                                jenis === 'IMP-U'
                                    ? 'bg-emerald-600 text-white shadow-sm'
                                    : 'text-emerald-700 hover:bg-emerald-50'
                            ]"
                        >
                            <span>IMP-U</span>
                            <span :class="['text-[11px] px-1.5 py-0.2 rounded-full', jenis === 'IMP-U' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-700']">
                                {{ summaryCounts?.imp_u || 0 }}
                            </span>
                        </button>

                        <button
                            @click="selectJenisTab('IMU')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5',
                                jenis === 'IMU'
                                    ? 'bg-teal-600 text-white shadow-sm'
                                    : 'text-teal-700 hover:bg-teal-50'
                            ]"
                        >
                            <span>IMU</span>
                            <span :class="['text-[11px] px-1.5 py-0.2 rounded-full', jenis === 'IMU' ? 'bg-teal-700 text-white' : 'bg-teal-100 text-teal-700']">
                                {{ summaryCounts?.imu || 0 }}
                            </span>
                        </button>

                        <button
                            @click="selectJenisTab('SPM')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5',
                                jenis === 'SPM'
                                    ? 'bg-sky-600 text-white shadow-sm'
                                    : 'text-sky-700 hover:bg-sky-50'
                            ]"
                        >
                            <span>SPM</span>
                            <span :class="['text-[11px] px-1.5 py-0.2 rounded-full', jenis === 'SPM' ? 'bg-sky-700 text-white' : 'bg-sky-100 text-sky-700']">
                                {{ summaryCounts?.spm || 0 }}
                            </span>
                        </button>
                    </div>

                    <button
                        v-if="search || jenis_indikator || is_active || (jenis && jenis !== 'all')"
                        @click="resetFilter"
                        class="text-xs text-rose-600 hover:text-rose-800 font-medium inline-flex items-center gap-1 px-2 py-1 rounded hover:bg-rose-50 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reset Filter
                    </button>
                </div>

                <!-- Input Controls -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilter()"
                            type="search"
                            placeholder="Cari kode, judul, atau definisi operasional..."
                            class="text-xs pl-9 pr-3 py-2 w-full border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <select
                        v-model="jenis_indikator"
                        @change="applyFilter()"
                        class="text-xs border-gray-200 rounded-lg py-2 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/50"
                    >
                        <option value="">Semua Tipe Indikator</option>
                        <option value="Input">Input</option>
                        <option value="Proses">Proses</option>
                        <option value="Output">Output</option>
                        <option value="Outcome">Outcome</option>
                    </select>

                    <select
                        v-model="is_active"
                        @change="applyFilter()"
                        class="text-xs border-gray-200 rounded-lg py-2 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/50"
                    >
                        <option value="">Semua Status</option>
                        <option value="1">Hanya Aktif</option>
                        <option value="0">Hanya Non-Aktif</option>
                    </select>

                    <button
                        @click="applyFilter()"
                        class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </div>

            <!-- 4. TABLE SECTION -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-3 py-3.5 text-center w-10">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                                        title="Pilih / Batalkan semua di halaman ini"
                                    />
                                </th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Kode</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Judul & Unit</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis (Kategori)</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Target</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Periode Pengumpulan & Sumber</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="ind in indicators.data"
                                :key="ind.id"
                                :class="[isSelected(ind.id) ? 'bg-indigo-50/60' : 'hover:bg-indigo-50/20', 'transition-colors']"
                            >
                                <!-- Checkbox Selection -->
                                <td class="px-3 py-3.5 align-top text-center" @click.stop>
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(ind.id)"
                                        @change="toggleSelect(ind.id)"
                                        class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer mt-0.5"
                                    />
                                </td>

                                <!-- Kode -->
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100">
                                        {{ ind.kode }}
                                    </span>
                                </td>

                                <!-- Judul & Unit -->
                                <td class="px-4 py-3.5 align-top max-w-md">
                                    <Link :href="route('indicators.show', ind.id)" class="text-sm font-semibold text-gray-900 hover:text-indigo-600 transition-colors">
                                        {{ ind.judul }}
                                    </Link>
                                    
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <!-- Unit badge (helpful when viewing all units or RS level) -->
                                        <span v-if="ind.unit" class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            {{ ind.unit.nama }}
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-[11px] font-medium text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">
                                            🏛️ Tingkat RS
                                        </span>

                                        <!-- Dimensi mutu snippet -->
                                        <span v-if="ind.dimensi_mutu?.length" class="text-[11px] text-gray-400">
                                            • {{ Array.isArray(ind.dimensi_mutu) ? ind.dimensi_mutu.join(', ') : ind.dimensi_mutu }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Jenis Multi Badges -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex flex-wrap gap-1" v-if="ind.jenis?.length">
                                        <span
                                            v-for="j in ind.jenis"
                                            :key="j"
                                            :class="['inline-flex px-2 py-0.5 rounded text-[11px] font-bold border', jenisBadgeStyles[j] || 'bg-gray-100 text-gray-700 border-gray-200']"
                                        >
                                            {{ j }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">-</span>
                                </td>

                                <!-- Tipe (Input / Proses / Output / Outcome) -->
                                <td class="px-4 py-3.5 align-top text-center">
                                    <div class="flex flex-wrap justify-center gap-1 max-w-[140px] mx-auto">
                                        <span
                                            v-for="ji in (Array.isArray(ind.jenis_indikator) ? ind.jenis_indikator : [ind.jenis_indikator].filter(Boolean))"
                                            :key="ji"
                                            :class="['inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium border', tipiColors[ji?.toLowerCase()] || 'bg-gray-100 text-gray-700 border-gray-200']"
                                        >
                                            {{ ji }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Target -->
                                <td class="px-4 py-3.5 align-top text-center whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">
                                        {{ operatorLabel(ind.target_operator) }} {{ ind.target_capaian }}{{ ind.satuan_pengukuran === 'Persentase' ? '%' : (ind.satuan_pengukuran === 'Permil' ? '‰' : '') }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 uppercase">{{ ind.satuan_pengukuran || 'Persen' }}</div>
                                </td>

                                <!-- Frekuensi & Sumber -->
                                <td class="px-4 py-3.5 align-top text-center whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-800 capitalize">
                                        {{ ind.frekuensi || 'Bulanan' }}
                                    </div>
                                    <div class="text-[10px] text-gray-500 mt-0.5 max-w-[120px] truncate mx-auto" :title="ind.sumber_data">
                                        {{ ind.sumber_data || '-' }}
                                    </div>
                                </td>

                                <!-- Status Toggle -->
                                <td class="px-4 py-3.5 align-top text-center">
                                    <button
                                        @click="toggleActive(ind.id)"
                                        :class="[
                                            'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                                            ind.is_active ? 'bg-emerald-500' : 'bg-gray-200'
                                        ]"
                                        role="switch"
                                        :aria-checked="ind.is_active"
                                        :title="ind.is_active ? 'Klik untuk non-aktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <span class="sr-only">Toggle active status</span>
                                        <span
                                            aria-hidden="true"
                                            :class="[
                                                'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                ind.is_active ? 'translate-x-4' : 'translate-x-0'
                                            ]"
                                        ></span>
                                    </button>
                                    <div class="text-[10px] font-medium mt-0.5" :class="ind.is_active ? 'text-emerald-600' : 'text-gray-400'">
                                        {{ ind.is_active ? 'Aktif' : 'Non-aktif' }}
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 align-top text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Tally Sheet Button (Step 3 Quick Access) -->
                                        <Link
                                            :href="route('data-entry.index')"
                                            class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Lanjut ke Form Pengumpul Data (Langkah 3)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        </Link>

                                        <!-- Lihat Profil Detail -->
                                        <Link
                                            :href="route('indicators.show', ind.id)"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Lihat Profil Lengkap"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>

                                        <!-- Edit Profil -->
                                        <Link
                                            :href="route('indicators.edit', ind.id)"
                                            class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Edit Profil Indikator"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>

                                        <!-- Hapus Profil -->
                                        <button
                                            @click="deleteIndicator(ind.id, ind.judul)"
                                            class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus Profil"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!indicators.data?.length">
                                <td colspan="9" class="px-4 py-16 text-center">
                                    <div class="max-w-md mx-auto space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800">Tidak ada profil indikator mutu ditemukan</h3>
                                        <p class="text-xs text-gray-500">
                                            {{ activeUnit ? `Unit ${activeUnit.nama} belum memiliki indikator untuk kriteria pencarian ini.` : 'Tidak ada data indikator yang cocok dengan filter yang dipilih.' }}
                                        </p>
                                        <div class="flex items-center justify-center gap-3 pt-2">
                                            <button
                                                v-if="search || jenis_indikator || (jenis && jenis !== 'all')"
                                                @click="resetFilter"
                                                class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                            >
                                                Hapus Filter
                                            </button>
                                            <Link
                                                :href="route('indicators.create', { unit_id: selectedUnitId !== 'all' && selectedUnitId !== 'rs' ? selectedUnitId : '' })"
                                                class="px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
                                            >
                                                + Tambah Profil Baru
                                            </Link>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="indicators.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-100 gap-3 bg-gray-50/50">
                    <p class="text-xs text-gray-500">
                        Menampilkan <span class="font-semibold text-gray-800">{{ indicators.from }}</span>–<span class="font-semibold text-gray-800">{{ indicators.to }}</span> dari <span class="font-semibold text-gray-800">{{ indicators.total }}</span> indikator mutu
                    </p>
                    <div class="flex gap-1 flex-wrap">
                        <Link
                            v-for="link in indicators.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg transition-colors font-medium',
                                link.active ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-40 cursor-not-allowed pointer-events-none'
                            ]"
                            v-html="link.label"
                            :preserve-scroll="true"
                        />
                    </div>
                </div>
            </div>

            <!-- Floating Selection Action Bar -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="selectedIds.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center gap-4 flex-wrap max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-500 text-white text-xs font-bold flex items-center justify-center">
                            {{ selectedIds.length }}
                        </span>
                        <span class="text-xs font-medium text-slate-200">Profil Terpilih</span>
                    </div>

                    <div class="h-4 w-px bg-slate-700 hidden sm:block"></div>

                    <div class="flex items-center gap-2">
                        <!-- Export PDF Button -->
                        <button
                            type="button"
                            @click="exportPdf"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Export PDF ({{ selectedIds.length }})</span>
                        </button>

                        <!-- Export Excel Button -->
                        <button
                            type="button"
                            @click="exportExcel"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Export Excel ({{ selectedIds.length }})</span>
                        </button>

                        <!-- Clear selection -->
                        <button
                            type="button"
                            @click="clearSelection"
                            class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors ml-1"
                            title="Batalkan Pilihan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>
