<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    risks: Object,
    units: Array,
    summary: Object,
    filters: Object,
    isUnitScope: Boolean,
});

const tahun = ref(props.filters.tahun || 2026);
const scope = ref(props.filters.scope || 'all');
const unitId = ref(props.filters.unit_id || '');
const band = ref(props.filters.band || '');
const kategori = ref(props.filters.kategori || '');
const kontrolabilitas = ref(props.filters.kontrolabilitas || '');
const status = ref(props.filters.status || '');
const search = ref(props.filters.search || '');

const kategoriOptions = [
    '',
    'Risiko Klinis',
    'Risiko Keuangan',
    'Risiko Kebijakan',
    'Risiko Kepatuhan',
    'Risiko Operasional',
    'Risiko Reputasi',
    'Risiko Legal',
    'Risiko Fraud',
];

const bandOptions = [
    { value: '', label: 'Semua Tingkat Risiko' },
    { value: 'ekstrem', label: 'Sangat Tinggi / Ekstrem (> 19) - Merah' },
    { value: 'tinggi', label: 'Tinggi (14.1 - 19) - Kuning Tua / Oranye' },
    { value: 'sedang', label: 'Sedang (9.1 - 14) - Kuning Muda' },
    { value: 'rendah', label: 'Rendah (4.1 - 9) - Hijau' },
    { value: 'sangat_rendah', label: 'Sangat Rendah (0 - 4) - Biru' },
];

const applyFilter = (newScope = null) => {
    if (newScope !== null) {
        scope.value = newScope;
    }
    router.get(route('manrisk.index'), {
        tahun: tahun.value,
        scope: scope.value,
        unit_id: unitId.value || null,
        band: band.value || null,
        kategori: kategori.value || null,
        kontrolabilitas: kontrolabilitas.value || null,
        status: status.value || null,
        search: search.value || null,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilter = () => {
    unitId.value = '';
    band.value = '';
    kategori.value = '';
    kontrolabilitas.value = '';
    status.value = '';
    search.value = '';
    applyFilter();
};

const deleteRisk = (id, kode) => {
    if (confirm(`Apakah Anda yakin ingin menghapus asesmen risiko ${kode}?`)) {
        router.delete(route('manrisk.destroy', id));
    }
};

const toggleRs = (id) => {
    router.post(route('manrisk.toggle-rs', id), {}, { preserveScroll: true });
};

const bandBadge = (item) => {
    const skor = Number(item.skoring_akhir || item.inherent_skor || 0);
    if (skor > 19) {
        return 'bg-red-600 text-white font-bold shadow-xs';
    } else if (skor > 14) {
        return 'bg-amber-600 text-white font-bold shadow-xs';
    } else if (skor > 9) {
        return 'bg-yellow-400 text-gray-900 font-bold shadow-xs';
    } else if (skor > 4) {
        return 'bg-emerald-600 text-white font-bold shadow-xs';
    } else {
        return 'bg-blue-600 text-white font-bold shadow-xs';
    }
};

const bandLabel = (item) => {
    const skor = Number(item.skoring_akhir || item.inherent_skor || 0);
    if (skor > 19) return 'Sangat Tinggi';
    if (skor > 14) return 'Tinggi';
    if (skor > 9) return 'Sedang';
    if (skor > 4) return 'Rendah';
    return 'Sangat Rendah';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-amber-500 to-orange-600 rounded-full inline-block"></span>
                        Manajemen Risiko Rumah Sakit (MR RSYS 2026)
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Register profil risiko 47-kolom, formula skoring (P &times; D &times; Bobot), 8 Pilar KARS, dan prioritas RS
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <Link
                        :href="route('manrisk.matrix')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Matriks Heatmap 5&times;5
                    </Link>
                    <Link
                        :href="route('manrisk.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/25 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        + Identifikasi Risiko Baru
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- ─── SCOPE TABS (Semua, Prioritas RS, 8 Pilar KARS, Unit) ──────────────────────── -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-3 overflow-x-auto">
                <button
                    @click="applyFilter('all')"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0',
                        scope === 'all'
                            ? 'bg-gray-900 text-white shadow-sm'
                            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                    ]"
                >
                    <span>Semua Register Risiko</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-700 text-white">{{ summary.total }}</span>
                </button>

                <button
                    @click="applyFilter('rs')"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0',
                        scope === 'rs'
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                    ]"
                >
                    <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span>Prioritas RS (Top Unit)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-700 text-white">{{ summary.rs_prioritas }}</span>
                </button>

                <button
                    @click="applyFilter('kars')"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0',
                        scope === 'kars'
                            ? 'bg-amber-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                    ]"
                >
                    <svg class="w-4 h-4 text-yellow-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <span>8 Pilar Prioritas KARS</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-700 text-white">{{ summary.kars }}</span>
                </button>

                <button
                    @click="applyFilter('unit')"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0',
                        scope === 'unit'
                            ? 'bg-sky-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                    ]"
                >
                    <span>Risiko Operasional Unit</span>
                </button>
            </div>

            <!-- ─── KPI SUMMARY CARDS (5 Gradasi Standar RSYS) ────────────────────────────────────────── -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs">
                    <p class="text-[11px] text-gray-500 font-semibold">Total Risiko</p>
                    <p class="text-2xl font-black text-gray-900 mt-0.5">{{ summary.total }}</p>
                    <span class="text-[10px] text-gray-400">Tahun {{ filters.tahun }}</span>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-red-100 shadow-xs">
                    <p class="text-[11px] text-red-600 font-semibold">Sangat Tinggi (>19)</p>
                    <p class="text-2xl font-black text-red-700 mt-0.5">{{ summary.sangat_tinggi }}</p>
                    <span class="text-[10px] text-red-500">Merah &bull; RTL Direksi</span>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-amber-100 shadow-xs">
                    <p class="text-[11px] text-amber-600 font-semibold">Tinggi (14.1 - 19)</p>
                    <p class="text-2xl font-black text-amber-700 mt-0.5">{{ summary.tinggi }}</p>
                    <span class="text-[10px] text-amber-500">Oranye &bull; Tindakan Unit</span>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-yellow-100 shadow-xs">
                    <p class="text-[11px] text-yellow-600 font-semibold">Sedang (9.1 - 14)</p>
                    <p class="text-2xl font-black text-yellow-700 mt-0.5">{{ summary.sedang }}</p>
                    <span class="text-[10px] text-yellow-500">Kuning &bull; Monitoring</span>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-emerald-100 shadow-xs">
                    <p class="text-[11px] text-emerald-600 font-semibold">Rendah / Terkendali</p>
                    <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ summary.rendah }}</p>
                    <span class="text-[10px] text-emerald-500">Hijau/Biru &bull; SOP Rutin</span>
                </div>
            </div>

            <!-- ─── FILTER BAR ────────────────────────────────────────────────── -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Cari Kegiatan / Isu Risiko</label>
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilter()"
                            placeholder="Register, topik, sebab, dampak, pemilik..."
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Unit Kerja / Pemilik Risiko</label>
                        <select
                            v-model="unitId"
                            @change="applyFilter()"
                            :disabled="isUnitScope"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-gray-100"
                        >
                            <option value="">Semua Unit (35 Unit Kerja)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Kategori Risiko RSYS & KARS</label>
                        <select
                            v-model="kategori"
                            @change="applyFilter()"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">Semua 8 Kategori Risiko</option>
                            <option v-for="k in kategoriOptions.filter(Boolean)" :key="k" :value="k">{{ k }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tingkat Risiko (Band RSYS)</label>
                        <select
                            v-model="band"
                            @change="applyFilter()"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option v-for="b in bandOptions" :key="b.value" :value="b.value">{{ b.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-gray-500">Filter Tambahan:</span>
                        <select
                            v-model="kontrolabilitas"
                            @change="applyFilter()"
                            class="text-xs rounded-lg border-gray-300 py-1"
                        >
                            <option value="">Semua Kontrolabilitas (C / UC)</option>
                            <option value="C">C (Controllable)</option>
                            <option value="UC">UC (Uncontrollable)</option>
                        </select>

                        <select
                            v-model="status"
                            @change="applyFilter()"
                            class="text-xs rounded-lg border-gray-300 py-1"
                        >
                            <option value="">Semua Status</option>
                            <option value="dalam_proses">Dalam Proses Mitigasi</option>
                            <option value="terkendali">Terkendali</option>
                            <option value="ditutup">Ditutup</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="resetFilter"
                            type="button"
                            class="px-3 py-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                        >
                            Reset
                        </button>
                        <button
                            @click="applyFilter()"
                            type="button"
                            class="px-3.5 py-1.5 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 transition"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── TABEL REGISTER RISIKO 47-KOLOM ────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-[10px]">
                                <th class="p-3.5 w-10 text-center">No</th>
                                <th class="p-3.5">Register & Kategori</th>
                                <th class="p-3.5 min-w-[220px]">Kegiatan & Tujuan SMART</th>
                                <th class="p-3.5 min-w-[260px]">Pernyataan Risiko RSYS</th>
                                <th class="p-3.5">Pemilik Risiko</th>
                                <th class="p-3.5 text-center">P &times; D &times; B</th>
                                <th class="p-3.5 text-center">Skor Akhir & Band</th>
                                <th class="p-3.5 min-w-[180px]">Penanganan & Mitigasi</th>
                                <th class="p-3.5 text-center">Residu & Tren</th>
                                <th class="p-3.5 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="risks.data.length === 0">
                                <td colspan="10" class="p-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <p class="font-medium">Tidak ada risiko yang sesuai dengan kriteria filter.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="(item, idx) in risks.data"
                                :key="item.id"
                                class="hover:bg-amber-50/30 transition"
                            >
                                <td class="p-3.5 text-center font-mono text-gray-400 font-bold">
                                    {{ (risks.current_page - 1) * risks.per_page + idx + 1 }}
                                </td>
                                <td class="p-3.5">
                                    <Link :href="route('manrisk.show', item.id)" class="font-bold text-amber-700 hover:underline block font-mono">
                                        {{ item.nomor_register || item.kode_risiko }}
                                    </Link>
                                    <div class="mt-1 flex items-center gap-1 flex-wrap">
                                        <span class="px-2 py-0.5 text-[9px] rounded-md uppercase tracking-wider bg-gray-100 text-gray-700 font-bold border border-gray-200">
                                            {{ item.kode_risiko }}
                                        </span>
                                        <span v-if="item.is_rs_priority" class="px-2 py-0.5 text-[9px] rounded-md bg-blue-100 text-blue-800 font-bold border border-blue-200">
                                            Prioritas RS {{ item.nomor_prioritas_rs ? `#${item.nomor_prioritas_rs}` : '' }}
                                        </span>
                                        <span v-if="item.kategori_kars" class="px-2 py-0.5 text-[9px] rounded-md bg-amber-100 text-amber-800 font-bold border border-amber-300">
                                            KARS: {{ item.kategori_kars }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">{{ item.kategori_risiko }}</p>
                                </td>
                                <td class="p-3.5 max-w-xs">
                                    <p class="font-bold text-gray-900 leading-snug">
                                        {{ item.kegiatan || item.pernyataan_risiko }}
                                    </p>
                                    <p v-if="item.tujuan_kegiatan" class="text-[10px] text-gray-500 mt-1 line-clamp-2" :title="item.tujuan_kegiatan">
                                        <span class="font-semibold text-gray-700">SMART:</span> {{ item.tujuan_kegiatan }}
                                    </p>
                                </td>
                                <td class="p-3.5 max-w-sm">
                                    <p class="text-gray-800 line-clamp-3 leading-snug" :title="item.pernyataan_risiko">
                                        {{ item.pernyataan_risiko }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1.5 text-[10px]">
                                        <span :class="['px-1.5 py-0.5 rounded font-bold', item.kontrolabilitas === 'C' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200']">
                                            {{ item.kontrolabilitas === 'C' ? 'C (Controllable)' : 'UC (Uncontrollable)' }}
                                        </span>
                                        <span v-if="item.dampak" class="text-gray-500 truncate" :title="item.dampak">
                                            Dampak: {{ item.dampak }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-gray-800">{{ item.unit?.nama || item.pemilik_risiko || '-' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ item.ruang_lingkup || 'Pelayanan' }}</p>
                                </td>
                                <td class="p-3.5 text-center font-mono">
                                    <div class="text-[11px] text-gray-700 font-semibold">
                                        {{ item.inherent_probabilitas }} &times; {{ item.inherent_dampak }} &times; {{ Number(item.bobot || 1).toFixed(2) }}
                                    </div>
                                    <span class="text-[9px] text-gray-400">P &times; D &times; Bobot</span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span :class="['px-2.5 py-1 text-xs rounded-lg font-mono font-black', bandBadge(item)]">
                                            {{ Number(item.skoring_akhir || item.inherent_skor).toFixed(2) }}
                                        </span>
                                        <span class="text-[9px] uppercase font-bold text-gray-600">
                                            {{ bandLabel(item) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3.5 max-w-xs">
                                    <p class="font-medium text-gray-800 line-clamp-2" :title="item.pengendalian_harus_ada || item.rencana_penanganan">
                                        {{ item.pengendalian_harus_ada || item.rencana_penanganan || 'Belum dirumuskan' }}
                                    </p>
                                    <p v-if="item.penanggung_jawab" class="text-[10px] text-amber-700 font-medium mt-1">
                                        PJ: {{ item.penanggung_jawab }}
                                    </p>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div v-if="item.residual_skor" class="flex flex-col items-center gap-0.5">
                                        <span class="px-2 py-0.5 text-[10px] rounded-md font-mono bg-gray-100 text-gray-800 font-bold">
                                            Residu: {{ Number(item.residual_skor).toFixed(2) }}
                                        </span>
                                        <span v-if="item.penurunan_risiko_persen" class="text-[9px] font-bold text-emerald-600">
                                            &darr; {{ item.penurunan_risiko_persen }}%
                                        </span>
                                    </div>
                                    <span v-else class="text-[10px] text-gray-300 italic">Belum evaluasi</span>
                                    <div class="mt-1">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 text-[9px] rounded-full font-bold uppercase tracking-wider',
                                                item.status_pemantauan === 'terkendali' ? 'bg-emerald-100 text-emerald-700' :
                                                item.status_pemantauan === 'dalam_proses' ? 'bg-amber-100 text-amber-700' :
                                                'bg-gray-100 text-gray-600'
                                            ]"
                                        >
                                            {{ item.status_pemantauan }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <Link
                                            :href="route('manrisk.show', item.id)"
                                            class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                            title="Buka Profil 8 Tahapan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        <button
                                            @click="toggleRs(item.id)"
                                            type="button"
                                            :class="[
                                                'p-1.5 rounded-lg transition',
                                                item.is_rs_priority
                                                    ? 'text-blue-600 hover:bg-blue-50 bg-blue-50/50'
                                                    : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50'
                                            ]"
                                            :title="item.is_rs_priority ? 'Hapus dari Prioritas RS' : 'Tandai Prioritas RS'"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                        <Link
                                            :href="route('manrisk.edit', item.id)"
                                            class="p-1.5 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition"
                                            title="Ubah Profil"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deleteRisk(item.id, item.nomor_register || item.kode_risiko)"
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
                <div v-if="risks.links?.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Menampilkan {{ risks.from || 0 }} - {{ risks.to || 0 }} dari {{ risks.total }} risiko register
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in risks.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg transition',
                                link.active ? 'bg-amber-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-100',
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
