<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    selection: Object,
    meetingLeaders: Array,
});

const isExportDropdownOpen = ref(false);
const exportDropdownRef = ref(null);
const showPrintPreviewModal = ref(false);
const previewIframeRef = ref(null);
const isPdfLoading = ref(true);
const previewImageModal = ref(false);

const toggleExportDropdown = () => {
    isExportDropdownOpen.value = !isExportDropdownOpen.value;
};

const handleClickOutside = (e) => {
    if (exportDropdownRef.value && !exportDropdownRef.value.contains(e.target)) {
        isExportDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const openPrintPreview = () => {
    isExportDropdownOpen.value = false;
    isPdfLoading.value = true;
    showPrintPreviewModal.value = true;
};

const printPreviewDoc = () => {
    const iframe = previewIframeRef.value;
    if (iframe && iframe.contentWindow) {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            return;
        } catch (e) {
            console.warn('Iframe print error fallback:', e);
        }
    }
    window.open(route('indicator-selections.export-pdf', { indicatorSelection: props.selection.id, stream: 1 }), '_blank');
};

const isImage = (path) => {
    if (!path) return false;
    const ext = path.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
};

// IMP & IMU Logic (Read-Only di Halaman Show)
const impItem = computed(() => {
    if (props.selection.imp_item_id) {
        const found = props.selection.items?.find(it => it.id === props.selection.imp_item_id);
        if (found) return found;
    }
    const impMarked = props.selection.items?.find(it => it.is_imp);
    if (impMarked) return impMarked;
    return props.selection.items?.find(it => it.is_ditetapkan) || props.selection.items?.[0] || null;
});

const imuItems = computed(() => {
    if (!props.selection.items) return [];
    if (!impItem.value) return props.selection.items;
    return props.selection.items.filter(it => it.id !== impItem.value.id);
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    <Link :href="route('indicator-selections.index')" class="hover:text-indigo-600">Pemilihan IMUT</Link>
                    <span>/</span>
                    <span class="text-gray-800 font-medium">Rekapitulasi Pemilihan</span>
                </nav>
                <div class="flex items-center gap-2">
                    <!-- 1 Tombol Export Laporan dengan Dropdown Opsi -->
                    <div class="relative inline-block text-left" ref="exportDropdownRef">
                        <button
                            type="button"
                            @click.stop="toggleExportDropdown"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-2xs cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Export Laporan</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': isExportDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <!-- Dropdown Opsi: Print Preview, PDF, Excel -->
                        <div
                            v-if="isExportDropdownOpen"
                            class="absolute right-0 mt-2 w-64 rounded-xl bg-white shadow-xl ring-1 ring-black/5 divide-y divide-slate-100 z-50 focus:outline-none"
                            @click="isExportDropdownOpen = false"
                        >
                            <div class="p-1.5">
                                <button
                                    type="button"
                                    @click="openPrintPreview"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition text-left cursor-pointer"
                                >
                                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-bold">Pratinjau Cetak (Preview)</p>
                                        <p class="text-[10px] text-slate-400 font-normal">Cek dokumen sebelum print/unduh</p>
                                    </div>
                                </button>
                            </div>

                            <div class="p-1.5 space-y-1">
                                <a
                                    :href="route('indicator-selections.export-pdf', selection.id)"
                                    class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                                >
                                    <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-6 4h4"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-bold">Unduh PDF (.pdf)</p>
                                        <p class="text-[10px] text-slate-400 font-normal">Format A4 Landscape KOP RSYS</p>
                                    </div>
                                </a>

                                <a
                                    :href="route('indicator-selections.export-excel', selection.id)"
                                    class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition"
                                >
                                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-bold">Unduh Excel (.xlsx)</p>
                                        <p class="text-[10px] text-slate-400 font-normal">Sheet PEMILIHAN Standar RS</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Edit Sesi (Tempat Satu-satunya untuk Mengubah Data) -->
                    <Link :href="route('indicator-selections.edit', selection.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white hover:bg-amber-600 rounded-lg text-xs font-semibold transition-colors shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Sesi
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Header Pertemuan -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b pb-4 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900">
                                PEMILIHAN INDIKATOR MUTU {{ selection.tipe === 'rs' ? 'TINGKAT RUMAH SAKIT (IMP-RS)' : 'TINGKAT UNIT' }}
                            </h2>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold', selection.status === 'final' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                {{ selection.status === 'final' ? 'DITETAPKAN' : 'DRAFT' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ selection.tipe === 'rs' ? 'Sesuai Standar KARS 4 Area Fokus & Sasaran Keselamatan Pasien' : 'Sesuai Panduan Tata Cara Pemilihan & Skoring Prioritas Mutu Faskes' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 text-xs">
                    <div>
                        <p class="text-gray-400 font-medium">UNIT / PELAKSANA</p>
                        <p class="font-bold text-gray-800 text-sm mt-0.5">{{ selection.unit?.nama || 'Komite Mutu (RS)' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">HARI / TANGGAL</p>
                        <p class="font-bold text-gray-800 text-sm mt-0.5">
                            {{ new Date(selection.tanggal).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">JAM / WAKTU</p>
                        <p class="font-bold text-gray-800 text-sm mt-0.5">{{ selection.jam || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">TEMPAT / RUANG</p>
                        <p class="font-bold text-gray-800 text-sm mt-0.5">{{ selection.tempat || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 font-medium">PEMIMPIN RAPAT</p>
                        <p class="font-bold text-indigo-700 text-sm mt-0.5 truncate" :title="selection.pemimpin_rapat?.name || selection.pemimpin_rapat_nama || '-'">
                            {{ selection.pemimpin_rapat?.name || selection.pemimpin_rapat_nama || selection.created_by?.name || '-' }}
                        </p>
                    </div>
                </div>

                <div v-if="selection.notulen" class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-700">
                    <span class="font-bold">Notulen / Catatan:</span> {{ selection.notulen }}
                </div>
            </div>

            <!-- Bagian I: Skrining Algoritma Pemilihan -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            I. Algoritma Pemilihan Indikator Mutu {{ selection.tipe === 'rs' ? 'Prioritas Rumah Sakit (IMP-RS)' : 'Unit' }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Skrining kelayakan usulan berdasarkan 3 Tahapan Algoritma</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-slate-100/90 text-slate-700 font-semibold">
                        <tr>
                            <th rowspan="2" class="p-2 border text-center w-8">No</th>
                            <th rowspan="2" class="p-2 border text-left min-w-[200px]">Usulan IMUT</th>
                            <th rowspan="2" class="p-2 border text-left min-w-[220px]">Data Pendukung</th>
                            <th colspan="5" class="p-1 border text-center bg-slate-200/70 font-bold">Tahap 1: Kriteria Kelayakan</th>
                            <th colspan="2" class="p-1 border text-center bg-slate-200/70 font-bold">
                                {{ selection.tipe === 'rs' ? 'Tahap 2: Kendali RS' : 'Tahap 2: Kendali' }}
                            </th>
                            <th rowspan="2" class="p-2 border text-center bg-slate-200/70 w-20">Tahap 3: Dapat Diukur</th>
                            <th rowspan="2" class="p-2 border text-center bg-slate-200/70 w-28">Penetapan</th>
                        </tr>
                        <tr>
                            <th class="p-1.5 border text-center">Visi Misi</th>
                            <th class="p-1.5 border text-center">Problem Prone</th>
                            <th class="p-1.5 border text-center">High Volume</th>
                            <th class="p-1.5 border text-center">High Risk/Cost</th>
                            <th class="p-1.5 border text-center">Sesuai Aturan</th>
                            <th class="p-1.5 border text-center">{{ selection.tipe === 'rs' ? 'Kendali RS' : 'Kendali Unit' }}</th>
                            <th class="p-1.5 border text-center">{{ selection.tipe === 'rs' ? 'Regulasi RS' : 'Kendali RS' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(item, idx) in selection.items" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border font-bold text-gray-800">{{ item.usulan_imut }}</td>
                            <td class="p-2 border text-gray-600">{{ item.data_pendukung || '-' }}</td>

                            <!-- Tahap 1 -->
                            <td class="p-2 border text-center font-bold" :class="item.ssi_visi_misi ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.ssi_visi_misi ? '✓' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-bold" :class="item.problem_prone ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.problem_prone ? '✓' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-bold" :class="item.high_volume ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.high_volume ? '✓' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-bold" :class="item.high_risk_cost ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.high_risk_cost ? '✓' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-bold" :class="item.ssi_aturan ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.ssi_aturan ? '✓' : '-' }}
                            </td>

                            <!-- Tahap 2 -->
                            <td class="p-2 border text-center font-bold" :class="item.dpt_dikendalikan_unit ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.dpt_dikendalikan_unit ? '✓' : '-' }}
                            </td>
                            <td class="p-2 border text-center font-bold" :class="item.tdk_dpt_dikendalikan_rs ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.tdk_dpt_dikendalikan_rs ? '✓' : '-' }}
                            </td>

                            <!-- Tahap 3 -->
                            <td class="p-2 border text-center font-bold" :class="item.dpt_diukur ? 'text-blue-600' : 'text-gray-300'">
                                {{ item.dpt_diukur ? '✓' : '-' }}
                            </td>

                            <!-- Penetapan Status -->
                            <td class="p-2 border text-center">
                                <span :class="['px-2.5 py-1 rounded-full text-[11px] font-bold border inline-flex items-center gap-1.5', item.is_ditetapkan ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', item.is_ditetapkan ? 'bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.7)]' : 'bg-slate-400']"></span>
                                    {{ item.is_ditetapkan ? 'Ditetapkan' : 'Tidak Ditetapkan' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bagian II: Skoring Prioritas (Ramping Tanpa Kolom Kategori) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">II. Matriks Skoring Prioritas Indikator</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Penilaian bobot skala 1–5 untuk menentukan indikator mutu terpilih</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 font-semibold">
                        <tr>
                            <th class="p-2 border text-center w-8">No</th>
                            <th class="p-2 border text-left min-w-[200px]">Topik Usulan IMUT</th>
                            <th class="p-1.5 border text-center w-16" title="Masalah yang banyak di RS">K1</th>
                            <th class="p-1.5 border text-center w-16" title="High Volume">K2</th>
                            <th class="p-1.5 border text-center w-16" title="High Proses">K3</th>
                            <th class="p-1.5 border text-center w-16" title="Ketidakpuasan Pasien & Staf">K4</th>
                            <th class="p-1.5 border text-center w-16" title="Kemudahan Pengukuran">K5</th>
                            <th class="p-1.5 border text-center w-16" title="Ketentuan Pemerintah/Regulasi">K6</th>
                            <th class="p-1.5 border text-center w-16" title="Sesuai Renstra RS">K7</th>
                            <th class="p-1.5 border text-center w-16" title="Patient Experience">K8</th>
                            <th class="p-2 border text-center bg-slate-100 font-bold text-slate-700 w-20">Total Skor</th>
                            <th class="p-2 border text-center w-40">Tindak Lanjut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(item, idx) in selection.items" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border">
                                <p class="font-bold text-gray-800">{{ item.usulan_imut }}</p>
                            </td>

                            <td class="p-1.5 border text-center font-medium">{{ item.skor_masalah_banyak }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_high_volume }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_high_proses }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_ketidakpuasan }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_kemudahan_ukur }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_ketentuan_pemerintah }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_sesuai_renstra }}</td>
                            <td class="p-1.5 border text-center font-medium">{{ item.skor_patient_experience }}</td>

                            <td class="p-2 border text-center bg-slate-50 font-bold text-blue-700 text-sm">
                                {{ item.total_skor }}
                            </td>

                            <td class="p-2 border text-center">
                                <div>
                                    <Link :href="route('indicator-selections.create-profile', item.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[11px] font-bold transition-colors shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        {{ selection.tipe === 'rs' ? 'Jadikan Profil IMP-RS' : (impItem?.id === item.id ? 'Jadikan Profil IMP-U' : 'Jadikan Profil IMU') }}
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- BAGIAN III: KESIMPULAN HASIL PENETAPAN (Desain Compact, Bersih & Profesional) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 space-y-5">
                <div class="border-b border-slate-100 pb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">III. Kesimpulan Hasil Penetapan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Penggolongan Indikator Mutu Prioritas (IMP) & Indikator Mutu Unit (IMU), Pemimpin Rapat, serta Lampiran Berkas
                        </p>
                    </div>
                    <div class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                        Total Usulan: {{ selection.items?.length || 0 }} Indikator
                    </div>
                </div>

                <!-- 1. IMP & 2. IMU -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- 1. IMP : (Indikator Mutu Prioritas) -->
                    <div class="rounded-xl border border-blue-200 bg-white p-4 shadow-2xs space-y-3 relative">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-600 text-white shadow-2xs">
                                IMP :
                            </span>
                            <span class="text-xs font-semibold text-slate-600"> 
                                Indikator Mutu Prioritas Terpilih
                            </span>
                        </div>

                        <!-- Keterangan Penetapan IMP (Read-Only) -->
                        <div v-if="!impItem" class="py-4 text-center text-xs text-slate-400 italic">
                            Belum ada usulan yang ditetapkan sebagai IMP. Silakan tentukan melalui menu Edit Sesi.
                        </div>

                        <!-- Data Otomatis Muncul dari Usulan IMUT -->
                        <div v-if="impItem" class="pt-1 space-y-2">
                            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-200 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Usulan IMUT Terpilih:</span>
                                <h4 class="text-xs font-bold text-slate-900 leading-snug">
                                    {{ impItem.usulan_imut }}
                                </h4>
                                <p v-if="impItem.data_pendukung" class="text-[11px] text-slate-600 pt-1 border-t border-slate-200/60">
                                    <span class="font-semibold text-slate-700">Data Pendukung:</span>
                                    {{ impItem.data_pendukung }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="font-semibold text-slate-700">
                                    Total Skor: <span class="font-bold text-blue-700 text-sm">{{ impItem.total_skor }}</span> Poin
                                </span>
                                <Link :href="route('indicator-selections.create-profile', impItem.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold transition shadow-2xs"
                                >
                                    <span>Jadikan Profil {{ selection.tipe === 'rs' ? 'IMP-RS' : 'IMP-U' }} →</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- 2. IMU : (Indikator Mutu Unit) -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-700 text-white shadow-2xs">
                                IMU :
                            </span>
                            <span class="text-xs font-semibold text-slate-600">
                                Seluruh Usulan Selain IMP
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-500">
                            Seluruh usulan indikator mutu KECUALI yang telah dipilih sebagai IMP, otomatis digolongkan sebagai <strong>Indikator Mutu Unit (IMU)</strong>.
                        </p>

                        <!-- Daftar IMU -->
                        <div v-if="imuItems.length > 0" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            <div v-for="(it, idx) in imuItems" :key="it.id" class="p-2.5 bg-slate-50/60 rounded-lg border border-slate-200 hover:border-slate-300 transition space-y-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-slate-200/80 text-slate-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">
                                            {{ idx + 1 }}
                                        </span>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-800 leading-snug">{{ it.usulan_imut }}</p>
                                            <p v-if="it.data_pendukung" class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                                {{ it.data_pendukung }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-600 bg-white border border-slate-200 px-1.5 py-0.5 rounded shrink-0">
                                        Skor: {{ it.total_skor }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-end pt-0.5">
                                    <Link :href="route('indicator-selections.create-profile', it.id)"
                                        class="text-[11px] font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"
                                    >
                                        <span>Jadikan Profil IMU →</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-6 text-center text-xs text-slate-400 italic bg-slate-50/50 rounded-lg border border-dashed border-slate-200">
                            Tidak ada indikator lain. Semua usulan yang diinput telah ditetapkan sebagai IMP.
                        </div>
                    </div>
                </div>

                <!-- 3. Pemimpin Rapat -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            3. Pemimpin Rapat :
                        </label>
                        <span class="text-[11px] text-slate-600 bg-white border border-slate-200 px-2 py-0.5 rounded font-medium">
                            User PIC Unit / Kepala Unit
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-3.5 rounded-xl border border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                                    {{ selection.pemimpin_rapat ? (selection.pemimpin_rapat.role === 'kepala_unit' ? 'Kepala Unit' : 'PIC Unit') : 'Pemimpin Rapat' }}
                                </span>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">
                                    {{ selection.pemimpin_rapat?.name || selection.pemimpin_rapat_nama || selection.created_by?.name || '-' }}
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Unit: <strong>{{ selection.unit?.nama || 'Seluruh Rumah Sakit' }}</strong>
                                    <span v-if="selection.pemimpin_rapat?.username" class="text-slate-400 font-mono"> ({{ selection.pemimpin_rapat.username }})</span>
                                </p>
                            </div>
                        </div>

                        <!-- Kolom Tanda Tangan Formal untuk Cetak -->
                        <div class="border border-slate-200 bg-slate-50/50 p-2.5 rounded-lg text-center min-w-[180px]">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Tanda Tangan Pengesahan</p>
                            <div class="h-10 border-b border-slate-300 my-1 flex items-center justify-center text-[10px] text-slate-300 italic">
                                (Tanda Tangan & Cap)
                            </div>
                            <p class="text-xs font-bold text-slate-800">{{ selection.pemimpin_rapat?.name || selection.pemimpin_rapat_nama || selection.created_by?.name }}</p>
                            <p class="text-[10px] text-slate-500">{{ selection.pemimpin_rapat ? (selection.pemimpin_rapat.role === 'kepala_unit' ? 'Kepala Unit' : 'PIC Unit') : 'Pemimpin Rapat' }}</p>
                        </div>
                    </div>
                </div>

                <!-- 4. Upload File / Gambar Pendukung (Kondisi Upload, Edit, Delete) -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            4. Berkas / Gambar Lampiran Pendukung :
                        </label>
                        <span class="text-[11px] text-slate-400">
                            Foto Rapat, Daftar Hadir, Berita Acara, SK Penetapan (PDF, DOCX, XLSX, JPG, PNG)
                        </span>
                    </div>

                    <!-- KONDISI A: Berkas Sudah Ada -->
                    <div v-if="selection.berkas_pendukung" class="p-4 rounded-xl border border-slate-200 bg-white space-y-3 shadow-2xs">
                        <!-- Jika format Gambar -->
                        <div v-if="isImage(selection.berkas_pendukung)" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Pratinjau Foto Bukti Rapat:
                                </span>
                                <span class="text-[11px] text-slate-400 font-mono">
                                    {{ selection.berkas_pendukung.split('/').pop() }}
                                </span>
                            </div>
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-center">
                                <img
                                    :src="route('evidence.show', { type: 'indicator-selection', record: selection.id })"
                                    alt="Foto / Berkas Pendukung Rapat"
                                    class="max-h-64 w-auto mx-auto object-contain rounded-lg shadow-2xs cursor-pointer hover:opacity-95 transition"
                                    @click="previewImageModal = true"
                                    title="Klik untuk memperbesar gambar"
                                />
                            </div>
                        </div>

                        <!-- Jika format Dokumen (PDF, XLSX, DOCX) -->
                        <div v-else class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">
                                    {{ selection.berkas_pendukung.split('/').pop() }}
                                </p>
                                <p class="text-[11px] text-slate-400">Dokumen digital lampiran sesi penetapan indikator mutu</p>
                            </div>
                        </div>

                        <!-- Tombol Aksi Berkas: Buka / Unduh File (Read-Only di Show) -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                            <a
                                :href="route('evidence.show', { type: 'indicator-selection', record: selection.id })"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Buka / Unduh Berkas Lampiran</span>
                            </a>

                            <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                <span>Untuk mengganti/menghapus berkas, silakan buka</span>
                                <Link :href="route('indicator-selections.edit', selection.id)" class="text-blue-600 hover:text-blue-800 font-semibold underline">
                                    Halaman Edit Sesi
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- KONDISI B: Belum Ada Berkas -->
                    <div v-else class="p-6 rounded-xl border border-dashed border-slate-300 bg-white text-center space-y-2">
                        <div class="mx-auto w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <p class="text-xs font-medium text-slate-700">Belum ada file dokumen atau gambar yang dilampirkan</p>
                        <p class="text-[11px] text-slate-400">Lampirkan foto rapat pemilihan, daftar hadir, atau notulen berita acara melalui halaman Edit Sesi.</p>
                        <Link
                            :href="route('indicator-selections.edit', selection.id)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition shadow-2xs mt-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Buka Halaman Edit untuk Melampirkan File</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Print Preview Interaktif (Cek Terlebih Dahulu Sebelum Cetak / Download) -->
        <div v-if="showPrintPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-3 sm:p-5 backdrop-blur-xs">
            <div class="bg-white rounded-2xl w-full max-w-6xl h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200">
                <!-- Header Modal Preview -->
                <div class="px-5 py-3.5 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-600/30 border border-blue-400/40 text-blue-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold tracking-tight">Pratinjau Cetak Lembar Rekapitulasi Pemilihan IMUT</h3>
                            <p class="text-[11px] text-slate-400">
                                {{ selection.unit?.nama || 'Komite Mutu (RS)' }} &bull; Tanggal {{ new Date(selection.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi di Header Preview: Print Langsung, Download PDF, Download Excel, Tutup -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="printPreviewDoc"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-semibold transition shadow-sm cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Sekarang</span>
                        </button>
                        <a
                            :href="route('indicator-selections.export-pdf', selection.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold transition shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-6 4h4"/></svg>
                            <span>Unduh PDF</span>
                        </a>
                        <a
                            :href="route('indicator-selections.export-excel', selection.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Unduh Excel</span>
                        </a>
                        <button
                            type="button"
                            @click="showPrintPreviewModal = false"
                            class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition ml-1 cursor-pointer"
                            title="Tutup Pratinjau"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Body Modal Preview: Iframe PDF Stream -->
                <div class="relative flex-1 bg-slate-200/80 overflow-hidden">
                    <div v-if="isPdfLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-100 z-10">
                        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-3"></div>
                        <p class="text-xs font-bold text-slate-700">Menyiapkan Pratinjau Dokumen Cetak...</p>
                        <p class="text-[11px] text-slate-400 mt-1">Format A4 Landscape KOP Rumah Sakit Yos Sudarso</p>
                    </div>
                    <iframe
                        ref="previewIframeRef"
                        :src="route('indicator-selections.export-pdf', { indicatorSelection: selection.id, stream: 1 })"
                        @load="isPdfLoading = false"
                        class="w-full h-full border-0"
                    ></iframe>
                </div>
            </div>
        </div>

        <!-- Lightbox Modal untuk Preview Gambar Fullscreen -->
        <div v-if="previewImageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="previewImageModal = false">
            <div class="max-w-4xl max-h-[90vh] bg-white rounded-2xl overflow-hidden shadow-2xl relative p-3">
                <button @click="previewImageModal = false" class="absolute top-4 right-4 bg-black/60 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-black transition z-10 font-bold">
                    ✕
                </button>
                <img
                    :src="route('evidence.show', { type: 'indicator-selection', record: selection.id })"
                    class="mx-auto max-h-[82vh] w-auto rounded-lg object-contain"
                />
                <div class="text-center pt-2">
                    <a :href="route('evidence.show', { type: 'indicator-selection', record: selection.id })" target="_blank" class="text-xs text-indigo-600 hover:underline font-semibold">
                        Buka di Tab Baru / Download Gambar Asli
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
