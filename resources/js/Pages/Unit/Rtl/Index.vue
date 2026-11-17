<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    records: Object,
    stats: Object,
    units: Array,
    filters: Object,
});

const formFilter = ref({
    unit_id: props.filters.unit_id || '',
    tahun: props.filters.tahun || 2026,
    status: props.filters.status || '',
});

const applyFilter = () => {
    router.get(route('unit.rtl.index'), formFilter.value, { preserveState: true });
};

// Modal Upload Bukti
const showUploadModal = ref(false);
const activeRecord = ref(null);
const uploadForm = useForm({
    berkas: null,
    keterangan: '',
});

const openUploadModal = (record) => {
    activeRecord.value = record;
    uploadForm.reset();
    showUploadModal.value = true;
};

const handleFileChange = (e) => {
    uploadForm.berkas = e.target.files[0];
};

const submitUpload = () => {
    if (!activeRecord.value || !uploadForm.berkas) return;

    uploadForm.post(route('unit.rtl.upload-bukti', activeRecord.value.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            window.notify?.toast?.success('Sukses', 'Berkas bukti RTL berhasil diunggah.');
        },
        onError: () => {
            window.notify?.toast?.error('Gagal', 'Terjadi kesalahan saat mengunggah berkas.');
        }
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'reviewed':
            return { label: 'Selesai & Diverifikasi', class: 'bg-emerald-100 text-emerald-800 border-emerald-300' };
        case 'submitted':
        case 'in_progress':
            return { label: 'Sedang Berjalan', class: 'bg-amber-100 text-amber-800 border-amber-300' };
        default:
            return { label: 'Draft RTL', class: 'bg-gray-100 text-gray-800 border-gray-300' };
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Rencana Tindak Lanjut (RTL) Unit" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold uppercase tracking-wider backdrop-blur-sm mb-2">
                            <span>📋 Butir A.6 Roadmap Mutu</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Tingkat Unit</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">Rencana Tindak Lanjut (RTL) Unit</h1>
                        <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                            Monitoring rencana perbaikan mutu unit kerja, realisasi tindak lanjut, dan unggah berkas bukti fisik (SPO revisi, foto sosialisasi, notulen rapat).
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link :href="route('pdsa.create')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-blue-900 font-semibold rounded-xl shadow-md hover:bg-blue-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Buat RTL / PDSA Baru</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total RTL Unit</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                        📋
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Selesai / Terverifikasi</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.selesai }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        ✓
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sedang Berjalan</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.berjalan }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                        ⏳
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bukti Terunggah</p>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ stats.ada_bukti }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">
                        📎
                    </div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <div v-if="units && units.length > 0" class="min-w-[180px]">
                        <select v-model="formFilter.unit_id" @change="applyFilter" class="w-full text-xs font-medium rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Unit Kerja</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                    </div>

                    <div class="w-28">
                        <select v-model="formFilter.tahun" @change="applyFilter" class="w-full text-xs font-medium rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option :value="2025">2025</option>
                            <option :value="2026">2026</option>
                            <option :value="2027">2027</option>
                        </select>
                    </div>

                    <div class="min-w-[150px]">
                        <select v-model="formFilter.status" @change="applyFilter" class="w-full text-xs font-medium rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Diajukan</option>
                            <option value="reviewed">Selesai / Terverifikasi</option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-gray-500">
                    Menampilkan <strong>{{ records.total }}</strong> item RTL
                </div>
            </div>

            <!-- RTL Table -->
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4 min-w-[220px]">Indikator & Unit</th>
                                <th class="py-3 px-4 min-w-[200px]">Identifikasi Masalah & Akar 5M</th>
                                <th class="py-3 px-4 min-w-[220px]">Rencana Tindak Lanjut (RTL)</th>
                                <th class="py-3 px-4 min-w-[130px]">PIC & Target</th>
                                <th class="py-3 px-4 min-w-[150px] text-center">Status RTL</th>
                                <th class="py-3 px-4 min-w-[140px] text-center">Berkas Bukti</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="records.data.length === 0">
                                <td colspan="8" class="text-center py-8 text-gray-400">
                                    Belum ada data Rencana Tindak Lanjut (RTL) untuk filter yang dipilih.
                                </td>
                            </tr>
                            <tr v-for="(item, idx) in records.data" :key="item.id" class="hover:bg-blue-50/40 transition">
                                <td class="py-3 px-4 text-center text-gray-400 font-mono">
                                    {{ (records.current_page - 1) * records.per_page + idx + 1 }}
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-gray-900 leading-snug">{{ item.indicator?.judul || 'Indikator Mutu' }}</p>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700">
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
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div v-if="item.bukti_rtl">
                                        <a
                                            :href="route('evidence.show', { type: 'pdsa', record: item.id })"
                                            target="_blank"
                                            class="shadow-xs inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 transition hover:bg-emerald-100"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Lihat Bukti</span>
                                        </a>
                                        <button @click="openUploadModal(item)" class="block text-[10px] text-gray-400 hover:text-blue-600 mt-1 mx-auto underline">
                                            Ganti File
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button @click="openUploadModal(item)" class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 text-gray-600 border border-dashed border-gray-300 rounded-lg text-[11px] font-medium hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                            <span>Upload Bukti</span>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Link :href="route('pdsa.show', item.id)" class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                        Detail PDSA →
                                    </Link>
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

        <!-- Modal Upload Bukti RTL -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Unggah Berkas Bukti RTL</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Lampirkan dokumen bukti pelaksanaan seperti SPO baru, notulen rapat koordinasi, atau dokumentasi foto pendukung.
                </p>

                <div v-if="activeRecord" class="bg-blue-50/60 p-3 rounded-lg border border-blue-100 mb-4 text-xs">
                    <p class="font-semibold text-blue-900">{{ activeRecord.indicator?.judul }}</p>
                    <p class="text-blue-700 mt-0.5 line-clamp-1">{{ activeRecord.rekomendasi_tindak_lanjut || activeRecord.plan }}</p>
                </div>

                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Pilih File Bukti (PDF, JPG, PNG, DOCX max 10MB)</label>
                        <input type="file" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx" required class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-xl cursor-pointer" />
                        <p v-if="uploadForm.errors.berkas" class="text-xs text-red-600 mt-1">{{ uploadForm.errors.berkas }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="uploadForm.processing || !uploadForm.berkas" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md transition disabled:opacity-50">
                            {{ uploadForm.processing ? 'Mengunggah...' : 'Simpan Berkas' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
