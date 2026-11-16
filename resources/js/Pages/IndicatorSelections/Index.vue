<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    selections: Object,
    units: Array,
    filters: Object,
    canFilter: Boolean,
    isUnitUser: Boolean,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const canFilterComputed = computed(() => {
    if (typeof props.canFilter !== 'undefined') return Boolean(props.canFilter);
    return ['komite_mutu', 'superadmin', 'direksi'].includes(currentUser.value?.role);
});

const isUnitUserComputed = computed(() => {
    if (typeof props.isUnitUser !== 'undefined') return Boolean(props.isUnitUser);
    return ['pic_unit', 'kepala_unit'].includes(currentUser.value?.role);
});

const unitId = ref(props.filters?.unit_id || '');
const tipe = ref(props.filters?.tipe || '');
const tahun = ref(props.filters?.tahun || '');

const applyFilter = () => {
    router.get(route('indicator-selections.index'), {
        unit_id: unitId.value,
        tipe: tipe.value,
        tahun: tahun.value,
    }, { preserveScroll: true, replace: true });
};

const deleteSelection = async (id) => {
    const confirmed = await window.notify.dangerConfirm({
        title: 'Hapus Sesi Pemilihan?',
        text: 'Apakah Anda yakin ingin menghapus sesi pemilihan indikator mutu ini beserta seluruh usulan di dalamnya?',
        confirmButtonText: 'Ya, Hapus Sesi',
    });
    if (confirmed) {
        router.delete(route('indicator-selections.destroy', id), {
            onSuccess: () => {
                window.notify?.toast?.success('Sesi Dihapus', 'Sesi pemilihan indikator mutu telah berhasil dihapus.');
            }
        });
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">
                        <template v-if="isUnitUserComputed">
                            Pemilihan Indikator Mutu Unit {{ currentUser?.unit?.nama ? `— ${currentUser.unit.nama}` : '' }}
                        </template>
                        <template v-else>
                            {{ tipe === 'rs' ? 'Pemilihan Indikator Mutu Prioritas RS (IMP-RS)' : (tipe === 'unit' ? 'Pemilihan Indikator Mutu Unit' : 'Pemilihan Indikator Mutu') }}
                        </template>
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <template v-if="isUnitUserComputed">
                            Daftar riwayat sesi rapat pemilihan dan penetapan indikator mutu internal unit kerja.
                        </template>
                        <template v-else>
                            {{ tipe === 'rs' ? 'Skrining 4 Area Fokus KARS & skoring pembobotan prioritas rumah sakit' : 'Skrining kelayakan 3 tahap & skoring prioritas pembobotan usulan indikator mutu faskes' }}
                        </template>
                    </p>
                </div>
                <Link :href="route('indicator-selections.create', { tipe: isUnitUserComputed ? 'unit' : (tipe || 'unit') })" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ !isUnitUserComputed && tipe === 'rs' ? 'Sesi Pemilihan IMP-RS Baru' : 'Sesi Pemilihan Baru' }}
                </Link>
            </div>
        </template>

        <!-- Banner Unit Kerja Khusus untuk PIC Unit & Kepala Unit -->
        <div v-if="isUnitUserComputed" class="mb-5 p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-center justify-between text-xs shadow-2xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
                <div>
                    <p class="font-bold text-slate-800">Unit Kerja: {{ currentUser?.unit?.nama || 'Unit Anda' }}</p>
                    <p class="text-slate-500 text-[11px]">Halaman ini dibatasi khusus menampilkan sesi pemilihan indikator mutu internal unit kerja Anda.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 bg-white border border-blue-200 text-blue-700 font-semibold rounded-lg shadow-2xs">
                    {{ currentUser?.role === 'kepala_unit' ? 'Kepala Unit' : 'PIC Unit' }}
                </span>
            </div>
        </div>

        <!-- Banner Info Tingkat RS (Hanya untuk Komite Mutu jika tab RS aktif) -->
        <div v-if="canFilterComputed && tipe === 'rs'" class="mb-5 p-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 shadow-2xs flex items-start gap-3">
            <span class="p-2 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-slate-900">Fokus Pemilihan Tingkat Rumah Sakit (IMP-RS)</p>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                    Sesi pemilihan tingkat RS bertujuan menetapkan indikator mutu prioritas rumah sakit berdasarkan <strong>4 Area Fokus KARS</strong> (Pelayanan Klinis, Sesuai Renstra, Perbaikan Sistem, Manajemen Risiko) serta Sasaran Keselamatan Pasien (SKP). Indikator yang ditetapkan pada sesi ini akan otomatis terhubung ke menu <strong>Tingkat Rumah Sakit</strong>.
                </p>
            </div>
        </div>

        <!-- Level Tabs & Filters Bar (HANYA DITAMPILKAN UNTUK USER KOMITE MUTU / ADMIN / DIREKSI) -->
        <div v-if="canFilterComputed" class="space-y-3 mb-5">
            <!-- Level Toggle Buttons -->
            <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs w-fit">
                <button
                    type="button"
                    @click="tipe = ''; applyFilter()"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', !tipe ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50']"
                >
                    Semua Sesi
                </button>
                <button
                    type="button"
                    @click="tipe = 'unit'; applyFilter()"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', tipe === 'unit' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50']"
                >
                    Tingkat Unit
                </button>
                <button
                    type="button"
                    @click="tipe = 'rs'; applyFilter()"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all', tipe === 'rs' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50']"
                >
                    Tingkat Rumah Sakit (IMP-RS)
                </button>
            </div>

            <!-- Detailed Filters -->
            <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex flex-wrap gap-3">
                    <select v-if="tipe !== 'rs'" v-model="unitId" @change="applyFilter" class="text-xs border-slate-200 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Unit</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                    </select>
                    <select v-model="tahun" @change="applyFilter" class="text-xs border-slate-200 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Tahun</option>
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('indicator-selections.export-rekap', { tipe: tipe, unit_id: unitId, tahun: tahun })" class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Rekap Excel
                    </a>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit / Pelaksana</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tingkat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Hari & Tanggal</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Usulan</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dibuat Oleh</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="sel in selections.data" :key="sel.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-sm font-bold text-gray-800">{{ sel.unit?.nama || 'Komite Mutu (RS)' }}</p>
                            <p v-if="sel.tempat" class="text-xs text-gray-400 mt-0.5">Lokasi: {{ sel.tempat }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="['inline-flex px-2 py-0.5 rounded-md text-xs font-semibold', sel.tipe === 'rs' ? 'bg-slate-100 text-slate-700 border border-slate-200' : 'bg-blue-50 text-blue-700 border border-blue-200']">
                                {{ sel.tipe === 'rs' ? 'Tingkat RS' : 'Tingkat Unit' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ new Date(sel.tanggal).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                            <span v-if="sel.jam" class="text-xs text-gray-400 ml-1">({{ sel.jam }})</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                                {{ sel.items_count }} usulan
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', sel.status === 'final' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                {{ sel.status === 'final' ? 'Selesai / Ditetapkan' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ sel.created_by?.name }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <Link :href="route('indicator-selections.show', sel.id)" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Lihat Rekapitulasi & Export">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </Link>
                                <Link :href="route('indicator-selections.edit', sel.id)" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit Sesi">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </Link>
                                <button @click="deleteSelection(sel.id)" class="p-1.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!selections.data?.length">
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                            <span v-if="isUnitUserComputed">
                                Belum ada sesi pemilihan indikator mutu untuk unit <strong>{{ currentUser?.unit?.nama || 'Anda' }}</strong>. Klik tombol <strong>Sesi Pemilihan Baru</strong> untuk memulai.
                            </span>
                            <span v-else>
                                Belum ada sesi pemilihan indikator mutu. Klik tombol <strong>Sesi Pemilihan Baru</strong> untuk memulai.
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
