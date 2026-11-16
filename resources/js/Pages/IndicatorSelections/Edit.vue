<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    selection: Object,
    units: Array,
    meetingLeaders: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isUnitUser = computed(() => ['pic_unit', 'kepala_unit'].includes(currentUser.value?.role));

const form = useForm({
    unit_id: props.selection.unit_id || '',
    tipe: props.selection.tipe || 'unit',
    tanggal: props.selection.tanggal ? props.selection.tanggal.split('T')[0] : '',
    jam: props.selection.jam || '',
    tempat: props.selection.tempat || '',
    notulen: props.selection.notulen || '',
    status: props.selection.status || 'draft',
    pemimpin_rapat_id: props.selection.pemimpin_rapat_id || '',
    pemimpin_rapat_nama: props.selection.pemimpin_rapat_nama || '',
    berkas: null,
    hapus_berkas: false,
    items: props.selection.items?.map((it, idx) => ({
        id: it.id,
        usulan_imut: it.usulan_imut,
        data_pendukung: it.data_pendukung || '',
        ssi_visi_misi: Boolean(it.ssi_visi_misi),
        problem_prone: Boolean(it.problem_prone),
        high_volume: Boolean(it.high_volume),
        high_risk_cost: Boolean(it.high_risk_cost),
        ssi_aturan: Boolean(it.ssi_aturan),
        dpt_dikendalikan_unit: Boolean(it.dpt_dikendalikan_unit),
        tdk_dpt_dikendalikan_rs: Boolean(it.tdk_dpt_dikendalikan_rs),
        dpt_diukur: Boolean(it.dpt_diukur),
        is_lolos_skrining: Boolean(it.is_lolos_skrining),
        skor_masalah_banyak: it.skor_masalah_banyak,
        skor_high_volume: it.skor_high_volume,
        skor_high_proses: it.skor_high_proses,
        skor_ketidakpuasan: it.skor_ketidakpuasan,
        skor_kemudahan_ukur: it.skor_kemudahan_ukur,
        skor_ketentuan_pemerintah: it.skor_ketentuan_pemerintah,
        skor_sesuai_renstra: it.skor_sesuai_renstra,
        skor_patient_experience: it.skor_patient_experience,
        is_imp: props.selection.imp_item_id ? (it.id === props.selection.imp_item_id) : (Boolean(it.is_imp) || idx === 0),
        is_ditetapkan: Boolean(it.is_ditetapkan),
    })) || []
});

const activeTab = ref('algoritma');

const addItem = () => {
    const isFirst = form.items.length === 0;
    form.items.push({
        id: null,
        usulan_imut: '',
        data_pendukung: '',
        ssi_visi_misi: false,
        problem_prone: false,
        high_volume: false,
        high_risk_cost: false,
        ssi_aturan: false,
        dpt_dikendalikan_unit: false,
        tdk_dpt_dikendalikan_rs: false,
        dpt_diukur: false,
        skor_masalah_banyak: 3,
        skor_high_volume: 3,
        skor_high_proses: 3,
        skor_ketidakpuasan: 3,
        skor_kemudahan_ukur: 3,
        skor_ketentuan_pemerintah: 3,
        skor_sesuai_renstra: 3,
        skor_patient_experience: 3,
        is_imp: isFirst,
        is_ditetapkan: false,
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        const wasImp = form.items[index]?.is_imp;
        form.items.splice(index, 1);
        if (wasImp && form.items.length > 0) {
            form.items[0].is_imp = true;
        }
    }
};

const hitungTotal = (item) => {
    return Number(item?.skor_masalah_banyak || 0) +
        Number(item?.skor_high_volume || 0) +
        Number(item?.skor_high_proses || 0) +
        Number(item?.skor_ketidakpuasan || 0) +
        Number(item?.skor_kemudahan_ukur || 0) +
        Number(item?.skor_ketentuan_pemerintah || 0) +
        Number(item?.skor_sesuai_renstra || 0) +
        Number(item?.skor_patient_experience || 0);
};

// IMP & IMU Logic
const impIndex = computed(() => {
    const idx = form.items.findIndex(it => it.is_imp);
    return idx !== -1 ? idx : 0;
});

const impItem = computed(() => {
    if (form.items.length === 0) return null;
    return form.items[impIndex.value] || form.items[0];
});

const imuItems = computed(() => {
    return form.items.filter((it, idx) => idx !== impIndex.value);
});

const selectImp = (targetIndex) => {
    form.items.forEach((it, idx) => {
        it.is_imp = (idx === targetIndex);
        if (it.is_imp) {
            it.is_ditetapkan = true;
        }
    });
};

// Pemimpin Rapat Logic
const onLeaderChange = () => {
    if (form.pemimpin_rapat_id) {
        const found = props.meetingLeaders?.find(l => l.id == form.pemimpin_rapat_id);
        if (found) {
            form.pemimpin_rapat_nama = found.name;
        }
    }
};

const selectedLeader = computed(() => {
    if (!form.pemimpin_rapat_id) return null;
    return props.meetingLeaders?.find(l => l.id == form.pemimpin_rapat_id) || null;
});

// File upload & management
const fileInputRef = ref(null);
const filePreviewUrl = ref(null);
const isImageFile = ref(false);

const isExistingImage = (path) => {
    if (!path) return false;
    const ext = path.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
};

const handleFileUpload = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    form.berkas = file;
    form.hapus_berkas = false;
    isImageFile.value = file.type.startsWith('image/');
    if (isImageFile.value) {
        if (filePreviewUrl.value) URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = URL.createObjectURL(file);
    } else {
        filePreviewUrl.value = null;
    }
};

const removeNewFile = () => {
    form.berkas = null;
    if (filePreviewUrl.value) {
        URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = null;
    }
    isImageFile.value = false;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const markDeleteExistingFile = () => {
    form.hapus_berkas = true;
    form.berkas = null;
    if (filePreviewUrl.value) {
        URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = null;
    }
};

const restoreExistingFile = () => {
    form.hapus_berkas = false;
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(route('indicator-selections.update', props.selection.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('indicator-selections.index', { tipe: form.tipe })" class="hover:text-indigo-600">
                    {{ form.tipe === 'rs' ? 'Pemilihan IMUT Prioritas RS' : 'Pemilihan IMUT Unit' }}
                </Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Edit Sesi Pemilihan</span>
            </nav>
        </template>

        <form @submit.prevent="submit" class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Header Data Pertemuan -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-bold text-gray-800 mb-4 pb-2 border-b">1. Informasi Pertemuan Pemilihan</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tingkat Pemilihan</label>
                        <select v-if="!isUnitUser" v-model="form.tipe" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="unit">Tingkat Unit</option>
                            <option value="rs">Tingkat Rumah Sakit (IMP-RS)</option>
                        </select>
                        <div v-else class="px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm font-semibold text-slate-700">
                            {{ form.tipe === 'rs' ? 'Tingkat Rumah Sakit (IMP-RS)' : 'Tingkat Unit' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Unit Pelaksana</label>
                        <select v-if="!isUnitUser" v-model="form.unit_id" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Komite Mutu (RS)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                        </select>
                        <div v-else class="px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm font-semibold text-slate-700">
                            {{ selection.unit?.nama || currentUser?.unit?.nama || 'Unit Anda' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Hari & Tanggal *</label>
                        <input v-model="form.tanggal" type="date" required class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Jam Pelaksanaan</label>
                        <input v-model="form.jam" type="text" placeholder="Contoh: 12:00 WIB" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tempat / Ruangan</label>
                        <input v-model="form.tempat" type="text" placeholder="Contoh: Ruang Rapat Unit IRJ" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Status Sesi</label>
                        <select v-model="form.status" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="draft">Draft (Masih dalam pembahasan)</option>
                            <option value="final">Final (Sudah ditetapkan)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tab Navigasi -->
            <div class="flex items-center justify-between bg-white px-6 py-3 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex gap-2">
                    <button type="button" @click="activeTab = 'algoritma'" :class="['px-4 py-2 text-xs font-bold rounded-lg transition-colors', activeTab === 'algoritma' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                        Tahap 1: Skrining Algoritma (3 Tahap)
                    </button>
                    <button type="button" @click="activeTab = 'skoring'" :class="['px-4 py-2 text-xs font-bold rounded-lg transition-colors', activeTab === 'skoring' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                        Tahap 2: Skoring Matriks Prioritas (8 Kriteria)
                    </button>
                </div>
                <button type="button" @click="addItem" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Usulan IMUT
                </button>
            </div>

            <!-- Tab 1: Skrining Algoritma -->
            <div v-show="activeTab === 'algoritma'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-gray-800">Skrining Algoritma Pemilihan Indikator Mutu Unit</h3>
                    <p class="text-xs text-gray-500">Beri centang (✓) pada kriteria yang memenuhi syarat.</p>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-slate-100/90 text-slate-700 font-semibold">
                        <tr>
                            <th rowspan="2" class="p-2 border text-center w-8">No</th>
                            <th rowspan="2" class="p-2 border text-left min-w-[200px]">Usulan IMUT *</th>
                            <th rowspan="2" class="p-2 border text-left min-w-[220px]">Data Pendukung (Masalah)</th>
                            <th colspan="5" class="p-1 border text-center bg-slate-200/70 font-bold">Tahap 1: Kriteria Kelayakan</th>
                            <th colspan="2" class="p-1 border text-center bg-slate-200/70 font-bold">
                                {{ form.tipe === 'rs' ? 'Tahap 2: Kendali RS' : 'Tahap 2: Kendali' }}
                            </th>
                            <th rowspan="2" class="p-2 border text-center bg-slate-200/70 w-20">Tahap 3: Dapat Diukur</th>
                            <th rowspan="2" class="p-2 border text-center bg-slate-200/70 w-28">Penetapan (Ditetapkan)</th>
                            <th rowspan="2" class="p-2 border text-center w-10">Aksi</th>
                        </tr>
                        <tr>
                            <th class="p-1.5 border text-center">Visi Misi</th>
                            <th class="p-1.5 border text-center">Problem Prone</th>
                            <th class="p-1.5 border text-center">High Volume</th>
                            <th class="p-1.5 border text-center">High Risk/Cost</th>
                            <th class="p-1.5 border text-center">Sesuai Aturan</th>
                            <th class="p-1.5 border text-center">{{ form.tipe === 'rs' ? 'Kendali RS' : 'Kendali Unit' }}</th>
                            <th class="p-1.5 border text-center">{{ form.tipe === 'rs' ? 'Regulasi RS' : 'Kendali RS' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border">
                                <textarea v-model="item.usulan_imut" required rows="2" class="w-full text-xs border-gray-200 rounded p-1.5 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </td>
                            <td class="p-2 border">
                                <textarea v-model="item.data_pendukung" rows="2" class="w-full text-xs border-gray-200 rounded p-1.5 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </td>
                            
                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.ssi_visi_misi" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>
                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.problem_prone" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>
                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.high_volume" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>
                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.high_risk_cost" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>
                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.ssi_aturan" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>

                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.dpt_dikendalikan_unit" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>
                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.tdk_dpt_dikendalikan_rs" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>

                            <td class="p-2 border text-center">
                                <input type="checkbox" v-model="item.dpt_diukur" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                            </td>

                            <!-- Penetapan (Ditetapkan) Status -->
                            <td class="p-2 border text-center">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" v-model="item.is_ditetapkan" class="rounded text-emerald-600 focus:ring-emerald-500 h-5 w-5" title="Centang jika usulan ditetapkan" />
                                    <span :class="['text-xs font-semibold', item.is_ditetapkan ? 'text-emerald-700 font-bold' : 'text-slate-400']">
                                        {{ item.is_ditetapkan ? 'Ditetapkan' : 'Tidak' }}
                                    </span>
                                </label>
                            </td>

                            <td class="p-2 border text-center">
                                <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Skoring Matriks Prioritas -->
            <div v-show="activeTab === 'skoring'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-x-auto">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-gray-800">Skoring Matriks Prioritas (Skala 1 - 5)</h3>
                </div>

                <!-- Tabel Panduan Bobot Skala Skoring Sesuai Juknis -->
                <div class="mb-5 border border-amber-200 bg-amber-50/40 rounded-xl p-3">
                    <p class="text-xs font-bold text-amber-900 mb-2 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Panduan Bobot Skala Nilai Matriks Skoring (1 - 5)
                    </p>
                    <div class="overflow-x-auto border border-amber-200 rounded-lg bg-white">
                        <table class="min-w-full text-[11px] text-center border-collapse">
                            <thead class="bg-amber-100/70 font-bold text-amber-950">
                                <tr>
                                    <th class="p-1.5 border border-amber-200 bg-amber-200/60 w-12 text-center">Nilai</th>
                                    <th class="p-1.5 border border-amber-200 text-center">Masalah yang Banyak di RS</th>
                                    <th class="p-1.5 border border-amber-200 text-center">High Volume</th>
                                    <th class="p-1.5 border border-amber-200 text-center">High Proses</th>
                                    <th class="p-1.5 border border-amber-200 text-center">Ketidakpuasan Pasien & Staf</th>
                                    <th class="p-1.5 border border-amber-200 text-center">Kemudahan dalam pengukuran</th>
                                    <th class="p-1.5 border border-amber-200 text-center">Ketentuan Pemerintah / Eksternal</th>
                                    <th class="p-1.5 border border-amber-200 text-center">Sesuai dengan Renstra RS</th>
                                    <th class="p-1.5 border border-amber-200 text-center">Patient Experience</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-200 text-gray-700">
                                <tr class="hover:bg-amber-50/50">
                                    <td class="p-1.5 border border-amber-200 font-bold bg-amber-50/60 text-amber-900">1</td>
                                    <td class="p-1.5 border border-amber-200">tidak ada</td>
                                    <td class="p-1.5 border border-amber-200">tidak ada</td>
                                    <td class="p-1.5 border border-amber-200">tidak berisiko</td>
                                    <td class="p-1.5 border border-amber-200">tidak tinggi</td>
                                    <td class="p-1.5 border border-amber-200">tidak mudah</td>
                                    <td class="p-1.5 border border-amber-200">tidak perlu</td>
                                    <td class="p-1.5 border border-amber-200">tidak</td>
                                    <td class="p-1.5 border border-amber-200">tidak baik</td>
                                </tr>
                                <tr class="hover:bg-amber-50/50">
                                    <td class="p-1.5 border border-amber-200 font-bold bg-amber-50/60 text-amber-900">2</td>
                                    <td class="p-1.5 border border-amber-200">kurang</td>
                                    <td class="p-1.5 border border-amber-200">kurang</td>
                                    <td class="p-1.5 border border-amber-200">kurang</td>
                                    <td class="p-1.5 border border-amber-200">kurang</td>
                                    <td class="p-1.5 border border-amber-200">kurang</td>
                                    <td class="p-1.5 border border-amber-200">bisa tidak</td>
                                    <td class="p-1.5 border border-amber-200">kurang</td>
                                    <td class="p-1.5 border border-amber-200">kurang baik</td>
                                </tr>
                                <tr class="hover:bg-amber-50/50">
                                    <td class="p-1.5 border border-amber-200 font-bold bg-amber-50/60 text-amber-900">3</td>
                                    <td class="p-1.5 border border-amber-200">cukup</td>
                                    <td class="p-1.5 border border-amber-200">cukup</td>
                                    <td class="p-1.5 border border-amber-200">cukup</td>
                                    <td class="p-1.5 border border-amber-200">cukup</td>
                                    <td class="p-1.5 border border-amber-200">cukup</td>
                                    <td class="p-1.5 border border-amber-200">bisa tidak</td>
                                    <td class="p-1.5 border border-amber-200">cukup</td>
                                    <td class="p-1.5 border border-amber-200">cukup baik</td>
                                </tr>
                                <tr class="hover:bg-amber-50/50">
                                    <td class="p-1.5 border border-amber-200 font-bold bg-amber-50/60 text-amber-900">4</td>
                                    <td class="p-1.5 border border-amber-200">banyak</td>
                                    <td class="p-1.5 border border-amber-200">banyak</td>
                                    <td class="p-1.5 border border-amber-200">tinggi</td>
                                    <td class="p-1.5 border border-amber-200">tinggi</td>
                                    <td class="p-1.5 border border-amber-200">mudah</td>
                                    <td class="p-1.5 border border-amber-200">sebaiknya</td>
                                    <td class="p-1.5 border border-amber-200">sesuai</td>
                                    <td class="p-1.5 border border-amber-200">lebih baik</td>
                                </tr>
                                <tr class="hover:bg-amber-50/50">
                                    <td class="p-1.5 border border-amber-200 font-bold bg-amber-50/60 text-amber-900">5</td>
                                    <td class="p-1.5 border border-amber-200">sangat</td>
                                    <td class="p-1.5 border border-amber-200">sangat</td>
                                    <td class="p-1.5 border border-amber-200">sangat</td>
                                    <td class="p-1.5 border border-amber-200">sangat</td>
                                    <td class="p-1.5 border border-amber-200">sangat</td>
                                    <td class="p-1.5 border border-amber-200">harus</td>
                                    <td class="p-1.5 border border-amber-200">sangat sesuai</td>
                                    <td class="p-1.5 border border-amber-200">sangat lebih</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-xs border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="p-2 border text-center w-8">No</th>
                            <th class="p-2 border text-left min-w-[200px]">Topik Usulan IMUT</th>
                            <th class="p-1.5 border text-center w-20">Masalah di RS</th>
                            <th class="p-1.5 border text-center w-20">High Volume</th>
                            <th class="p-1.5 border text-center w-20">High Proses</th>
                            <th class="p-1.5 border text-center w-20">Ketidakpuasan</th>
                            <th class="p-1.5 border text-center w-20">Mudah Diukur</th>
                            <th class="p-1.5 border text-center w-20">Regulasi</th>
                            <th class="p-1.5 border text-center w-20">Renstra RS</th>
                            <th class="p-1.5 border text-center w-20">Patient Exp</th>
                            <th class="p-2 border text-center bg-slate-100 font-bold text-slate-700 w-24">Total Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 border text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                            <td class="p-2 border">
                                <p class="font-bold text-gray-800">{{ item.usulan_imut || '(Belum diisi)' }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-xs">{{ item.data_pendukung }}</p>
                            </td>

                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_masalah_banyak" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_high_volume" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_high_proses" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_ketidakpuasan" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_kemudahan_ukur" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_ketentuan_pemerintah" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_sesuai_renstra" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>
                            <td class="p-1.5 border text-center">
                                <select v-model.number="item.skor_patient_experience" class="w-full text-xs border-gray-200 rounded p-1 text-center font-bold">
                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </td>

                            <td class="p-2 border text-center bg-slate-50">
                                <span class="text-sm font-bold text-blue-700">
                                    {{ hitungTotal(item) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- BAGIAN III: KESIMPULAN HASIL PENETAPAN (Sesuai Butir Kebutuhan User) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
                <div class="border-b pb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">III. Kesimpulan Hasil Penetapan</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Penetapan Indikator Mutu Prioritas (IMP) & Indikator Mutu Unit (IMU), Pemimpin Rapat, serta Lampiran Berkas/Gambar
                        </p>
                    </div>
                    <div class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                        Total Usulan: {{ form.items.length }} Indikator
                    </div>
                </div>

                <!-- 1. IMP & 2. IMU (Grid 2 Kolom) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- 1. IMP : (Indikator Mutu Prioritas) -->
                    <div class="rounded-xl border border-blue-200 bg-white p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-600 text-white shadow-2xs">
                                IMP :
                            </span>
                            <span class="text-xs font-semibold text-slate-600">
                                Indikator Mutu Prioritas Terpilih
                            </span>
                        </div>

                        <!-- Pill Switcher jika ada usulan untuk dipilih -->
                        <div v-if="form.items.length > 0" class="space-y-1.5 pt-1">
                            <span class="text-[11px] font-semibold text-slate-600 block">
                                Klik untuk memilih Indikator Mutu yang menjadi IMP :
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="(it, idx) in form.items"
                                    :key="idx"
                                    @click="selectImp(idx)"
                                    type="button"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-xs font-medium transition border text-left flex items-center gap-1.5 cursor-pointer',
                                        it.is_imp
                                            ? 'bg-blue-600 text-white border-blue-600 shadow-2xs font-semibold'
                                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                                    ]"
                                    :title="'Klik untuk memilih usulan nomor ' + (idx + 1) + ' sebagai IMP'"
                                >
                                    <span :class="['w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold', it.is_imp ? 'bg-white text-blue-600' : 'bg-slate-200 text-slate-700']">
                                        {{ idx + 1 }}
                                    </span>
                                    <span class="truncate max-w-[150px]">{{ it.usulan_imut || ('Usulan #' + (idx + 1)) }}</span>
                                    <span v-if="it.is_imp">✓</span>
                                </button>
                            </div>
                        </div>

                        <!-- Data Otomatis Muncul dari Usulan IMUT -->
                        <div v-if="impItem" class="pt-1 space-y-2">
                            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-200 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                                    Data Usulan IMUT Terpilih (Otomatis):
                                </span>
                                <h4 class="text-xs font-bold text-slate-900 leading-snug">
                                    {{ impItem.usulan_imut || '(Nama usulan belum diisi pada tabel di atas)' }}
                                </h4>
                                <div class="text-[11px] text-slate-600 pt-1 border-t border-slate-200/60">
                                    <span class="font-semibold text-slate-700">Latar Belakang / Data Pendukung:</span>
                                    <p class="mt-0.5 text-slate-600 italic">
                                        {{ impItem.data_pendukung || 'Belum ada data pendukung yang diinput.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="font-semibold text-slate-700">
                                    Total Skor Prioritas:
                                </span>
                                <span class="text-sm font-bold text-blue-700">
                                    {{ hitungTotal(impItem) }} Poin
                                </span>
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
                            Seluruh indikator mutu <strong>KECUALI</strong> yang telah dipilih untuk menjadi IMP, secara otomatis digolongkan sebagai <strong>Indikator Mutu Unit (IMU)</strong>:
                        </p>

                        <!-- Daftar IMU -->
                        <div v-if="imuItems.length > 0" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            <div v-for="(it, idx) in imuItems" :key="idx" class="p-2.5 bg-slate-50/60 rounded-lg border border-slate-200 hover:border-slate-300 transition space-y-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-slate-200/80 text-slate-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">
                                            {{ idx + 1 }}
                                        </span>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-800 leading-snug">
                                                {{ it.usulan_imut || '(Usulan belum diberi judul)' }}
                                            </p>
                                            <p v-if="it.data_pendukung" class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                                {{ it.data_pendukung }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-600 bg-white border border-slate-200 px-1.5 py-0.5 rounded shrink-0">
                                        Skor: {{ hitungTotal(it) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-end pt-0.5">
                                    <button
                                        type="button"
                                        @click="selectImp(form.items.indexOf(it))"
                                        class="text-[11px] font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 cursor-pointer"
                                    >
                                        <span>Ganti Menjadi IMP ↗</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-6 text-center text-xs text-slate-400 italic bg-slate-50/50 rounded-lg border border-dashed border-slate-200">
                            Semua usulan telah ditetapkan sebagai IMP atau hanya ada 1 usulan. Tambah usulan baru untuk melihat daftar IMU.
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Pilih dari User PIC / Kepala Unit -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Pilih PIC Unit / Kepala Unit :
                            </label>
                            <select
                                v-model="form.pemimpin_rapat_id"
                                @change="onLeaderChange"
                                class="w-full text-xs border-gray-200 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                            >
                                <option value="">-- Pilih PIC / Kepala Unit yang Memimpin --</option>
                                <option
                                    v-for="leader in meetingLeaders"
                                    :key="leader.id"
                                    :value="leader.id"
                                >
                                    {{ leader.name }} — [{{ leader.role === 'kepala_unit' ? 'Kepala Unit' : (leader.role === 'pic_unit' ? 'PIC Unit' : leader.role) }}] {{ leader.unit ? '(' + leader.unit.nama + ')' : '' }}
                                </option>
                            </select>
                            <p class="text-[11px] text-gray-500 mt-1">
                                Menampilkan akun penanggung jawab dengan hak akses PIC Unit atau Kepala Unit.
                            </p>
                        </div>

                        <!-- Input Nama Pemimpin Rapat (Bisa custom jika diperlukan) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Nama Lengkap Pemimpin Rapat & Gelar :
                            </label>
                            <input
                                v-model="form.pemimpin_rapat_nama"
                                type="text"
                                placeholder="Contoh: dr. Budi Santoso, Sp.A / Ns. Siti Rahma, S.Kep"
                                class="w-full text-xs border-gray-200 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                            />
                            <p class="text-[11px] text-gray-500 mt-1">
                                Otomatis terisi saat memilih user di sebelah kiri, atau dapat diketik manual.
                            </p>
                        </div>
                    </div>

                    <!-- Signature Box Preview -->
                    <div v-if="form.pemimpin_rapat_nama || selectedLeader" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-3.5 rounded-xl border border-slate-200 mt-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                                    {{ selectedLeader ? (selectedLeader.role === 'kepala_unit' ? 'Kepala Unit' : 'PIC Unit') : 'Pemimpin Rapat' }}
                                </span>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">
                                    {{ form.pemimpin_rapat_nama || selectedLeader?.name }}
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ selectedLeader?.unit ? selectedLeader.unit.nama : (form.tipe === 'rs' ? 'Tingkat Rumah Sakit' : 'Unit Kerja') }}
                                </p>
                            </div>
                        </div>
                        <div class="border border-slate-200 bg-slate-50/50 p-2.5 rounded-lg text-center min-w-[170px]">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Tanda Tangan Pengesahan</p>
                            <div class="h-8 border-b border-slate-300 my-1 flex items-center justify-center text-[10px] text-slate-300 italic">
                                (Tanda Tangan)
                            </div>
                            <p class="text-xs font-bold text-slate-800">{{ form.pemimpin_rapat_nama || selectedLeader?.name }}</p>
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

                    <!-- Hidden File Input -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".pdf,.doc,.docx,.xlsx,.xls,.jpg,.jpeg,.png"
                        @change="handleFileUpload"
                        class="hidden"
                        id="file-upload-edit"
                    />

                    <!-- KASUS A: Berkas Baru Sedang Dipilih Untuk Menggantikan / Menambahkan -->
                    <div v-if="form.berkas" class="p-4 rounded-xl border border-slate-200 bg-white space-y-3 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                File Baru Terpilih (Akan disimpan menggantikan file sebelumnya):
                            </span>
                            <span class="text-xs text-slate-500 font-mono">{{ formatFileSize(form.berkas.size) }}</span>
                        </div>

                        <!-- Gambar Preview -->
                        <div v-if="isImageFile && filePreviewUrl" class="space-y-2">
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-center">
                                <img
                                    :src="filePreviewUrl"
                                    alt="Preview File Baru"
                                    class="max-h-60 w-auto mx-auto object-contain rounded-lg shadow-2xs"
                                />
                            </div>
                            <p class="text-[11px] text-center text-slate-500 font-medium">{{ form.berkas.name }}</p>
                        </div>

                        <!-- Dokumen Preview -->
                        <div v-else class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ form.berkas.name }}</p>
                                <p class="text-[11px] text-slate-400">Dokumen Lampiran Baru</p>
                            </div>
                        </div>

                        <!-- Tombol Aksi untuk File Baru -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="$refs.fileInputRef.click()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Pilih File Lain</span>
                            </button>
                            <button
                                type="button"
                                @click="removeNewFile"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-medium transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Batalkan Pilihan Baru</span>
                            </button>
                        </div>
                    </div>

                    <!-- KASUS B: Ada Berkas Lama yang Tersimpan dan Belum Ditandai Hapus -->
                    <div v-else-if="existingFile && !form.hapus_berkas" class="p-4 rounded-xl border border-slate-200 bg-white space-y-3 shadow-2xs">
                        <!-- Format Gambar Lama -->
                        <div v-if="isExistingImage(existingFile)" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Pratinjau Foto Lampiran Tersimpan:
                                </span>
                                <span class="text-[11px] text-slate-400 font-mono">
                                    {{ existingFile.split('/').pop() }}
                                </span>
                            </div>
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-center">
                                <img
                                    :src="'/storage/' + existingFile"
                                    alt="Foto Pendukung Tersimpan"
                                    class="max-h-60 w-auto mx-auto object-contain rounded-lg shadow-2xs"
                                />
                            </div>
                        </div>

                        <!-- Format Dokumen Lama -->
                        <div v-else class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">
                                    {{ existingFile.split('/').pop() }}
                                </p>
                                <p class="text-[11px] text-slate-400">Dokumen Lampiran Tersimpan</p>
                            </div>
                        </div>

                        <!-- Tombol Aksi: Buka, Ganti (Edit), Hapus (Delete) -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                            <a
                                :href="'/storage/' + existingFile"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Buka / Unduh Berkas</span>
                            </a>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="$refs.fileInputRef.click()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Ganti Berkas</span>
                                </button>
                                <button
                                    type="button"
                                    @click="markDeleteExistingFile"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-medium transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Hapus Berkas</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- KASUS C: Berkas Lama Ditandai Hapus -->
                    <div v-else-if="form.hapus_berkas" class="p-4 rounded-xl border border-rose-200 bg-rose-50/60 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-rose-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Berkas ditandai untuk dihapus saat Anda menekan tombol "Perbarui Sesi Pemilihan".
                            </span>
                            <button
                                type="button"
                                @click="restoreExistingFile"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline cursor-pointer"
                            >
                                Batalkan Penghapusan
                            </button>
                        </div>
                    </div>

                    <!-- KASUS D: Belum Ada Berkas Sama Sekali -->
                    <div
                        v-else
                        @click="$refs.fileInputRef.click()"
                        class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-xl p-5 text-center cursor-pointer transition bg-white hover:bg-blue-50/20"
                    >
                        <div class="mx-auto w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Klik untuk Mengunggah Berkas / Foto Lampiran Baru</p>
                        <p class="text-[11px] text-slate-400 mt-1">Format: PDF, Word, Excel, JPG, PNG (Maksimal 10 MB)</p>
                    </div>
                </div>
            </div>

            <!-- Notulen & Form Actions -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Catatan / Notulen Rapat Pemilihan</label>
                <textarea v-model="form.notulen" rows="3" class="w-full text-sm border-gray-200 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500"></textarea>

                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t">
                    <Link :href="route('indicator-selections.index')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Batal
                    </Link>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 shadow-sm">
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Sesi Pemilihan' }}
                    </button>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
