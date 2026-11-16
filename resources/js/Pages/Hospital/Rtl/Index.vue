<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    kategori: String,
    rentang: String,
    tahun: Number,
    counts: Object,
    stats: Object,
    records: Object,
    units: Array,
    filters: Object,
});

const filterForm = ref({
    kategori: props.kategori || 'IMP-RS',
    rentang: props.rentang || 'bulanan',
    tahun: props.tahun || 2026,
    unit_id: props.filters.unit_id || '',
    bulan: props.filters.bulan || '',
});

const applyFilters = () => {
    router.get(route('hospital.rtl.index'), filterForm.value, { preserveState: true });
};

const switchKategori = (kat) => {
    filterForm.value.kategori = kat;
    applyFilters();
};

const switchRentang = (rng) => {
    filterForm.value.rentang = rng;
    applyFilters();
};

// Modal Verifikasi Komite Mutu
const showVerifyModal = ref(false);
const selectedItem = ref(null);
const verifyForm = useForm({
    status: 'reviewed',
    catatan_review: '',
});

const openVerifyModal = (item) => {
    selectedItem.value = item;
    verifyForm.status = item.status === 'reviewed' ? 'reviewed' : 'reviewed';
    verifyForm.catatan_review = item.catatan_review || '';
    showVerifyModal.value = true;
};

const submitVerify = () => {
    if (!selectedItem.value) return;
    verifyForm.post(route('hospital.rtl.verify', selectedItem.value.id), {
        onSuccess: () => {
            showVerifyModal.value = false;
            window.notify?.toast?.success('Sukses', 'RTL berhasil diverifikasi Komite Mutu.');
        }
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'reviewed':
            return { label: 'Terverifikasi Komite Mutu', class: 'bg-emerald-100 text-emerald-800 border-emerald-300' };
        case 'submitted':
        case 'in_progress':
            return { label: 'Dalam Pelaksanaan', class: 'bg-amber-100 text-amber-800 border-amber-300' };
        default:
            return { label: 'Draft RTL', class: 'bg-gray-100 text-gray-800 border-gray-300' };
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Rencana Tindak Lanjut (RTL) Tingkat RS" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold uppercase tracking-wider backdrop-blur-sm mb-2">
                            <span>📋 Butir B.3 Roadmap Mutu</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Tingkat Rumah Sakit (Konsolidasi Otomatis dari Unit)</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">Rencana Tindak Lanjut (RTL) Rumah Sakit</h1>
                        <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                            Konsolidasi rencana aksi perbaikan mutu seluruh unit kerja, verifikasi tindak lanjut oleh Komite Mutu, dan pengawasan bukti berkas pendukung.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 4 Kategori Tabs (Butir B.3.a, B.3.b, B.3.c, B.3.d Roadmap) -->
            <div class="bg-white rounded-xl p-2 border border-gray-200 shadow-sm flex flex-wrap gap-2">
                <button
                    @click="switchKategori('IMP-RS')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        filterForm.kategori === 'IMP-RS' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>⭐ a. RTL IMP-RS</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', filterForm.kategori === 'IMP-RS' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-800']">
                        {{ counts['IMP-RS'] || 0 }}
                    </span>
                </button>

                <button
                    @click="switchKategori('IMN')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        filterForm.kategori === 'IMN' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>🇮🇩 b. RTL IMN</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', filterForm.kategori === 'IMN' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-800']">
                        {{ counts['IMN'] || 0 }}
                    </span>
                </button>

                <button
                    @click="switchKategori('IMP-U')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        filterForm.kategori === 'IMP-U' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>🏥 c. RTL IMP-U</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', filterForm.kategori === 'IMP-U' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800']">
                        {{ counts['IMP-U'] || 0 }}
                    </span>
                </button>

                <button
                    @click="switchKategori('SPM')"
                    :class="[
                        'flex-1 min-w-[160px] py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        filterForm.kategori === 'SPM' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>📋 d. RTL SPM</span>
                    <span :class="['px-2 py-0.5 rounded-full text-[10px]', filterForm.kategori === 'SPM' ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-800']">
                        {{ counts['SPM'] || 0 }}
                    </span>
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Item RTL RS</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                        📋
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Terverifikasi Komite</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.selesai }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        ✓
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dalam Proses</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.dalam_proses }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                        ⏳
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bukti Terlampir</p>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ stats.ada_bukti }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">
                        📎
                    </div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
                <!-- Filter Waktu (Bulanan, Triwulan, Semester, Tahunan) -->
                <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-xl w-full md:w-auto">
                    <button
                        @click="switchRentang('bulanan')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', filterForm.rentang === 'bulanan' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        📅 Bulanan
                    </button>
                    <button
                        @click="switchRentang('triwulan')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', filterForm.rentang === 'triwulan' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        📊 Triwulan
                    </button>
                    <button
                        @click="switchRentang('semester')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', filterForm.rentang === 'semester' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        📈 Semester
                    </button>
                    <button
                        @click="switchRentang('tahunan')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition', filterForm.rentang === 'tahunan' ? 'bg-white text-blue-700 shadow-xs' : 'text-gray-600 hover:text-gray-900']"
                    >
                        🏆 Tahunan
                    </button>
                </div>

                <!-- Unit & Year Picker -->
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="min-w-[180px]">
                        <select v-model="filterForm.unit_id" @change="applyFilters" class="w-full text-xs font-medium rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Unit Pelaksana</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>

                    <div class="w-28">
                        <select v-model="filterForm.tahun" @change="applyFilters" class="w-full text-xs font-medium rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option :value="2025">2025</option>
                            <option :value="2026">2026</option>
                            <option :value="2027">2027</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- RTL Consolidated Table -->
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4 min-w-[220px]">Indikator & Unit Pelaksana</th>
                                <th class="py-3 px-4 min-w-[200px]">Masalah & Akar Masalah</th>
                                <th class="py-3 px-4 min-w-[220px]">Rencana Tindak Lanjut (RTL)</th>
                                <th class="py-3 px-4 min-w-[130px]">PIC & Target</th>
                                <th class="py-3 px-4 min-w-[150px] text-center">Status Verifikasi</th>
                                <th class="py-3 px-4 min-w-[140px] text-center">Bukti Fisik</th>
                                <th class="py-3 px-4 text-center">Aksi Komite</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="records.data.length === 0">
                                <td colspan="8" class="text-center py-10 text-gray-400">
                                    Tidak ada data RTL untuk kategori {{ filterForm.kategori }} pada rentang waktu yang dipilih.
                                </td>
                            </tr>
                            <tr v-for="(item, idx) in records.data" :key="item.id" class="hover:bg-blue-50/40 transition">
                                <td class="py-3 px-4 text-center text-gray-400 font-mono">
                                    {{ (records.current_page - 1) * records.per_page + idx + 1 }}
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-gray-900 leading-snug">{{ item.indicator?.judul || 'Indikator Mutu' }}</p>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                            {{ item.unit?.nama || 'RS' }}
                                        </span>
                                        <span v-if="item.period" class="text-[10px] text-gray-400">
                                            Bulan {{ item.period.bulan }}/{{ item.period.tahun }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-gray-800 line-clamp-2"><span class="font-semibold text-red-600">Masalah:</span> {{ item.masalah }}</p>
                                    <p v-if="item.akar_masalah" class="text-gray-500 text-[11px] mt-1 line-clamp-2">
                                        <span class="font-medium text-gray-700">Akar:</span> {{ item.akar_masalah }}
                                    </p>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-gray-900 font-medium leading-relaxed">
                                        {{ item.rekomendasi_tindak_lanjut || item.plan || '-' }}
                                    </p>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-gray-800 font-medium">{{ item.pj_rtl || item.user?.nama_lengkap || item.user?.name || '-' }}</p>
                                    <p v-if="item.rencana_selesai" class="text-gray-400 text-[11px] mt-0.5">
                                        Target: {{ item.rencana_selesai }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span :class="['inline-flex px-2 py-1 rounded-full text-[10px] font-semibold border', getStatusBadge(item.status).class]">
                                        {{ getStatusBadge(item.status).label }}
                                    </span>
                                    <p v-if="item.catatan_review" class="text-[10px] text-gray-500 mt-1 italic">
                                        "{{ item.catatan_review }}"
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div v-if="item.bukti_rtl">
                                        <a :href="'/storage/' + item.bukti_rtl" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-medium hover:bg-emerald-100 transition shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Unduh Bukti</span>
                                        </a>
                                    </div>
                                    <span v-else class="text-gray-400 text-[11px] italic">Belum Ada</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        @click="openVerifyModal(item)"
                                        class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs transition shadow-xs"
                                    >
                                        Verifikasi
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="records.links && records.links.length > 3" class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Hal {{ records.current_page }} dari {{ records.last_page }}
                    </div>
                    <div class="flex gap-1">
                        <Link v-for="(link, lIdx) in records.links" :key="lIdx" :href="link.url || '#'" :class="['px-2.5 py-1 text-xs rounded', link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200', !link.url ? 'opacity-40 cursor-not-allowed' : '']" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Verifikasi RTL RS -->
        <div v-if="showVerifyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Verifikasi RTL Rumah Sakit</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Tinjau realisasi perbaikan mutu dan berkas bukti dukung untuk menetapkan status verifikasi Komite Mutu.
                </p>

                <div v-if="selectedItem" class="bg-blue-50/60 p-3 rounded-lg border border-blue-100 mb-4 text-xs">
                    <p class="font-semibold text-blue-900">{{ selectedItem.indicator?.judul }}</p>
                    <p class="text-blue-700 mt-0.5 line-clamp-2">{{ selectedItem.rekomendasi_tindak_lanjut || selectedItem.plan }}</p>
                </div>

                <form @submit.prevent="submitVerify" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Status Verifikasi Komite</label>
                        <select v-model="verifyForm.status" class="w-full text-xs font-semibold rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="reviewed">✓ Selesai & Terverifikasi Komite Mutu</option>
                            <option value="submitted">⏳ Masih Dalam Proses Pelaksanaan Unit</option>
                            <option value="draft">Draft / Perlu Perbaikan RTL</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Catatan / Arahan Komite Mutu</label>
                        <textarea v-model="verifyForm.catatan_review" rows="3" placeholder="Tuliskan arahan perbaikan lebih lanjut atau catatan supervisi..." class="w-full text-xs rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" @click="showVerifyModal = false" class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="verifyForm.processing" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md transition disabled:opacity-50">
                            {{ verifyForm.processing ? 'Menyimpan...' : 'Simpan Verifikasi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
