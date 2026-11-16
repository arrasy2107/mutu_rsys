<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import NotionIcon from '@/Components/NotionIcon.vue';
import axios from 'axios';

const props = defineProps({
    entries: Object,
    units: Array,
    years: Array,
    modalIndicators: Array,
    filters: Object,
    stats: Object,
    permissions: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// Filter states - every tab is sourced from validation decision history
const currentCategory = ref(props.filters?.category || 'history');
const selectedUnitId = ref(props.filters?.unit_id || '');
const selectedTahun = ref(props.filters?.tahun || '');
const selectedBulan = ref(props.filters?.bulan || '');
const searchQuery = ref(props.filters?.search || '');

const bulanList = [
    { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
    { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
    { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
    { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' },
];

const hasActiveFilter = computed(() => {
    return currentCategory.value !== 'history' || !!selectedUnitId.value || !!selectedTahun.value || !!selectedBulan.value || !!searchQuery.value;
});

const applyFilter = (newCategory = null) => {
    if (newCategory !== null) {
        currentCategory.value = newCategory;
    }
    router.get(route('validation.index'), {
        category: currentCategory.value,
        unit_id: selectedUnitId.value || undefined,
        tahun: selectedTahun.value || undefined,
        bulan: selectedBulan.value || undefined,
        search: searchQuery.value || undefined,
    }, { preserveScroll: true, replace: true });
};

const resetFilter = () => {
    currentCategory.value = 'history';
    selectedUnitId.value = '';
    selectedTahun.value = '';
    selectedBulan.value = '';
    searchQuery.value = '';
    applyFilter('history');
};

// ─── STATE & LOGIKA MODAL EKSPOR LAPORAN VALIDASI (EXCEL / PDF) ───
const showExportModal = ref(false);
const exportFormat = ref('pdf'); // 'pdf' atau 'excel'
const activeRowExportId = ref(null);
const exportUnitId = ref(props.filters?.unit_id || currentUser.value?.unit_id || (props.units?.[0]?.id ?? ''));
const exportTahun = ref(props.filters?.tahun || props.years?.[0] || new Date().getFullYear());
const exportBulan = ref(props.filters?.bulan ? String(props.filters.bulan) : ''); // '' = Semua Bulan
const exportStatus = ref('all'); // 'all', 'valid_only', 'invalid_only', 'approved_only'
const exportSelectionMode = ref('all'); // 'all' (semua indikator unit), 'custom' (pilih indikator tertentu)
const selectedExportIndicatorIds = ref([]);

// Filter indikator aktif yang sesuai dengan unit yang dipilih pada modal ekspor
const availableExportIndicators = computed(() => {
    if (!props.modalIndicators) return [];
    if (!exportUnitId.value) return props.modalIndicators;
    return props.modalIndicators.filter(i => !i.unit_id || i.unit_id === Number(exportUnitId.value));
});

// Helper check apakah IMP-U
const isIndicatorImpu = (ind) => {
    const kode = ind?.kode || '';
    const jenis = ind?.jenis || [];
    const jenisArr = Array.isArray(jenis) ? jenis : [jenis];
    return kode.toUpperCase().includes('IMP-U') || jenisArr.some(j => typeof j === 'string' && (j.toUpperCase().includes('IMP-U') || j.toLowerCase().includes('prioritas unit')));
};

const selectAllExportIndicators = () => {
    selectedExportIndicatorIds.value = availableExportIndicators.value.map(i => i.id);
};

const selectOnlyImpuExportIndicators = () => {
    selectedExportIndicatorIds.value = availableExportIndicators.value.filter(isIndicatorImpu).map(i => i.id);
};

const clearExportIndicators = () => {
    selectedExportIndicatorIds.value = [];
};

const openExportModal = () => {
    exportUnitId.value = props.filters?.unit_id || currentUser.value?.unit_id || (props.units?.[0]?.id ?? '');
    exportTahun.value = props.filters?.tahun || props.years?.[0] || new Date().getFullYear();
    exportBulan.value = props.filters?.bulan ? String(props.filters.bulan) : '';
    exportStatus.value = props.filters?.category && ['valid', 'tidak_valid'].includes(props.filters.category)
        ? (props.filters.category === 'valid' ? 'valid_only' : 'invalid_only')
        : 'all';
    exportSelectionMode.value = 'all';
    selectedExportIndicatorIds.value = availableExportIndicators.value.map(i => i.id);
    showExportModal.value = true;
};

const onExportUnitChange = () => {
    selectedExportIndicatorIds.value = availableExportIndicators.value.map(i => i.id);
};

const triggerExportDownload = (format = null, isPreview = false) => {
    const targetFormat = format || exportFormat.value || 'pdf';
    if (!exportUnitId.value) {
        window.notify?.alert?.warning('Unit Belum Dipilih', 'Silakan pilih unit kerja terlebih dahulu.');
        return;
    }

    if (exportSelectionMode.value === 'custom' && selectedExportIndicatorIds.value.length === 0) {
        window.notify?.alert?.warning('Indikator Belum Dipilih', 'Pilih minimal satu indikator mutu untuk diekspor atau pilih opsi "Semua Indikator".');
        return;
    }

    const params = new URLSearchParams();
    params.append('unit_id', exportUnitId.value);
    params.append('tahun', exportTahun.value);
    if (exportBulan.value) {
        params.append('bulan', exportBulan.value);
    }
    if (exportStatus.value && exportStatus.value !== 'all') {
        params.append('status_filter', exportStatus.value);
    }
    if (exportSelectionMode.value === 'custom' && selectedExportIndicatorIds.value.length > 0) {
        params.append('indicator_ids', selectedExportIndicatorIds.value.join(','));
    }
    if (isPreview) {
        params.append('preview', '1');
    }

    const routeName = targetFormat === 'pdf' ? 'validation.export-pdf' : 'validation.export-report';
    const url = `${route(routeName)}?${params.toString()}`;

    if (isPreview) {
        window.open(url, '_blank');
    } else {
        showExportModal.value = false;
        window.location.href = url;
    }
};

const getBulanName = (monthNum) => {
    return bulanList.find(b => b.v === Number(monthNum))?.l || '-';
};

const getPaginationLabel = (label) => String(label)
    .replace(/<[^>]*>/g, '')
    .replace(/&laquo;/g, '«')
    .replace(/&raquo;/g, '»');

// Helper getter untuk relasi latestValidation yang aman dari perbedaan penamaan snake_case / camelCase
const getVal = (entry) => {
    return entry?.latest_validation || entry?.latestValidation || null;
};

// Helper status kesesuaian data & konsekuensi validasi
const getValidationStatusMeta = (entry) => {
    if (!entry) {
        return {
            category: 'pending',
            label: 'Menunggu',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
            dotClass: 'bg-amber-500 animate-pulse',
            icon: 'clock',
            consequence: 'Menunggu penetapan internal unit',
            consequenceClass: 'text-amber-700 bg-amber-50/60 border-amber-200',
        };
    }

    const val = getVal(entry);
    const isSubmitted = entry.status === 'submitted' || (val && val.status === 'submitted');

    if (isSubmitted) {
        return {
            category: 'pending',
            label: 'Menunggu',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
            dotClass: 'bg-amber-500 animate-pulse',
            icon: 'clock',
            consequence: 'Menunggu penetapan internal unit',
            consequenceClass: 'text-amber-700 bg-amber-50/60 border-amber-200',
        };
    }

    const isValid = entry.status === 'approved' && (
        val?.is_valid === true ||
        (val?.is_valid == null && val?.tingkat_kesesuaian != null && val.tingkat_kesesuaian >= 90)
    );

    if (isValid) {
        return {
            category: 'valid',
            label: 'VALID',
            badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200 font-bold',
            dotClass: 'bg-emerald-500',
            icon: 'check',
            consequence: 'Profil valid untuk tahun ini',
            consequenceClass: 'text-emerald-700 bg-emerald-50/60 border-emerald-200 font-medium',
        };
    } else if (entry.status === 'rejected' || (entry.status === 'approved' && val?.is_valid === false)) {
        return {
            category: 'tidak_valid',
            label: 'TIDAK VALID',
            badgeClass: 'bg-rose-50 text-rose-800 border-rose-200 font-bold',
            dotClass: 'bg-rose-500',
            icon: 'x',
            consequence: 'Pilih bulan lain untuk validasi ulang',
            consequenceClass: 'text-rose-700 bg-rose-50/60 border-rose-200 font-medium',
        };
    } else {
        return {
            category: 'pending',
            label: entry.status === 'revision' ? 'REVISI' : 'MENUNGGU',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
            dotClass: 'bg-amber-500',
            icon: 'clock',
            consequence: entry.status === 'revision' ? 'Data menunggu perbaikan unit' : 'Menunggu keputusan internal unit',
            consequenceClass: 'text-amber-700 bg-amber-50/60 border-amber-200',
        };
    }
};

// ─── STATE & LOGIKA MODAL AJUKAN VALIDASI DATA ───
const showSubmitModal = ref(false);
const modalUnitId = ref(props.filters?.unit_id || currentUser.value?.unit_id || (props.units?.[0]?.id ?? ''));
const modalIndicatorId = ref('');
const modalTahun = ref(props.filters?.tahun || props.years?.[0] || new Date().getFullYear());
const modalSelectedMonth = ref(null);
const isLoadingPeriods = ref(false);
const periodData = ref(null);
const isSubmittingValidation = ref(false);

// Filter indikator aktif yang sesuai dengan unit yang dipilih pada modal
const availableModalIndicators = computed(() => {
    if (!props.modalIndicators) return [];
    if (!modalUnitId.value) return props.modalIndicators;
    return props.modalIndicators.filter(i => !i.unit_id || i.unit_id === Number(modalUnitId.value));
});

// Rekomendasi sampel tabel 3 untuk data bulan terpilih
const selectedMonthObject = computed(() => {
    if (!periodData.value?.months || !modalSelectedMonth.value) return null;
    return periodData.value.months.find(m => m.bulan === modalSelectedMonth.value);
});

const selectedMonthSampleRec = computed(() => {
    const den = selectedMonthObject.value?.denominator || 0;
    if (den <= 0) return 0;
    if (den >= 480) return 48;
    if (den >= 161) return 48;
    if (den >= 17) return Math.max(16, Math.round(den * 0.10));
    return den;
});

const openSubmitModal = () => {
    modalUnitId.value = props.filters?.unit_id || currentUser.value?.unit_id || (props.units?.[0]?.id ?? '');
    modalTahun.value = props.filters?.tahun || props.years?.[0] || new Date().getFullYear();
    showSubmitModal.value = true;

    // Prioritaskan indikator IMP-U jika tersedia
    if (availableModalIndicators.value.length > 0) {
        const impu = availableModalIndicators.value.find(i => {
            const kode = i.kode || '';
            const jenis = Array.isArray(i.jenis) ? i.jenis : [i.jenis];
            return kode.toUpperCase().includes('IMP-U') || jenis.some(j => typeof j === 'string' && j.toUpperCase().includes('IMP-U'));
        });
        modalIndicatorId.value = impu ? impu.id : availableModalIndicators.value[0].id;
        fetchIndicatorPeriods();
    }
};

const onModalUnitChange = () => {
    if (availableModalIndicators.value.length > 0) {
        modalIndicatorId.value = availableModalIndicators.value[0].id;
        fetchIndicatorPeriods();
    } else {
        modalIndicatorId.value = '';
        periodData.value = null;
    }
};

const fetchIndicatorPeriods = async () => {
    if (!modalIndicatorId.value || !modalUnitId.value) return;
    isLoadingPeriods.value = true;
    try {
        const res = await axios.get(route('validation.indicator-periods'), {
            params: {
                indicator_id: modalIndicatorId.value,
                unit_id: modalUnitId.value,
                tahun: modalTahun.value,
            }
        });
        periodData.value = res.data;
        modalSelectedMonth.value = res.data.recommended_month || 1;
    } catch (err) {
        console.error('Gagal mengambil status periode indikator:', err);
    } finally {
        isLoadingPeriods.value = false;
    }
};

const submitValidationEntry = () => {
    if (!selectedMonthObject.value?.can_submit) {
        window.notify?.alert?.warning('Data Belum Siap', 'Pilih bulan yang datanya sudah siap diisi pada Form Pengumpul Data.');
        return;
    }
    isSubmittingValidation.value = true;
    router.post(route('validation.submit-entry'), {
        indicator_id: modalIndicatorId.value,
        unit_id: modalUnitId.value,
        tahun: modalTahun.value,
        bulan: modalSelectedMonth.value,
    }, {
        onSuccess: () => {
            showSubmitModal.value = false;
        },
        onFinish: () => {
            isSubmittingValidation.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-bold text-slate-900 leading-none">Validasi Data Indikator Mutu</h1>
                    <span class="text-xs text-slate-400 font-normal">•</span>
                    <span class="text-xs text-slate-500">Penetapan validitas oleh PIC / Kepala Unit</span>
                    <span v-if="permissions?.isQualityCommittee"
                        class="ml-1 text-[10px] font-semibold text-teal-800 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded">
                        Komite Mutu
                    </span>
                    <span v-else-if="permissions?.isExecutive"
                        class="ml-1 text-[10px] font-semibold text-blue-800 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">
                        Direksi
                    </span>
                </div>
            </div>
        </template>

        <div class="space-y-3">
            <!-- ─── COMPACT TOOLBAR & STATS STRIP ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-3 space-y-2.5">
                <!-- Row 1: Kategori Utama (Segmented Pills) & Action Buttons Utama (High Visibility) -->
                <div class="flex flex-wrap items-center justify-between gap-2.5">
                    <!-- Segmented Pills Kategori -->
                    <div
                        class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-lg border border-slate-200/80">
                        <!-- Tab RIWAYAT VALIDASI -->
                        <button @click="applyFilter('history')" :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all flex items-center gap-1.5',
                            currentCategory === 'history'
                                ? 'bg-white text-slate-900 shadow-2xs font-bold'
                                : 'text-slate-600 hover:text-slate-900'
                        ]">
                            <span>Riwayat Validasi</span>
                            <span
                                :class="['px-1.5 py-0.2 rounded-full text-[10px] font-mono', currentCategory === 'history' ? 'bg-slate-200 text-slate-900 font-bold' : 'bg-white text-slate-600 border border-slate-200']">
                                {{ stats?.history ?? 0 }}
                            </span>
                        </button>

                        <!-- Tab VALID -->
                        <button @click="applyFilter('valid')" :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all flex items-center gap-1.5',
                            currentCategory === 'valid'
                                ? 'bg-emerald-700 text-white shadow-2xs font-bold'
                                : 'text-slate-600 hover:text-emerald-800'
                        ]">
                            <span class="w-1.5 h-1.5 rounded-full"
                                :class="currentCategory === 'valid' ? 'bg-white' : 'bg-emerald-600'"></span>
                            <span>Valid</span>
                            <span
                                :class="['px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold', currentCategory === 'valid' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800']">
                                {{ stats?.valid ?? 0 }}
                            </span>
                        </button>

                        <!-- Tab TIDAK VALID -->
                        <button @click="applyFilter('tidak_valid')" :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all flex items-center gap-1.5',
                            currentCategory === 'tidak_valid'
                                ? 'bg-rose-700 text-white shadow-2xs font-bold'
                                : 'text-slate-600 hover:text-rose-800'
                        ]">
                            <span class="w-1.5 h-1.5 rounded-full"
                                :class="currentCategory === 'tidak_valid' ? 'bg-white' : 'bg-rose-600'"></span>
                            <span>Tidak Valid</span>
                            <span
                                :class="['px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold', currentCategory === 'tidak_valid' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800']">
                                {{ stats?.tidak_valid ?? 0 }}
                            </span>
                        </button>

                    </div>

                    <!-- Action Buttons Toolbar (High Visibility) -->
                    <div class="flex items-center gap-2">
                        <!-- Tombol Ekspor Laporan (Pilihan PDF & Excel) -->
                        <button
                            @click="openExportModal"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-2xs hover:border-slate-300 transition-all shrink-0 cursor-pointer"
                            title="Ekspor laporan validasi data ke format PDF atau Excel"
                        >
                            <NotionIcon name="download" size="13" class="text-slate-500" />
                            <span>Ekspor Laporan</span>
                        </button>

                        <!-- Tombol Ajukan Validasi Data Utama -->
                        <button
                            v-if="permissions?.canValidate && !permissions?.isExecutive"
                            @click="openSubmitModal"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-all shrink-0 cursor-pointer"
                            title="Pilih Indikator Mutu dari Profil Indikator & ajukan data periode yang sudah siap untuk divalidasi"
                        >
                            <NotionIcon name="plus" size="13" />
                            <span>Ajukan Validasi Data</span>
                        </button>
                    </div>
                </div>

                <!-- Row 2: Filter Toolbar Ringkas & Akurasi RS -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                    <!-- Unit Filter -->
                    <div v-if="permissions?.isAdmin || permissions?.isQualityCommittee || permissions?.isExecutive"
                        class="min-w-[180px]">
                        <select v-model="selectedUnitId" @change="applyFilter()"
                            class="w-full text-xs font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400 focus:border-slate-400">
                            <option value="">Semua Unit Kerja</option>
                            <option v-for="u in (units || [])" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>

                    <!-- Bulan Filter -->
                    <div class="w-32">
                        <select v-model="selectedBulan" @change="applyFilter()"
                            class="w-full text-xs font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400 focus:border-slate-400">
                            <option value="">Semua Bulan</option>
                            <option v-for="b in bulanList" :key="b.v" :value="b.v">{{ b.l }}</option>
                        </select>
                    </div>

                    <!-- Tahun Filter -->
                    <div class="w-28">
                        <select v-model="selectedTahun" @change="applyFilter()"
                            class="w-full text-xs font-mono font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400 focus:border-slate-400">
                            <option value="">Semua Tahun</option>
                            <option v-for="y in (years || [])" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="flex-1 min-w-[200px] relative">
                        <input v-model="searchQuery" @keydown.enter="applyFilter()" type="text"
                            placeholder="Cari kode [INM/IMP] atau judul indikator..."
                            class="w-full text-xs border-slate-200 rounded-lg py-1.5 pl-7 pr-7 bg-slate-50 focus:bg-white focus:ring-slate-400 focus:border-slate-400" />
                        <div class="absolute left-2 top-2 text-slate-400">
                            <NotionIcon name="search" size="13" />
                        </div>
                        <button v-if="searchQuery" @click="searchQuery = ''; applyFilter();"
                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                            <NotionIcon name="x" size="12" />
                        </button>
                    </div>

                    <!-- Reset Button -->
                    <button v-if="hasActiveFilter" @click="resetFilter"
                        class="px-2.5 py-1.5 text-xs text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg font-medium transition-colors"
                        title="Reset Filter">
                        Reset
                    </button>
                </div>

                <!-- Ringkasan Akurasi RS -->
                <div class="flex items-center gap-3 text-xs text-slate-500 font-medium shrink-0 pl-2">
                    <div class="flex items-center gap-1.5">
                        <span>Akurasi RS:</span>
                        <strong class="font-mono text-slate-900 font-bold text-sm">{{ stats?.avg_agreement ?? 0 }}%</strong>
                    </div>
                    <span class="text-slate-300">|</span>
                    <span class="text-[11px] text-slate-500">Standar Lolos: <strong>≥ 90.0%</strong></span>
                </div>
            </div>
        </div>

            <!-- ─── TABEL KOMPARASI DATA VALIDASI (HIGH-DENSITY & CLEAN) ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead
                            class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider select-none">
                            <tr>
                                <th class="px-3 py-2.5 w-64 border-r border-slate-200">Indikator Mutu &amp; Unit</th>
                                <th class="px-2 py-2.5 text-center w-20 border-r border-slate-200">Periode</th>
                                <th
                                    class="px-3 py-2.5 text-center w-36 border-r border-slate-200 bg-slate-100/60 text-slate-700">
                                    Data Unit</th>
                                <th
                                    class="px-3 py-2.5 text-center w-36 border-r border-slate-200 bg-slate-100/60 text-slate-700">
                                    Data Validasi (PIC / Kepala Unit)</th>
                                <th class="px-3 py-2.5 text-center w-40 border-r border-slate-200">Kesesuaian &amp;
                                    Status</th>
                                <th class="px-2 py-2.5 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="entry in (entries?.data || [])" :key="entry.validation_event_id || entry.id"
                                class="hover:bg-slate-50/80 transition-colors">
                                <!-- Kolom 1: Indikator & Unit -->
                                <td class="px-3 py-2 border-r border-slate-200 align-top">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span
                                            class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 text-slate-800 border border-slate-200">
                                            {{ entry.indicator?.kode }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-medium">
                                            {{ entry.unit?.nama }}
                                        </span>
                                    </div>
                                    <p class="font-semibold text-slate-900 text-xs mt-1 line-clamp-2 leading-snug"
                                        :title="entry.indicator?.judul">
                                        {{ entry.indicator?.judul }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">
                                        Target: {{ entry.indicator?.target_operator === '>=' ? '≥' :
                                        (entry.indicator?.target_operator === '<=' ? '≤' :
                                            entry.indicator?.target_operator) }} {{ entry.indicator?.target_capaian }}%
                                            </span>
                                </td>

                                <!-- Kolom 2: Periode Bulan & Tahun -->
                                <td class="px-2 py-2 border-r border-slate-200 text-center align-middle">
                                    <span class="font-bold text-slate-900 block text-xs">
                                        {{ getBulanName(entry.period?.bulan) }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-500">
                                        {{ entry.period?.tahun }}
                                    </span>
                                    <span v-if="getVal(entry)?.tanggal_validasi"
                                        class="text-[9px] text-slate-400 block">
                                        {{ new Date(getVal(entry).tanggal_validasi).toLocaleDateString('id-ID') }}
                                    </span>
                                </td>

                                <!-- Kolom 3: Data Pengumpul Unit -->
                                <td class="px-3 py-2 border-r border-slate-200 text-center align-middle bg-slate-50/30">
                                    <span class="text-xs font-mono font-semibold text-slate-800 block">
                                        N: {{ Number(getVal(entry)?.unit_numerator ?? entry.numerator).toLocaleString('id-ID') }} / D: {{
                                        Number(getVal(entry)?.unit_denominator ?? entry.denominator).toLocaleString('id-ID') }}
                                    </span>
                                    <span class="font-mono font-bold text-xs text-slate-900 block mt-0.5">
                                        {{ getVal(entry)?.unit_capaian ?? entry.capaian }}%
                                    </span>
                                </td>

                                <!-- Kolom 4: Data Validasi Internal -->
                                <td class="px-3 py-2 border-r border-slate-200 text-center align-middle bg-slate-50/30">
                                    <template v-if="getVal(entry) && entry.status !== 'submitted'">
                                        <span class="text-xs font-mono font-semibold text-slate-800 block">
                                            N: {{ Number(getVal(entry)?.validator_numerator ??
                                            entry.numerator).toLocaleString('id-ID') }} / D: {{
                                            Number(getVal(entry)?.validator_denominator ??
                                            entry.denominator).toLocaleString('id-ID') }}
                                        </span>
                                        <span class="font-mono font-bold text-xs text-slate-900 block mt-0.5">
                                            {{ getVal(entry)?.validator_capaian ?? entry.capaian }}%
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="text-[11px] text-slate-400 italic">
                                            Menunggu penetapan internal
                                        </span>
                                    </template>
                                </td>

                                <!-- Kolom 5: Tingkat Kesesuaian & Konsekuensi Akreditasi -->
                                <td class="px-3 py-2 border-r border-slate-200 align-middle">
                                    <div class="flex flex-col items-center gap-1 text-center">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full"
                                                :class="getValidationStatusMeta(entry).dotClass"></span>
                                            <span class="text-xs font-mono font-black"
                                                :class="getValidationStatusMeta(entry).category === 'valid' ? 'text-emerald-700' : (getValidationStatusMeta(entry).category === 'tidak_valid' ? 'text-rose-700' : 'text-amber-700')">
                                                {{ (getVal(entry)?.tingkat_kesesuaian !== null &&
                                                getVal(entry)?.tingkat_kesesuaian !== undefined) ?
                                                getVal(entry).tingkat_kesesuaian + '%' : (entry.status === 'submitted' ?
                                                '—' : '100%') }}
                                            </span>
                                            <span class="px-1.5 py-0.2 rounded text-[10px] border font-bold uppercase"
                                                :class="getValidationStatusMeta(entry).badgeClass">
                                                {{ getValidationStatusMeta(entry).label }}
                                            </span>
                                        </div>
                                        <span
                                            class="text-[10px] px-2 py-0.5 rounded border block truncate max-w-[150px]"
                                            :class="getValidationStatusMeta(entry).consequenceClass"
                                            :title="getValidationStatusMeta(entry).consequence">
                                            {{ getValidationStatusMeta(entry).consequence }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Kolom 6: Aksi Cepat -->
                                <td class="px-2 py-2 text-center align-middle">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Tombol Telaah / Validasi -->
                                        <Link v-if="permissions?.canValidate && entry.status === 'submitted' && entry.is_current_validation_event"
                                            :href="route('validation.show', { dataEntry: entry.id, validation_id: entry.validation_event_id })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded text-[11px] transition-colors"
                                            :title="permissions?.isUnitStaff && !permissions?.isAdmin ? 'Validasi langsung data internal unit' : 'Tinjau dan tetapkan hasil validasi'">
                                            <span>{{ permissions?.isUnitStaff && !permissions?.isAdmin ? 'Validasi' : 'Tinjau Validasi' }}</span>
                                        </Link>

                                        <!-- Tombol Rincian / Edit -->
                                        <Link v-else :href="route('validation.show', { dataEntry: entry.id, validation_id: entry.validation_event_id })"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded text-[11px] transition-colors"
                                            :title="permissions?.canEditValidation && entry.status === 'approved' ? 'Lihat & edit data validasi' : 'Lihat lembar perbandingan data'">
                                            <span>{{ (permissions?.canEditValidation && entry.status === 'approved') ? 'Detail / Edit' : 'Rincian' }}</span>
                                        </Link>

                                        <!-- Tombol Form Pengumpul -->
                                        <Link
                                            v-if="entry.is_current_validation_event"
                                            :href="route('data-entry.index', { unit_id: entry.unit_id, tahun: entry.period?.tahun, bulan: entry.period?.bulan, indicator_id: entry.indicator_id })"
                                            class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded transition-colors"
                                            title="Buka Form Pengumpul Data Unit">
                                            <NotionIcon name="file-text" size="13" />
                                        </Link>

                                        <!-- 1 Tombol Ekspor Lembar Validasi (Pilihan PDF & Excel) -->
                                        <div v-if="entry.is_current_validation_event" class="relative inline-block text-left">
                                            <button
                                                type="button"
                                                @click.stop="activeRowExportId = activeRowExportId === entry.validation_event_id ? null : entry.validation_event_id"
                                                class="p-1 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded transition-colors cursor-pointer"
                                                title="Ekspor Laporan (Pilih PDF atau Excel)">
                                                <NotionIcon name="download" size="13" />
                                            </button>

                                            <!-- Dropdown Menu Pilihan PDF / Excel Per Baris -->
                                            <div
                                                v-if="activeRowExportId === entry.validation_event_id"
                                                @mouseleave="activeRowExportId = null"
                                                class="origin-top-right absolute right-0 mt-1 w-48 rounded-xl shadow-lg bg-white ring-1 ring-slate-200 divide-y divide-slate-100 z-50 p-1 text-left"
                                            >
                                                <div class="px-2 py-1">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pilih Format Unduh</span>
                                                </div>
                                                <div class="p-1 space-y-0.5">
                                                    <!-- Unduh PDF -->
                                                    <a
                                                        :href="route('validation.export-single-pdf', entry.id)"
                                                        @click="activeRowExportId = null"
                                                        class="flex items-center gap-2 px-2 py-1.5 text-xs text-slate-700 hover:bg-rose-50 hover:text-rose-900 rounded-md transition-colors"
                                                    >
                                                        <NotionIcon name="file-text" size="12" class="text-rose-600" />
                                                        <span class="font-medium">Unduh Dokumen PDF</span>
                                                    </a>
                                                    <!-- Pratinjau PDF -->
                                                    <a
                                                        :href="`${route('validation.export-single-pdf', entry.id)}?preview=1`"
                                                        target="_blank"
                                                        @click="activeRowExportId = null"
                                                        class="flex items-center gap-2 px-2 py-1.5 text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 rounded-md transition-colors"
                                                    >
                                                        <NotionIcon name="eye" size="12" class="text-slate-400" />
                                                        <span>Pratinjau PDF (Tab Baru)</span>
                                                    </a>
                                                    <!-- Unduh Excel -->
                                                    <a
                                                        :href="route('validation.export-excel', entry.id)"
                                                        @click="activeRowExportId = null"
                                                        class="flex items-center gap-2 px-2 py-1.5 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 rounded-md transition-colors"
                                                    >
                                                        <NotionIcon name="table" size="12" class="text-emerald-600" />
                                                        <span class="font-medium">Unduh Tabel Excel</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!entries?.data?.length">
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                    <p class="font-semibold text-slate-700 text-xs">Tidak ada data validasi pada filter ini</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Data indikator akan muncul saat unit mengajukan form pengumpul data atau menekan tombol <strong>Ajukan Validasi Data</strong> di atas.
                                    </p>
                                    <div class="mt-3 flex items-center justify-center gap-2">
                                        <button v-if="permissions?.canValidate && !permissions?.isExecutive" @click="openSubmitModal"
                                            class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                            + Ajukan Validasi Sekarang
                                        </button>
                                        <button v-if="hasActiveFilter" @click="resetFilter"
                                            class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded transition-colors">
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Minimalis -->
                <div v-if="entries?.links && entries?.links?.length > 3"
                    class="px-3 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between flex-wrap gap-2 text-xs">
                    <p class="text-slate-500 text-[11px]">
                        Menampilkan <span class="font-medium text-slate-800">{{ entries?.from || 0 }}</span>–<span
                            class="font-medium text-slate-800">{{ entries?.to || 0 }}</span> dari total <span
                            class="font-medium text-slate-800">{{ entries?.total || 0 }}</span>
                    </p>
                    <div class="flex gap-1">
                        <Link v-for="(link, i) in (entries?.links || [])" :key="i" :href="link.url || '#'" :class="[
                            'px-2 py-0.5 text-xs rounded transition-colors',
                            link.active
                                ? 'bg-slate-900 text-white font-bold'
                                : (link.url ? 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' : 'text-slate-300 pointer-events-none')
                        ]">{{ getPaginationLabel(link.label) }}</Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── MODAL AJUKAN VALIDASI DATA DARI PROFIL INDIKATOR ─── -->
        <Teleport to="body">
            <div
                v-if="showSubmitModal"
                class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
                @click.self="showSubmitModal = false"
            >
                <div class="bg-white rounded-xl max-w-2xl w-full p-4 sm:p-5 shadow-xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95 duration-150 max-h-[92vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-200">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-800 flex items-center justify-center">
                                <NotionIcon name="shield" size="16" />
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900">Ajukan Data untuk Validasi Mutu</h3>
                                <p class="text-[11px] text-slate-500">Pilih Indikator Mutu dari Profil Indikator &amp; ambil data Form Pengumpul yang siap divalidasi.</p>
                            </div>
                        </div>
                        <button @click="showSubmitModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <NotionIcon name="x" size="15" />
                        </button>
                    </div>

                    <!-- Step 1: Pemilihan Unit & Indikator Mutu -->
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                            <!-- Unit Selector jika Admin / Komite Mutu -->
                            <div v-if="permissions?.isAdmin || permissions?.isQualityCommittee" class="sm:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Unit Kerja <span class="text-rose-500">*</span></label>
                                <select
                                    v-model="modalUnitId"
                                    @change="onModalUnitChange"
                                    class="w-full text-xs border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:ring-slate-400"
                                >
                                    <option v-for="u in (units || [])" :key="u.id" :value="u.id">{{ u.nama }}</option>
                                </select>
                            </div>

                            <!-- Indikator Mutu Selector -->
                            <div :class="(permissions?.isAdmin || permissions?.isQualityCommittee) ? 'sm:col-span-2' : 'sm:col-span-3'">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                    Profil Indikator Mutu Unit <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="modalIndicatorId"
                                    @change="fetchIndicatorPeriods"
                                    class="w-full text-xs font-semibold border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:ring-slate-400"
                                >
                                    <option v-for="ind in availableModalIndicators" :key="ind.id" :value="ind.id">
                                        [{{ ind.kode }}] {{ ind.judul }}
                                    </option>
                                </select>
                            </div>

                            <div class="sm:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tahun</label>
                                <select
                                    v-model="modalTahun"
                                    @change="fetchIndicatorPeriods"
                                    class="w-full text-xs font-mono border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:ring-slate-400"
                                >
                                    <option v-for="y in (years || [])" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Target Indikator Info Strip -->
                        <div v-if="periodData?.indicator" class="px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-[11px] text-slate-600">
                            <span>
                                Target Capaian: <strong>{{ periodData.indicator.target_operator }} {{ periodData.indicator.target_capaian }}%</strong>
                            </span>
                            <span class="font-mono text-[10px] text-slate-500">
                                Satuan: {{ periodData.indicator.satuan_pengukuran || 'Persentase' }}
                            </span>
                        </div>
                    </div>

                    <!-- Step 2: Status Periode Bulan & Kesiapan Data Form Pengumpul -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-[11px] font-semibold text-slate-700 uppercase tracking-wide">
                                Pilih Periode Bulan yang Siap Diajukan (Tahun {{ modalTahun }})
                            </label>
                            <span v-if="isLoadingPeriods" class="text-[11px] text-slate-400 italic">Memuat data...</span>
                        </div>

                        <!-- Banner Notifikasi Jika Indikator Sudah Pernah Lolos Validasi di Tahun Ini -->
                        <div v-if="periodData?.passed_month" class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-900 text-xs flex items-center gap-2">
                            <NotionIcon name="check" size="14" class="text-emerald-700 shrink-0" />
                            <p class="leading-relaxed">
                                Indikator ini telah <strong>LOLOS validasi</strong> pada bulan <strong>{{ periodData.passed_month_name }} {{ modalTahun }}</strong>. 
                                Sesuai pedoman mutu RS, unit <strong>dibebaskan</strong> dari kewajiban validasi profil ini selama tahun ini.
                            </p>
                        </div>

                        <!-- Grid 12 Bulan -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                v-for="m in (periodData?.months || [])"
                                :key="m.bulan"
                                type="button"
                                @click="m.can_submit ? modalSelectedMonth = m.bulan : null"
                                :disabled="!m.can_submit"
                                :class="[
                                    'p-2 rounded-lg border text-left transition-all relative flex flex-col justify-between min-h-[64px]',
                                    modalSelectedMonth === m.bulan
                                        ? 'border-slate-900 bg-slate-900 text-white shadow-xs'
                                        : (m.can_submit 
                                            ? 'border-slate-200 bg-white hover:border-slate-400 cursor-pointer text-slate-800' 
                                            : 'border-slate-100 bg-slate-50/60 opacity-60 cursor-not-allowed text-slate-400')
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold">{{ m.bulan_name }}</span>
                                    <span v-if="m.can_submit && modalSelectedMonth === m.bulan" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                </div>

                                <div class="mt-1">
                                    <!-- Jika ada data -->
                                    <template v-if="m.has_data">
                                        <p :class="['text-[10px] font-mono', modalSelectedMonth === m.bulan ? 'text-slate-300' : 'text-slate-600']">
                                            N: {{ m.numerator }} / D: {{ m.denominator }}
                                        </p>
                                        <p :class="['text-[11px] font-mono font-bold', modalSelectedMonth === m.bulan ? 'text-emerald-300' : 'text-slate-900']">
                                            {{ m.capaian }}%
                                        </p>
                                    </template>
                                    <template v-else>
                                        <p :class="['text-[10px] italic', modalSelectedMonth === m.bulan ? 'text-slate-400' : 'text-slate-400']">
                                            Data Kosong
                                        </p>
                                    </template>
                                </div>

                                <!-- Status Badge Bulan -->
                                <div class="mt-1">
                                    <span v-if="m.can_submit" :class="['text-[9px] font-bold px-1 py-0.2 rounded', modalSelectedMonth === m.bulan ? 'bg-emerald-400/20 text-emerald-300' : 'bg-emerald-50 text-emerald-700 border border-emerald-200']">
                                        Siap Diajukan
                                    </span>
                                    <span v-else-if="m.is_exempt" class="text-[9px] font-medium text-emerald-600">
                                        Bebas Validasi
                                    </span>
                                    <span v-else-if="m.is_validated" class="text-[9px] font-medium text-blue-600">
                                        Tervalidasi
                                    </span>
                                    <span v-else-if="m.is_submitted" class="text-[9px] font-medium text-amber-600">
                                        Menunggu
                                    </span>
                                    <span v-else-if="m.is_rejected" class="text-[9px] font-medium text-rose-600">
                                        Tidak Valid
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Ringkasan Snapshot Data yang Akan Ditarik -->
                    <div v-if="selectedMonthObject && selectedMonthObject.can_submit" class="bg-slate-50 rounded-lg p-3 border border-slate-200 text-xs space-y-1.5">
                        <span class="text-[11px] font-bold text-slate-800 uppercase block">Ringkasan Data yang Akan Diajukan:</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center my-1.5">
                            <div class="bg-white p-2 rounded border border-slate-200">
                                <span class="text-[10px] text-slate-400 uppercase block">Numerator (N)</span>
                                <span class="font-mono font-bold text-slate-800">{{ selectedMonthObject.numerator }}</span>
                            </div>
                            <div class="bg-white p-2 rounded border border-slate-200">
                                <span class="text-[10px] text-slate-400 uppercase block">Denominator (D)</span>
                                <span class="font-mono font-bold text-slate-800">{{ selectedMonthObject.denominator }}</span>
                            </div>
                            <div class="bg-white p-2 rounded border border-slate-200">
                                <span class="text-[10px] text-slate-400 uppercase block">Capaian Unit</span>
                                <span class="font-mono font-bold text-slate-900">{{ selectedMonthObject.capaian }}%</span>
                            </div>
                            <div class="bg-white p-2 rounded border border-slate-200">
                                <span class="text-[10px] text-slate-400 uppercase block">Sampel Val (Tabel 3)</span>
                                <span class="font-mono font-bold text-indigo-700">{{ selectedMonthSampleRec }} Sampel</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500 italic">
                            * Data Form Pengumpul pada bulan {{ selectedMonthObject.bulan_name }} {{ modalTahun }} akan dibekukan (snapshot mandiri) dan langsung masuk ke lembar kerja Validasi Data untuk dapat disahkan.
                        </p>
                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="flex items-center justify-between pt-2.5 border-t border-slate-100">
                        <button
                            @click="showSubmitModal = false"
                            type="button"
                            class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                        >
                            Batal
                        </button>

                        <button
                            @click="submitValidationEntry"
                            :disabled="!selectedMonthObject?.can_submit || isSubmittingValidation"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-40 text-white text-xs font-semibold rounded-lg shadow-2xs transition-all active:scale-95 cursor-pointer"
                        >
                            <NotionIcon name="check" size="13" />
                            <span>{{ isSubmittingValidation ? 'Memproses Pengajuan...' : 'Tarik & Ajukan Validasi →' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── MODAL TELEPORT: EKSPOR LAPORAN VALIDASI DATA (EXCEL) ─── -->
        <Teleport to="body">
            <div v-if="showExportModal" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showExportModal = false"></div>

                <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200 p-5 space-y-4">
                        <!-- Modal Header -->
                        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0">
                                    <NotionIcon name="download" size="16" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900">Ekspor Laporan Validasi Data (Excel)</h2>
                                    <p class="text-[11px] text-slate-500">Rekapitulasi Lembar Validasi Format Baku Standar Akreditasi Rumah Sakit</p>
                                </div>
                            </div>
                            <button @click="showExportModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                                <NotionIcon name="x" size="14" />
                            </button>
                        </div>

                        <!-- Keterangan & Deskripsi Laporan -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1 text-slate-600">
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                <NotionIcon name="file-text" size="13" class="text-slate-600" />
                                <span>Keterangan Format Dokumen</span>
                            </div>
                            <p class="text-[11px] leading-relaxed text-slate-600">
                                Dokumen Excel (.xlsx) memuat rekapitulasi validasi dan perbandingan data Form Pengumpul Unit dengan hasil pemeriksaan internal PIC / Kepala Unit.
                            </p>
                        </div>

                        <!-- Form Pengaturan Ekspor -->
                        <div class="space-y-3.5 text-xs">
                            <!-- Unit Kerja & Tahun -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                        Unit Kerja <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-if="permissions?.isAdmin || permissions?.isQualityCommittee || permissions?.isExecutive"
                                        v-model="exportUnitId"
                                        @change="onExportUnitChange"
                                        class="w-full text-xs font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400"
                                    >
                                        <option v-for="u in (units || [])" :key="u.id" :value="u.id">{{ u.nama }} ({{ u.kode }})</option>
                                    </select>
                                    <div v-else class="px-2.5 py-1.5 bg-slate-100 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800">
                                        {{ currentUser?.unit?.nama || 'Unit Anda' }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                        Tahun Periode <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="exportTahun"
                                        class="w-full text-xs font-mono font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400"
                                    >
                                        <option v-for="y in (years || [])" :key="y" :value="y">{{ y }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Periode Bulan & Filter Status -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                        Periode Bulan
                                    </label>
                                    <select
                                        v-model="exportBulan"
                                        class="w-full text-xs font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400"
                                    >
                                        <option value="">Semua Bulan (Rekap 1 Tahun Penuh)</option>
                                        <option v-for="b in bulanList" :key="b.v" :value="String(b.v)">{{ b.l }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                        Kategori Status Validasi
                                    </label>
                                    <select
                                        v-model="exportStatus"
                                        class="w-full text-xs font-medium border-slate-200 rounded-lg py-1.5 px-2 bg-slate-50 focus:bg-white focus:ring-slate-400"
                                    >
                                        <option value="all">Semua Status (Valid, Tidak Valid, Menunggu)</option>
                                        <option value="valid_only">Hanya Data VALID (≥ 90%)</option>
                                        <option value="invalid_only">Hanya Data TIDAK VALID (&lt; 90%)</option>
                                        <option value="approved_only">Hanya yang Telah Disahkan (Approved)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Pilihan Indikator Mutu -->
                            <div class="space-y-2 pt-1">
                                <div class="flex items-center justify-between flex-wrap gap-1.5">
                                    <label class="text-[11px] font-semibold text-slate-700 uppercase tracking-wide">
                                        Pilihan Indikator Mutu yang Diekspor
                                    </label>
                                    <div class="flex items-center gap-3 text-[11px]">
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" v-model="exportSelectionMode" value="all" class="text-slate-900 focus:ring-slate-400 text-xs" />
                                            <span class="text-slate-700 font-medium">Semua Indikator Unit ({{ availableExportIndicators.length }})</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" v-model="exportSelectionMode" value="custom" class="text-slate-900 focus:ring-slate-400 text-xs" />
                                            <span class="text-slate-700 font-medium">Pilih Indikator Tertentu</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Panel Checkbox Indikator Kustom -->
                                <div v-if="exportSelectionMode === 'custom'" class="border border-slate-200 rounded-xl p-2.5 bg-slate-50/60 space-y-2">
                                    <div class="flex items-center justify-between text-[11px] pb-1.5 border-b border-slate-200/80 flex-wrap gap-1">
                                        <span class="text-slate-500">
                                            Terpilih: <strong>{{ selectedExportIndicatorIds.length }}</strong> dari {{ availableExportIndicators.length }} indikator
                                        </span>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="selectAllExportIndicators" class="px-2 py-0.5 bg-white border border-slate-200 rounded text-slate-700 hover:bg-slate-100 font-medium">
                                                Pilih Semua
                                            </button>
                                            <button type="button" @click="selectOnlyImpuExportIndicators" class="px-2 py-0.5 bg-white border border-slate-200 rounded text-slate-700 hover:bg-slate-100 font-medium">
                                                Hanya IMP-U
                                            </button>
                                            <button type="button" @click="clearExportIndicators" class="px-2 py-0.5 bg-white border border-slate-200 rounded text-slate-700 hover:bg-slate-100 font-medium">
                                                Kosongkan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1 divide-y divide-slate-100">
                                        <label
                                            v-for="ind in availableExportIndicators"
                                            :key="ind.id"
                                            class="flex items-start gap-2 p-1.5 rounded hover:bg-white transition-colors cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="ind.id"
                                                v-model="selectedExportIndicatorIds"
                                                class="mt-0.5 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="px-1.5 py-0.2 rounded font-mono font-bold text-[10px] bg-slate-100 text-slate-800 border border-slate-200">
                                                        {{ ind.kode }}
                                                    </span>
                                                    <span v-if="isIndicatorImpu(ind)" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                        IMP-U
                                                    </span>
                                                    <span class="text-xs text-slate-800 font-medium truncate">
                                                        {{ ind.judul }}
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                        <div v-if="availableExportIndicators.length === 0" class="text-center py-4 text-slate-400 text-xs">
                                            Tidak ada indikator aktif untuk unit kerja ini.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pilihan Format Berkas (PDF / Excel) -->
                            <div class="space-y-1.5 pt-1">
                                <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wide">
                                    Format Berkas Laporan <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <label
                                        :class="[
                                            'flex items-start gap-2.5 p-2.5 rounded-xl border transition-all cursor-pointer',
                                            exportFormat === 'pdf'
                                                ? 'border-rose-400 bg-rose-50/60 shadow-2xs ring-1 ring-rose-300'
                                                : 'border-slate-200 hover:border-slate-300 bg-white'
                                        ]"
                                    >
                                        <input
                                            type="radio"
                                            v-model="exportFormat"
                                            value="pdf"
                                            class="mt-0.5 text-rose-600 focus:ring-rose-400"
                                        />
                                        <div class="text-left flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 font-bold text-xs text-slate-800">
                                                <NotionIcon name="file-text" size="13" class="text-rose-600" />
                                                <span>Dokumen PDF (.pdf)</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                                Format cetak resmi RS Yos Sudarso Padang lengkap dengan tabel akreditasi Kemenkes 12 baris &amp; lembar tanda tangan.
                                            </p>
                                        </div>
                                    </label>

                                    <label
                                        :class="[
                                            'flex items-start gap-2.5 p-2.5 rounded-xl border transition-all cursor-pointer',
                                            exportFormat === 'excel'
                                                ? 'border-emerald-400 bg-emerald-50/60 shadow-2xs ring-1 ring-emerald-300'
                                                : 'border-slate-200 hover:border-slate-300 bg-white'
                                        ]"
                                    >
                                        <input
                                            type="radio"
                                            v-model="exportFormat"
                                            value="excel"
                                            class="mt-0.5 text-emerald-600 focus:ring-emerald-400"
                                        />
                                        <div class="text-left flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 font-bold text-xs text-slate-800">
                                                <NotionIcon name="table" size="13" class="text-emerald-600" />
                                                <span>Tabel Excel (.xlsx)</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                                Format spreadsheet terstruktur untuk rekapitulasi data numerik dan hasil validasi internal.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Ringkasan Ekspor Sebelum Download -->
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs flex items-center justify-between gap-3">
                                <div class="space-y-0.5">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Ringkasan Berkas yang Akan Diunduh:</span>
                                    <div class="text-[11px] text-slate-700 leading-snug">
                                        Format: <strong class="uppercase text-slate-900">{{ exportFormat }}</strong> • 
                                        Unit: <strong>{{ units?.find(u => u.id === Number(exportUnitId))?.nama || currentUser?.unit?.nama || 'Unit' }}</strong> • 
                                        Periode: <strong>{{ exportBulan ? bulanList.find(b => b.v === Number(exportBulan))?.l + ' ' : 'Seluruh Bulan ' }}{{ exportTahun }}</strong> • 
                                        Filter: <strong>{{ { all: 'Semua Status', valid_only: 'Hanya VALID (≥90%)', invalid_only: 'Hanya TIDAK VALID (<90%)', approved_only: 'Hanya Disahkan' }[exportStatus] }}</strong> • 
                                        Total: <strong>{{ exportSelectionMode === 'all' ? availableExportIndicators.length : selectedExportIndicatorIds.length }} Indikator</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Modal Action -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showExportModal = false"
                                class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Tombol Pratinjau PDF (Hanya jika opsi PDF dipilih) -->
                                <button
                                    v-if="exportFormat === 'pdf'"
                                    type="button"
                                    @click="triggerExportDownload('pdf', true)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs hover:border-slate-400 transition-all cursor-pointer"
                                    title="Buka pratinjau dokumen PDF di tab browser baru"
                                >
                                    <NotionIcon name="eye" size="13" class="text-slate-500" />
                                    <span>Pratinjau PDF</span>
                                </button>

                                <!-- Satu Tombol Unduh Utama Dinamis Berdasarkan Format -->
                                <button
                                    type="button"
                                    @click="triggerExportDownload(exportFormat, false)"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-4 py-1.5 text-white text-xs font-semibold rounded-lg shadow-2xs hover:shadow transition-all cursor-pointer',
                                        exportFormat === 'pdf' ? 'bg-rose-700 hover:bg-rose-800' : 'bg-emerald-700 hover:bg-emerald-800'
                                    ]"
                                    :title="`Unduh laporan dalam format ${exportFormat === 'pdf' ? 'PDF (.pdf)' : 'Excel (.xlsx)'}`"
                                >
                                    <NotionIcon :name="exportFormat === 'pdf' ? 'download' : 'table'" size="13" />
                                    <span>Unduh Laporan {{ exportFormat === 'pdf' ? 'PDF (.pdf)' : 'Excel (.xlsx)' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
