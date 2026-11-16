<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ units: Array, logs: Array });

const form = useForm({
    jenis: 'bulanan',
    tahun: new Date().getFullYear(),
    bulan: new Date().getMonth() + 1,
    triwulan: 1,
    semester: 1,
    format: 'excel',
    jenis_indikator_filter: '',
    unit_id: '',
});

const generating = ref(false);

const generate = () => {
    generating.value = true;
    form.post(route('reports.generate'), {
        onSuccess: () => {
            window.notify?.toast?.success('Laporan Dibuat', 'Laporan mutu berhasil digenerate dan siap diunduh.');
        },
        onError: () => {
            window.notify?.toast?.error('Gagal Generate', 'Terjadi kendala saat menyusun laporan mutu.');
        },
        onFinish: () => generating.value = false,
    });
};

const bulanOptions = [
    { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
    { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
    { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
    { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Laporan Mutu</h1>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Generator Form -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Generate Laporan</h2>
                </div>
                <form @submit.prevent="generate" class="p-6 space-y-5">
                    <!-- Jenis -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Jenis Laporan</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label v-for="opt in [{ v: 'bulanan', l: '📅 Bulanan' }, { v: 'triwulan', l: '📆 Triwulan' }, { v: 'semesteran', l: '📊 Semesteran' }, { v: 'tahunan', l: '🗓️ Tahunan' }]"
                                :key="opt.v"
                                :class="['text-center py-3 rounded-xl border-2 cursor-pointer text-sm font-medium transition-colors', form.jenis === opt.v ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:border-gray-300']"
                            >
                                <input type="radio" v-model="form.jenis" :value="opt.v" class="sr-only" />
                                {{ opt.l }}
                            </label>
                        </div>
                    </div>

                    <!-- Tahun & Periode -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tahun</label>
                            <select v-model="form.tahun" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <div v-if="form.jenis === 'bulanan'">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bulan</label>
                            <select v-model="form.bulan" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option v-for="b in bulanOptions" :key="b.v" :value="b.v">{{ b.l }}</option>
                            </select>
                        </div>
                        <div v-if="form.jenis === 'triwulan'">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Triwulan</label>
                            <select v-model="form.triwulan" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="1">TW 1 (Jan–Mar)</option>
                                <option value="2">TW 2 (Apr–Jun)</option>
                                <option value="3">TW 3 (Jul–Sep)</option>
                                <option value="4">TW 4 (Okt–Des)</option>
                            </select>
                        </div>
                        <div v-if="form.jenis === 'semesteran'">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Semester</label>
                            <select v-model="form.semester" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="1">Semester 1 (Jan–Jun)</option>
                                <option value="2">Semester 2 (Jul–Des)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Filter Jenis Indikator (opsional)</label>
                            <select v-model="form.jenis_indikator_filter" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Jenis</option>
                                <option value="IMN">IMN</option>
                                <option value="IMP-RS">IMP-RS</option>
                                <option value="IMP-U">IMP-U</option>
                                <option value="IMU">IMU</option>
                                <option value="SPM">SPM</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Filter Unit (opsional)</label>
                            <select v-model="form.unit_id" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Unit</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Format -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Format Output</label>
                        <div class="flex gap-3">
                            <label :class="['flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 cursor-pointer text-sm font-medium transition-colors', form.format === 'pdf' ? 'border-rose-400 bg-rose-50 text-rose-700' : 'border-gray-200 text-gray-600 hover:border-gray-300']">
                                <input type="radio" v-model="form.format" value="pdf" class="sr-only" />
                                📄 PDF
                            </label>
                            <label :class="['flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 cursor-pointer text-sm font-medium transition-colors', form.format === 'excel' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-600 hover:border-gray-300']">
                                <input type="radio" v-model="form.format" value="excel" class="sr-only" />
                                📊 Excel
                            </label>
                        </div>
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? '⏳ Sedang Generate...' : '📥 Generate & Download Laporan' }}
                    </button>
                </form>
            </div>

            <!-- Log Laporan Sebelumnya -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Riwayat Laporan</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-if="!logs.length" class="p-5 text-center text-gray-400 text-sm">Belum ada laporan digenerate.</div>
                    <div v-for="log in logs" :key="log.id" class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-700 capitalize">
                                    {{ log.jenis }}
                                    {{ log.bulan ? '— Bln ' + log.bulan : '' }}
                                    {{ log.triwulan ? '— TW' + log.triwulan : '' }}
                                    {{ (log.parameter && JSON.parse(log.parameter)?.semester) ? '— Sem ' + JSON.parse(log.parameter).semester : '' }}
                                    / {{ log.tahun }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ new Date(log.created_at).toLocaleDateString('id-ID') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="['text-xs px-2 py-0.5 rounded-full font-medium uppercase', log.format === 'pdf' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700']">{{ log.format }}</span>
                                <a v-if="log.file_path" :href="route('reports.download', log.id)" class="p-1.5 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
