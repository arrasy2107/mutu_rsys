<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({ entry: Object });

const form = useForm({
    tanggal:     props.entry.tanggal ? props.entry.tanggal.substring(0, 10) : '',
    numerator:   props.entry.numerator,
    denominator: props.entry.denominator,
    catatan:     props.entry.catatan || '',
    file_bukti:  null,
    log_harian:  props.entry.log_harian || {},
});

const daysInMonth = computed(() => {
    return new Date(props.entry.period.tahun, props.entry.period.bulan, 0).getDate();
});

// Initialize log_harian if empty and bulanan
if (props.entry.indicator?.frekuensi === 'bulanan' && Object.keys(form.log_harian).length === 0) {
    for (let i = 1; i <= daysInMonth.value; i++) {
        form.log_harian[i] = { n: '', d: '' };
    }
}

watch(() => form.log_harian, (newLog) => {
    if (props.entry.indicator?.frekuensi === 'bulanan') {
        let totalN = 0;
        let totalD = 0;
        for (let i = 1; i <= daysInMonth.value; i++) {
            totalN += parseFloat(newLog[i]?.n) || 0;
            totalD += parseFloat(newLog[i]?.d) || 0;
        }
        form.numerator = totalN;
        form.denominator = totalD;
    }
}, { deep: true });

const capaianPreview = computed(() => {
    const n = parseFloat(form.numerator), d = parseFloat(form.denominator);
    return (!isNaN(n) && !isNaN(d) && d > 0) ? ((n / d) * 100).toFixed(2) : null;
});

const handleFile = (e) => { form.file_bukti = e.target.files[0]; };
const submit = () => form.post(route('data-entry.update', props.entry.id), { forceFormData: true });
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('data-entry.index')" class="hover:text-indigo-600">Entri Data</Link>
                <span>/</span>
                <Link :href="route('data-entry.show', entry.id)" class="hover:text-indigo-600">Detail</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Edit</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Edit Data Entri</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ entry.indicator?.kode }} — {{ entry.indicator?.judul }}</p>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div v-if="entry.indicator?.frekuensi === 'harian'">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal</label>
                        <input v-model="form.tanggal" type="date" class="w-48 text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div v-if="entry.indicator?.frekuensi === 'harian'" class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Numerator *</label>
                            <input v-model="form.numerator" type="number" min="0" step="any"
                                class="w-full text-lg font-mono border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-center" />
                            <p v-if="form.errors.numerator" class="text-xs text-rose-500 mt-1">{{ form.errors.numerator }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Denominator *</label>
                            <input v-model="form.denominator" type="number" min="0" step="any"
                                class="w-full text-lg font-mono border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-center" />
                            <p v-if="form.errors.denominator" class="text-xs text-rose-500 mt-1">{{ form.errors.denominator }}</p>
                        </div>
                    </div>

                    <div v-else class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-xs font-semibold text-gray-700">Tally Sheet / Log Harian</h3>
                            <div class="text-xs font-mono font-semibold text-indigo-700 bg-white px-2 py-1 rounded border border-indigo-100">
                                Total: {{ form.numerator }} / {{ form.denominator }}
                            </div>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-white sticky top-0 border-b border-gray-200 z-10 text-xs text-gray-500">
                                    <tr>
                                        <th class="px-4 py-2 text-center w-20">Tgl</th>
                                        <th class="px-4 py-2">Numerator (Pembilang)</th>
                                        <th class="px-4 py-2">Denominator (Penyebut)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="day in daysInMonth" :key="day" class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-center font-mono text-gray-500">{{ day }}</td>
                                        <td class="px-4 py-1">
                                            <input v-model="form.log_harian[day].n" type="number" min="0" step="any" placeholder="0" class="w-full text-sm font-mono border-gray-200 rounded py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </td>
                                        <td class="px-4 py-1">
                                            <input v-model="form.log_harian[day].d" type="number" min="0" step="any" placeholder="0" class="w-full text-sm font-mono border-gray-200 rounded py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="form.errors.numerator || form.errors.denominator" class="text-xs text-rose-500 mt-2 px-4 pb-2">Pastikan input data log harian valid.</p>
                    </div>

                    <!-- Preview -->
                    <div v-if="capaianPreview" class="p-3 bg-indigo-50 rounded-lg border border-indigo-100 text-center">
                        <p class="text-xs text-indigo-600 font-medium">Capaian Baru</p>
                        <p class="text-3xl font-bold text-indigo-700">
                            {{ capaianPreview }}
                            <span class="text-lg font-medium">{{ entry.indicator?.satuan_pengukuran === 'Persentase' ? '%' : (entry.indicator?.satuan_pengukuran === 'Permil' ? '‰' : entry.indicator?.satuan_pengukuran) }}</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Catatan</label>
                        <textarea v-model="form.catatan" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Upload Bukti Baru (opsional)</label>
                        <input @change="handleFile" type="file" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm hover:file:bg-indigo-100" />
                        <p v-if="entry.file_bukti" class="text-xs text-gray-400 mt-1">File sebelumnya: {{ entry.file_bukti.split('/').pop() }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('data-entry.show', entry.id)" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Perbarui Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
