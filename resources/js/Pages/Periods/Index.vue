<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ indicators: Array, periods: Object, units: Array, tahun: Number });

const tahun = ref(props.tahun);

const applyYear = () => router.get(route('periods.index'), { tahun: tahun.value }, { preserveScroll: true });

const bulkForm = useForm({ tahun: props.tahun, bulan: new Date().getMonth() + 1, deadline_input: '' });
const openBulk = async () => {
    const bulanLabel = bulanNames[bulkForm.bulan] || bulkForm.bulan;
    const confirmed = await window.notify.confirm({
        title: 'Buka Periode Massal?',
        html: `Apakah Anda yakin ingin membuka pengumpulan data untuk seluruh indikator aktif pada periode <b>${bulanLabel} ${bulkForm.tahun}</b>?`,
        icon: 'question',
        confirmButtonText: 'Ya, Buka Periode',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563eb',
    });
    if (confirmed) {
        bulkForm.post(route('periods.bulk-open'), {
            onSuccess: () => {
                bulkForm.reset('deadline_input');
                window.notify?.toast?.success('Periode Dibuka', `Seluruh indikator periode ${bulanLabel} ${bulkForm.tahun} siap diinput oleh unit.`);
            }
        });
    }
};

const bulanNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
const bulanOptions = [1,2,3,4,5,6,7,8,9,10,11,12];

const getStatus = (indicator, bulan) => {
    const key = indicator.id.toString();
    const periodsForIndicator = props.periods[key] || [];
    const period = periodsForIndicator.find(p => p.bulan === bulan && p.tahun === props.tahun);
    return period?.status || null;
};

const statusColor = (status) => ({
    open:   'bg-emerald-100 text-emerald-700 border-emerald-200',
    closed: 'bg-gray-100 text-gray-400 border-gray-200',
})[status] || 'bg-transparent';
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Manajemen Periode</h1>
        </template>

        <!-- Bulk Open -->
        <div class="bg-indigo-50 rounded-xl border border-indigo-200 p-5 mb-5">
            <h2 class="text-sm font-semibold text-indigo-800 mb-3">📅 Buka Periode Massal</h2>
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-indigo-700 mb-1">Tahun</label>
                    <select v-model="bulkForm.tahun" class="text-sm border-indigo-200 rounded-lg px-3 py-2 bg-white focus:ring-indigo-500">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-700 mb-1">Bulan</label>
                    <select v-model="bulkForm.bulan" class="text-sm border-indigo-200 rounded-lg px-3 py-2 bg-white focus:ring-indigo-500">
                        <option v-for="b in bulanOptions" :key="b" :value="b">{{ bulanNames[b] }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-700 mb-1">Deadline Input</label>
                    <input v-model="bulkForm.deadline_input" type="date" class="text-sm border-indigo-200 rounded-lg px-3 py-2 bg-white focus:ring-indigo-500" />
                </div>
                <button @click="openBulk" :disabled="bulkForm.processing"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    {{ bulkForm.processing ? 'Membuka...' : '🔓 Buka Semua Periode' }}
                </button>
            </div>
        </div>

        <!-- Filter Tahun -->
        <div class="flex items-center gap-3 mb-4">
            <label class="text-sm font-medium text-gray-600">Tampilkan Tahun:</label>
            <select v-model="tahun" @change="applyYear" class="text-sm border-gray-200 rounded-lg px-3 py-2">
                <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <!-- Grid Matrix: Indikator × Bulan -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-auto">
            <table class="min-w-max divide-y divide-gray-100 text-xs">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 sticky left-0 bg-gray-50 z-10 min-w-[220px]">Indikator</th>
                        <th v-for="b in bulanOptions" :key="b" class="px-3 py-3 text-center font-semibold text-gray-500 w-16">
                            {{ bulanNames[b] }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="indicator in indicators" :key="indicator.id" class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 sticky left-0 bg-white z-10 border-r border-gray-100">
                            <p class="font-bold text-indigo-700 font-mono">{{ indicator.kode }}</p>
                            <p class="text-gray-600 truncate max-w-[200px]">{{ indicator.judul }}</p>
                        </td>
                        <td v-for="b in bulanOptions" :key="b" class="px-2 py-2 text-center">
                            <span v-if="getStatus(indicator, b)"
                                :class="['inline-block px-2 py-0.5 rounded text-xs font-medium border', statusColor(getStatus(indicator, b))]">
                                {{ getStatus(indicator, b) === 'open' ? '✓' : '×' }}
                            </span>
                            <span v-else class="inline-block w-6 h-5 rounded border border-dashed border-gray-200"></span>
                        </td>
                    </tr>
                    <tr v-if="!indicators?.length">
                        <td :colspan="13" class="px-4 py-10 text-center text-gray-400">Tidak ada indikator aktif.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="inline-block w-4 h-4 rounded bg-emerald-100 border border-emerald-200"></span> Terbuka (open)</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-4 h-4 rounded bg-gray-100 border border-gray-200"></span> Ditutup (closed)</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-4 h-4 rounded border border-dashed border-gray-300"></span> Belum dibuka</span>
        </div>
    </AppLayout>
</template>
