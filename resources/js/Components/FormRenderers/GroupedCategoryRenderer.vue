<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Object, Array],
        default: () => ({}),
    },
    indicator: {
        type: Object,
        required: true,
    },
    schema: {
        type: Object,
        default: () => ({}),
    },
    bulan: {
        type: Number,
        default: 1,
    },
    tahun: {
        type: Number,
        default: 2026,
    },
    permissions: {
        type: Object,
        default: () => ({}),
    },
    isPeriodApproved: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'update:summary', 'change']);

// Kategori default jika schema belum terdefinisi
const categories = computed(() => {
    return props.schema?.categories || [
        { key: 'obat_jadi', label: 'Obat Jadi (1 Minggu & 1 Bulan)', standard_label: '≤ 30 Menit', standard_minutes: 30, default_sample: 778, default_compliant: 670 },
        { key: 'obat_umum', label: 'Obat Umum / Jaminan Perusahaan', standard_label: '≤ 30 Menit', standard_minutes: 30, default_sample: 219, default_compliant: 198 },
        { key: 'obat_racikan', label: 'Obat Racikan', standard_label: '≤ 60 Menit', standard_minutes: 60, default_sample: 134, default_compliant: 130 },
    ];
});

// State Data Form dengan default initial categories yang aman
const initialCats = {
    obat_jadi: { sample: 778, compliant: 670, total_minutes: 0 },
    obat_umum: { sample: 219, compliant: 198, total_minutes: 0 },
    obat_racikan: { sample: 134, compliant: 130, total_minutes: 0 },
};

const formData = ref({
    categories: initialCats,
    sampling_logs: [],
});
const activeTab = ref('rekap'); // 'rekap' | 'sampling_log'

// Helper aman agar template tidak pernah crash jika kategori belum siap
const getCat = (key) => {
    if (!formData.value) formData.value = {};
    if (!formData.value.categories) formData.value.categories = {};
    if (!formData.value.categories[key]) {
        formData.value.categories[key] = { sample: 0, compliant: 0, total_minutes: 0 };
    }
    return formData.value.categories[key];
};

// Inisialisasi data dari modelValue
const initData = () => {
    const raw = props.modelValue || {};
    let existingCats = {};
    if (raw && typeof raw === 'object') {
        if (raw.categories && typeof raw.categories === 'object') {
            existingCats = raw.categories;
        } else if (raw.obat_jadi || raw.obat_umum || raw.obat_racikan) {
            existingCats = raw;
        }
    }

    const updatedCategories = {};
    categories.value.forEach(cat => {
        const catData = existingCats[cat.key] || {};
        updatedCategories[cat.key] = {
            sample: catData.sample !== undefined ? Number(catData.sample) : (cat.default_sample ?? 0),
            compliant: catData.compliant !== undefined ? Number(catData.compliant) : (cat.default_compliant ?? 0),
            total_minutes: catData.total_minutes !== undefined ? Number(catData.total_minutes) : 0,
        };
    });

    formData.value = {
        categories: updatedCategories,
        sampling_logs: Array.isArray(raw.sampling_logs) ? raw.sampling_logs : [],
    };

    // Emit ringkasan kalkulasi ke parent tanpa memicu mutasi v-model balik
    emitSummary();
};

// Kalkulasi Rollup (Numerator, Denominator, Capaian)
const totals = computed(() => {
    let sumSample = 0;
    let sumCompliant = 0;
    let sumMinutes = 0;

    categories.value.forEach(cat => {
        const data = getCat(cat.key);
        sumSample += Number(data.sample || 0);
        sumCompliant += Number(data.compliant || 0);
        sumMinutes += Number(data.total_minutes || 0);
    });

    const capaian = sumSample > 0 ? Number(((sumCompliant / sumSample) * 100).toFixed(2)) : 0;
    const avgMinutes = sumSample > 0 ? Number((sumMinutes / sumSample).toFixed(1)) : 0;

    return {
        sample: sumSample,
        compliant: sumCompliant,
        total_minutes: sumMinutes,
        avg_minutes: avgMinutes,
        capaian: capaian,
    };
});

const emitSummary = () => {
    emit('update:summary', {
        numerator: totals.value.compliant,
        denominator: totals.value.sample,
        capaian: totals.value.capaian,
    });
};

const calculateRollup = () => {
    emit('update:modelValue', formData.value);
    emitSummary();
    emit('change');
};

// Jalankan initData sekali saat inisialisasi komponen
initData();

// Watch hanya saat ID indikator berganti
watch(() => props.indicator?.id, () => {
    initData();
});

const onInput = () => {
    calculateRollup();
};

// Fitur Sampling Log Individual Resep (Opsional)
const newLog = ref({
    category_key: 'obat_jadi',
    no_resep: '',
    jam_masuk: '',
    jam_selesai: '',
});

const addSamplingLog = () => {
    if (!newLog.value.jam_masuk || !newLog.value.jam_selesai) {
        return;
    }

    const tMasuk = new Date(`1970-01-01T${newLog.value.jam_masuk}:00`);
    const tSelesai = new Date(`1970-01-01T${newLog.value.jam_selesai}:00`);
    let diffMs = tSelesai - tMasuk;
    if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000;
    const diffMinutes = Math.round(diffMs / 60000);

    const cat = categories.value.find(c => c.key === newLog.value.category_key);
    const standard = cat ? cat.standard_minutes : 30;
    const isCompliant = diffMinutes <= standard;

    if (!formData.value.sampling_logs) formData.value.sampling_logs = [];
    formData.value.sampling_logs.unshift({
        id: Date.now(),
        category_key: newLog.value.category_key,
        category_label: cat?.label || newLog.value.category_key,
        no_resep: newLog.value.no_resep || `R-${formData.value.sampling_logs.length + 1}`,
        jam_masuk: newLog.value.jam_masuk,
        jam_selesai: newLog.value.jam_selesai,
        minutes: diffMinutes,
        is_compliant: isCompliant,
    });

    // Reset input
    newLog.value.no_resep = '';
    newLog.value.jam_masuk = '';
    newLog.value.jam_selesai = '';

    calculateRollup();
};

const removeSamplingLog = (idx) => {
    formData.value.sampling_logs.splice(idx, 1);
    calculateRollup();
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Header Mode Format -->
        <div class="px-5 py-4 bg-gradient-to-r from-teal-50/80 to-indigo-50/50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    ⏱️
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 leading-snug">
                        {{ schema?.title || 'Format Waktu Tunggu Berdasarkan Kategori Standar Resep' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        Pengelompokan resep berdasarkan batas toleransi waktu (Obat Jadi ≤ 30', Racikan ≤ 60').
                    </p>
                </div>
            </div>

            <!-- Tab View -->
            <div class="inline-flex p-1 bg-white border border-gray-200 rounded-lg shadow-2xs">
                <button
                    @click="activeTab = 'rekap'"
                    :class="[
                        'px-3 py-1 text-xs font-semibold rounded-md transition-colors flex items-center gap-1.5',
                        activeTab === 'rekap' ? 'bg-teal-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'
                    ]"
                >
                    <NotionIcon name="bar-chart" size="14" />
                    <span>Rekapitulasi Kategori</span>
                </button>
                <button
                    @click="activeTab = 'sampling_log'"
                    :class="[
                        'px-3 py-1 text-xs font-semibold rounded-md transition-colors flex items-center gap-1.5',
                        activeTab === 'sampling_log' ? 'bg-teal-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'
                    ]"
                >
                    <NotionIcon name="file-text" size="14" />
                    <span>Log Sampling Resep ({{ formData.sampling_logs?.length || 0 }})</span>
                </button>
            </div>
        </div>

        <!-- ── TAB 1: REKAPITULASI KATEGORI ────────────────────────────────────── -->
        <div v-if="activeTab === 'rekap'" class="p-5 space-y-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                    <thead class="bg-slate-100 text-gray-700 text-xs font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Kategori Resep</th>
                            <th class="px-4 py-3 text-center w-36">Standar Waktu</th>
                            <th class="px-4 py-3 text-right w-44">Jumlah Sampel (D)</th>
                            <th class="px-4 py-3 text-right w-44">Sesuai Waktu (N)</th>
                            <th class="px-4 py-3 text-right w-48">Total Waktu (Menit)</th>
                            <th class="px-4 py-3 text-center w-32">Capaian (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="cat in categories" :key="cat.key" class="hover:bg-slate-50/80 transition-colors">
                            <!-- Kategori Label -->
                            <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                    <span>{{ cat.label }}</span>
                                </div>
                            </td>

                            <!-- Standar Waktu -->
                            <td class="px-4 py-3 text-xs font-bold text-center">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono bg-teal-50 text-teal-700 border border-teal-200">
                                    {{ cat.standard_label }}
                                </span>
                            </td>

                            <!-- Jumlah Sampel (D) -->
                            <td class="px-4 py-2.5 text-right">
                                <div v-if="permissions.isExecutive" class="text-xs font-mono font-bold py-1.5 px-3 text-slate-800 bg-slate-50 rounded-lg">
                                    {{ (getCat(cat.key).sample || 0).toLocaleString('id-ID') }}
                                </div>
                                <input
                                    v-else
                                    type="number"
                                    min="0"
                                    v-model.number="getCat(cat.key).sample"
                                    @input="onInput"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-teal-500 focus:border-teal-500 disabled:bg-gray-50 font-semibold"
                                    placeholder="0"
                                />
                            </td>

                            <!-- Sesuai Waktu (N) -->
                            <td class="px-4 py-2.5 text-right">
                                <div v-if="permissions.isExecutive" class="text-xs font-mono font-bold py-1.5 px-3 text-slate-800 bg-slate-50 rounded-lg">
                                    {{ (getCat(cat.key).compliant || 0).toLocaleString('id-ID') }}
                                </div>
                                <input
                                    v-else
                                    type="number"
                                    min="0"
                                    v-model.number="getCat(cat.key).compliant"
                                    @input="onInput"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-teal-500 focus:border-teal-500 disabled:bg-gray-50 font-semibold text-teal-700"
                                    placeholder="0"
                                />
                            </td>

                            <!-- Total Waktu (Menit) -->
                            <td class="px-4 py-2.5 text-right">
                                <div v-if="permissions.isExecutive" class="text-xs font-mono font-bold py-1.5 px-3 text-slate-800 bg-slate-50 rounded-lg">
                                    {{ (getCat(cat.key).total_minutes || 0).toLocaleString('id-ID') }}
                                </div>
                                <input
                                    v-else
                                    type="number"
                                    min="0"
                                    v-model.number="getCat(cat.key).total_minutes"
                                    @input="onInput"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-teal-500 focus:border-teal-500 disabled:bg-gray-50 text-gray-600"
                                    placeholder="Opsional"
                                />
                            </td>

                            <!-- Capaian (%) -->
                            <td class="px-4 py-3 text-center text-xs font-mono font-bold">
                                <span
                                    v-if="getCat(cat.key).sample > 0"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-xs font-bold inline-block',
                                        (getCat(cat.key).compliant / getCat(cat.key).sample) >= 0.8
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                            : 'bg-rose-50 text-rose-700 border border-rose-200'
                                    ]"
                                >
                                    {{ ((getCat(cat.key).compliant / getCat(cat.key).sample) * 100).toFixed(1) }}%
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Total Baris Agregat -->
                    <tfoot class="bg-amber-50/70 border-t-2 border-amber-200 font-bold text-xs text-gray-900">
                        <tr>
                            <td class="px-4 py-3 uppercase tracking-wider text-amber-900">
                                TOTAL KESELURUHAN
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500 font-mono">-</td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900">
                                {{ totals.sample.toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-teal-700">
                                {{ totals.compliant.toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-gray-600">
                                {{ totals.total_minutes.toLocaleString('id-ID') }} mnt
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-sm">
                                <span class="px-3 py-1 rounded-lg bg-teal-600 text-white shadow-2xs font-bold">
                                    {{ totals.capaian }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Kartu Edukasi Rumus Mutu Farmasi -->
            <div class="p-4 rounded-xl bg-teal-50/50 border border-teal-100 flex items-start gap-3 text-xs text-teal-900">
                <NotionIcon name="lightbulb" size="18" class="text-teal-600 shrink-0" />
                <div class="space-y-1">
                    <p class="font-bold">Ketentuan Standar Waktu Tunggu Farmasi (Kemenkes & SNARS):</p>
                    <p>
                        Numerator adalah total lembar resep yang diselesaikan sesuai atau lebih cepat dari standar waktu (≤ 30 menit untuk obat jadi/umum, ≤ 60 menit untuk obat racikan).
                        Denominator adalah seluruh jumlah lembar resep yang dijadikan sampel audit pada periode berjalan.
                    </p>
                </div>
            </div>
        </div>

        <!-- ── TAB 2: SAMPLING LOG RESEP INDIVIDUAL ──────────────────────────────── -->
        <div v-else-if="activeTab === 'sampling_log'" class="p-5 space-y-4">
            <!-- Form Input Sampling Resep Cepat -->
            <div v-if="permissions.canEditDaily && !isPeriodApproved" class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Kategori Resep</label>
                    <select v-model="newLog.category_key" class="w-full text-xs border-gray-200 rounded-lg px-2.5 py-1.5 bg-white">
                        <option v-for="cat in categories" :key="cat.key" :value="cat.key">{{ cat.label }} ({{ cat.standard_label }})</option>
                    </select>
                </div>
                <div class="w-36">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">No. Resep / RM</label>
                    <input v-model="newLog.no_resep" type="text" placeholder="Contoh: R-102" class="w-full text-xs border-gray-200 rounded-lg px-2.5 py-1.5 bg-white" />
                </div>
                <div class="w-28">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Jam Masuk</label>
                    <input v-model="newLog.jam_masuk" type="time" class="w-full text-xs border-gray-200 rounded-lg px-2.5 py-1.5 bg-white font-mono" />
                </div>
                <div class="w-28">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Jam Selesai</label>
                    <input v-model="newLog.jam_selesai" type="time" class="w-full text-xs border-gray-200 rounded-lg px-2.5 py-1.5 bg-white font-mono" />
                </div>
                <button
                    @click="addSamplingLog"
                    class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors"
                >
                    + Tambah Sample
                </button>
            </div>

            <!-- Tabel Daftar Log Sampling -->
            <div class="overflow-x-auto max-h-[400px] overflow-y-auto border border-gray-200 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-slate-100 text-gray-700 uppercase font-bold sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-center w-12">No</th>
                            <th class="px-3 py-2 text-left">No. Resep</th>
                            <th class="px-3 py-2 text-left">Kategori</th>
                            <th class="px-3 py-2 text-center">Jam Masuk</th>
                            <th class="px-3 py-2 text-center">Jam Selesai</th>
                            <th class="px-3 py-2 text-center">Durasi (Menit)</th>
                            <th class="px-3 py-2 text-center">Kepatuhan</th>
                            <th v-if="permissions.canEditDaily && !isPeriodApproved" class="px-3 py-2 text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="(log, idx) in formData.sampling_logs" :key="log.id" class="hover:bg-slate-50">
                            <td class="px-3 py-2 text-center font-mono text-gray-500">{{ idx + 1 }}</td>
                            <td class="px-3 py-2 font-mono font-bold text-gray-800">{{ log.no_resep }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ log.category_label }}</td>
                            <td class="px-3 py-2 text-center font-mono text-gray-600">{{ log.jam_masuk }}</td>
                            <td class="px-3 py-2 text-center font-mono text-gray-600">{{ log.jam_selesai }}</td>
                            <td class="px-3 py-2 text-center font-mono font-bold text-gray-900">{{ log.minutes }} mnt</td>
                            <td class="px-3 py-2 text-center">
                                <span v-if="log.is_compliant" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    <NotionIcon name="check-circle" size="12" />
                                    <span>Tepat Waktu</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                    <NotionIcon name="alert" size="12" />
                                    <span>Terlambat</span>
                                </span>
                            </td>
                            <td v-if="permissions.canEditDaily && !isPeriodApproved" class="px-3 py-2 text-center">
                                <button @click="removeSamplingLog(idx)" class="text-rose-500 hover:text-rose-700 font-bold p-1 rounded hover:bg-rose-50" title="Hapus sample">
                                    <NotionIcon name="trash" size="13" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!formData.sampling_logs || formData.sampling_logs.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-xs text-gray-400">
                                Belum ada sampling resep individual yang dicatat. Anda dapat langsung mengisi total pada tab <strong>Rekapitulasi Kategori</strong>.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
