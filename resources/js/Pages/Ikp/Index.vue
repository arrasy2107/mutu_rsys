<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    incidents: Object,
    units: Array,
    summary: Object,
    filters: Object,
    isUnitScope: Boolean,
});

const search = ref(props.filters.search || '');
const tahun = ref(props.filters.tahun || new Date().getFullYear());
const bulan = ref(props.filters.bulan || '');
const grading = ref(props.filters.grading || '');
const jenis = ref(props.filters.jenis_insiden || '');
const tipe = ref(props.filters.tipe_insiden || '');
const status = ref(props.filters.status || '');
const unitId = ref(props.filters.unit_id || '');

const bulanOptions = [
    { value: '', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const jenisOptions = [
    { value: '', label: 'Semua Jenis Insiden' },
    { value: 'Sentinel', label: 'Sentinel (Kematian/Cedera Permanen)' },
    { value: 'KTD', label: 'KTD (Kejadian Tidak Diharapkan)' },
    { value: 'KTC', label: 'KTC (Kejadian Tidak Cedera)' },
    { value: 'KNC', label: 'KNC (Kejadian Nyaris Cedera)' },
    { value: 'KPC', label: 'KPC (Kondisi Potensial Cedera)' },
];

const gradingOptions = [
    { value: '', label: 'Semua Grading' },
    { value: 'biru', label: 'Biru (Rendah / Low)' },
    { value: 'hijau', label: 'Hijau (Sedang / Moderate)' },
    { value: 'kuning', label: 'Kuning (Tinggi / High)' },
    { value: 'merah', label: 'Merah (Ekstrem / Extreme)' },
];

const tipeOptions = [
    '',
    'Prosedur Medis / Tindakan Klinis',
    'Medikasi / Obat / Farmasi',
    'Dokumentasi & Rekam Medis',
    'Infeksi Terkait Layanan (HAIs)',
    'Pasien Jatuh / Keselamatan Fisik',
    'Sarana / Prasarana / Alat Medis',
    'Laboratorium & Diagnostik',
    'Gizi / Nutrisi Pasien',
    'Lain-lain',
];

const applyFilter = () => {
    router.get(route('ikp.index'), {
        search: search.value || null,
        tahun: tahun.value,
        bulan: bulan.value || null,
        grading: grading.value || null,
        jenis_insiden: jenis.value || null,
        tipe_insiden: tipe.value || null,
        status: status.value || null,
        unit_id: unitId.value || null,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilter = () => {
    search.value = '';
    bulan.value = '';
    grading.value = '';
    jenis.value = '';
    tipe.value = '';
    status.value = '';
    unitId.value = '';
    applyFilter();
};

const deleteIncident = (id, nomor) => {
    if (confirm(`Apakah Anda yakin ingin menghapus laporan insiden ${nomor}?`)) {
        router.delete(route('ikp.destroy', id));
    }
};

const gradingBadge = (g) => {
    switch (g?.toLowerCase()) {
        case 'merah':
            return 'bg-red-500 text-white shadow-red-500/20';
        case 'kuning':
            return 'bg-amber-400 text-gray-900 shadow-amber-400/20';
        case 'hijau':
            return 'bg-emerald-500 text-white shadow-emerald-500/20';
        case 'biru':
            return 'bg-blue-500 text-white shadow-blue-500/20';
        default:
            return 'bg-gray-400 text-white';
    }
};

const jenisBadge = (j) => {
    switch (j) {
        case 'Sentinel':
            return 'bg-rose-900 text-rose-100 border border-rose-700 font-bold';
        case 'KTD':
            return 'bg-red-100 text-red-800 border border-red-300 font-semibold';
        case 'KTC':
            return 'bg-amber-100 text-amber-800 border border-amber-300 font-semibold';
        case 'KNC':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-semibold';
        case 'KPC':
            return 'bg-sky-100 text-sky-800 border border-sky-300 font-semibold';
        default:
            return 'bg-gray-100 text-gray-800 border border-gray-300';
    }
};

const formatDateTime = (val) => {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (val) => {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-rose-500 to-red-600 rounded-full inline-block"></span>
                        Pelaporan Insiden Keselamatan Pasien (IKP)
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pencatatan, investigasi, grading risiko, dan evaluasi kepatuhan tindak lanjut insiden RS
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <Link
                        :href="route('ikp.mapping')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                        Pemetaan & Grafik IKP
                    </Link>
                    <Link
                        :href="route('ikp.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-500/25 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        + Laporkan Insiden Baru
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- ─── KPI SUMMARY CARDS ────────────────────────────────────────── -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Total Insiden ({{ filters.tahun }})</p>
                        <p class="text-2xl font-black text-gray-900 mt-1">{{ summary.total }}</p>
                        <span class="text-[10px] text-gray-400">Seluruh unit pelapor</span>
                    </div>
                    <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs text-rose-600 font-medium">Sentinel / High Risk</p>
                        <p class="text-2xl font-black text-rose-700 mt-1">{{ summary.sentinel + summary.high_risk }}</p>
                        <span class="text-[10px] text-rose-500">{{ summary.sentinel }} Sentinel &bull; {{ summary.high_risk }} Kuning/Merah</span>
                    </div>
                    <div class="w-11 h-11 bg-red-50 text-red-600 rounded-xl flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs text-emerald-600 font-medium">Investigasi Selesai</p>
                        <p class="text-2xl font-black text-emerald-700 mt-1">{{ summary.completed }}</p>
                        <span class="text-[10px] text-gray-400">Kasus tuntas ditindaklanjuti</span>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs text-sky-600 font-medium">Kepatuhan Waktu</p>
                        <p class="text-2xl font-black text-sky-700 mt-1">{{ summary.compliance_rate }}%</p>
                        <span class="text-[10px] text-gray-400">Sesuai tenggat waktu RCA/Investigasi</span>
                    </div>
                    <div class="w-11 h-11 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- ─── FILTER BAR ────────────────────────────────────────────────── -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Cari Laporan / Kata Kunci</label>
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilter"
                            placeholder="Nomor, insiden, kronologi..."
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                        />
                    </div>

                    <!-- Tahun & Bulan -->
                    <div class="flex gap-2">
                        <div class="w-1/2">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tahun</label>
                            <input
                                type="number"
                                v-model="tahun"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            />
                        </div>
                        <div class="w-1/2">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Bulan</label>
                            <select
                                v-model="bulan"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option v-for="b in bulanOptions" :key="b.value" :value="b.value">{{ b.label }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Grading & Jenis -->
                    <div class="flex gap-2">
                        <div class="w-1/2">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Grading</label>
                            <select
                                v-model="grading"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option v-for="g in gradingOptions" :key="g.value" :value="g.value">{{ g.label }}</option>
                            </select>
                        </div>
                        <div class="w-1/2">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Jenis Insiden</label>
                            <select
                                v-model="jenis"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option v-for="j in jenisOptions" :key="j.value" :value="j.value">{{ j.label }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Unit Pelapor -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Unit Pelapor / Terkait</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter"
                            :disabled="isUnitScope"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 disabled:bg-gray-100"
                        >
                            <option value="">Semua Unit (35 Unit)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Filter Tambahan:</span>
                        <select
                            v-model="tipe"
                            @change="applyFilter"
                            class="text-xs rounded-lg border-gray-300 py-1"
                        >
                            <option value="">Semua Tipe Insiden</option>
                            <option v-for="t in tipeOptions.filter(Boolean)" :key="t" :value="t">{{ t }}</option>
                        </select>

                        <select
                            v-model="status"
                            @change="applyFilter"
                            class="text-xs rounded-lg border-gray-300 py-1"
                        >
                            <option value="">Semua Status</option>
                            <option value="dilaporkan">Dilaporkan</option>
                            <option value="investigasi">Investigasi</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="resetFilter"
                            type="button"
                            class="px-3 py-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                        >
                            Reset Filter
                        </button>
                        <button
                            @click="applyFilter"
                            type="button"
                            class="px-3.5 py-1.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── TABEL LAPORAN IKP ────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-[10px]">
                                <th class="p-3.5 w-12 text-center">No</th>
                                <th class="p-3.5">No. Laporan & Tanggal</th>
                                <th class="p-3.5">Insiden & Tipe</th>
                                <th class="p-3.5 text-center">Jenis & Grading</th>
                                <th class="p-3.5">Unit Pelapor & Terkait</th>
                                <th class="p-3.5">Penyelesaian & Kriteria Waktu</th>
                                <th class="p-3.5 text-center">Bukti RTL</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="incidents.data.length === 0">
                                <td colspan="9" class="p-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="font-medium">Tidak ada laporan insiden yang sesuai filter.</p>
                                        <Link :href="route('ikp.create')" class="text-rose-600 font-semibold hover:underline mt-1 text-xs">
                                            + Buat Laporan IKP Baru
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="(item, idx) in incidents.data"
                                :key="item.id"
                                class="hover:bg-gray-50/80 transition"
                            >
                                <td class="p-3.5 text-center font-mono text-gray-400">
                                    {{ (incidents.current_page - 1) * incidents.per_page + idx + 1 }}
                                </td>
                                <td class="p-3.5">
                                    <Link :href="route('ikp.show', item.id)" class="font-bold text-blue-600 hover:underline">
                                        {{ item.nomor_laporan }}
                                    </Link>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        Kejadian: {{ formatDateTime(item.tanggal_kejadian) }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        Lapor: {{ formatDate(item.tanggal_laporan) }}
                                    </div>
                                </td>
                                <td class="p-3.5 max-w-xs">
                                    <p class="font-bold text-gray-900 truncate" :title="item.insiden">{{ item.insiden }}</p>
                                    <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ item.tipe_insiden }}</p>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span :class="['px-2 py-0.5 text-[10px] rounded-md font-mono', jenisBadge(item.jenis_insiden)]">
                                            {{ item.jenis_insiden }}
                                        </span>
                                        <span :class="['px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider', gradingBadge(item.grading)]">
                                            {{ item.grading }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <p class="font-semibold text-gray-800">{{ item.unit_pelapor?.nama || '-' }}</p>
                                    <p v-if="item.unit_terkait" class="text-[11px] text-gray-400">
                                        Terkait: {{ item.unit_terkait?.nama }}
                                    </p>
                                </td>
                                <td class="p-3.5">
                                    <span v-if="item.tipe_penyelesaian === 'rca'" class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded text-[11px] border border-rose-200">
                                        RCA / AAM
                                    </span>
                                    <span v-else-if="item.tipe_penyelesaian === 'investigasi_sederhana'" class="font-medium text-sky-700 bg-sky-50 px-2 py-0.5 rounded text-[11px] border border-sky-200">
                                        Investigasi Sederhana
                                    </span>
                                    <span v-else class="text-gray-400 text-[11px] italic">
                                        Belum Ditentukan
                                    </span>

                                    <div class="mt-1">
                                        <span v-if="item.kriteria_waktu_penyelesaian === 'sesuai'" class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                                            &bull; Waktu Sesuai
                                        </span>
                                        <span v-else-if="item.kriteria_waktu_penyelesaian === 'tidak_sesuai'" class="text-[10px] text-red-600 font-bold flex items-center gap-1">
                                            &bull; Melewati Batas Waktu
                                        </span>
                                        <span v-else class="text-[10px] text-amber-600 font-medium">
                                            &bull; Dalam Proses
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3.5 text-center">
                                    <a
                                        v-if="item.bukti_rtl_path"
                                        :href="route('evidence.show', { type: 'ikp', record: item.id })"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2 py-1 rounded-lg border border-blue-200"
                                        title="Lihat Bukti RTL"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        Berkas
                                    </a>
                                    <span v-else class="text-[10px] text-gray-300 italic">-</span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 text-[10px] rounded-full font-bold uppercase tracking-wider',
                                            item.status === 'selesai' ? 'bg-emerald-100 text-emerald-700' :
                                            item.status === 'investigasi' ? 'bg-amber-100 text-amber-700' :
                                            'bg-gray-100 text-gray-600'
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Link
                                            :href="route('ikp.show', item.id)"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Rincian Lengkap"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        <Link
                                            :href="route('ikp.edit', item.id)"
                                            class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                            title="Ubah / Tindak Lanjut"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deleteIncident(item.id, item.nomor_laporan)"
                                            type="button"
                                            class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="incidents.links?.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Menampilkan {{ incidents.from || 0 }} - {{ incidents.to || 0 }} dari {{ incidents.total }} laporan
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in incidents.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg transition',
                                link.active ? 'bg-rose-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-40 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

