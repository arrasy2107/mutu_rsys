<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    items: Array,
    units: Array,
    selectedUnit: Object,
    filters: Object,
});

const tahun = ref(props.filters.tahun || 2026);
const scope = ref(props.filters.scope || 'rs');
const unitId = ref(props.filters.unit_id || '');
const status = ref(props.filters.status || 'all');

const applyFilter = (newScope = null, newStatus = null) => {
    if (newScope !== null) scope.value = newScope;
    if (newStatus !== null) status.value = newStatus;

    router.get(route('hva.rtl'), {
        tahun: tahun.value,
        scope: scope.value,
        unit_id: scope.value === 'unit' ? (unitId.value || null) : null,
        status: status.value,
    }, { preserveState: true, preserveScroll: true });
};

// Modal Edit RTL
const isEditModalOpen = ref(false);
const editingItem = ref(null);
const rtlForm = ref({
    rencana_tindak_lanjut: '',
    pj_mitigasi: '',
    target_waktu: '',
    status_mitigasi: 'belum_mulai',
});

const openEditModal = (item) => {
    editingItem.value = item;
    rtlForm.value = {
        rencana_tindak_lanjut: item.rencana_tindak_lanjut || '',
        pj_mitigasi: item.pj_mitigasi || '',
        target_waktu: item.target_waktu || '',
        status_mitigasi: item.status_mitigasi || 'belum_mulai',
    };
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingItem.value = null;
};

const submitRtlForm = () => {
    if (!editingItem.value) return;
    router.put(route('hva.update-rtl', editingItem.value.id), rtlForm.value, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
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
        case 'technological': return 'Teknologi';
        case 'human': return 'Faktor Manusia';
        case 'hazardous_materials': return 'B3 & Radiasi';
        default: return cat;
    }
};

const getStatusBadge = (s) => {
    switch (s) {
        case 'selesai':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'proses':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
    }
};

const getStatusLabel = (s) => {
    switch (s) {
        case 'selesai': return 'Selesai';
        case 'proses': return 'Dalam Proses';
        default: return 'Belum Mulai';
    }
};
</script>

<template>
    <AppLayout title="Rencana Tindak Lanjut (RTL) HVA">
        <div class="space-y-6 pb-12">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <Link
                                :href="route('hva.index', { scope, unit_id: unitId || null, tahun })"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-300 hover:text-white transition-colors"
                            >
                                &larr; Kembali ke Dashboard HVA
                            </Link>
                            <span class="text-slate-600">•</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/30 text-emerald-200 border border-emerald-400/30">
                                Program Mitigasi Bencana
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            Rencana Tindak Lanjut (RTL) & Mitigasi HVA
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-2xl">
                            Daftar rencana tindakan, penanggung jawab (PJ), dan target waktu implementasi mitigasi kedaruratan bencana rumah sakit untuk bahaya-bahaya prioritas.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('hva.summary', { scope, unit_id: unitId || null, tahun })"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-md transition-all hover:scale-102"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Lihat Grafik Analitik
                        </Link>
                    </div>
                </div>

                <!-- Scope Switcher -->
                <div class="mt-6 pt-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="inline-flex p-1 bg-slate-800/80 rounded-xl border border-slate-700">
                        <button
                            type="button"
                            @click="applyFilter('rs')"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                scope === 'rs' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            Tingkat Rumah Sakit (RSYS)
                        </button>
                        <button
                            type="button"
                            @click="applyFilter('unit')"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                scope === 'unit' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            Tingkat Unit Kerja
                        </button>
                    </div>

                    <div v-if="scope === 'unit'" class="flex items-center gap-2">
                        <label class="text-xs text-slate-300 font-medium">Pilih Unit:</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter('unit')"
                            class="bg-slate-800 text-white text-xs rounded-lg px-3 py-1.5 border border-slate-700"
                        >
                            <option v-for="u in units" :key="u.id" :value="u.id">
                                [{{ u.kode }}] {{ u.nama }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Filter Status Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Status Mitigasi:</span>
                    <button
                        type="button"
                        @click="applyFilter(null, 'all')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            status === 'all'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Semua Status ({{ items.length }})
                    </button>
                    <button
                        type="button"
                        @click="applyFilter(null, 'proses')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            status === 'proses'
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Dalam Proses
                    </button>
                    <button
                        type="button"
                        @click="applyFilter(null, 'selesai')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            status === 'selesai'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Selesai
                    </button>
                    <button
                        type="button"
                        @click="applyFilter(null, 'belum_mulai')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            status === 'belum_mulai'
                                ? 'bg-amber-600 text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Belum Mulai
                    </button>
                </div>

                <div class="text-xs text-gray-400">
                    Menampilkan <span class="font-bold text-gray-700 dark:text-gray-300">{{ items.length }}</span> rencana mitigasi
                </div>
            </div>

            <!-- RTL Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/60 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 px-3 text-center w-12">No</th>
                                <th class="py-3 px-3 w-28">Kategori</th>
                                <th class="py-3 px-4 w-52">Kejadian Bahaya</th>
                                <th class="py-3 px-3 text-center w-24">Skor Risiko</th>
                                <th class="py-3 px-4 min-w-[280px]">Rencana Tindak Lanjut (Mitigasi)</th>
                                <th class="py-3 px-3 w-40">Penanggung Jawab</th>
                                <th class="py-3 px-3 w-32">Target Waktu</th>
                                <th class="py-3 px-3 text-center w-28">Status</th>
                                <th class="py-3 px-3 text-center w-16">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs text-gray-700 dark:text-gray-300">
                            <tr
                                v-for="(item, index) in items"
                                :key="item.id"
                                class="hover:bg-indigo-50/20 dark:hover:bg-indigo-950/10 transition-colors"
                            >
                                <td class="py-3.5 px-3 text-center font-mono text-gray-400">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-3.5 px-3">
                                    <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-md inline-block', getCategoryBadgeClass(item.kategori)]">
                                        {{ getCategoryLabel(item.kategori) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                                    {{ item.nama_kejadian }}
                                    <div v-if="item.peringkat_prioritas" class="text-[10px] text-gray-400 mt-0.5">
                                        Peringkat Prioritas: #{{ item.peringkat_prioritas }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="font-bold text-gray-800 dark:text-gray-200">
                                        {{ Number(item.skor_risiko_relatif).toFixed(2) }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 leading-relaxed text-gray-800 dark:text-gray-200">
                                    <div v-if="item.rencana_tindak_lanjut">
                                        {{ item.rencana_tindak_lanjut }}
                                    </div>
                                    <span v-else class="text-gray-400 italic">
                                        Belum diisi rencana tindak lanjut
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 font-medium text-gray-700 dark:text-gray-300">
                                    {{ item.pj_mitigasi || '-' }}
                                </td>
                                <td class="py-3.5 px-3 text-gray-600 dark:text-gray-400">
                                    {{ item.target_waktu || '-' }}
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full inline-block', getStatusBadge(item.status_mitigasi)]">
                                        {{ getStatusLabel(item.status_mitigasi) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditModal(item)"
                                        class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                        title="Ubah RTL"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="items.length === 0">
                                <td colspan="9" class="py-12 text-center text-gray-400">
                                    Tidak ada rencana tindak lanjut untuk filter ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit RTL Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 relative animate-in fade-in zoom-in duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase">
                            Rencana Tindak Lanjut (Mitigasi)
                        </span>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">
                            {{ editingItem?.nama_kejadian }}
                        </h3>
                    </div>
                    <button type="button" @click="closeEditModal" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitRtlForm" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                            Rencana Tindak Lanjut / Program Mitigasi
                        </label>
                        <textarea
                            v-model="rtlForm.rencana_tindak_lanjut"
                            rows="4"
                            placeholder="Tuliskan program pencegahan, SOP penanggulangan, pelatihan/drill, atau pengadaan sarana..."
                            class="w-full text-xs rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Penanggung Jawab (PJ)
                            </label>
                            <input
                                v-model="rtlForm.pj_mitigasi"
                                type="text"
                                placeholder="Contoh: K3RS / IPS-U / IGD"
                                class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Target Waktu
                            </label>
                            <input
                                v-model="rtlForm.target_waktu"
                                type="text"
                                placeholder="Contoh: Triwulan II 2026"
                                class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                            Status Mitigasi
                        </label>
                        <select
                            v-model="rtlForm.status_mitigasi"
                            class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="belum_mulai">Belum Mulai</option>
                            <option value="proses">Dalam Proses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 text-xs font-semibold rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 shadow-md">
                            Simpan RTL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

