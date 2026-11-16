<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    assessments: Array,
    kategori: String,
    kategoriLabel: String,
    units: Array,
    selectedUnit: Object,
    filters: Object,
});

const tahun = ref(props.filters.tahun || 2026);
const scope = ref(props.filters.scope || 'rs');
const unitId = ref(props.filters.unit_id || '');

const categoryList = [
    { key: 'natural', label: 'Bencana Alam', icon: '🌋' },
    { key: 'technological', label: 'Teknologi & Utilitas', icon: '⚡' },
    { key: 'human', label: 'Faktor Manusia', icon: '👥' },
    { key: 'hazardous_materials', label: 'Bahan Berbahaya (B3)', icon: '☣️' },
];

const switchCategory = (catKey) => {
    router.get(route('hva.category', {
        kategori: catKey,
        scope: scope.value,
        unit_id: scope.value === 'unit' ? (unitId.value || null) : null,
        tahun: tahun.value,
    }));
};

const switchScope = (newScope) => {
    scope.value = newScope;
    router.get(route('hva.category', {
        kategori: props.kategori,
        scope: scope.value,
        unit_id: scope.value === 'unit' ? (unitId.value || null) : null,
        tahun: tahun.value,
    }));
};

// Modal Edit Row
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

const getRiskBadgeClass = (score) => {
    const s = Number(score);
    if (s >= 20.0) return 'bg-red-500 text-white font-bold shadow-xs';
    if (s >= 10.0) return 'bg-orange-500 text-white font-bold shadow-xs';
    if (s >= 5.0) return 'bg-yellow-400 text-slate-900 font-bold shadow-xs';
    if (s > 0.0) return 'bg-emerald-500 text-white font-bold shadow-xs';
    return 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 font-medium';
};

const averageThreat = computed(() => {
    if (!props.assessments || props.assessments.length === 0) return 0;
    const total = props.assessments.reduce((acc, curr) => acc + Number(curr.skor_risiko_relatif), 0);
    return (total / props.assessments.length).toFixed(2);
});
</script>

<template>
    <AppLayout :title="`Lembar Skoring HVA: ${kategoriLabel}`">
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
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                Kaiser Permanente HVA Matrix
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            {{ kategoriLabel }}
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-2xl">
                            Lembar kerja instrumen pengkajian 7 parameter skoring probabilitas, besaran dampak fisik & operasional, serta kesiapsiagaan mitigasi.
                        </p>
                    </div>

                    <!-- Average Relative Threat Card -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center shrink-0">
                        <span class="text-xs text-indigo-200 block uppercase tracking-wider font-semibold">Rata-rata Ancaman</span>
                        <span class="text-3xl font-extrabold text-white mt-1 block">
                            {{ averageThreat }}%
                        </span>
                        <span class="text-[10px] text-slate-300 block mt-0.5">
                            Dari {{ assessments.length }} kejadian dikaji
                        </span>
                    </div>
                </div>

                <!-- Category Switcher Tabs -->
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center gap-2 overflow-x-auto pb-1">
                    <button
                        v-for="cat in categoryList"
                        :key="cat.key"
                        type="button"
                        @click="switchCategory(cat.key)"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2',
                            kategori === cat.key
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-800'
                        ]"
                    >
                        <span>{{ cat.icon }}</span>
                        {{ cat.label }}
                    </button>
                </div>
            </div>

            <!-- Scope Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Ruang Lingkup:</span>
                    <button
                        type="button"
                        @click="switchScope('rs')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            scope === 'rs'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Tingkat Rumah Sakit (RSYS)
                    </button>
                    <button
                        type="button"
                        @click="switchScope('unit')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            scope === 'unit'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Tingkat Unit Kerja
                    </button>
                </div>

                <div v-if="scope === 'unit'" class="flex items-center gap-2">
                    <label class="text-xs text-gray-500 font-medium">Pilih Unit:</label>
                    <select
                        v-model="unitId"
                        @change="switchScope('unit')"
                        class="text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 px-3 py-1.5 dark:text-white"
                    >
                        <option v-for="u in units" :key="u.id" :value="u.id">
                            [{{ u.kode }}] {{ u.nama }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Detailed 7-Column Scoring Sheet Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <!-- Top Header Groups -->
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-900 text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider border-b border-gray-300 dark:border-gray-700">
                                <th rowspan="2" class="py-3 px-3 text-center border-r border-gray-200 dark:border-gray-700 w-12">No</th>
                                <th rowspan="2" class="py-3 px-4 border-r border-gray-200 dark:border-gray-700 min-w-[220px]">Kejadian Bahaya (Event)</th>
                                <th rowspan="2" class="py-3 px-2 text-center border-r border-gray-200 dark:border-gray-700 w-24 bg-indigo-50/50 dark:bg-indigo-950/20">
                                    Probabilitas<br>
                                    <span class="text-[9px] font-normal text-gray-500">0=N/A, 1=Low, 2=Mod, 3=High</span>
                                </th>
                                <th colspan="3" class="py-2 px-3 text-center border-r border-gray-200 dark:border-gray-700 bg-amber-50/50 dark:bg-amber-950/20">
                                    Besarnya Dampak (Impact)
                                </th>
                                <th colspan="3" class="py-2 px-3 text-center border-r border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20">
                                    Kesiapsiagaan & Mitigasi (Preparedness & Response)
                                </th>
                                <th rowspan="2" class="py-3 px-3 text-center border-r border-gray-200 dark:border-gray-700 w-24">
                                    Total Keparahan<br>
                                    <span class="text-[9px] font-normal text-gray-500">(0 - 18)</span>
                                </th>
                                <th rowspan="2" class="py-3 px-3 text-center border-r border-gray-200 dark:border-gray-700 w-28 bg-indigo-50/80 dark:bg-indigo-950/40">
                                    Ancaman Relatif<br>
                                    <span class="text-[9px] font-normal text-gray-500">(0 - 100%)</span>
                                </th>
                                <th rowspan="2" class="py-3 px-3 text-center w-20">Aksi</th>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800/80 text-[10px] font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <!-- Impact sub-headers -->
                                <th class="py-2 px-2 text-center w-16 bg-amber-50/30 dark:bg-amber-950/10" title="Dampak pada Manusia (Kemungkinan meninggal/cedera)">Manusia (H)</th>
                                <th class="py-2 px-2 text-center w-16 bg-amber-50/30 dark:bg-amber-950/10" title="Dampak pada Properti (Kerugian dan kerusakan fisik)">Properti (Pr)</th>
                                <th class="py-2 px-2 text-center w-16 bg-amber-50/30 dark:bg-amber-950/10 border-r border-gray-200 dark:border-gray-700" title="Dampak pada Bisnis (Gangguan operasional layanan)">Bisnis (B)</th>

                                <!-- Mitigation sub-headers -->
                                <th class="py-2 px-2 text-center w-16 bg-emerald-50/30 dark:bg-emerald-950/10" title="Kesiapan / Perencanaan awal / SOP">Kesiapan (Pp)</th>
                                <th class="py-2 px-2 text-center w-16 bg-emerald-50/30 dark:bg-emerald-950/10" title="Respon Internal (Kecepatan, efektivitas staf internal)">Respon Int (Ri)</th>
                                <th class="py-2 px-2 text-center w-16 bg-emerald-50/30 dark:bg-emerald-950/10 border-r border-gray-200 dark:border-gray-700" title="Respon Eksternal (Bantuan BPBD, Damkar, Polisi)">Respon Eks (Re)</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs text-gray-700 dark:text-gray-300">
                            <tr
                                v-for="(item, index) in assessments"
                                :key="item.id"
                                class="hover:bg-indigo-50/20 dark:hover:bg-indigo-950/10 transition-colors"
                            >
                                <td class="py-3 px-3 text-center font-mono text-gray-400 border-r border-gray-100 dark:border-gray-700/50">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white border-r border-gray-100 dark:border-gray-700/50">
                                    {{ item.nama_kejadian }}
                                    <span v-if="item.peringkat_prioritas" class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                                        Peringkat #{{ item.peringkat_prioritas }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-center font-mono font-bold border-r border-gray-100 dark:border-gray-700/50" :class="item.probabilitas > 0 ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/10' : 'text-gray-400'">
                                    {{ item.probabilitas }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.dampak_manusia }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.dampak_properti }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300 border-r border-gray-100 dark:border-gray-700/50">
                                    {{ item.dampak_bisnis }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.kesiapan }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300">
                                    {{ item.respon_internal }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-gray-600 dark:text-gray-300 border-r border-gray-100 dark:border-gray-700/50">
                                    {{ item.respon_eksternal }}
                                </td>
                                <td class="py-3 px-3 text-center border-r border-gray-100 dark:border-gray-700/50">
                                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                                        {{ item.skor_keparahan }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center border-r border-gray-100 dark:border-gray-700/50">
                                    <span :class="['text-xs font-extrabold px-2.5 py-1 rounded-md inline-block', getRiskBadgeClass(item.skor_risiko_relatif)]">
                                        {{ Number(item.skor_risiko_relatif).toFixed(2) }}%
                                    </span>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Edit Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 relative">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">
                            Skoring Kejadian Bahaya
                        </span>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-0.5">
                            {{ editingItem?.nama_kejadian }}
                        </h3>
                    </div>
                    <button type="button" @click="closeEditModal" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEditForm" class="mt-5 space-y-4">
                    <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <label class="block text-xs font-bold text-indigo-950 dark:text-indigo-200 uppercase mb-1">
                            Probabilitas (Probability)
                        </label>
                        <select v-model.number="editForm.probabilitas" class="w-full text-xs rounded-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white">
                            <option :value="0">0 = N/A (Tidak Ada Risiko)</option>
                            <option :value="1">1 = Low (Rendah / Sangat Jarang)</option>
                            <option :value="2">2 = Moderate (Sedang / Pernah Terjadi)</option>
                            <option :value="3">3 = High (Tinggi / Sering Terjadi)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Dampak Manusia</label>
                            <select v-model.number="editForm.dampak_manusia" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                <option :value="0">0 = N/A</option>
                                <option :value="1">1 = Rendah</option>
                                <option :value="2">2 = Sedang</option>
                                <option :value="3">3 = Tinggi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Dampak Properti</label>
                            <select v-model.number="editForm.dampak_properti" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                <option :value="0">0 = N/A</option>
                                <option :value="1">1 = Rendah</option>
                                <option :value="2">2 = Sedang</option>
                                <option :value="3">3 = Tinggi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Dampak Bisnis</label>
                            <select v-model.number="editForm.dampak_bisnis" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                <option :value="0">0 = N/A</option>
                                <option :value="1">1 = Rendah</option>
                                <option :value="2">2 = Sedang</option>
                                <option :value="3">3 = Tinggi</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Kesiapan (Pp)</label>
                            <select v-model.number="editForm.kesiapan" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                <option :value="0">0 = N/A</option>
                                <option :value="1">1 = Baik</option>
                                <option :value="2">2 = Sedang</option>
                                <option :value="3">3 = Kurang</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Respon Internal</label>
                            <select v-model.number="editForm.respon_internal" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                <option :value="0">0 = N/A</option>
                                <option :value="1">1 = Cepat</option>
                                <option :value="2">2 = Sedang</option>
                                <option :value="3">3 = Lambat</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Respon Eksternal</label>
                            <select v-model.number="editForm.respon_eksternal" class="w-full text-xs rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-2 py-1.5 dark:text-white">
                                <option :value="0">0 = N/A</option>
                                <option :value="1">1 = Siap</option>
                                <option :value="2">2 = Sedang</option>
                                <option :value="3">3 = Minim</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/70 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-500">Total Keparahan:</span>
                            <div class="text-base font-bold text-gray-900 dark:text-white">
                                {{ computedSeverity }} / 18
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-500">Ancaman Relatif:</span>
                            <div>
                                <span :class="['text-sm font-extrabold px-2.5 py-0.5 rounded-md inline-block', getRiskBadgeClass(computedRelativeRisk)]">
                                    {{ computedRelativeRisk }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 text-xs font-semibold rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 shadow-md">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

