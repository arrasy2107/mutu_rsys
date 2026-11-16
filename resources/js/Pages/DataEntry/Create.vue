<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    indicator: Object,
    period: Object,
    existingEntries: Array,
    unit: Object,
    tahun: Number,
    bulan: Number,
});

const bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const form = useForm({
    indicator_id: props.indicator.id,
    period_id: props.period.id,
    unit_id: props.unit.id,
    tanggal: props.indicator.frekuensi === 'harian' ? new Date().toISOString().split('T')[0] : null,
    numerator: 0,
    denominator: 0,
    catatan: '',
    file_bukti: null,
    log_harian: {}, // To store { '1': {n:0, d:0}, '2': ... }
});

const daysInMonth = computed(() => {
    return new Date(props.tahun, props.bulan, 0).getDate();
});

// Initialize log_harian if bulanan
if (props.indicator.frekuensi === 'bulanan') {
    for (let i = 1; i <= daysInMonth.value; i++) {
        form.log_harian[i] = { n: '', d: '' };
    }
}

watch(() => form.log_harian, (newLog) => {
    if (props.indicator.frekuensi === 'bulanan') {
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

const indicatorMultiplier = computed(() => {
    if (props.indicator?.multiplier) {
        return parseFloat(props.indicator.multiplier);
    }
    const f = props.indicator?.formula || '';
    const match = f.match(/(?:x|\*|×)\s*(\d+(?:\.\d+)?)/i);
    return match ? parseFloat(match[1]) : 100;
});

const isIndexBased = computed(() => indicatorMultiplier.value !== 100);

const capaianPreview = computed(() => {
    const num = parseFloat(form.numerator);
    const den = parseFloat(form.denominator);
    if (!isNaN(num) && !isNaN(den) && den > 0) {
        return ((num / den) * indicatorMultiplier.value).toFixed(2);
    }
    return null;
});

const capaianStatus = computed(() => {
    if (!capaianPreview.value || !props.indicator.target_capaian) return null;
    const cap = parseFloat(capaianPreview.value);
    const target = parseFloat(props.indicator.target_capaian);
    const op = props.indicator.target_operator;
    const met = op === '>=' ? cap >= target : cap <= target;
    if (met) return 'green';
    const gap = Math.abs(cap - target);
    return gap <= 10 ? 'yellow' : 'red';
});

const handleFileChange = (e) => { form.file_bukti = e.target.files[0]; };

const submit = (asDraft = true) => {
    form.post(route('data-entry.store'), {
        onSuccess: () => { /* reset atau redirect */ },
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('data-entry.index')" class="hover:text-indigo-600">Entri Data</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Input Baru</span>
            </nav>
        </template>

        <div class="max-w-3xl mx-auto space-y-5">
            <!-- Profil Indikator Summary -->
            <div class="bg-indigo-50 rounded-xl border border-indigo-100 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="text-xs font-mono font-bold text-indigo-700">{{ indicator.kode }}</span>
                            <div class="flex gap-1" v-if="indicator.jenis">
                                <span v-for="j in indicator.jenis" :key="j" class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-indigo-100 text-indigo-700">
                                    {{ j }}
                                </span>
                            </div>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">{{ indicator.judul }}</h2>
                        <p class="text-xs text-gray-500 mt-1">Unit: <strong>{{ unit.nama }}</strong> · Periode: <strong>{{ bulanNames[bulan] }} {{ tahun }}</strong></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Target</p>
                        <p class="text-lg font-bold text-indigo-700">
                            {{ indicator.target_operator === '>=' ? '≥' : '≤' }} {{ indicator.target_capaian }}
                            <span class="text-sm font-medium">{{ indicator.satuan_pengukuran === 'Persentase' ? '%' : (indicator.satuan_pengukuran === 'Permil' ? '‰' : indicator.satuan_pengukuran) }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="indicator.formula" class="mt-3 p-2 bg-white rounded-lg border border-indigo-100">
                    <p class="text-xs text-gray-500">Formula: <span class="font-mono text-gray-700">{{ indicator.formula }}</span></p>
                </div>
            </div>

            <!-- Data Sebelumnya -->
            <div v-if="existingEntries.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-xs font-semibold text-gray-600 mb-3">Data yang Sudah Diinput Periode Ini</h3>
                <div class="space-y-2">
                    <div v-for="e in existingEntries" :key="e.id" class="flex items-center justify-between text-xs py-1.5 px-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">{{ e.tanggal ? new Date(e.tanggal).toLocaleDateString('id-ID') : 'Bulanan' }}</span>
                        <span class="font-mono">{{ e.numerator }} / {{ e.denominator }}</span>
                        <span class="font-bold text-indigo-700">{{ e.capaian }}%</span>
                        <span :class="['px-2 py-0.5 rounded-full text-xs', e.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600']">{{ e.status }}</span>
                    </div>
                </div>
            </div>

            <!-- Form Input -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Input Data Baru</h2>
                </div>
                <form @submit.prevent="submit()" class="p-6 space-y-5">
                    <!-- Tanggal (untuk harian) -->
                    <div v-if="indicator.frekuensi === 'harian'">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal *</label>
                        <input v-model="form.tanggal" type="date" class="w-48 text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <!-- Numerator & Denominator -->
                    <div v-if="indicator.frekuensi === 'harian'" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Numerator *
                                <span class="font-normal text-gray-400 ml-1">(Pembilang)</span>
                            </label>
                            <input v-model="form.numerator" type="number" min="0" step="any"
                                class="w-full text-lg font-mono border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-center"
                                placeholder="0"
                            />
                            <p v-if="form.errors.numerator" class="text-xs text-rose-500 mt-1">{{ form.errors.numerator }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Denominator *
                                <span class="font-normal text-gray-400 ml-1">(Penyebut)</span>
                            </label>
                            <input v-model="form.denominator" type="number" min="0" step="any"
                                class="w-full text-lg font-mono border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-center"
                                placeholder="0"
                            />
                            <p v-if="form.errors.denominator" class="text-xs text-rose-500 mt-1">{{ form.errors.denominator }}</p>
                        </div>
                    </div>

                    <!-- Log Harian Grid untuk Bulanan -->
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

                    <!-- Auto-computed Capaian -->
                    <div class="p-4 rounded-xl border-2 text-center" :class="{
                        'border-emerald-200 bg-emerald-50': capaianStatus === 'green',
                        'border-amber-200 bg-amber-50': capaianStatus === 'yellow',
                        'border-rose-200 bg-rose-50': capaianStatus === 'red',
                        'border-gray-200 bg-gray-50': !capaianStatus,
                    }">
                        <p class="text-xs font-medium mb-1" :class="{
                            'text-emerald-600': capaianStatus === 'green',
                            'text-amber-600': capaianStatus === 'yellow',
                            'text-rose-600': capaianStatus === 'red',
                            'text-gray-500': !capaianStatus,
                        }">Capaian (Otomatis Dihitung)</p>
                        <p class="text-4xl font-bold" :class="{
                            'text-emerald-700': capaianStatus === 'green',
                            'text-amber-700': capaianStatus === 'yellow',
                            'text-rose-700': capaianStatus === 'red',
                            'text-gray-400': !capaianStatus,
                        }">
                            {{ capaianPreview ? capaianPreview : '—' }}
                            <span v-if="capaianPreview && !isIndexBased" class="text-xl font-medium">{{ indicator.satuan_pengukuran === 'Persentase' ? '%' : (indicator.satuan_pengukuran === 'Permil' ? '‰' : indicator.satuan_pengukuran) }}</span>
                        </p>
                        <p v-if="capaianStatus === 'green'" class="text-xs text-emerald-600 mt-1">Target tercapai</p>
                        <p v-else-if="capaianStatus === 'yellow'" class="text-xs text-amber-600 mt-1">Hampir mencapai target</p>
                        <p v-else-if="capaianStatus === 'red'" class="text-xs text-rose-600 mt-1">Di bawah target — perlu PDSA</p>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Catatan (opsional)</label>
                        <textarea v-model="form.catatan" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Keterangan tambahan..." />
                    </div>

                    <!-- Upload Bukti -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Upload Bukti (PDF/Gambar, maks 5MB)</label>
                        <input @change="handleFileChange" type="file" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100"
                        />
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('data-entry.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan sebagai Draft' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
