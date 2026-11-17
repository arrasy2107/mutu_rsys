<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    incident: Object,
});

const uploadForm = useForm({
    bukti_rtl: null,
});

const fileInputRef = ref(null);

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    uploadForm.bukti_rtl = file;
    uploadForm.post(route('ikp.upload-bukti', props.incident.id), {
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
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
        month: 'long',
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
        month: 'long',
        year: 'numeric',
    });
};

const printPage = () => {
    window.print();
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-rose-500 to-red-600 rounded-full inline-block"></span>
                        Detail Laporan IKP: {{ incident.nomor_laporan }}
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Rekam medis insiden keselamatan pasien dan status investigasi tindak lanjut
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="printPage"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Cetak Laporan
                    </button>
                    <Link
                        :href="route('ikp.edit', incident.id)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-xs transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Ubah Laporan
                    </Link>
                    <Link
                        :href="route('ikp.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition"
                    >
                        &larr; Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
            <!-- ─── TOP STATUS BANNER ────────────────────────────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 flex-wrap mb-1.5">
                        <span class="font-mono font-black text-lg text-gray-900">{{ incident.nomor_laporan }}</span>
                        <span :class="['px-2.5 py-0.5 text-xs rounded-md font-mono', jenisBadge(incident.jenis_insiden)]">
                            {{ incident.jenis_insiden }}
                        </span>
                        <span :class="['px-2.5 py-0.5 text-xs rounded-md font-bold uppercase tracking-wider', gradingBadge(incident.grading)]">
                            Grading: {{ incident.grading }}
                        </span>
                    </div>
                    <p class="text-sm font-bold text-gray-800">{{ incident.insiden }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Kategori: <span class="font-semibold text-gray-700">{{ incident.tipe_insiden }}</span>
                    </p>
                </div>

                <div class="sm:text-right">
                    <p class="text-[11px] text-gray-400">Status Penanganan</p>
                    <span
                        :class="[
                            'inline-block px-3 py-1 text-xs rounded-full font-bold uppercase tracking-wider mt-0.5',
                            incident.status === 'selesai' ? 'bg-emerald-100 text-emerald-700 border border-emerald-300' :
                            incident.status === 'investigasi' ? 'bg-amber-100 text-amber-700 border border-amber-300' :
                            'bg-gray-100 text-gray-700 border border-gray-300'
                        ]"
                    >
                        {{ incident.status }}
                    </span>
                </div>
            </div>

            <!-- ─── DETAIL METADATA ─────────────────────────────────────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kolom Kiri: Waktu & Unit -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-100 pb-2">
                        Waktu & Unit Kerja
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Tanggal & Jam Kejadian:</span>
                            <span class="font-bold text-gray-900">{{ formatDateTime(incident.tanggal_kejadian) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Tanggal Pelaporan:</span>
                            <span class="font-bold text-gray-900">{{ formatDate(incident.tanggal_laporan) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Unit Pelapor:</span>
                            <span class="font-bold text-blue-700">{{ incident.unit_pelapor?.nama || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Unit Terkait:</span>
                            <span class="font-bold text-gray-800">{{ incident.unit_terkait?.nama || 'Sama dengan pelapor' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Petugas Pembuat Laporan:</span>
                            <span class="font-semibold text-gray-700">{{ incident.creator?.nama_lengkap || incident.creator?.name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Investigasi & Kepatuhan Waktu -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-100 pb-2">
                        Investigasi & Kepatuhan Waktu
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Tipe Penyelesaian:</span>
                            <span v-if="incident.tipe_penyelesaian === 'rca'" class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                RCA / AAM (Analisis Akar Masalah)
                            </span>
                            <span v-else-if="incident.tipe_penyelesaian === 'investigasi_sederhana'" class="font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                Investigasi Sederhana
                            </span>
                            <span v-else class="text-gray-400 italic">Belum Ditentukan</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Tanggal Selesai Penyelesaian:</span>
                            <span class="font-bold text-gray-900">{{ formatDate(incident.tanggal_selesai_penyelesaian) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Kriteria Kepatuhan Waktu:</span>
                            <span v-if="incident.kriteria_waktu_penyelesaian === 'sesuai'" class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                Sesuai Standar Waktu
                            </span>
                            <span v-else-if="incident.kriteria_waktu_penyelesaian === 'tidak_sesuai'" class="font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                                Terlambat / Melewati Batas
                            </span>
                            <span v-else class="font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                Dalam Proses
                            </span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Penanggung Jawab (PJ) RTL:</span>
                            <span class="font-bold text-gray-800">{{ incident.penanggung_jawab_rtl || '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── KRONOLOGI INSIDEN ───────────────────────────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Kronologi Lengkap Peristiwa Kejadian
                </h3>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-xs text-gray-800 leading-relaxed whitespace-pre-line">
                    {{ incident.kronologi }}
                </div>
            </div>

            <!-- ─── RENCANA TINDAK LANJUT & BUKTI ───────────────────────────── -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Rencana Tindak Lanjut (RTL) & Bukti Pelaksanaan
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 mb-1">Uraian Tindak Lanjut:</p>
                        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 text-xs text-gray-800 leading-relaxed min-h-[90px] whitespace-pre-line">
                            {{ incident.rencana_tindak_lanjut || 'Belum ada uraian RTL yang dimasukkan.' }}
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-600">Berkas Bukti Pelaksanaan RTL:</p>

                        <div v-if="incident.bukti_rtl_path" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-8 h-8 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Berkas Bukti Tersedia</p>
                                    <p class="text-[10px] text-emerald-700">Lampiran bukti perbaikan insiden</p>
                                </div>
                            </div>
                            <a
                                :href="route('evidence.show', { type: 'ikp', record: incident.id })"
                                target="_blank"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-xs"
                            >
                                Buka Berkas
                            </a>
                        </div>

                        <div v-else class="p-4 bg-gray-50 border border-dashed border-gray-300 rounded-xl text-center">
                            <p class="text-xs text-gray-500 mb-2 font-medium">Belum ada berkas bukti yang diunggah.</p>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold cursor-pointer transition shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span>Unggah Berkas Bukti RTL</span>
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="handleFileUpload"
                                />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

